<?php

declare(strict_types=1);

namespace App\Controllers\Api\Admin;

use App\Http\Router\Request;
use App\Http\Responses\Response;
use App\Repositories\Admin\AdminUserRepository;

require_once APP_ROOT . '/app/Http/Responses/Response.php';
require_once APP_ROOT . '/app/Repositories/Admin/AdminUserRepository.php';

final class AuthController
{
   private function startSession(): void
   {
      if (session_status() !== PHP_SESSION_ACTIVE) {
         // cookies seguras (en prod con https)
         session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => false, // true en HTTPS
         ]);
         session_start();
      }
   }

   public function login(Request $req): void
   {
      $this->startSession();
      $b = $req->json();

      $email = strtolower(trim((string)($b['email'] ?? '')));
      $pass  = (string)($b['password'] ?? '');

      if ($email === '' || $pass === '') {
         Response::badRequest('email y password requeridos');
         return;
      }

      $repo = new AdminUserRepository();
      $u = $repo->findByEmail($email);

      if (!$u || !password_verify($pass, (string)$u['password_hash'])) {
         Response::json(false, null, ['code' => 'FORBIDDEN', 'message' => 'Credenciales inválidas'], 403);
         return;
      }

      $_SESSION['admin_id'] = (int)$u['id'];
      $_SESSION['admin_email'] = (string)$u['email'];

      Response::json(true, ['id' => (int)$u['id'], 'email' => (string)$u['email'], 'nombre' => (string)$u['nombre']]);
   }

   public function me(Request $req): void
   {
      $this->startSession();

      if (empty($_SESSION['admin_id'])) {
         Response::json(false, null, ['code' => 'FORBIDDEN', 'message' => 'No autenticado'], 403);
         return;
      }

      Response::json(true, [
         'id' => (int)$_SESSION['admin_id'],
         'email' => (string)$_SESSION['admin_email'],
      ]);
   }

   public function logout(Request $req): void
   {
      $this->startSession();
      session_destroy();
      Response::json(true, ['logout' => true]);
   }
}
