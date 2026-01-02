<?php

declare(strict_types=1);

$title   = $title   ?? 'Citas';
$styles  = $styles  ?? [];
$scripts = $scripts ?? [];
?>
<!doctype html>
<html lang="es">

<head>
   <meta charset="utf-8">
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <title><?= htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8') ?></title>

   <!-- Tabler LOCAL (light) -->
   <link rel="stylesheet" href="/assets/vendor/tabler/tabler.min.css">

   <?php foreach ($styles as $href): ?>
      <link rel="stylesheet" href="<?= htmlspecialchars((string)$href, ENT_QUOTES, 'UTF-8') ?>">
   <?php endforeach; ?>

   <style>
      body {
         background: #f5f7fb;
      }

      .public-wrap {
         padding-top: 18px;
         padding-bottom: 24px;
      }
   </style>
</head>

<body>
   <div class="page">
      <header class="navbar navbar-expand-md navbar-light d-print-none">
         <div class="container-xl">
            <a class="navbar-brand d-flex align-items-center gap-2" href="/">
               <span class="avatar avatar-sm bg-primary-lt">C</span>
               <strong>Citas</strong>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
               <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbar-menu">
               <div class="navbar-nav ms-auto">
                  <a class="nav-link" href="/agendar">Agendar</a>
                  <a class="nav-link" href="/mi_cita">Consultar</a>
               </div>
            </div>
         </div>
      </header>

      <div class="page-wrapper">
         <div class="page-body">
            <div class="container-xl public-wrap">
               <?= $content ?? '' ?>
            </div>
         </div>

         <footer class="footer footer-transparent d-print-none">
            <div class="container-xl">
               <div class="text-center text-muted small">© <?= date('Y') ?> · Sistema de Citas</div>
            </div>
         </footer>
      </div>
   </div>

   <script src="/assets/vendor/tabler/tabler.min.js"></script>

   <?php foreach ($scripts as $src): ?>
      <script src="<?= htmlspecialchars((string)$src, ENT_QUOTES, 'UTF-8') ?>"></script>
   <?php endforeach; ?>
</body>

</html>