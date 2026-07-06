<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\public\components\topbar.php
if (!isset($search_placeholder)) {
    $search_placeholder = "Buscar incidencias, aulas, usuarios...";
}
if (!isset($search_oninput)) {
    $search_oninput = "";
}
?>
<!-- TOPBAR -->
<header class="topbar">
  <button class="topbar-btn d-lg-none" onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="fa-solid fa-bars"></i>
  </button>
  <div class="topbar-search">
    <span class="search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
    <input type="text" placeholder="<?php echo htmlspecialchars($search_placeholder); ?>" <?php echo $search_oninput ? 'oninput="' . htmlspecialchars($search_oninput) . '"' : ''; ?>>
  </div>
  <div class="topbar-actions">
    <?php if (isset($extra_topbar_actions)): ?>
      <?php echo $extra_topbar_actions; ?>
    <?php endif; ?>
    <div class="topbar-btn" title="Notificaciones">
      <i class="fa-regular fa-bell"></i>
      <div class="notif-dot"></div>
    </div>
    <div class="topbar-btn" title="Mensajes">
      <i class="fa-regular fa-envelope"></i>
    </div>
    <div class="d-flex align-items-center gap-2 ms-1" style="cursor:pointer;">
      <div class="user-avatar" style="width:36px;height:36px;font-size:0.8rem;background:linear-gradient(135deg,#38bdf8,#0ea5e9);">AO</div>
      <small class="d-none d-md-block fw-600 text-sky-900">Admin</small>
    </div>
  </div>
</header>
