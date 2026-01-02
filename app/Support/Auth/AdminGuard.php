<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Http\Responses\Response;

require_once APP_ROOT . '/app/Http/Responses/Response.php';

final class AdminGuard
{
   public static function require(): void
   {
      if (session_status() !== PHP_SESSION_ACTIVE) session_start();

      if (empty($_SESSION['admin_id'])) {
         Response::json(false, null, ['code' => 'UNAUTH', 'message' => 'No autenticado'], 401);
         exit;
      }
   }
}
