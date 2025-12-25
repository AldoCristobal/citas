<?php
$title = $title ?? 'Operación';
$styles = $styles ?? [];
$scripts = $scripts ?? [];
?>
<!doctype html>
<html lang="es">

<head>
   <meta charset="utf-8">
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <title><?= htmlspecialchars($title) ?></title>

   <!-- Vendors (local) -->
   <link rel="stylesheet" href="/assets/vendor/bootstrap/css/bootstrap.min.css">
   <link rel="stylesheet" href="/assets/vendor/material-symbols/material-symbols.css">
   <link rel="stylesheet" href="/assets/vendor/ag-grid/ag-grid-community.min.css">
   <link rel="stylesheet" href="/assets/vendor/ag-grid/ag-theme-quartz.min.css">

   <!-- Oper CSS -->
   <link rel="stylesheet" href="/assets/css/oper/oper.css">
   <?php foreach ($styles as $href): ?>
      <link rel="stylesheet" href="<?= htmlspecialchars($href) ?>">
   <?php endforeach; ?>
</head>

<body>
   <nav class="navbar navbar-dark bg-dark px-3">
      <span class="navbar-brand mb-0 h1">Citas · Turnos</span>
   </nav>

   <main class="container-fluid py-3">
      <?= $content ?? '' ?>
   </main>

   <!-- Vendors (local) -->
   <script src="/assets/vendor/jquery/jquery.min.js"></script>
   <script src="/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
   <script src="/assets/vendor/ag-grid/ag-grid-community.min.js"></script>

   <?php foreach ($scripts as $src): ?>
      <script src="<?= htmlspecialchars($src) ?>"></script>
   <?php endforeach; ?>
</body>

</html>