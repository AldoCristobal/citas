<?php

declare(strict_types=1);

namespace App\Services\Booking;

use DateTimeImmutable;
use DateTimeZone;

use App\Http\Router\Request;
use App\Support\Database\Db;
use App\Repositories\Booking\CitaRepository;
use App\Repositories\Admin\FolioCounterRepository;
use App\Repositories\Catalog\SedeRepository;
use App\Repositories\Catalog\TramiteRepository;
use App\Repositories\Booking\CitaEventoRepository;

require_once APP_ROOT . '/app/Support/Database/Db.php';
require_once APP_ROOT . '/app/Repositories/Booking/CitaRepository.php';
require_once APP_ROOT . '/app/Repositories/Admin/FolioCounterRepository.php';
require_once APP_ROOT . '/app/Repositories/Catalog/SedeRepository.php';
require_once APP_ROOT . '/app/Repositories/Catalog/TramiteRepository.php';
require_once APP_ROOT . '/app/Repositories/Booking/CitaEventoRepository.php';

final class BookingService
{
   private DateTimeZone $tz;

   public function __construct()
   {
      $this->tz = new DateTimeZone('America/Mexico_City');
   }

   public function confirmHold(string $holdToken, array $persona, Request $req): array
   {
      $pdo = Db::pdo();

      $citaRepo = new CitaRepository();
      $folioRepo = new FolioCounterRepository();
      $evtRepo = new CitaEventoRepository();

      try {
         // Limpieza "lazy"
         $citaRepo->expireHolds();

         $pdo->beginTransaction();

         // ✅ Lock del hold
         $hold = $citaRepo->findHoldForUpdateByToken($holdToken);
         if (!$hold) {
            $pdo->rollBack();
            return $this->fail('NOT_FOUND', 'Hold no existe', 404);
         }

         // ✅ Vigencia
         $now = new DateTimeImmutable('now', $this->tz);

         $expiresAtRaw = (string)($hold['expires_at'] ?? '');
         if ($expiresAtRaw === '') {
            $citaRepo->markExpired((int)$hold['id']);
            $evtRepo->insert((int)$hold['id'], 'hold_expirado_al_confirmar', $req);
            $pdo->commit();
            return $this->fail('EXPIRED', 'El tiempo de reserva expiró', 409);
         }

         $expiresAt = new DateTimeImmutable($expiresAtRaw, $this->tz);
         if ($expiresAt <= $now) {
            $citaRepo->markExpired((int)$hold['id']);
            $evtRepo->insert((int)$hold['id'], 'hold_expirado_al_confirmar', $req);
            $pdo->commit();
            return $this->fail('EXPIRED', 'El tiempo de reserva expiró', 409);
         }

         // ✅ Revalidación de cupo por traslape
         $cfg = $citaRepo->getSedeTramiteConfig((int)$hold['sede_id'], (int)$hold['tramite_id']);
         if (!$cfg || (int)$cfg['activo'] !== 1) {
            $pdo->rollBack();
            return $this->fail('NOT_AVAILABLE', 'Trámite no disponible en esta sede', 409);
         }
         $cupo = (int)$cfg['cupo_por_slot'];

         $horaInicio = substr((string)$hold['hora_inicio'], 0, 8);
         $horaFin = substr((string)$hold['hora_fin'], 0, 8);

         $over = $citaRepo->countOverlaps(
            (int)$hold['sede_id'],
            (int)$hold['tramite_id'],
            (string)$hold['fecha'],
            $horaInicio,
            $horaFin,
            (int)$hold['id']
         );

         if ($over >= $cupo) {
            $pdo->rollBack();
            return $this->fail('FULL', 'Ese horario ya no está disponible', 409);
         }

         // ===== Derivar nacimiento/edad desde CURP (si faltan) =====
         $curp = strtoupper(trim((string)($persona['curp_rfc'] ?? '')));
         $fechaNac = $persona['fecha_nacimiento'] ?? null; // YYYY-MM-DD|null
         $edad = $persona['edad'] ?? null; // int|null

         if ((!$fechaNac || !$edad) && $curp !== '') {
            $parsed = $this->birthFromCurp($curp, $now);
            if ($parsed) {
               if (!$fechaNac) $fechaNac = $parsed['fecha_nacimiento'];
               if (!$edad && $parsed['edad'] !== null) $edad = $parsed['edad'];
            }
         }

         // ===== Folio + access token =====
         $sedeId = (int)$hold['sede_id'];
         $next = $folioRepo->nextForSede($sedeId);

         $sedeRepo = new SedeRepository();
         $sede = $sedeRepo->getById($sedeId);
         if (!$sede) {
            $pdo->rollBack();
            return $this->fail('SERVER', 'Sede inválida', 500);
         }

         $folioPublico = $sede['prefijo_folio'] . '-' . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
         $accessToken = bin2hex(random_bytes(32));

         // ===== Confirmar y guardar datos =====
         $citaRepo->confirmHold((int)$hold['id'], [
            'nombre' => $persona['nombre'] ?? null,
            'apellido_paterno' => $persona['apellido_paterno'] ?? null,
            'apellido_materno' => $persona['apellido_materno'] ?? null,

            'curp_rfc' => $curp ?: null,
            'email' => $persona['email'] ?? null,
            'telefono' => $persona['telefono'] ?? null,

            'calle' => $persona['calle'] ?? null,
            'numero_exterior' => $persona['numero_exterior'] ?? null,
            'numero_interior' => $persona['numero_interior'] ?? null,
            'colonia' => $persona['colonia'] ?? null,
            'codigo_postal' => $persona['codigo_postal'] ?? null,
            'estado' => $persona['estado'] ?? null,
            'municipio' => $persona['municipio'] ?? null,

            'fecha_nacimiento' => $fechaNac,
            'edad' => $edad,

            'folio_num' => $next,
            'folio_publico' => $folioPublico,
            'access_token' => $accessToken,
         ]);

         // Reagenda (si aplica)
         $reFolio = trim((string)($persona['reagenda_folio'] ?? ''));
         $reToken = trim((string)($persona['reagenda_token'] ?? ''));

         if ($reFolio !== '' && $reToken !== '') {
            $prev = $citaRepo->findForUpdateByFolioAndToken($reFolio, $reToken);
            if ($prev && $prev['estado'] === 'confirmada') {
               $citaRepo->markReprogramada((int)$prev['id'], $folioPublico);
               $evtRepo->insert((int)$prev['id'], 'cita_reprogramada', $req);
            }
         }

         $evtRepo->insert((int)$hold['id'], 'cita_confirmada', $req);

         $tramRepo = new TramiteRepository();
         $tram = $tramRepo->getById((int)$hold['tramite_id']);

         $pdo->commit();

         return [
            'ok' => true,
            'data' => [
               'folio' => $folioPublico,
               'access_token' => $accessToken,
               'fecha' => $hold['fecha'],
               'hora' => substr((string)$hold['hora_inicio'], 0, 5),
               'sede' => [
                  'id' => (int)$sede['id'],
                  'nombre' => $sede['nombre'],
               ],
               'tramite' => $tram ? ['id' => (int)$tram['id'], 'nombre' => $tram['nombre']] : null,
               'persona' => [
                  'nombre' => $persona['nombre'] ?? null,
                  'apellido_paterno' => $persona['apellido_paterno'] ?? null,
                  'apellido_materno' => $persona['apellido_materno'] ?? null,
                  'curp_rfc' => $curp ?: null,
                  'fecha_nacimiento' => $fechaNac,
                  'edad' => $edad,
               ],
            ],
         ];
      } catch (\Throwable $e) {
         if ($pdo->inTransaction()) $pdo->rollBack();
         return $this->fail('SERVER', 'No se pudo confirmar la cita', 500);
      }
   }

   /**
    * Intenta obtener fecha nacimiento y edad desde CURP.
    * Devuelve null si no se puede.
    *
    * CURP posiciones 5-10: YYMMDD
    * Siglo: se infiere con la letra 17 (pos 16) [A-Z0-9], usando regla típica:
    *  - Si es dígito (0-9) => 1900-1999
    *  - Si es letra (A-Z) => 2000-2099
    */
   private function birthFromCurp(string $curp, DateTimeImmutable $now): ?array
   {
      $c = strtoupper(trim($curp));
      if (strlen($c) < 10) return null;

      $yy = substr($c, 4, 2);
      $mm = substr($c, 6, 2);
      $dd = substr($c, 8, 2);

      if (!ctype_digit($yy . $mm . $dd)) return null;

      // inferir siglo con caracter 17 (pos 16), si existe
      $siglo = 1900;
      if (strlen($c) >= 17) {
         $ch17 = $c[16];
         if (ctype_alpha($ch17)) $siglo = 2000;
         if (ctype_digit($ch17)) $siglo = 1900;
      } else {
         // fallback: si no hay 17, inferir por "si queda en futuro"
         $siglo = 1900;
      }

      $year = $siglo + (int)$yy;
      $dateStr = sprintf('%04d-%02d-%02d', $year, (int)$mm, (int)$dd);

      try {
         $dob = new DateTimeImmutable($dateStr, $this->tz);
      } catch (\Throwable $e) {
         return null;
      }

      // si por alguna razón queda en el futuro, intenta 1900
      if ($dob > $now) {
         $year2 = 1900 + (int)$yy;
         $dateStr2 = sprintf('%04d-%02d-%02d', $year2, (int)$mm, (int)$dd);
         try {
            $dob = new DateTimeImmutable($dateStr2, $this->tz);
         } catch (\Throwable $e) {
            return null;
         }
      }

      $edad = $dob ? $this->calcAge($dob, $now) : null;

      return [
         'fecha_nacimiento' => $dob->format('Y-m-d'),
         'edad' => $edad,
      ];
   }

   private function calcAge(DateTimeImmutable $dob, DateTimeImmutable $now): ?int
   {
      $y = (int)$now->format('Y') - (int)$dob->format('Y');

      $mNow = (int)$now->format('m');
      $dNow = (int)$now->format('d');
      $mDob = (int)$dob->format('m');
      $dDob = (int)$dob->format('d');

      if ($mNow < $mDob || ($mNow === $mDob && $dNow < $dDob)) {
         $y--;
      }
      if ($y < 0 || $y > 130) return null;
      return $y;
   }

   private function fail(string $code, string $message, int $status): array
   {
      return ['ok' => false, 'error' => ['code' => $code, 'message' => $message], 'status' => $status];
   }
}
