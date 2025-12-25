<?php

declare(strict_types=1);

namespace App\Controllers\Api\Admin;

use App\Http\Router\Request;
use App\Http\Responses\Response;
use App\Repositories\Admin\SedeHorarioAdminRepository;

require_once APP_ROOT . '/app/Http/Responses/Response.php';
require_once APP_ROOT . '/app/Repositories/Admin/SedeHorarioAdminRepository.php';

final class SedeHorarioController
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
      $sedeId = (int)($req->query['sede_id'] ?? 0);
      if ($sedeId <= 0) {
         $this->bad('sede_id es requerido');
         return;
      }

      $repo = new SedeHorarioAdminRepository();
      Response::json(true, $repo->listBySede($sedeId));
   }

   public function store(Request $req): void
   {
      $this->requireAdmin();
      $b = $req->json();

      $sedeId = (int)($b['sede_id'] ?? 0);
      $dow = (int)($b['dow'] ?? 0);
      $orden = (int)($b['orden'] ?? 1);
      $abre = (string)($b['abre'] ?? '');
      $cierra = (string)($b['cierra'] ?? '');
      $activo = isset($b['activo']) ? ((int)$b['activo'] ? 1 : 0) : 1;

      if ($sedeId <= 0) {
         $this->bad('sede_id requerido');
         return;
      }
      if ($dow < 1 || $dow > 7) {
         $this->bad('dow inválido (1-7)');
         return;
      }
      if ($orden < 1 || $orden > 10) {
         $this->bad('orden inválido (1-10)');
         return;
      }
      if (!$this->isTime($abre) || !$this->isTime($cierra)) {
         $this->bad('abre/cierra inválidos (HH:MM)');
         return;
      }
      if ($abre >= $cierra) {
         $this->bad('abre debe ser menor que cierra');
         return;
      }

      $repo = new SedeHorarioAdminRepository();
      $id = $repo->insert([
         'sede_id' => $sedeId,
         'dow' => $dow,
         'orden' => $orden,
         'abre' => $abre,
         'cierra' => $cierra,
         'activo' => $activo
      ]);

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

      if (array_key_exists('dow', $b)) {
         $dow = (int)$b['dow'];
         if ($dow < 1 || $dow > 7) {
            $this->bad('dow inválido (1-7)');
            return;
         }
         $b['dow'] = $dow;
      }
      if (array_key_exists('orden', $b)) {
         $orden = (int)$b['orden'];
         if ($orden < 1 || $orden > 10) {
            $this->bad('orden inválido (1-10)');
            return;
         }
         $b['orden'] = $orden;
      }
      if (array_key_exists('abre', $b) && !$this->isTime((string)$b['abre'])) {
         $this->bad('abre inválido (HH:MM)');
         return;
      }
      if (array_key_exists('cierra', $b) && !$this->isTime((string)$b['cierra'])) {
         $this->bad('cierra inválido (HH:MM)');
         return;
      }
      if (array_key_exists('activo', $b)) $b['activo'] = (int)$b['activo'] ? 1 : 0;

      // si vienen ambos, valida abre < cierra
      if (array_key_exists('abre', $b) && array_key_exists('cierra', $b)) {
         if ((string)$b['abre'] >= (string)$b['cierra']) {
            $this->bad('abre debe ser menor que cierra');
            return;
         }
      }

      (new SedeHorarioAdminRepository())->update($id, $b);
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

      (new SedeHorarioAdminRepository())->softDelete($id);
      Response::json(true, ['deleted' => true]);
   }

   private function isTime(string $hhmm): bool
   {
      return (bool)preg_match('/^\d{2}:\d{2}$/', $hhmm) || (bool)preg_match('/^\d{2}:\d{2}:\d{2}$/', $hhmm);
   }
}
