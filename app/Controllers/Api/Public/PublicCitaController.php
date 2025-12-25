<?php

declare(strict_types=1);

namespace App\Controllers\Api\Public;

use App\Http\Router\Request;
use App\Http\Responses\Response;
use App\Services\Booking\PublicCitaService;

require_once APP_ROOT . '/app/Http/Responses/Response.php';
require_once APP_ROOT . '/app/Services/Booking/PublicCitaService.php';

final class PublicCitaController
{
   public function show(Request $req): void
   {
      $folio = (string)($req->params['folio'] ?? '');
      $token = (string)($req->query['token'] ?? '');

      if ($folio === '' || $token === '') {
         Response::badRequest('folio y token son requeridos');
         return;
      }

      $svc = new PublicCitaService();
      $res = $svc->getByFolioAndToken($folio, $token);

      if (!$res['ok']) {
         Response::json(false, null, $res['error'], $res['status'] ?? 400);
         return;
      }

      Response::json(true, $res['data']);
   }

   public function cancel(Request $req): void
   {
      $folio = (string)($req->params['folio'] ?? '');
      $body = $req->json();
      $token = (string)($body['token'] ?? '');

      if ($folio === '' || $token === '') {
         Response::badRequest('folio y token son requeridos');
         return;
      }

      $svc = new PublicCitaService();
      $res = $svc->cancelByFolioAndToken($folio, $token, $req);

      if (!$res['ok']) {
         Response::json(false, null, $res['error'], $res['status'] ?? 400);
         return;
      }

      Response::json(true, $res['data']);
   }
}
