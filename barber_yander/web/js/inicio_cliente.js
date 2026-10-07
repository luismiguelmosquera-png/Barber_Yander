// =========================================================
// PANEL CLIENTE — BARBERÍA YANDER
// Reservas en MySQL, Sincronización Automática (Polling 5s)
// Horarios Dinámicos de 40 min y Edición de Perfil
// =========================================================

// Prevenir visualización de datos privados en memoria caché al presionar botón Atrás (requisitos 14, 15, 16)
window.addEventListener("pageshow", function(event) {
  if (event.persisted) {
    window.location.reload();
  }
});

let ultimaHuellaCitasCliente = null;
let intervalPollingCliente = null;

document.addEventListener("DOMContentLoaded", () => {
  inicializarTema();
  inicializarLogout();
  inicializarServicios();
  inicializarFormularioReserva();
  inicializarEdicionPerfil();
  
  // Cargar reservas iniciales
  cargarReservas(true);

  // Sincronización periódica automática (cada 5 segundos)
  // Permite ver si el administrador confirmó o completó su cita sin presionar F5 (requisito 3)
  intervalPollingCliente = setInterval(() => {
    cargarReservas(false);
  }, 5000);
});

function escapeHtml(texto) {
  if (!texto) return "";
  const div = document.createElement("div");
  div.textContent = texto;
  return div.innerHTML;
}

function obtenerFechaHoyLocal() {
  const d = new Date();
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

/* =========================================================
                    TEMA Y LOGOUT
========================================================= */
function inicializarTema() {
  const btn = document.getElementById("btnThemeToggle");
  const temaGuardado = localStorage.getItem("barber_theme") || "dark";
  
  if (temaGuardado === "light") {
    document.body.classList.add("theme-light");
    if (btn) btn.textContent = "☀️";
  } else {
    document.body.classList.remove("theme-light");
    if (btn) btn.textContent = "🌙";
  }

  if (btn) {
    btn.onclick = () => {
      const esLuz = document.body.classList.toggle("theme-light");
      localStorage.setItem("barber_theme", esLuz ? "light" : "dark");
      btn.textContent = esLuz ? "☀️" : "🌙";
    };
  }
}

function inicializarLogout() {
  const btnSalir = document.getElementById("btnCerrarSesion");
  if (!btnSalir) return;

  btnSalir.onclick = async (e) => {
    e.preventDefault();
    if (intervalPollingCliente) clearInterval(intervalPollingCliente);

    try {
      await fetch("php/logout.php");
    } catch (e) {}

    localStorage.removeItem("barber_usuario");
    window.location.replace("login.html");
  };
}

/* =========================================================
                    SELECCIÓN DE SERVICIOS
========================================================= */
function inicializarServicios() {
  const tarjetas = document.querySelectorAll(".servicio-card");
  const selectServicio = document.getElementById("servicioSelect");

  tarjetas.forEach(card => {
    card.addEventListener("click", () => {
      tarjetas.forEach(c => c.classList.remove("active"));
      card.classList.add("active");
      if (selectServicio) {
        selectServicio.value = card.dataset.servicio;
      }
    });
  });

  if (selectServicio) {
    selectServicio.addEventListener("change", (e) => {
      tarjetas.forEach(c => {
        if (c.dataset.servicio === e.target.value) {
          c.classList.add("active");
        } else {
          c.classList.remove("active");
        }
      });
    });
  }
}

/* =========================================================
                    HORARIOS DINÁMICOS
========================================================= */
async function consultarHorariosDisponibles(fecha) {
  const contenedor = document.getElementById("contenedorHorarios");
  const inputHora = document.getElementById("horaSeleccionada");
  if (!contenedor || !fecha) return;

  contenedor.innerHTML = "<p class='horarios-aviso'>Consultando disponibilidad en servidor...</p>";
  if (inputHora) inputHora.value = "";

  try {
    const res = await fetch(`php/obtener_horarios.php?fecha=${encodeURIComponent(fecha)}`);
    const data = await res.json();

    if (data && data.success && data.horarios) {
      renderizarHorarios(data.horarios);
    } else {
      contenedor.innerHTML = "<p class='horarios-aviso'>No hay horarios disponibles para esta fecha.</p>";
    }
  } catch (err) {
    console.error("Error al obtener horarios:", err);
    contenedor.innerHTML = "<p class='horarios-aviso'>Error al consultar horarios.</p>";
  }
}

function renderizarHorarios(horarios) {
  const contenedor = document.getElementById("contenedorHorarios");
  const inputHora = document.getElementById("horaSeleccionada");
  if (!contenedor) return;

  if (!horarios || horarios.length === 0) {
    contenedor.innerHTML = `
      <div class="alerta-sin-horarios">
        <span>⚠️</span>
        <p>No hay turnos disponibles para esta fecha. Selecciona otro día.</p>
      </div>
    `;
    return;
  }

  contenedor.innerHTML = "";
  horarios.forEach(slot => {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "btn-slot";
    btn.textContent = slot.formato;
    btn.dataset.hora = slot.hora;

    btn.onclick = () => {
      document.querySelectorAll(".btn-slot").forEach(b => b.classList.remove("selected-slot"));
      btn.classList.add("selected-slot");
      if (inputHora) inputHora.value = slot.hora;
    };

    contenedor.appendChild(btn);
  });
}

/* =========================================================
                    FORMULARIO DE RESERVA
========================================================= */
function inicializarFormularioReserva() {
  const form = document.getElementById("formReserva");
  const inputFecha = document.getElementById("inputFechaReserva");
  const btnSubmit = document.getElementById("btnSubmitReserva");

  // Establecer fecha mínima como hoy
  const hoyStr = obtenerFechaHoyLocal();
  if (inputFecha) {
    inputFecha.min = hoyStr;
    inputFecha.addEventListener("change", () => {
      consultarHorariosDisponibles(inputFecha.value);
    });
  }

  if (!form) return;

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const formData = new FormData(form);
    const nombre = (formData.get("nombre") || "").trim();
    const telefono = (formData.get("telefono") || "").trim();
    const email = (formData.get("email") || "").trim();
    const servicio = formData.get("servicio");
    const fecha = formData.get("fecha");
    const hora = formData.get("hora");

    if (!nombre || !telefono || !email || !servicio || !fecha) {
      mostrarMensaje("⚠️ Por favor completa todos los campos de la reserva.", "warning");
      return;
    }

    if (!hora) {
      mostrarMensaje("⚠️ Selecciona un horario disponible para agendar tu cita.", "warning");
      return;
    }

    if (btnSubmit) {
      btnSubmit.disabled = true;
      btnSubmit.textContent = "Procesando...";
    }

    try {
      const response = await fetch("php/reservar_cita.php", {
        method: "POST",
        body: formData
      });

      if (response.status === 401) {
        window.location.replace("login.html");
        return;
      }

      const data = await response.json();

      if (data && data.success) {
        mostrarMensaje(data.message, "success", 4000);
        
        // Limpiar selección de servicio y horarios
        const selectServicio = document.getElementById("servicioSelect");
        if (selectServicio) selectServicio.value = "";
        document.querySelectorAll(".servicio-card").forEach(c => c.classList.remove("active"));
        const inputHora = document.getElementById("horaSeleccionada");
        if (inputHora) inputHora.value = "";

        consultarHorariosDisponibles(fecha);
        cargarReservas(true);
      } else {
        const msg = (data && data.message) ? data.message : "Error al procesar la cita.";
        mostrarMensaje("❌ " + msg, "error", 5000);
      }
    } catch (err) {
      console.error("Error al reservar cita:", err);
      mostrarMensaje("❌ Error de comunicación con el servidor.", "error", 4000);
    } finally {
      if (btnSubmit) {
        btnSubmit.disabled = false;
        btnSubmit.textContent = "Reservar Cita";
      }
    }
  });
}

/* =========================================================
                    MIS RESERVAS (CLIENTE)
========================================================= */
async function cargarReservas(esCargaInicial = false) {
  const contenedor = document.getElementById("tablaReservas");
  if (!contenedor) return;

  try {
    const res = await fetch("php/obtener_citas_cliente.php");
    if (res.status === 401) {
      clearInterval(intervalPollingCliente);
      window.location.replace("login.html");
      return;
    }

    const data = await res.json();
    if (!data || !data.success) return;

    // Si los datos no han variado, no redibujar el DOM
    if (!esCargaInicial && data.hash === ultimaHuellaCitasCliente) {
      return;
    }

    const habiaPrevio = (ultimaHuellaCitasCliente !== null);
    ultimaHuellaCitasCliente = data.hash;

    renderizarTablaReservas(data.citas || []);

    if (!esCargaInicial && habiaPrevio) {
      if (typeof mostrarMensaje === "function") {
        mostrarMensaje("🔔 El estado de tus reservas ha sido actualizado.", "info", 2000);
      }
    }
  } catch (err) {
    console.warn("Error al cargar reservas del cliente:", err);
  }
}

function renderizarTablaReservas(citas) {
  const contenedor = document.getElementById("tablaReservas");
  if (!contenedor) return;

  if (!citas || citas.length === 0) {
    contenedor.innerHTML = `
      <div class="empty-state">
        <span>📅</span>
        <p>Aún no tienes citas agendadas.</p>
        <small>Selecciona un servicio arriba y agenda tu horario favorito.</small>
      </div>
    `;
    return;
  }

  let html = `
    <table class="admin-table">
      <thead>
        <tr>
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
      <tr>
        <td data-label="Servicio"><strong>${escapeHtml(c.servicio)}</strong></td>
        <td data-label="Fecha">📅 ${c.fecha_cita}</td>
        <td data-label="Hora">⏰ ${hora}</td>
        <td data-label="Estado"><span class="status-badge ${badgeClass}">${escapeHtml(c.estado)}</span></td>
        <td data-label="Acciones">
    `;

    if (c.estado !== "cancelada" && c.estado !== "completada") {
      html += `<button class="btn-cancelar" data-id="${c.id}">Cancelar Cita</button>`;
    } else {
      html += `<span class="sin-accion">-</span>`;
    }

    html += `</td></tr>`;
  });

  html += `</tbody></table>`;
  contenedor.innerHTML = html;

  // Activar botones de cancelación por parte del cliente
  document.querySelectorAll("#tablaReservas .btn-cancelar").forEach(btn => {
    btn.onclick = async () => {
      const id = btn.dataset.id;
      const seguro = confirm("¿Deseas cancelar tu cita agendada?");
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
          mostrarMensaje("✅ Cita cancelada correctamente.", "info");
          cargarReservas(true);
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
                    EDICIÓN DE PERFIL
========================================================= */
function inicializarEdicionPerfil() {
  const modal = document.getElementById("modalPerfil");
  const btnAbrir = document.getElementById("btnAbrirPerfil");
  const btnCerrar = document.getElementById("btnCancelarPerfil");
  const btnX = document.getElementById("btnCerrarModalX");
  const form = document.getElementById("formEditarPerfil");

  const cerrar = () => { if (modal) modal.style.display = "none"; };
  if (btnCerrar) btnCerrar.onclick = cerrar;
  if (btnX) btnX.onclick = cerrar;

  if (btnAbrir && modal) {
    btnAbrir.onclick = async () => {
      modal.style.display = "flex";
      try {
        const res = await fetch("php/verificar_sesion.php");
        const d = await res.json();
        if (d && d.autenticado && d.usuario) {
          const u = d.usuario;
          const pNom = document.getElementById("perfilNombre");
          const pTel = document.getElementById("perfilTelefono");
          const pCor = document.getElementById("perfilCorreo");
          const pDoc = document.getElementById("perfilNumDoc");
          if (pNom) pNom.value = u.nombre || "";
          if (pTel) pTel.value = u.telefono || "";
          if (pCor) pCor.value = u.correo || "";
          if (pDoc) pDoc.value = u.num_doc || "";
        }
      } catch (err) {}
    };
  }

  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const formData = new FormData(form);

      try {
        const res = await fetch("php/actualizar_perfil.php", {
          method: "POST",
          body: formData
        });
        const d = await res.json();

        if (d && d.success) {
          mostrarMensaje("✅ " + d.message, "success");
          cerrar();
          if (d.usuario && d.usuario.nombre) {
            const spanNom = document.getElementById("clienteNombreSpan");
            if (spanNom) spanNom.textContent = d.usuario.nombre;
          }
        } else {
          mostrarMensaje("❌ " + (d.message || "Error al actualizar perfil"), "error");
        }
      } catch (err) {
        mostrarMensaje("❌ Error al guardar datos.", "error");
      }
    });
  }
}
