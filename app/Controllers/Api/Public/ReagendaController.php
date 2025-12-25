<?php

declare(strict_types=1);

namespace App\Controllers\Api\Public;

use App\Http\Router\Request;
use App\Http\Responses\Response;
use App\Services\Booking\ReagendaService;

require_once APP_ROOT . '/app/Http/Responses/Response.php';
require_once APP_ROOT . '/app/Services/Booking/ReagendaService.php';

final class ReagendaController
{
   public function createHold(Request $req): void
   {
      $folio = trim((string)($req->params['folio'] ?? ''));
      $body  = $req->json();

      $token    = trim((string)($body['token'] ?? ''));
      $tramiteId = (int)($body['tramite_id'] ?? 0);
      $sedeId   = (int)($body['sede_id'] ?? 0);
      $fecha    = trim((string)($body['fecha'] ?? ''));
      $hora     = trim((string)($body['hora'] ?? ''));

      if ($folio === '' || $token === '') {
         Response::badRequest('folio y token son requeridos');
         return;
      }

      // access_token es 64 hex
      if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
         Response::badRequest('token inválido');
         return;
      }

      if ($tramiteId <= 0 || $sedeId <= 0) {
         Response::badRequest('tramite_id y sede_id son requeridos');
         return;
      }

      if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
         Response::badRequest('fecha inválida (YYYY-MM-DD)');
         return;
      }

      if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hora)) {
         Response::badRequest('hora inválida (HH:MM)');
         return;
      }

      $svc = new ReagendaService();
      $res = $svc->createReagendaHold($folio, $token, $sedeId, $tramiteId, $fecha, $hora, $req);

      if (!$res['ok']) {
         Response::json(false, null, $res['error'], $res['status'] ?? 400);
         return;
      }

      Response::json(true, $res['data'], null, 201);
   }
}
