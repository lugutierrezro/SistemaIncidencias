<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\public\components\sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo">
      <div class="logo-icon" style="background:transparent;"><img src="images.jpg" alt="Logo" style="width:100%;height:100%;object-fit:contain;"></div>
      <div class="logo-text">
        <h2>Tiketera Adex</h2>
        <span>Panel de Control</span>
      </div>
    </div>
  </div>
  <div class="sidebar-user">
    <div class="user-avatar">AO</div>
    <div class="user-info">
      <strong>Admin Operaciones</strong>
      <small>Soporte TI · En línea</small>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Principal</div>
    <a href="index.php" class="nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
      <span class="nav-icon"><i class="fa-solid fa-gauge-high"></i></span>
      Dashboard
    </a>
    <a href="incidencias.php" class="nav-item <?php echo $current_page == 'incidencias.php' ? 'active' : ''; ?>">
      <span class="nav-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
      Incidencias
      <span class="nav-badge">7</span>
    </a>
    <a href="aulas.php" class="nav-item <?php echo $current_page == 'aulas.php' ? 'active' : ''; ?>">
      <span class="nav-icon"><i class="fa-solid fa-school"></i></span>
      Gestión de Aulas
    </a>
    <a href="chat.php" class="nav-item <?php echo $current_page == 'chat.php' ? 'active' : ''; ?>">
      <span class="nav-icon"><i class="fa-solid fa-comments"></i></span>
      Soporte Live
      <span class="nav-badge">3</span>
    </a>
    <div class="nav-section-label">Gestión</div>
    <a href="usuarios.php" class="nav-item <?php echo $current_page == 'usuarios.php' ? 'active' : ''; ?>">
      <span class="nav-icon"><i class="fa-solid fa-users"></i></span>
      Usuarios
    </a>
    <a href="reportes.php" class="nav-item <?php echo $current_page == 'reportes.php' ? 'active' : ''; ?>">
      <span class="nav-icon"><i class="fa-solid fa-chart-bar"></i></span>
      Reportes
    </a>
    <div class="nav-section-label">Sistema</div>
    <a href="#" class="nav-item">
      <span class="nav-icon"><i class="fa-solid fa-gear"></i></span>
      Configuración
    </a>
    <a href="#" class="nav-item">
      <span class="nav-icon"><i class="fa-solid fa-circle-question"></i></span>
      Ayuda
    </a>
  </nav>
  <div class="sidebar-footer">
    <a href="#" class="nav-item" style="color:rgba(255,255,255,0.6);">
      <span class="nav-icon"><i class="fa-solid fa-right-from-bracket"></i></span>
      Cerrar Sesión
    </a>
  </div>
</aside>
