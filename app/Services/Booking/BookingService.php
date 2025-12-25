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
         // Limpieza "lazy" (opcional pero recomendable)
         $citaRepo->expireHolds();

         $pdo->beginTransaction();

         // ✅ Lock del hold (FOR UPDATE) + filtro estado='hold'
         $hold = $citaRepo->findHoldForUpdateByToken($holdToken);
         if (!$hold) {
            $pdo->rollBack();
            return $this->fail('NOT_FOUND', 'Hold no existe', 404);
         }

         // ✅ Vigencia (comparación robusta)
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

         // ✅ Revalidación de cupo (anti-empalme real por traslape)
         $cfg = $citaRepo->getSedeTramiteConfig((int)$hold['sede_id'], (int)$hold['tramite_id']);
         if (!$cfg || (int)$cfg['activo'] !== 1) {
            $pdo->rollBack();
            return $this->fail('NOT_AVAILABLE', 'Trámite no disponible en esta sede', 409);
         }
         $cupo = (int)$cfg['cupo_por_slot'];

         // El hold ocupa un intervalo [hora_inicio, hora_fin)
         $horaInicio = substr((string)$hold['hora_inicio'], 0, 8); // HH:MM:SS
         $horaFin = substr((string)$hold['hora_fin'], 0, 8);       // HH:MM:SS

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

         // Generar folio secuencial por sede (de por vida)
         $sedeId = (int)$hold['sede_id'];
         $next = $folioRepo->nextForSede($sedeId); // bloquea contador FOR UPDATE

         // Obtener prefijo de sede
         $sedeRepo = new SedeRepository();
         $sede = $sedeRepo->getById($sedeId);
         if (!$sede) {
            $pdo->rollBack();
            return $this->fail('SERVER', 'Sede inválida', 500);
         }

         $folioPublico = $sede['prefijo_folio'] . '-' . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
         $accessToken = bin2hex(random_bytes(32));

         // Confirmar y guardar datos ciudadano + folio + access token
         $citaRepo->confirmHold((int)$hold['id'], [
            'nombre' => $persona['nombre'] ?? null,
            'curp_rfc' => $persona['curp_rfc'] ?? null,
            'email' => $persona['email'] ?? null,
            'telefono' => $persona['telefono'] ?? null,
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

         // Para respuesta amigable
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
            ],
         ];
      } catch (\Throwable $e) {
         if ($pdo->inTransaction()) $pdo->rollBack();
         return $this->fail('SERVER', 'No se pudo confirmar la cita', 500);
      }
   }

   private function fail(string $code, string $message, int $status): array
   {
      return ['ok' => false, 'error' => ['code' => $code, 'message' => $message], 'status' => $status];
   }
}
