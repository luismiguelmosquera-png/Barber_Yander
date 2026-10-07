<?php
require_once __DIR__ . '/php/seguridad.php';
verificarSesion('admin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Panel de Administración - Barbería Yander</title>
  <link rel="stylesheet" href="css/inicio_admin.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet" />
</head>
<body>

  <!-- ENCABEZADO -->
  <header class="admin-header">
    <div class="logo-title">
      <img src="img/banner.jpeg" alt="Logo Barber Yander" class="logo-admin" />
      <div>
        <h1>🔒 Panel de Administración</h1>
        <p>Control total del sistema Barbería Yander</p>
      </div>
    </div>

    <div class="user-info">
      <button id="btnThemeToggle" class="btn-theme-toggle" type="button" title="Cambiar tema Día/Noche">☀️ Modo Día</button>
      <span><?php echo htmlspecialchars($_SESSION['usuario'] ?? 'Administrador'); ?><br><small>Admin</small></span>
      <a href="login.html" class="btn salir" id="btnCerrarSesion">Salir</a>
    </div>
  </header>

  <!-- ESTADÍSTICAS -->
  <section class="stats-section">
    <div class="stat-card total">
      <h3>Total de Citas</h3>
      <p id="totalCitas">0</p>
    </div>
    <div class="stat-card pendientes">
      <h3>Citas Pendientes</h3>
      <p id="citasPendientes">0</p>
    </div>
    <div class="stat-card confirmadas">
      <h3>Citas Confirmadas</h3>
      <p id="citasConfirmadas">0</p>
    </div>
    <div class="stat-card completadas">
      <h3>Citas Completadas</h3>
      <p id="citasCompletadas">0</p>
    </div>
  </section>

  <!-- GESTIÓN DE CITAS -->
  <section class="table-section">
    <h2>📅 Gestión de Citas</h2>
    <div id="tablaCitas" class="table-placeholder">
      <p>Cargando citas...</p>
    </div>
  </section>

  <!-- USUARIOS REGISTRADOS -->
  <section class="table-section">
    <h2>👥 Usuarios Registrados</h2>
    <div id="tablaUsuarios" class="table-placeholder">
      <p>Cargando usuarios...</p>
    </div>
  </section>

  <!-- MODAL BLOQUEAR USUARIO TEMPORALMENTE -->
  <div id="modalBloquear" class="modal-overlay" style="display: none;">
    <div class="modal-card">
      <div class="modal-header">
        <h3>🔒 Bloquear Usuario</h3>
        <button type="button" class="btn-cerrar-modal" id="btnCerrarModalBloquear">&times;</button>
      </div>
      <form id="formBloquearUsuario">
        <input type="hidden" id="bloquearUserId" value="" />
        <p class="modal-desc">Restringir temporalmente el acceso a: <strong id="bloquearUserNombre"></strong></p>
        
        <div class="form-group">
          <label for="selectDuracionBloqueo">Duración del bloqueo:</label>
          <select id="selectDuracionBloqueo" class="form-control">
            <option value="1h">1 hora</option>
            <option value="1d" selected>1 día (24 horas)</option>
            <option value="3d">3 días</option>
            <option value="7d">7 días (1 semana)</option>
            <option value="30d">30 días (1 mes)</option>
            <option value="custom">Fecha y hora personalizada...</option>
          </select>
        </div>

        <div class="form-group" id="grupoFechaPersonalizada" style="display: none;">
          <label for="fechaBloqueoPersonalizada">Desbloquear el:</label>
          <input type="datetime-local" id="fechaBloqueoPersonalizada" class="form-control" />
        </div>

        <div class="form-group">
          <label for="motivoBloqueo">Motivo de la sanción (opcional):</label>
          <input type="text" id="motivoBloqueo" class="form-control" placeholder="Ej. Inasistencias reiteradas" />
        </div>

        <div class="modal-actions">
          <button type="button" class="btn-modal-cancelar" id="btnCancelarBloqueo">Cancelar</button>
          <button type="submit" class="btn-modal-confirmar">Confirmar Bloqueo</button>
        </div>
      </form>
    </div>
  </div>

  <script src="js/notificaciones.js"></script>
  <script src="js/inicio_admin.js"></script>
</body>
</html>
