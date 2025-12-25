<?php
// app/Controllers/Api/Public/AvailabilityController.php
declare(strict_types=1);

namespace App\Controllers\Api\Public;

use App\Http\Router\Request;
use App\Http\Responses\Response;
use App\Services\AvailabilityService;

require_once APP_ROOT . '/app/Http/Responses/Response.php';
require_once APP_ROOT . '/app/Services/Availability/AvailabilityService.php';

final class AvailabilityController
{
   public function disponibilidad(Request $req): void
   {
      $tramiteId = (int)($req->query['tramite_id'] ?? 0);
      $sedeId    = (int)($req->query['sede_id'] ?? 0);
      $mes       = (string)($req->query['mes'] ?? '');

      if ($tramiteId <= 0 || $sedeId <= 0 || !preg_match('/^\d{4}-\d{2}$/', $mes)) {
         Response::badRequest('Parámetros requeridos: tramite_id, sede_id, mes=YYYY-MM');
         return;
      }

      $svc = new AvailabilityService();
      $data = $svc->monthAvailability($sedeId, $tramiteId, $mes);

      Response::json(true, $data);
   }

   public function slots(Request $req): void
   {
      $tramiteId = (int)($req->query['tramite_id'] ?? 0);
      $sedeId    = (int)($req->query['sede_id'] ?? 0);
      $fecha     = (string)($req->query['fecha'] ?? '');

      if ($tramiteId <= 0 || $sedeId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
         Response::badRequest('Parámetros requeridos: tramite_id, sede_id, fecha=YYYY-MM-DD');
         return;
      }

      $svc = new AvailabilityService();
      $data = $svc->daySlots($sedeId, $tramiteId, $fecha);

      Response::json(true, $data);
   }

   public function month($req): void
   {
      $sedeId = (int)($_GET['sede_id'] ?? 0);
      $tramiteId = (int)($_GET['tramite_id'] ?? 0);
      $mes = (string)($_GET['mes'] ?? '');

      if ($sedeId <= 0 || $tramiteId <= 0 || $mes === '') {
         \App\Http\Responses\Response::json(false, null, [
            'code' => 'VALIDATION',
            'message' => 'sede_id, tramite_id y mes son requeridos'
         ], 400);
         return;
      }

      $svc = new \App\Services\AvailabilityService();
      $out = $svc->monthAvailability($sedeId, $tramiteId, $mes);

      if (!$out['ok']) {
         \App\Http\Responses\Response::json(false, null, [
            'code' => 'NOT_AVAILABLE',
            'message' => $out['error'] ?? 'No disponible'
         ], 409);
         return;
      }

      \App\Http\Responses\Response::json(true, $out['data']);
   }


   public function day(\App\Http\Router\Request $req): void
   {
      $tramiteId = (int)($req->query['tramite_id'] ?? 0);
      $sedeId = (int)($req->query['sede_id'] ?? 0);
      $fecha = (string)($req->query['fecha'] ?? '');

      if ($tramiteId <= 0 || $sedeId <= 0 || $fecha === '') {
         \App\Http\Responses\Response::badRequest('tramite_id, sede_id y fecha son requeridos');
         return;
      }

      $svc = new \App\Services\AvailabilityService(new \App\Repositories\Public\AvailabilityRepository());
      $res = $svc->availabilityDay($sedeId, $tramiteId, $fecha);

      if (!$res['ok']) {
         \App\Http\Responses\Response::json(false, null, ['code' => 'NOT_AVAILABLE', 'message' => $res['error']], 409);
         return;
      }

      \App\Http\Responses\Response::json(true, $res['data']);
   }
}
