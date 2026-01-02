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

      // === Datos persona (nuevos) ===
      $nombre          = trim((string)($body['nombre'] ?? ''));
      $apellidoPaterno = trim((string)($body['apellido_paterno'] ?? ''));
      $apellidoMaterno = trim((string)($body['apellido_materno'] ?? ''));

      $curpRfc  = strtoupper(trim((string)($body['curp_rfc'] ?? '')));
      $email    = trim((string)($body['email'] ?? ''));
      $telefono = trim((string)($body['telefono'] ?? ''));

      // === Dirección ===
      $calle        = trim((string)($body['calle'] ?? ''));
      $numExt       = trim((string)($body['numero_exterior'] ?? ''));
      $numInt       = trim((string)($body['numero_interior'] ?? '')); // opcional
      $colonia      = trim((string)($body['colonia'] ?? ''));
      $codigoPostal = trim((string)($body['codigo_postal'] ?? ''));
      $estado       = trim((string)($body['estado'] ?? ''));
      $municipio    = trim((string)($body['municipio'] ?? ''));

      // === Nacimiento/edad (opcionales; si faltan se intentan derivar de CURP) ===
      $fechaNacimiento = trim((string)($body['fecha_nacimiento'] ?? '')); // YYYY-MM-DD
      $edadRaw         = $body['edad'] ?? null; // int|string|null

      // Reagenda
      $reFolio = trim((string)($body['reagenda_folio'] ?? ''));
      $reToken = trim((string)($body['reagenda_token'] ?? ''));

      // hold_token: 64 hex
      if ($holdToken === '' || !preg_match('/^[a-f0-9]{64}$/i', $holdToken)) {
         Response::badRequest('hold_token requerido');
         return;
      }

      // Validaciones mínimas (ajusta si quieres más estrictas)
      if ($nombre === '' || mb_strlen($nombre) < 2) {
         Response::badRequest('nombre requerido');
         return;
      }
      if ($apellidoPaterno === '' || mb_strlen($apellidoPaterno) < 2) {
         Response::badRequest('apellido_paterno requerido');
         return;
      }

      // CURP: dejamos simple (>=10) como lo traías, pero ya viene en mayúsculas.
      if ($curpRfc === '' || mb_strlen($curpRfc) < 10) {
         Response::badRequest('curp_rfc requerido');
         return;
      }

      if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
         Response::badRequest('email inválido');
         return;
      }

      // Dirección (si quieres hacerla opcional, quita estas validaciones)
      if ($calle === '' || mb_strlen($calle) < 2) {
         Response::badRequest('calle requerida');
         return;
      }
      if ($numExt === '') {
         Response::badRequest('numero_exterior requerido');
         return;
      }
      if ($colonia === '' || mb_strlen($colonia) < 2) {
         Response::badRequest('colonia requerida');
         return;
      }
      if (!preg_match('/^\d{5}$/', $codigoPostal)) {
         Response::badRequest('codigo_postal inválido (5 dígitos)');
         return;
      }
      if ($estado === '' || mb_strlen($estado) < 2) {
         Response::badRequest('estado requerido');
         return;
      }
      if ($municipio === '' || mb_strlen($municipio) < 2) {
         Response::badRequest('municipio requerido');
         return;
      }

      // fecha_nacimiento si viene: YYYY-MM-DD
      if ($fechaNacimiento !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaNacimiento)) {
         Response::badRequest('fecha_nacimiento inválida (YYYY-MM-DD)');
         return;
      }

      // edad si viene: int razonable
      $edad = null;
      if ($edadRaw !== null && $edadRaw !== '') {
         $edad = (int)$edadRaw;
         if ($edad < 0 || $edad > 130) {
            Response::badRequest('edad inválida');
            return;
         }
      }

      // Si es reagenda, deben venir ambos
      if (($reFolio !== '' && $reToken === '') || ($reFolio === '' && $reToken !== '')) {
         Response::badRequest('reagenda_folio y reagenda_token deben enviarse juntos');
         return;
      }

      $svc = new BookingService();
      $result = $svc->confirmHold($holdToken, [
         'nombre' => $nombre,
         'apellido_paterno' => $apellidoPaterno,
         'apellido_materno' => $apellidoMaterno,

         'curp_rfc' => $curpRfc,
         'email' => $email,
         'telefono' => $telefono,

         'calle' => $calle,
         'numero_exterior' => $numExt,
         'numero_interior' => $numInt !== '' ? $numInt : null,
         'colonia' => $colonia,
         'codigo_postal' => $codigoPostal,
         'estado' => $estado,
         'municipio' => $municipio,

         'fecha_nacimiento' => $fechaNacimiento !== '' ? $fechaNacimiento : null,
         'edad' => $edad,

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
