<?php

declare(strict_types=1);

namespace App\Controllers\Api\Public;

use App\Http\Router\Request;
use App\Http\Responses\Response;
use App\Services\Booking\BookingService;

require_once APP_ROOT . '/app/Http/Responses/Response.php';
require_once APP_ROOT . '/app/Services/Booking/BookingService.php';

final class BookingController
{
   public function confirm(Request $req): void
   {
      $body = $req->json();

      $holdToken = trim((string)($body['hold_token'] ?? ''));

      $nombre    = trim((string)($body['nombre'] ?? ''));
      $curpRfc   = strtoupper(trim((string)($body['curp_rfc'] ?? '')));
      $email     = trim((string)($body['email'] ?? ''));
      $telefono  = trim((string)($body['telefono'] ?? ''));

      $reFolio = trim((string)($body['reagenda_folio'] ?? ''));
      $reToken = trim((string)($body['reagenda_token'] ?? ''));

      // hold_token: 64 hex
      if ($holdToken === '' || !preg_match('/^[a-f0-9]{64}$/i', $holdToken)) {
         Response::badRequest('hold_token requerido');
         return;
      }

      if ($nombre === '' || mb_strlen($nombre) < 3) {
         Response::badRequest('nombre requerido');
         return;
      }

      if ($curpRfc === '' || mb_strlen($curpRfc) < 10) {
         Response::badRequest('curp_rfc requerido');
         return;
      }

      if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
         Response::badRequest('email inválido');
         return;
      }

      // Si es reagenda, deben venir ambos
      if (($reFolio !== '' && $reToken === '') || ($reFolio === '' && $reToken !== '')) {
         Response::badRequest('reagenda_folio y reagenda_token deben enviarse juntos');
         return;
      }

      $svc = new BookingService();
      $result = $svc->confirmHold($holdToken, [
         'nombre' => $nombre,
         'curp_rfc' => $curpRfc,
         'email' => $email,
         'telefono' => $telefono,
         'reagenda_folio' => $reFolio,
         'reagenda_token' => $reToken,
      ], $req);

      if (!$result['ok']) {
         Response::json(false, null, $result['error'], $result['status'] ?? 400);
         return;
      }

      Response::json(true, $result['data'], null, 200);
   }
}
