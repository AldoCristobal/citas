<?php

declare(strict_types=1);

namespace App\Controllers\Api\Admin;

use App\Http\Router\Request;
use App\Http\Responses\Response;
use App\Repositories\Admin\TramiteAdminRepository;

require_once APP_ROOT . '/app/Http/Responses/Response.php';
require_once APP_ROOT . '/app/Repositories/Admin/TramiteAdminRepository.php';

final class TramiteController
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
      $repo = new TramiteAdminRepository();
      Response::json(true, $repo->list());
   }

   public function store(Request $req): void
   {
      $this->requireAdmin();
      $b = $req->json();

      $nombre = trim((string)($b['nombre'] ?? ''));
      $descripcion = (string)($b['descripcion'] ?? '');
      $duracionMin = (int)($b['duracion_min'] ?? 15);
      $activo = isset($b['activo']) ? (int)$b['activo'] : 1;

      if ($nombre === '') {
         Response::badRequest('nombre es requerido');
         return;
      }
      if ($duracionMin <= 0 || $duracionMin > 480) {
         Response::badRequest('duracion_min inválida');
         return;
      }

      $repo = new TramiteAdminRepository();
      $id = $repo->insert([
         'nombre' => $nombre,
         'descripcion' => trim($descripcion) === '' ? null : $descripcion,
         'duracion_min' => $duracionMin,
         'activo' => $activo ? 1 : 0,
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
      $repo = new TramiteAdminRepository();
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

      $repo = new TramiteAdminRepository();
      $repo->softDelete($id);

      Response::json(true, ['deleted' => true]);
   }
}
