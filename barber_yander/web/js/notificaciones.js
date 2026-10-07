// =========================================================
// BARBERÍA YANDER — SISTEMA DE MENSAJES EMERGENTES (TOASTS & MODALES)
// Soporte Universal: Toasts Flotantes, Confirmaciones y Override de alert()
// =========================================================

(function initNotificacionesSystem() {
  // 1. Inyectar hoja de estilo si no está presente
  if (!document.querySelector("link[href*='notificaciones.css']")) {
    const link = document.createElement("link");
    link.rel = "stylesheet";
    link.href = "css/notificaciones.css";
    document.head.appendChild(link);
  }

  // 2. Obtener o crear el contenedor flotante
  function getToastContainer() {
    let container = document.getElementById("barberToastContainer");
    if (!container) {
      container = document.createElement("div");
      container.id = "barberToastContainer";
      container.className = "toast-container";
      document.body.appendChild(container);
    }
    return container;
  }

  /**
   * Muestra un mensaje emergente flotante (Toast)
   * @param {string} mensaje - Texto del mensaje
   * @param {string} tipo - 'success' | 'error' | 'warning' | 'info'
   * @param {number} duracion - Tiempo en milisegundos (default: 4000ms)
   * @param {string|null} titulo - Título opcional
   */
  function mostrarMensaje(mensaje, tipo = "info", duracion = 4200, titulo = null) {
    const container = getToastContainer();
    const msgStr = String(mensaje || "");

    // Detección automática de tipo e iconos según emojis
    let icon = "🔔";
    let autoTipo = tipo;
    let autoTitulo = titulo;

    if (msgStr.includes("✅") || msgStr.toLowerCase().includes("exitoso") || msgStr.toLowerCase().includes("correctamente")) {
      autoTipo = tipo === "info" ? "success" : tipo;
      icon = "✅";
      if (!autoTitulo) autoTitulo = "Éxito";
    } else if (msgStr.includes("❌") || msgStr.includes("⛔") || msgStr.toLowerCase().includes("error") || msgStr.toLowerCase().includes("denegado")) {
      autoTipo = tipo === "info" ? "error" : tipo;
      icon = msgStr.includes("⛔") ? "⛔" : "❌";
      if (!autoTitulo) autoTitulo = "Atención";
    } else if (msgStr.includes("⚠️") || msgStr.includes("🔒") || msgStr.toLowerCase().includes("bloquead") || msgStr.toLowerCase().includes("advertencia")) {
      autoTipo = tipo === "info" ? "warning" : tipo;
      icon = msgStr.includes("🔒") ? "🔒" : "⚠️";
      if (!autoTitulo) autoTitulo = "Aviso";
    } else if (msgStr.includes("ℹ️") || msgStr.toLowerCase().includes("informaci")) {
      autoTipo = "info";
      icon = "ℹ️";
      if (!autoTitulo) autoTitulo = "Información";
    } else {
      if (autoTipo === "success") icon = "✅";
      else if (autoTipo === "error") icon = "❌";
      else if (autoTipo === "warning") icon = "⚠️";
      else icon = "ℹ️";
      if (!autoTitulo) {
        autoTitulo = autoTipo === "success" ? "Éxito" : autoTipo === "error" ? "Error" : autoTipo === "warning" ? "Aviso" : "Notificación";
      }
    }

    // Limpiar emojis redundantes del inicio del texto para presentación limpia
    let cleanMsg = msgStr.replace(/^([✅❌⚠️ℹ️🔒⛔🚪👤📅⏰]\s*)+/, "").trim();

    // Crear elemento toast
    const toast = document.createElement("div");
    toast.className = `toast-item toast-${autoTipo}`;

    toast.innerHTML = `
      <div class="toast-icon">${icon}</div>
      <div class="toast-content">
        <div class="toast-title">${autoTitulo}</div>
        <div class="toast-body">${cleanMsg}</div>
      </div>
      <button type="button" class="toast-close" title="Cerrar">&times;</button>
      <div class="toast-progress">
        <div class="toast-progress-bar" style="animation-duration: ${duracion}ms;"></div>
      </div>
    `;

    container.appendChild(toast);

    let removeTimer = null;

    const cerrarToast = () => {
      if (removeTimer) clearTimeout(removeTimer);
      toast.classList.add("toast-salida");
      setTimeout(() => {
        if (toast.parentNode) toast.parentNode.removeChild(toast);
      }, 300);
    };

    toast.querySelector(".toast-close").onclick = cerrarToast;

    removeTimer = setTimeout(cerrarToast, duracion);
  }

  /**
   * Muestra un modal de confirmación emergente elegante
   * @param {Object} config
   * @returns {Promise<boolean>}
   */
  function mostrarConfirmacion({
    titulo = "¿Estás seguro?",
    mensaje = "",
    textoConfirmar = "Confirmar",
    textoCancelar = "Cancelar",
    tipo = "warning", // 'warning' | 'danger' | 'success'
    icono = null
  } = {}) {
    return new Promise((resolve) => {
      let iconHtml = "⚠️";
      if (icono) iconHtml = icono;
      else if (tipo === "danger") iconHtml = "🗑️";
      else if (tipo === "success") iconHtml = "✅";
      else iconHtml = "❓";

      const overlay = document.createElement("div");
      overlay.className = "confirm-modal-overlay";

      overlay.innerHTML = `
        <div class="confirm-modal-card">
          <div class="confirm-icon-box ${tipo}">${iconHtml}</div>
          <h3 class="confirm-title">${titulo}</h3>
          <p class="confirm-message">${mensaje}</p>
          <div class="confirm-actions">
            <button type="button" class="btn-confirm-cancel" id="btnConfirmCancel">${textoCancelar}</button>
            <button type="button" class="btn-confirm-accept ${tipo === 'danger' ? 'danger' : ''}" id="btnConfirmAccept">${textoConfirmar}</button>
          </div>
        </div>
      `;

      document.body.appendChild(overlay);

      const cerrar = (res) => {
        overlay.style.opacity = "0";
        overlay.style.transition = "opacity 0.2s ease";
        setTimeout(() => {
          if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
          resolve(res);
        }, 200);
      };

      overlay.querySelector("#btnConfirmCancel").onclick = () => cerrar(false);
      overlay.querySelector("#btnConfirmAccept").onclick = () => cerrar(true);

      // Cerrar con Escape
      const onKeyDown = (e) => {
        if (e.key === "Escape") {
          window.removeEventListener("keydown", onKeyDown);
          cerrar(false);
        }
      };
      window.addEventListener("keydown", onKeyDown);
    });
  }

  // 3. Exponer globalmente
  window.mostrarMensaje = mostrarMensaje;
  window.mostrarToast = mostrarMensaje;
  window.mostrarConfirmacion = mostrarConfirmacion;

  // 4. Interceptar y mejorar window.alert de forma no bloqueante y visual
  window.alert = function (msg) {
    mostrarMensaje(msg);
  };
})();
