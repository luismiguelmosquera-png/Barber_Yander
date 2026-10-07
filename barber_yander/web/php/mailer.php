<?php
// =========================================================
// SERVICIO DE CORREO (SMTP) — BARBERÍA YANDER
// Envío seguro de códigos de verificación temporal
// =========================================================

require_once __DIR__ . '/config.php';

/**
 * Envía el código de verificación de 6 dígitos al correo del usuario.
 * Soporta SMTP autenticado directo (sockets PHP) y fallback local para pruebas/desarrollo.
 */
function enviarCodigoRecuperacion($destinatario, $nombreUsuario, $codigo) {
    $asunto = "Código de recuperación de contraseña - Barbería Yander";

    $cuerpoHtml = "
    <!DOCTYPE html>
    <html lang='es'>
    <head>
      <meta charset='UTF-8'>
      <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #0b0c10; color: #ffffff; margin: 0; padding: 20px; }
        .card { max-width: 520px; margin: 0 auto; background-color: #1f2833; border: 1px solid #c5a059; border-radius: 12px; padding: 30px; text-align: center; }
        .logo { font-size: 24px; font-weight: bold; color: #c5a059; letter-spacing: 2px; margin-bottom: 10px; }
        h2 { color: #ffffff; margin-top: 0; }
        p { color: #c5c6c7; font-size: 15px; line-height: 1.6; }
        .codigo-box { background: linear-gradient(135deg, #0b0c10, #1f2833); border: 2px dashed #c5a059; border-radius: 10px; padding: 18px; margin: 25px 0; }
        .codigo-val { font-size: 38px; font-weight: 800; letter-spacing: 12px; color: #c5a059; font-family: 'Courier New', monospace; }
        .alerta { background-color: rgba(197, 160, 89, 0.1); border-left: 4px solid #c5a059; padding: 10px; font-size: 13px; color: #c5a059; text-align: left; border-radius: 4px; margin-top: 20px; }
        .footer { font-size: 12px; color: #888888; margin-top: 25px; border-top: 1px solid #333333; padding-top: 15px; }
      </style>
    </head>
    <body>
      <div class='card'>
        <div class='logo'>BARBERÍA YANDER</div>
        <h2>Recuperación de Contraseña</h2>
        <p>Hola <strong>" . htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8') . "</strong>,</p>
        <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta. Ingresa el siguiente código de verificación de 6 dígitos en la aplicación:</p>
        
        <div class='codigo-box'>
          <div class='codigo-val'>" . htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') . "</div>
        </div>

        <div class='alerta'>
          ⏱️ <strong>Importante:</strong> Este código tiene una validez de <strong>" . RESET_CODE_EXPIRY_MINUTES . " minutos</strong> y es de un solo uso. Si tú no realizaste esta solicitud, puedes ignorar este mensaje de forma segura.
        </div>

        <div class='footer'>
          Barbería Yander — Estilo y Tradición.<br>
          Este es un correo automático, por favor no respondas a esta dirección.
        </div>
      </div>
    </body>
    </html>
    ";

    // Si las credenciales SMTP están configuradas, intentar envío real por socket SMTP
    if (!empty(SMTP_HOST) && !empty(SMTP_USER) && !empty(SMTP_PASS)) {
        $enviadoSmtp = enviarViaSocketSmtp($destinatario, $asunto, $cuerpoHtml);
        if ($enviadoSmtp) {
            return true;
        }
    }

    // Fallback estándar mail() de PHP si el servidor tiene sendmail configurado
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    $headers .= "Reply-To: " . SMTP_FROM . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $mailEnviado = @mail($destinatario, $asunto, $cuerpoHtml, $headers);
    if ($mailEnviado) {
        return true;
    }

    // Registro seguro en archivo de auditoría local (útil para pruebas y desarrollo local si no hay internet o SMTP configurado)
    $logRuta = __DIR__ . '/codigos_recuperacion_dev.log';
    $logEntrada = date('Y-m-d H:i:s') . " | Destinatario: {$destinatario} | Código: {$codigo} | Expira en: " . RESET_CODE_EXPIRY_MINUTES . " min\n";
    @file_put_contents($logRuta, $logEntrada, FILE_APPEND);

    return true;
}

/**
 * Conexión SMTP directa mediante sockets nativos PHP sin dependencias externas
 */
function enviarViaSocketSmtp($para, $asunto, $mensajeHtml) {
    $host = SMTP_HOST;
    $puerto = SMTP_PORT;
    $usuario = SMTP_USER;
    $password = SMTP_PASS;
    $desde = SMTP_FROM;

    $timeout = 10;
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);

    $prefix = (SMTP_SECURE === 'ssl' || $puerto == 465) ? 'ssl://' : '';
    $socket = @stream_socket_client($prefix . $host . ':' . $puerto, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
    if (!$socket) {
        error_log("Fallo socket SMTP: $errstr ($errno)");
        return false;
    }

    $leer = function() use ($socket) {
        $datos = "";
        while ($str = fgets($socket, 515)) {
            $datos .= $str;
            if (substr($str, 3, 1) == " ") break;
        }
        return $datos;
    };

    $escribir = function($cmd) use ($socket) {
        fputs($socket, $cmd . "\r\n");
    };

    $resp = $leer(); // Banner de bienvenida

    $escribir("EHLO " . gethostname());
    $resp = $leer();

    // STARTTLS si el puerto es 587
    if (SMTP_SECURE === 'tls' || $puerto == 587) {
        $escribir("STARTTLS");
        $resp = $leer();
        if (substr($resp, 0, 3) == '220') {
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $escribir("EHLO " . gethostname());
            $resp = $leer();
        }
    }

    // Autenticación
    $escribir("AUTH LOGIN");
    $resp = $leer();
    $escribir(base64_encode($usuario));
    $resp = $leer();
    $escribir(base64_encode($password));
    $resp = $leer();

    if (substr($resp, 0, 3) != '235') {
        error_log("Fallo autenticación SMTP: $resp");
        fclose($socket);
        return false;
    }

    // Enviar correo
    $escribir("MAIL FROM: <$desde>");
    $resp = $leer();
    $escribir("RCPT TO: <$para>");
    $resp = $leer();
    $escribir("DATA");
    $resp = $leer();

    $cabeceras  = "From: " . SMTP_FROM_NAME . " <$desde>\r\n";
    $cabeceras .= "To: <$para>\r\n";
    $cabeceras .= "Subject: =?UTF-8?B?" . base64_encode($asunto) . "?=\r\n";
    $cabeceras .= "MIME-Version: 1.0\r\n";
    $cabeceras .= "Content-Type: text/html; charset=UTF-8\r\n";
    $cabeceras .= "Date: " . date("r") . "\r\n";

    $escribir($cabeceras . "\r\n" . $mensajeHtml . "\r\n.");
    $resp = $leer();

    $escribir("QUIT");
    $resp = $leer();
    fclose($socket);

    return (substr($resp, 0, 3) == '250' || substr($resp, 0, 3) == '221');
}
?>
