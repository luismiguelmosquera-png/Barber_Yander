<?php
require_once __DIR__ . '/php/seguridad.php';
verificarSesion('cliente');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Panel Cliente - Barbería Yander</title>

  <!-- Estilos -->
  <link rel="stylesheet" href="css/inicio_cliente.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet" />
</head>

<body>

  <!-- ===================== HEADER ===================== -->
  <header class="cliente-header">
    <div class="header-info">
      <h1>💈 Barbería Yander</h1>
      <p>Bienvenido, <strong id="clienteNombreSpan"><?php echo htmlspecialchars($_SESSION['usuario'] ?? 'Cliente'); ?></strong> — Reserva tu cita exclusiva</p>
    </div>

    <div class="header-actions">
      <button id="btnThemeToggle" class="btn-theme-toggle" title="Cambiar modo día/noche">🌙</button>
      <button id="btnAbrirPerfil" class="btn-perfil">👤 Editar mis datos</button>
      <span class="barbero-badge">👤 1 Barbero Experto | Citas cada 40 min</span>
      <a href="login.html" class="btn salir" id="btnCerrarSesion">🚪 Cerrar Sesión</a>
    </div>
  </header>

  <!-- ===================== MODAL / SECCIÓN: EDITAR MIS DATOS ===================== -->
  <div id="modalPerfil" class="modal-perfil" style="display: none;">
    <div class="modal-perfil-card">
      <div class="modal-perfil-header">
        <h3>✏️ Editar mis datos personales</h3>
        <button id="btnCerrarModalX" class="btn-close-modal">&times;</button>
      </div>
      <p class="modal-sub">Actualiza tu información de contacto. Tu contraseña se mantiene protegida.</p>

      <form id="formEditarPerfil">
        <div class="form-group-perfil">
          <label for="perfilNombre">Nombre completo</label>
          <input type="text" id="perfilNombre" name="nombre" value="<?php echo htmlspecialchars($_SESSION['usuario'] ?? ''); ?>" required>
        </div>

        <div class="form-group-perfil">
          <label for="perfilTelefono">Teléfono</label>
          <input type="tel" id="perfilTelefono" name="telefono" value="<?php echo htmlspecialchars($_SESSION['telefono'] ?? ''); ?>" maxlength="10" required>
        </div>

        <div class="form-group-perfil">
          <label for="perfilCorreo">Correo electrónico</label>
          <input type="email" id="perfilCorreo" name="correo" value="<?php echo htmlspecialchars($_SESSION['correo'] ?? ''); ?>" required>
        </div>

        <div class="form-group-perfil">
          <label for="perfilNumDoc">Número de documento</label>
          <input type="text" id="perfilNumDoc" name="num_doc" value="<?php echo htmlspecialchars($_SESSION['num_doc'] ?? ''); ?>">
        </div>

        <div class="modal-perfil-botones">
          <button type="button" id="btnCancelarPerfil" class="btn-cancelar-modal">Cancelar</button>
          <button type="submit" class="btn-guardar-modal">Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ===================== SERVICIOS ===================== -->
  <section class="servicios-section">
    <h2>💇‍♂️ Selecciona un Servicio</h2>

    <div class="servicios-grid" id="servicios">
      <div class="servicio-card" data-servicio="Corte de Cabello" data-precio="15000">
        <div class="servicio-thumb">
          <img src="img/corte_fade.jpg" alt="Corte de Cabello">
        </div>
        <h3>Corte de Cabello</h3>
        <p>$15.000</p>
      </div>

      <div class="servicio-card" data-servicio="Arreglo de Barba" data-precio="10000">
        <div class="servicio-thumb">
          <img src="img/arreglo_barba.jpg" alt="Arreglo de Barba">
        </div>
        <h3>Arreglo de Barba</h3>
        <p>$10.000</p>
      </div>

      <div class="servicio-card" data-servicio="Corte + Barba" data-precio="25000">
        <div class="servicio-thumb">
          <img src="img/combo_corte_barba.jpg" alt="Corte + Barba">
        </div>
        <h3>Corte + Barba</h3>
        <p>$25.000</p>
      </div>

      <div class="servicio-card" data-servicio="Tinte de Cabello" data-precio="90000">
        <div class="servicio-thumb">
          <img src="img/corte_tijera.jpg" alt="Tinte de Cabello">
        </div>
        <h3>Tinte de Cabello</h3>
        <p>$90.000</p>
      </div>

      <div class="servicio-card" data-servicio="Arreglo de Cejas" data-precio="5000">
        <div class="servicio-thumb">
          <img src="img/perfilado_cejas.jpg" alt="Arreglo de Cejas">
        </div>
        <h3>Arreglo de Cejas</h3>
        <p>$5.000</p>
      </div>

      <div class="servicio-card" data-servicio="Servicio Premium" data-precio="80000">
        <div class="servicio-thumb">
          <img src="img/afeitado_spa.jpg" alt="Servicio Premium">
        </div>
        <h3>Servicio Premium</h3>
        <p>$80.000</p>
      </div>
    </div>
  </section>

  <!-- ===================== FORMULARIO DE RESERVA ===================== -->
  <section class="reserva-section">
    <h2>📅 Datos de Reserva</h2>

    <form id="formReserva" class="form-reserva">
      <div class="form-row">
        <input type="text" name="nombre" id="inputReservaNombre" placeholder="Nombre completo" value="<?php echo htmlspecialchars($_SESSION['usuario'] ?? ''); ?>" required>
        <input type="tel" name="telefono" id="inputReservaTelefono" placeholder="Teléfono (10 dígitos)" value="<?php echo htmlspecialchars($_SESSION['telefono'] ?? ''); ?>" required>
      </div>

      <div class="form-row">
        <input type="email" name="email" id="inputReservaEmail" placeholder="Correo electrónico" value="<?php echo htmlspecialchars($_SESSION['correo'] ?? ''); ?>" required>
      </div>

      <div class="form-row">
        <select name="servicio" id="servicioSelect" required>
          <option value="">Selecciona un servicio</option>
          <option value="Corte de Cabello">Corte de Cabello</option>
          <option value="Arreglo de Barba">Arreglo de Barba</option>
          <option value="Corte + Barba">Corte + Barba</option>
          <option value="Tinte de Cabello">Tinte de Cabello</option>
          <option value="Arreglo de Cejas">Arreglo de Cejas</option>
          <option value="Servicio Premium">Servicio Premium</option>
        </select>

        <input type="date" name="fecha" id="inputFechaReserva" required>
      </div>

      <!-- HORARIOS DINÁMICOS DISPONIBLES (CONSULTA AUTOMÁTICA) -->
      <div class="horarios-bloque">
        <label class="lbl-horarios">⏰ Horarios Disponibles para esta Fecha (Intervalos de 40 min):</label>
        <div id="contenedorHorarios" class="horarios-grid">
          <p class="horarios-aviso">Elige una fecha arriba para consultar los horarios disponibles.</p>
        </div>
        <input type="hidden" name="hora" id="horaSeleccionada" required>
      </div>

      <button type="submit" class="btn reservar" id="btnSubmitReserva">Reservar Cita</button>
    </form>
  </section>

  <!-- ===================== MIS RESERVAS ===================== -->
  <section class="tabla-reservas-section">
    <h2>📋 Mis Reservas</h2>

    <div id="tablaReservas">
      <p>Cargando tus reservas...</p>
    </div>
  </section>

  <!-- ===================== SCRIPT ===================== -->
  <script src="js/notificaciones.js"></script>
  <script src="js/inicio_cliente.js"></script>

</body>
</html>
