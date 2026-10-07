// =========================================================
// REGISTRO DE USUARIOS — BARBERÍA YANDER
// Validaciones en Frontend y Registro Directo en MySQL vía PHP
// =========================================================

document.addEventListener("DOMContentLoaded", () => {
  const registroForm = document.getElementById("registroForm");
  if (!registroForm) return;

  registroForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const formData = new FormData(registroForm);
    const nombre = (formData.get("nombre") || "").trim();
    const correo = (formData.get("usuario") || "").trim().toLowerCase();
    const telefono = (formData.get("telefono") || "").trim();
    const tipo_doc = formData.get("tipo_doc");
    const num_doc = (formData.get("num_doc") || "").trim();
    const pass = formData.get("password") || "";
    const confirm = formData.get("confirm_password") || "";

    // Validaciones en cliente
    if (!nombre || !correo || !telefono || !tipo_doc || !num_doc || !pass || !confirm) {
      mostrarMensaje("⚠️ Por favor completa todos los campos del formulario.", "warning");
      return;
    }

    const regexNombre = /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/;
    if (!regexNombre.test(nombre)) {
      mostrarMensaje("⚠️ El nombre solo puede contener letras y espacios.", "warning");
      return;
    }

    if (nombre.length > 70) {
      mostrarMensaje("⚠️ El nombre no puede exceder 70 caracteres.", "warning");
      return;
    }

    const regexCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!regexCorreo.test(correo)) {
      mostrarMensaje("⚠️ Ingrese un formato de correo electrónico válido.", "warning");
      return;
    }

    const regexTelefono = /^\d{10}$/;
    if (!regexTelefono.test(telefono)) {
      mostrarMensaje("⚠️ El teléfono debe tener exactamente 10 dígitos numéricos.", "warning");
      return;
    }

    if (pass.length < 4) {
      mostrarMensaje("⚠️ La contraseña debe tener al menos 4 caracteres.", "warning");
      return;
    }

    if (pass !== confirm) {
      mostrarMensaje("⚠️ Las contraseñas no coinciden. Verifícalas.", "warning");
      return;
    }

    const btnSubmit = document.getElementById("btnSubmitRegistro");
    if (btnSubmit) {
      btnSubmit.disabled = true;
      btnSubmit.textContent = "Registrando...";
    }

    try {
      const response = await fetch("php/registrar.php", {
        method: "POST",
        body: formData
      });

      const data = await response.json();

      if (data && data.success) {
        mostrarMensaje("✅ " + data.message, "success", 2500);
        registroForm.reset();
        setTimeout(() => {
          window.location.href = "login.html";
        }, 1500);
      } else {
        const msg = (data && data.message) ? data.message : "Error al registrar el usuario.";
        mostrarMensaje("❌ " + msg, "error", 4000);
      }
    } catch (err) {
      console.error("Error al registrar:", err);
      mostrarMensaje("❌ Error de comunicación con el servidor. Intenta nuevamente.", "error", 4000);
    } finally {
      if (btnSubmit) {
        btnSubmit.disabled = false;
        btnSubmit.textContent = "Registrar Usuario";
      }
    }
  });
});
