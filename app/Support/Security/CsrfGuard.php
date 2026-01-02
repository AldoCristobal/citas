<?php

declare(strict_types=1);

namespace App\Support\Security;

use App\Http\Responses\Response;

require_once APP_ROOT . '/app/Http/Responses/Response.php';
require_once APP_ROOT . '/app/Support/Security/Csrf.php';

final class CsrfGuard
{
   public static function requireForMutation(string $method): void
   {
      $m = strtoupper($method);
      if (!in_array($m, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) return;

      $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
      if (!Csrf::validate($token)) {
         Response::json(false, null, ['code' => 'CSRF', 'message' => 'CSRF inválido'], 419);
         exit;
      }
   }
}
