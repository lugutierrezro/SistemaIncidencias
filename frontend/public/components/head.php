<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\public\components\head.php
if (!isset($page_title)) {
    $page_title = "Tiketera Adex";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title); ?> | Tiketera Adex</title>
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome 6 -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <!-- AOS -->
  <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
  <!-- Custom -->
  <link href="css/main.css" rel="stylesheet">
  <?php if (isset($extra_css)): ?>
    <?php echo $extra_css; ?>
  <?php endif; ?>
</head>
<body>
