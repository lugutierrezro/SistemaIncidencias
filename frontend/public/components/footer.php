<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\public\components\footer.php
?>
<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>
  AOS.init({ duration: 600, once: true });
</script>
<?php if (isset($extra_js)): ?>
  <?php echo $extra_js; ?>
<?php endif; ?>
</body>
</html>
