<?php

declare(strict_types=1);

namespace App\Controllers\Api\Admin;

use App\Http\Router\Request;
use App\Http\Responses\Response;
use App\Repositories\Admin\SedeAdminRepository;

require_once APP_ROOT . '/app/Http/Responses/Response.php';
require_once APP_ROOT . '/app/Repositories/Admin/SedeAdminRepository.php';

final class SedeController
{
   private function requireAdmin(): void
   {
      if (session_status() !== PHP_SESSION_ACTIVE) session_start();
      if (empty($_SESSION['admin_id'])) {
         Response::json(false, null, ['code' => 'FORBIDDEN', 'message' => 'No autenticado'], 403);
         exit;
      }
   }

   public function index(Request $req): void
   {
      $this->requireAdmin();
      $repo = new SedeAdminRepository();
      Response::json(true, $repo->list());
   }

   public function store(Request $req): void
   {
      $this->requireAdmin();
      $b = $req->json();

      $nombre = trim((string)($b['nombre'] ?? ''));
      $prefijo = strtoupper(trim((string)($b['prefijo_folio'] ?? '')));
      $ciudad = trim((string)($b['ciudad'] ?? ''));
      $direccion = trim((string)($b['direccion'] ?? ''));
      $telefono  = trim((string)($b['telefono'] ?? ''));

      if ($nombre === '' || $prefijo === '') {
         Response::badRequest('nombre y prefijo_folio son requeridos');
         return;
      }

      $repo = new SedeAdminRepository();
      $id = $repo->insert([
         'nombre' => $nombre,
         'prefijo_folio' => $prefijo,
         'ciudad' => $ciudad,
         'direccion' => $direccion,
         'telefono' => $telefono
      ]);

      Response::json(true, ['id' => $id], null, 201);
   }

   public function update(Request $req): void
   {
      $this->requireAdmin();
      $id = (int)($req->params['id'] ?? 0);
      if ($id <= 0) {
         Response::badRequest('id inválido');
         return;
      }

      $b = $req->json();
      $repo = new SedeAdminRepository();
      $repo->update($id, $b);

      Response::json(true, ['updated' => true]);
   }

   public function delete(Request $req): void
   {
      $this->requireAdmin();
      $id = (int)($req->params['id'] ?? 0);
      if ($id <= 0) {
         Response::badRequest('id inválido');
         return;
      }

      $repo = new SedeAdminRepository();
      $repo->softDelete($id);

      Response::json(true, ['deleted' => true]);
   }
}
