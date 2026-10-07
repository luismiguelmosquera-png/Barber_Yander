// =========================================================
// RECUPERAR CONTRASEÑA — BARBERÍA YANDER
// Flujo Seguro con Código de Verificación de 6 Dígitos
// =========================================================

document.addEventListener("DOMContentLoaded", () => {
  const formPaso1 = document.getElementById("formPaso1");
  const formPaso2 = document.getElementById("formPaso2");
  const correoInput = document.getElementById("usuario");
  const correoConfirmado = document.getElementById("correoConfirmado");
  const codigoInput = document.getElementById("codigoVerificacion");
  const nuevaContrasenaInput = document.getElementById("nuevaContrasena");
  const confirmarContrasenaInput = document.getElementById("confirmarNuevaContrasena");
  const subtitulo = document.getElementById("recuperarSubtitulo");
  const btnEnviarCodigo = document.getElementById("btnEnviarCodigo");
  const btnVerificarCodigo = document.getElementById("btnVerificarCodigo");
  const btnReenviarCodigo = document.getElementById("btnReenviarCodigo");

  // Permitir solo números en el código de 6 dígitos
  if (codigoInput) {
    codigoInput.addEventListener("input", () => {
      codigoInput.value = codigoInput.value.replace(/\D/g, "").slice(0, 6);
    });
  }

  // --- PASO 1: ENVIAR CÓDIGO ---
  if (formPaso1) {
    formPaso1.addEventListener("submit", async (e) => {
      e.preventDefault();

      const correo = (correoInput.value || "").trim().toLowerCase();
      if (!correo) {
        mostrarMensaje("⚠️ Por favor ingresa tu correo electrónico registrado.", "warning");
        return;
      }

      const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!regexEmail.test(correo)) {
        mostrarMensaje("⚠️ Ingresa un formato de correo válido.", "warning");
        return;
      }

      if (btnEnviarCodigo) {
        btnEnviarCodigo.disabled = true;
        btnEnviarCodigo.textContent = "Enviando código...";
      }

      try {
        const response = await fetch("php/solicitar_codigo.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ correo })
        });

        const data = await response.json();

        if (data && data.success) {
          mostrarMensaje("📨 " + data.message, "success", 4000);
          
          // Pasar a Paso 2
          formPaso1.style.display = "none";
          formPaso2.style.display = "block";
          if (correoConfirmado) correoConfirmado.value = correo;
          if (subtitulo) subtitulo.textContent = "Ingresa el código de 6 dígitos y tu nueva contraseña";
          if (codigoInput) codigoInput.focus();
        } else {
          const msg = (data && data.message) ? data.message : "No fue posible procesar la solicitud.";
          mostrarMensaje("❌ " + msg, "error", 4000);
        }
      } catch (err) {
        console.error("Error al solicitar código:", err);
        mostrarMensaje("❌ Error de conexión con el servidor. Intenta nuevamente.", "error", 4000);
      } finally {
        if (btnEnviarCodigo) {
          btnEnviarCodigo.disabled = false;
          btnEnviarCodigo.textContent = "Enviar Código de Verificación";
        }
      }
    });
  }

  // --- REENVIAR CÓDIGO ---
  if (btnReenviarCodigo) {
    btnReenviarCodigo.addEventListener("click", async () => {
      const correo = (correoConfirmado.value || correoInput.value || "").trim().toLowerCase();
      if (!correo) return;

      btnReenviarCodigo.disabled = true;
      btnReenviarCodigo.textContent = "Reenviando...";

      try {
        const response = await fetch("php/solicitar_codigo.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ correo })
        });
        const data = await response.json();
        if (data && data.success) {
          mostrarMensaje("🔄 Nuevo código de verificación enviado a tu correo.", "info", 4000);
          if (codigoInput) {
            codigoInput.value = "";
            codigoInput.focus();
          }
        } else {
          mostrarMensaje("❌ " + (data.message || "Error al reenviar código"), "error");
        }
      } catch (err) {
        mostrarMensaje("❌ Error al reenviar código.", "error");
      } finally {
        setTimeout(() => {
          btnReenviarCodigo.disabled = false;
          btnReenviarCodigo.textContent = "Reenviar Código";
        }, 5000);
      }
    });
  }

  // --- PASO 2: VERIFICAR CÓDIGO Y ESTABLECER NUEVA CONTRASEÑA ---
  if (formPaso2) {
    formPaso2.addEventListener("submit", async (e) => {
      e.preventDefault();

      const correo = (correoConfirmado.value || "").trim().toLowerCase();
      const codigo = (codigoInput.value || "").trim();
      const nuevaContrasena = (nuevaContrasenaInput.value || "").trim();
      const confirmarContrasena = (confirmarContrasenaInput.value || "").trim();

      if (!correo || !codigo || !nuevaContrasena || !confirmarContrasena) {
        mostrarMensaje("⚠️ Por favor completa todos los campos.", "warning");
        return;
      }

      if (codigo.length !== 6) {
        mostrarMensaje("⚠️ El código de verificación debe tener exactamente 6 dígitos.", "warning");
        return;
      }

      if (nuevaContrasena.length < 4) {
        mostrarMensaje("⚠️ La nueva contraseña debe tener al menos 4 caracteres.", "warning");
        return;
      }

      if (nuevaContrasena !== confirmarContrasena) {
        mostrarMensaje("⚠️ Las contraseñas ingresadas no coinciden.", "warning");
        return;
      }

      if (btnVerificarCodigo) {
        btnVerificarCodigo.disabled = true;
        btnVerificarCodigo.textContent = "Restableciendo...";
      }

      try {
        const response = await fetch("php/verificar_codigo.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            correo,
            codigo,
            nuevaContrasena
          })
        });

        const data = await response.json();

        if (data && data.success) {
          mostrarMensaje("✅ " + data.message, "success", 3000);
          formPaso2.reset();
          setTimeout(() => {
            window.location.href = "login.html";
          }, 1600);
        } else {
          const msg = (data && data.message) ? data.message : "Error al restablecer la contraseña.";
          mostrarMensaje("❌ " + msg, "error", 4500);
        }
      } catch (err) {
        console.error("Error al verificar código:", err);
        mostrarMensaje("❌ Error de comunicación con el servidor.", "error", 4000);
      } finally {
        if (btnVerificarCodigo) {
          btnVerificarCodigo.disabled = false;
          btnVerificarCodigo.textContent = "Restablecer Contraseña";
        }
      }
    });
  }
});
