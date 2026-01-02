<?php
$title = $title ?? 'Admin';
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
   <link rel="stylesheet" href="/assets/vendor/adminlte/adminlte.min.css">

   <!-- Iconos Google (local) -->
   <link rel="stylesheet" href="/assets/vendor/material-symbols/material-symbols.css">

   <!-- AG Grid (local) -->
   <link rel="stylesheet" href="/assets/vendor/ag-grid/ag-grid-community.min.css">
   <link rel="stylesheet" href="/assets/vendor/ag-grid/ag-theme-quartz.min.css">

   <!-- App CSS -->
   <link rel="stylesheet" href="/assets/css/admin/admin.css">
   <?php foreach ($styles as $href): ?>
      <link rel="stylesheet" href="<?= htmlspecialchars($href) ?>">
   <?php endforeach; ?>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
   <div class="wrapper">

      <nav class="main-header navbar navbar-expand navbar-white navbar-light">
         <ul class="navbar-nav">
            <li class="nav-item">
               <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                  <span class="material-symbols-outlined">menu</span>
               </a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
               <a href="/admin/sedes" class="nav-link">Citas · Admin</a>
            </li>
         </ul>
      </nav>

      <aside class="main-sidebar sidebar-dark-primary elevation-4">
         <a href="/admin/sedes" class="brand-link">
            <span class="brand-text font-weight-light">Panel</span>
         </a>

         <div class="sidebar">
            <nav class="mt-2">
               <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                  <li class="nav-item">
                     <a href="/admin/sedes" class="nav-link">
                        <span class="material-symbols-outlined nav-icon">domain</span>
                        <p>Sedes</p>
                     </a>
                  </li>
                  <li class="nav-item">
                     <a href="/admin/tramites" class="nav-link">
                        <span class="material-symbols-outlined nav-icon">description</span>
                        <p>Trámites</p>
                     </a>
                  </li>
                  <li class="nav-item">
                     <a href="/admin/sede-tramites" class="nav-link">
                        <span class="material-symbols-outlined nav-icon">tune</span>
                        <p>Sede · Trámites</p>
                     </a>
                  </li>
                  <li class="nav-item">
                     <a href="/admin/sede-horarios" class="nav-link">
                        <span class="material-symbols-outlined nav-icon">schedule</span>
                        <p>Horarios</p>
                     </a>
                  </li>
                  <li class="nav-item">
                     <a href="/admin/feriados" class="nav-link">
                        <span class="material-symbols-outlined nav-icon">event_busy</span>
                        <p>Feriados</p>
                     </a>
                  </li>
                  <li class="nav-item">
                     <a href="/admin/agenda" class="nav-link">
                        <span class="material-symbols-outlined nav-icon">calendar_month</span>
                        <p>Agenda</p>
                     </a>
                  </li>
               </ul>
            </nav>
         </div>
      </aside>

      <div class="content-wrapper">
         <section class="content pt-3 px-3">
            <?= $content ?? '' ?>
         </section>
      </div>

   </div>

   <!-- Vendors (local) -->
   <script src="/assets/vendor/jquery/jquery.min.js"></script>
   <script src="/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
   <script src="/assets/vendor/adminlte/adminlte.min.js"></script>
   <script src="/assets/vendor/ag-grid/ag-grid-community.min.js"></script>

   <!-- App JS -->
   <script src="/assets/js/admin/api.js"></script>
   <script src="/assets/js/admin/http.js"></script>

   <?php foreach ($scripts as $src): ?>
      <script src="<?= htmlspecialchars($src) ?>"></script>
   <?php endforeach; ?>
</body>

</html>