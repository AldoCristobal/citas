<?php

declare(strict_types=1);

namespace App\Controllers\Api\Public;

use App\Http\Router\Request;
use App\Http\Responses\Response;
use App\Services\Booking\HoldService;

require_once APP_ROOT . '/app/Http/Responses/Response.php';
require_once APP_ROOT . '/app/Services/Booking/HoldService.php';

final class HoldController
{
   public function create(Request $req): void
   {
      $body = $req->json();

      $tramiteId = (int)($body['tramite_id'] ?? 0);
      $sedeId    = (int)($body['sede_id'] ?? 0);
      $fecha     = trim((string)($body['fecha'] ?? ''));
      $hora      = trim((string)($body['hora'] ?? ''));

      if ($tramiteId <= 0 || $sedeId <= 0) {
         Response::badRequest('tramite_id y sede_id son requeridos');
         return;
      }

      if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
         Response::badRequest('fecha inválida (YYYY-MM-DD)');
         return;
      }

      // HH:MM validado (0-23, 0-59)
      if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hora)) {
         Response::badRequest('hora inválida (HH:MM)');
         return;
      }

      $svc = new HoldService();
      $result = $svc->createHold($sedeId, $tramiteId, $fecha, $hora, $req);

      if (!$result['ok']) {
         Response::json(false, null, $result['error'], $result['status'] ?? 400);
         return;
      }

      Response::json(true, $result['data'], null, 201);
   }
}
