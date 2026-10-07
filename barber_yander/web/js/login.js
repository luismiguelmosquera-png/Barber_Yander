// =========================================================
// INICIO DE SESIÓN — BARBERÍA YANDER
// Autenticación Segura en Servidor (MySQL + PHP)
// =========================================================

document.addEventListener("DOMContentLoaded", () => {
  const loginForm = document.getElementById("loginForm");
  const correoInput = document.getElementById("usuario");
  const contrasenaInput = document.getElementById("password");

  if (!loginForm) return;

  // Limitar longitud mientras escribe
  if (correoInput) {
    correoInput.addEventListener("input", () => {
      if (correoInput.value.length > 100) correoInput.value = correoInput.value.slice(0, 100);
    });
  }

  if (contrasenaInput) {
    contrasenaInput.addEventListener("input", () => {
      if (contrasenaInput.value.length > 50) contrasenaInput.value = contrasenaInput.value.slice(0, 50);
    });
  }

  loginForm.addEventListener("submit", async function(e) {
    e.preventDefault();

    const correo = (correoInput.value || "").trim();
    const contrasena = (contrasenaInput.value || "").trim();

    // Validaciones básicas en cliente
    if (!correo || !contrasena) {
      if (typeof mostrarMensaje === "function") {
        mostrarMensaje("⚠️ Por favor completa tu correo y contraseña.", "warning");
      } else {
        alert("⚠️ Por favor completa tu correo y contraseña.");
      }
      return;
    }

    if (correo.length > 100) {
      mostrarMensaje("⚠️ El correo no puede tener más de 100 caracteres.", "warning");
      return;
    }

    if (contrasena.length > 50) {
      mostrarMensaje("⚠️ La contraseña no puede tener más de 50 caracteres.", "warning");
      return;
    }

    const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!regexEmail.test(correo)) {
      mostrarMensaje("⚠️ Ingrese un correo electrónico válido (ejemplo: cliente@gmail.com).", "warning");
      return;
    }

    const btnSubmit = document.getElementById("btnSubmitLogin");
    if (btnSubmit) {
      btnSubmit.disabled = true;
      btnSubmit.textContent = "Verificando...";
    }

    try {
      const response = await fetch("./php/validar_login.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ correo, contrasena })
      });

      const data = await response.json();

      if (data && data.success) {
        // Almacenar únicamente datos públicos de perfil para interfaz visual (NUNCA contraseñas)
        if (data.usuario) {
          localStorage.setItem("barber_usuario", JSON.stringify({
            id: data.usuario.id,
            nombre: data.usuario.nombre,
            correo: data.usuario.correo,
            telefono: data.usuario.telefono || "",
            tipo_doc: data.usuario.tipo_doc || "",
            num_doc: data.usuario.num_doc || "",
            rol: data.usuario.rol
          }));
        }

        mostrarMensaje("✅ ¡Bienvenido! Ingresando al sistema...", "success", 2000);
        setTimeout(() => {
          window.location.href = data.redirect || "inicio_cliente.php";
        }, 600);
      } else {
        const errorMsg = (data && data.message) ? data.message : "No fue posible iniciar sesión. Verifica tus datos.";
        mostrarMensaje("❌ " + errorMsg, "error", 4000);
      }
    } catch (err) {
      console.error("Error al conectar con validar_login.php:", err);
      mostrarMensaje("❌ No se pudo conectar con el servidor. Verifica que los servicios estén activos.", "error", 4000);
    } finally {
      if (btnSubmit) {
        btnSubmit.disabled = false;
        btnSubmit.textContent = "Iniciar Sesión";
      }
    }
  });
});
