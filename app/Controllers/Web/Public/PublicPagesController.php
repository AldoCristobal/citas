<?php

declare(strict_types=1);

namespace App\Controllers\Web\Public;

final class PublicPagesController
{
   public function home(): void
   {
      require APP_ROOT . '/resources/views/public/home.php';
   }

   public function citas(): void
   {
      require APP_ROOT . '/resources/views/public/agendar.php';
   }

   public function miCita(): void
   {
      require APP_ROOT . '/resources/views/public/mi_cita.php';
   }
}
