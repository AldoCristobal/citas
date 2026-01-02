<?php

declare(strict_types=1);

namespace App\Support\Security;

final class Csrf
{
   public static function token(): string
   {
      if (session_status() !== PHP_SESSION_ACTIVE) session_start();

      if (empty($_SESSION['_csrf'])) {
         $_SESSION['_csrf'] = bin2hex(random_bytes(32));
      }
      return (string)$_SESSION['_csrf'];
   }

   public static function validate(?string $token): bool
   {
      if (session_status() !== PHP_SESSION_ACTIVE) session_start();
      $sess = (string)($_SESSION['_csrf'] ?? '');
      if ($sess === '' || !$token) return false;
      return hash_equals($sess, $token);
   }
}
