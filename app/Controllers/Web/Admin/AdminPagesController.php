<?php

declare(strict_types=1);

namespace App\Controllers\Web\Admin;

final class AdminPagesController
{
   public function login(): void
   {
      require APP_ROOT . '/resources/views/admin/login.php';
   }

   public function sedes(): void
   {
      require APP_ROOT . '/resources/views/admin/sedes.php';
   }

   public function tramites(): void
   {
      require APP_ROOT . '/resources/views/admin/tramites.php';
   }

   public function sedeTramites(): void
   {
      require APP_ROOT . '/resources/views/admin/sede_tramites.php';
   }

   public function sedeHorarios(): void
   {
      require APP_ROOT . '/resources/views/admin/sede_horarios.php';
   }

   public function feriados(): void
   {
      require APP_ROOT . '/resources/views/admin/feriados.php';
   }

   public function agenda(): void
   {
      require APP_ROOT . '/resources/views/admin/agenda.php';
   }

   public function turnos(): void
   {
      require APP_ROOT . '/resources/views/admin/turnos.php';
   }
}
