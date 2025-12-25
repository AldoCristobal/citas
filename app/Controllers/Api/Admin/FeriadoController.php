<?php

declare(strict_types=1);

namespace App\Controllers\Api\Admin;

use App\Http\Router\Request;
use App\Http\Responses\Response;
use App\Repositories\Admin\FeriadoAdminRepository;

require_once APP_ROOT . '/app/Http/Responses/Response.php';
require_once APP_ROOT . '/app/Repositories/Admin/FeriadoAdminRepository.php';

final class FeriadoController
{
   private function requireAdmin(): void
   {
      if (session_status() !== PHP_SESSION_ACTIVE) session_start();
      if (empty($_SESSION['admin_id'])) {
         Response::json(false, null, ['code' => 'FORBIDDEN', 'message' => 'No autenticado'], 403);
         exit;
      }
   }

   private function bad(string $msg): void
   {
      Response::json(false, null, ['code' => 'VALIDATION', 'message' => $msg], 400);
   }

   public function index(Request $req): void
   {
      $this->requireAdmin();
      $mes = (string)($req->query['mes'] ?? '');
      $sedeId = (int)($req->query['sede_id'] ?? 0);

      $repo = new FeriadoAdminRepository();
      Response::json(true, $repo->list(['mes' => $mes, 'sede_id' => $sedeId ?: null]));
   }

   public function store(Request $req): void
   {
      $this->requireAdmin();
      $b = $req->json();

      $fecha = (string)($b['fecha'] ?? '');
      $desc  = (string)($b['descripcion'] ?? '');
      $sedeId = isset($b['sede_id']) && $b['sede_id'] !== '' ? (int)$b['sede_id'] : null;
      $activo = isset($b['activo']) ? ((int)$b['activo'] ? 1 : 0) : 1;

      if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
         $this->bad('fecha inválida (YYYY-MM-DD)');
         return;
      }
      if ($sedeId !== null && $sedeId <= 0) {
         $this->bad('sede_id inválido');
         return;
      }

      $repo = new FeriadoAdminRepository();
      try {
         $id = $repo->insert([
            'fecha' => $fecha,
            'descripcion' => $desc ?: null,
            'sede_id' => $sedeId,
            'activo' => $activo
         ]);
      } catch (\PDOException $e) {
         // duplicado por UNIQUE(fecha,sede_id)
         if (($e->errorInfo[1] ?? null) == 1062) {
            Response::json(false, null, ['code' => 'DUPLICATE', 'message' => 'Ya existe un feriado para esa fecha/sede'], 409);
            return;
         }
         throw $e;
      }

      Response::json(true, ['id' => $id], null, 201);
   }

   public function update(Request $req): void
   {
      $this->requireAdmin();
      $id = (int)($req->params['id'] ?? 0);
      if ($id <= 0) {
         $this->bad('id inválido');
         return;
      }

      $b = $req->json();
      $patch = [];

      if (array_key_exists('fecha', $b)) {
         $fecha = (string)$b['fecha'];
         if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $this->bad('fecha inválida (YYYY-MM-DD)');
            return;
         }
         $patch['fecha'] = $fecha;
      }
      if (array_key_exists('descripcion', $b)) {
         $patch['descripcion'] = (string)$b['descripcion'] ?: null;
      }
      if (array_key_exists('sede_id', $b)) {
         $s = $b['sede_id'];
         $patch['sede_id'] = ($s === null || $s === '') ? null : (int)$s;
         if ($patch['sede_id'] !== null && $patch['sede_id'] <= 0) {
            $this->bad('sede_id inválido');
            return;
         }
      }
      if (array_key_exists('activo', $b)) {
         $patch['activo'] = (int)$b['activo'] ? 1 : 0;
      }

      $repo = new FeriadoAdminRepository();
      try {
         $repo->update($id, $patch);
      } catch (\PDOException $e) {
         if (($e->errorInfo[1] ?? null) == 1062) {
            Response::json(false, null, ['code' => 'DUPLICATE', 'message' => 'Ya existe un feriado para esa fecha/sede'], 409);
            return;
         }
         throw $e;
      }

      Response::json(true, ['updated' => true]);
   }

   public function delete(Request $req): void
   {
      $this->requireAdmin();
      $id = (int)($req->params['id'] ?? 0);
      if ($id <= 0) {
         $this->bad('id inválido');
         return;
      }

      (new FeriadoAdminRepository())->softDelete($id);
      Response::json(true, ['deleted' => true]);
   }
}
