<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\public\reportes.php
require_once __DIR__ . '/api/config.php';

$page_title = "Reportes y Estadísticas";
include 'components/head.php';
include 'components/sidebar.php';
?>
<div class="page-wrapper">
  <?php 
  $search_placeholder = "Buscar reportes...";
  include 'components/topbar.php'; 
  ?>
  <main class="page-content">
    <div class="page-header" data-aos="fade-up">
      <div class="breadcrumb-custom">
        <a href="index.php">Inicio</a><span><i class="fa-solid fa-chevron-right" style="font-size:0.6rem"></i></span><span>Reportes</span>
      </div>
      <h1>Reportes y Estadísticas</h1>
      <p>Genera reportes detallados y análisis avanzados de incidencias y mantenimiento.</p>
    </div>
    
    <div class="card-panel text-center py-5" data-aos="fade-up" data-aos-delay="100">
      <div style="font-size:4rem; color:var(--primary); margin-bottom:1.5rem;">
        <i class="fa-solid fa-chart-pie"></i>
      </div>
      <h3 class="fw-bold mb-3">Módulo de Reportes en Desarrollo</h3>
      <p class="text-muted mx-auto" style="max-width:500px;">
        Este módulo te permitirá exportar informes personalizados en PDF/Excel y visualizar métricas de rendimiento del soporte técnico.
      </p>
      <a href="index.php" class="btn-sky mt-3 px-4 py-2">
        <i class="fa-solid fa-arrow-left me-1"></i> Volver al Dashboard
      </a>
    </div>
  </main>
</div>
<?php
include 'components/footer.php';
?>
