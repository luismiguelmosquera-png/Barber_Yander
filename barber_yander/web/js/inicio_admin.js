// =========================================================
// PANEL ADMINISTRADOR — BARBERÍA YANDER
// Sincronización Automática en Tiempo Real con MySQL (Polling 4s)
// Protección contra Caché en Flecha Atrás y Operaciones Seguras
// =========================================================

// Prevenir visualización de datos privados desde el caché del navegador al presionar "Atrás" (requisitos 14, 15, 16)
window.addEventListener("pageshow", function(event) {
  if (event.persisted) {
    window.location.reload();
  }
});

let ultimaHuellaCitas = null;
let intervalPollingAdmin = null;

document.addEventListener("DOMContentLoaded", () => {
  inicializarTema();
  inicializarLogout();
  inicializarModalBloqueo();
  
  // Carga inicial
  cargarCitas(true);
  cargarUsuarios();
  
  // Sincronización periódica automática (cada 4 segundos)
  // Sin recargar la página, detectando cambios en MySQL (requisito 2 y 3)
  intervalPollingAdmin = setInterval(() => {
    cargarCitas(false);
  }, 4000);
});

// Helper para escapar HTML y prevenir XSS
function escapeHtml(texto) {
  if (!texto) return "";
  const div = document.createElement("div");
  div.textContent = texto;
  return div.innerHTML;
}

/* =========================================================
                    CARGAR CITAS Y CONTADORES
========================================================= */
async function cargarCitas(esCargaInicial = false) {
  try {
    const res = await fetch("php/obtener_citas.php");
    
    // Si la sesión expiró o fue rechazada en el servidor
    if (res.status === 401 || res.status === 403) {
      clearInterval(intervalPollingAdmin);
      window.location.replace("login.html");
      return;
    }

    if (!res.ok) throw new Error("HTTP error " + res.status);

    const data = await res.json();
    if (!data || !data.success) return;

    // Actualizar contadores si están disponibles
    if (data.contadores) {
      actualizarContadoresUI(data.contadores);
    }

    // Si los datos no han cambiado según el hash del servidor, no tocar el DOM (cero parpadeo)
    if (!esCargaInicial && data.hash === ultimaHuellaCitas) {
      return;
    }

    const habiaDatosAntes = (ultimaHuellaCitas !== null);
    ultimaHuellaCitas = data.hash;

    // Renderizar tabla
    renderizarTablaCitas(data.citas || []);

    // Notificación sutil si llegó una nueva reserva de cliente en segundo plano
    if (!esCargaInicial && habiaDatosAntes) {
      if (typeof mostrarMensaje === "function") {
        mostrarMensaje("🔔 Citas actualizadas automáticamente desde el servidor.", "info", 1800);
      }
    }
  } catch (err) {
    console.warn("Error en sincronización de citas:", err);
  }
}

function actualizarContadoresUI(contadores) {
  const elTotal = document.getElementById("totalCitas");
  const elPendientes = document.getElementById("citasPendientes");
  const elConfirmadas = document.getElementById("citasConfirmadas");
  const elCompletadas = document.getElementById("citasCompletadas");

  if (elTotal) elTotal.textContent = contadores.total ?? 0;
  if (elPendientes) elPendientes.textContent = contadores.pendientes ?? 0;
  if (elConfirmadas) elConfirmadas.textContent = contadores.confirmadas ?? 0;
  if (elCompletadas) elCompletadas.textContent = contadores.completadas ?? 0;
}

function renderizarTablaCitas(citas) {
  const contenedor = document.getElementById("tablaCitas");
  if (!contenedor) return;

  if (!citas || citas.length === 0) {
    contenedor.innerHTML = "<div class='empty-state'><span>📅</span><p>No hay citas registradas en el sistema.</p></div>";
    return;
  }

  let html = `
    <table class="admin-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Cliente</th>
          <th>Servicio</th>
          <th>Fecha</th>
          <th>Hora</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
  `;

  citas.forEach(c => {
    let badgeClass = "badge-pendiente";
    if (c.estado === "confirmada") badgeClass = "badge-confirmada";
    if (c.estado === "completada") badgeClass = "badge-completada";
    if (c.estado === "cancelada") badgeClass = "badge-cancelada";

    const hora = (c.hora_cita || "").substring(0, 5);

    html += `
      <tr data-cita-id="${c.id}">
        <td data-label="ID">#${c.id}</td>
        <td data-label="Cliente">
          <strong>${escapeHtml(c.cliente)}</strong>
          ${c.telefono ? `<br><small style="color: var(--text-muted); font-size: 0.78rem;">📞 ${escapeHtml(c.telefono)}</small>` : ""}
        </td>
        <td data-label="Servicio">${escapeHtml(c.servicio)}</td>
        <td data-label="Fecha">📅 ${c.fecha_cita}</td>
        <td data-label="Hora">⏰ ${hora}</td>
        <td data-label="Estado"><span class="status-badge ${badgeClass}">${escapeHtml(c.estado)}</span></td>
        <td data-label="Acciones">
    `;

    if (c.estado === "pendiente") {
      html += `<button class="btn-confirmar" data-id="${c.id}">Confirmar</button> `;
    }
    if (c.estado === "confirmada") {
      html += `<button class="btn-completada" data-id="${c.id}">Completar</button> `;
    }
    if (c.estado !== "cancelada") {
      html += `<button class="btn-cancelar" data-id="${c.id}">Cancelar</button>`;
    }

    html += `</td></tr>`;
  });

  html += `</tbody></table>`;
  contenedor.innerHTML = html;
  activarBotonesCitas();
}

function activarBotonesCitas() {
  // 1. Confirmar
  document.querySelectorAll(".btn-confirmar").forEach(btn => {
    btn.onclick = async () => {
      const id = btn.dataset.id;
      btn.disabled = true;
      try {
        const res = await fetch("php/confirmar_cita.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id })
        });
        const d = await res.json();
        if (d && d.success) {
          mostrarMensaje("✅ Cita confirmada correctamente.", "success", 2000);
          cargarCitas(true);
        } else {
          mostrarMensaje("❌ " + (d.message || "Error al confirmar"), "error");
        }
      } catch (e) {
        mostrarMensaje("❌ Error de comunicación", "error");
      }
    };
  });

  // 2. Completar
  document.querySelectorAll(".btn-completada").forEach(btn => {
    btn.onclick = async () => {
      const id = btn.dataset.id;
      btn.disabled = true;
      try {
        const res = await fetch("php/completar_cita.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id })
        });
        const d = await res.json();
        if (d && (d.success || d.status === "ok")) {
          mostrarMensaje("✅ Cita marcada como completada.", "success", 2000);
          cargarCitas(true);
        } else {
          mostrarMensaje("❌ " + (d.message || "Error al completar"), "error");
        }
      } catch (e) {
        mostrarMensaje("❌ Error de comunicación", "error");
      }
    };
  });

  // 3. Cancelar
  document.querySelectorAll(".btn-cancelar").forEach(btn => {
    btn.onclick = async () => {
      const id = btn.dataset.id;
      const seguro = confirm("¿Estás seguro de cancelar la cita #" + id + "?");
      if (!seguro) return;

      btn.disabled = true;
      try {
        const res = await fetch("php/cancelar_cita.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id })
        });
        const d = await res.json();
        if (d && d.success) {
          mostrarMensaje("✅ Cita cancelada.", "info", 2000);
          cargarCitas(true);
        } else {
          mostrarMensaje("❌ " + (d.message || "Error al cancelar"), "error");
        }
      } catch (e) {
        mostrarMensaje("❌ Error de comunicación", "error");
      }
    };
  });
}

/* =========================================================
                    GESTIÓN DE USUARIOS
========================================================= */
async function cargarUsuarios() {
  const contenedor = document.getElementById("tablaUsuarios");
  if (!contenedor) return;

  try {
    const res = await fetch("php/obtener_usuarios.php");
    if (res.status === 401 || res.status === 403) {
      window.location.replace("login.html");
      return;
    }
    const data = await res.json();
    if (!data || !data.success) return;

    renderizarTablaUsuarios(data.usuarios || []);
  } catch (err) {
    console.warn("Error al cargar usuarios:", err);
  }
}

function renderizarTablaUsuarios(usuarios) {
  const contenedor = document.getElementById("tablaUsuarios");
  if (!contenedor) return;

  if (!usuarios || usuarios.length === 0) {
    contenedor.innerHTML = "<div class='empty-state'><span>👥</span><p>No hay usuarios registrados</p></div>";
    return;
  }

  let html = `
    <table class="admin-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Correo</th>
          <th>Rol</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
  `;

  usuarios.forEach(u => {
    const esAdmin = (u.rol === "admin");
    const rolBadge = esAdmin 
      ? `<span class="status-badge badge-confirmada">Admin</span>` 
      : `<span class="status-badge badge-completada">Cliente</span>`;

    let estadoBadge = `<span class="status-badge badge-confirmada">Activo</span>`;
    if (u.estado === "bloqueado") {
      const fechaTxt = u.bloqueado_hasta ? new Date(u.bloqueado_hasta).toLocaleString() : "Indefinido";
      estadoBadge = `<span class="status-badge badge-cancelada" title="Hasta: ${fechaTxt}">Bloqueado</span>`;
    }

    html += `
      <tr>
        <td data-label="ID">#${u.id}</td>
        <td data-label="Nombre"><strong>${escapeHtml(u.nombre)}</strong></td>
        <td data-label="Correo">${escapeHtml(u.correo)}</td>
        <td data-label="Rol">${rolBadge}</td>
        <td data-label="Estado">${estadoBadge}</td>
        <td data-label="Acciones">
    `;

    if (!esAdmin) {
      if (u.estado === "bloqueado") {
        html += `<button class="btn-desbloquear-usuario" data-id="${u.id}" data-nombre="${escapeHtml(u.nombre)}">🔓 Desbloquear</button> `;
      } else {
        html += `<button class="btn-bloquear-usuario" data-id="${u.id}" data-nombre="${escapeHtml(u.nombre)}">🔒 Bloquear</button> `;
      }
      html += `<button class="btn-eliminar-usuario" data-id="${u.id}" data-nombre="${escapeHtml(u.nombre)}">🗑️ Eliminar</button>`;
    } else {
      html += `<span class="sin-accion">Protegido</span>`;
    }

    html += `</td></tr>`;
  });

  html += `</tbody></table>`;
  contenedor.innerHTML = html;
  activarBotonesUsuarios();
}

function activarBotonesUsuarios() {
  // Desbloquear
  document.querySelectorAll(".btn-desbloquear-usuario").forEach(btn => {
    btn.onclick = async () => {
      const id = btn.dataset.id;
      try {
        const res = await fetch("php/desbloquear_usuario.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: "id=" + encodeURIComponent(id)
        });
        const d = await res.json();
        if (d && d.success) {
          mostrarMensaje("🔓 " + d.message, "success");
          cargarUsuarios();
        } else {
          mostrarMensaje("❌ " + (d.message || "Error al desbloquear"), "error");
        }
      } catch (e) {
        mostrarMensaje("❌ Error de comunicación", "error");
      }
    };
  });

  // Bloquear (abre modal)
  document.querySelectorAll(".btn-bloquear-usuario").forEach(btn => {
    btn.onclick = () => {
      const modal = document.getElementById("modalBloquear");
      const inputId = document.getElementById("bloquearUserId");
      const txtNombre = document.getElementById("bloquearUserNombre");

      if (inputId) inputId.value = btn.dataset.id;
      if (txtNombre) txtNombre.textContent = btn.dataset.nombre || "";
      if (modal) modal.style.display = "flex";
    };
  });

  // Eliminar
  document.querySelectorAll(".btn-eliminar-usuario").forEach(btn => {
    btn.onclick = async () => {
      const id = btn.dataset.id;
      const nombre = btn.dataset.nombre;
      const seguro = confirm(`¿Estás seguro de eliminar al usuario ${nombre}? Se eliminarán todas sus citas asociadas en MySQL.`);
      if (!seguro) return;

      try {
        const res = await fetch("php/borrar.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: "id=" + encodeURIComponent(id)
        });
        const d = await res.json();
        if (d && d.success) {
          mostrarMensaje("🗑️ " + d.message, "success");
          cargarUsuarios();
          cargarCitas(true);
        } else {
          mostrarMensaje("❌ " + (d.message || "Error al eliminar"), "error");
        }
      } catch (e) {
        mostrarMensaje("❌ Error de comunicación", "error");
      }
    };
  });
}

/* =========================================================
                    MODAL DE BLOQUEO
========================================================= */
function inicializarModalBloqueo() {
  const modal = document.getElementById("modalBloquear");
  const btnCerrar = document.getElementById("btnCerrarModalBloquear");
  const btnCancelar = document.getElementById("btnCancelarBloqueo");
  const form = document.getElementById("formBloquearUsuario");
  const selectDuracion = document.getElementById("selectDuracionBloqueo");
  const grupoCustom = document.getElementById("grupoFechaPersonalizada");

  const cerrar = () => { if (modal) modal.style.display = "none"; };
  if (btnCerrar) btnCerrar.onclick = cerrar;
  if (btnCancelar) btnCancelar.onclick = cerrar;

  if (selectDuracion && grupoCustom) {
    selectDuracion.onchange = () => {
      grupoCustom.style.display = (selectDuracion.value === "custom") ? "block" : "none";
    };
  }

  if (form) {
    form.onsubmit = async (e) => {
      e.preventDefault();
      const id = document.getElementById("bloquearUserId").value;
      let duracion = selectDuracion.value;
      if (duracion === "custom") {
        duracion = document.getElementById("fechaBloqueoPersonalizada").value;
        if (!duracion) {
          mostrarMensaje("⚠️ Selecciona una fecha válida", "warning");
          return;
        }
      }
      const motivo = document.getElementById("motivoBloqueo").value;

      try {
        const res = await fetch("php/bloquear_usuario.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: `id=${encodeURIComponent(id)}&duracion=${encodeURIComponent(duracion)}&motivo=${encodeURIComponent(motivo)}`
        });
        const d = await res.json();
        if (d && d.success) {
          mostrarMensaje("🔒 " + d.message, "success");
          cerrar();
          cargarUsuarios();
        } else {
          mostrarMensaje("❌ " + (d.message || "Error al bloquear"), "error");
        }
      } catch (e) {
        mostrarMensaje("❌ Error de comunicación", "error");
      }
    };
  }
}

/* =========================================================
                    TEMA Y CERRAR SESIÓN
========================================================= */
function inicializarTema() {
  const btn = document.getElementById("btnThemeToggle");
  const temaGuardado = localStorage.getItem("barber_theme") || "dark";

  if (temaGuardado === "light") {
    document.body.classList.add("theme-light");
    if (btn) btn.textContent = "☀️ Modo Día";
  } else {
    document.body.classList.remove("theme-light");
    if (btn) btn.textContent = "🌙 Modo Noche";
  }

  if (btn) {
    btn.onclick = () => {
      const esLuz = document.body.classList.toggle("theme-light");
      localStorage.setItem("barber_theme", esLuz ? "light" : "dark");
      btn.textContent = esLuz ? "☀️ Modo Día" : "🌙 Modo Noche";
    };
  }
}

function inicializarLogout() {
  const btnSalir = document.getElementById("btnCerrarSesion");
  if (!btnSalir) return;

  btnSalir.onclick = async (e) => {
    e.preventDefault();
    if (intervalPollingAdmin) clearInterval(intervalPollingAdmin);

    try {
      await fetch("php/logout.php");
    } catch (e) {}

    localStorage.removeItem("barber_usuario");
    window.location.replace("login.html");
  };
}
