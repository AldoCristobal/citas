<?php

declare(strict_types=1);

namespace App\Controllers\Api\Admin;

use App\Http\Router\Request;
use App\Http\Responses\Response;
use App\Repositories\Admin\SedeTramiteAdminRepository;

require_once APP_ROOT . '/app/Http/Responses/Response.php';
require_once APP_ROOT . '/app/Repositories/Admin/SedeTramiteAdminRepository.php';

final class SedeTramiteController
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
      $sedeId = (int)($req->query['sede_id'] ?? 0);

      if ($sedeId <= 0) {
         Response::badRequest('sede_id es requerido');
         return;
      }

      $repo = new SedeTramiteAdminRepository();
      Response::json(true, $repo->listBySede($sedeId));
   }

   public function store(Request $req): void
   {
      $this->requireAdmin();
      $b = $req->json();

      $sedeId = (int)($b['sede_id'] ?? 0);
      $tramiteId = (int)($b['tramite_id'] ?? 0);
      $cupo = (int)($b['cupo_por_slot'] ?? 0);
      $slotMin = (int)($b['slot_min'] ?? 15);
      $ventana = (int)($b['ventana_dias'] ?? 30);
      $activo = isset($b['activo']) ? (int)$b['activo'] : 1;

      if ($sedeId <= 0 || $tramiteId <= 0) {
         Response::badRequest('sede_id y tramite_id son requeridos');
         return;
      }
      if ($cupo <= 0 || $cupo > 50) {
         Response::badRequest('cupo_por_slot inválido');
         return;
      }
      if ($slotMin < 5 || $slotMin > 240) {
         Response::badRequest('slot_min inválido');
         return;
      }
      if ($ventana < 1 || $ventana > 365) {
         Response::badRequest('ventana_dias inválido');
         return;
      }

      $repo = new SedeTramiteAdminRepository();
      $id = $repo->insert([
         'sede_id' => $sedeId,
         'tramite_id' => $tramiteId,
         'cupo_por_slot' => $cupo,
         'slot_min' => $slotMin,
         'ventana_dias' => $ventana,
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

      // validaciones suaves si vienen campos
      if (array_key_exists('cupo_por_slot', $b)) {
         $cupo = (int)$b['cupo_por_slot'];
         if ($cupo <= 0 || $cupo > 50) {
            Response::badRequest('cupo_por_slot inválido');
            return;
         }
      }
      if (array_key_exists('slot_min', $b)) {
         $slot = (int)$b['slot_min'];
         if ($slot < 5 || $slot > 240) {
            Response::badRequest('slot_min inválido');
            return;
         }
      }
      if (array_key_exists('ventana_dias', $b)) {
         $v = (int)$b['ventana_dias'];
         if ($v < 1 || $v > 365) {
            Response::badRequest('ventana_dias inválido');
            return;
         }
      }
      if (array_key_exists('activo', $b)) {
         $b['activo'] = (int)$b['activo'] ? 1 : 0;
      }

      $repo = new SedeTramiteAdminRepository();
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

      $repo = new SedeTramiteAdminRepository();
      $repo->softDelete($id);

      Response::json(true, ['deleted' => true]);
   }
}
