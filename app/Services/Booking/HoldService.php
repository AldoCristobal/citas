<?php

declare(strict_types=1);

namespace App\Services\Booking;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;

use App\Http\Router\Request;
use App\Support\Database\Db;
use App\Repositories\Catalog\SedeTramiteRepository;
use App\Repositories\Catalog\FeriadoRepository;
use App\Repositories\Catalog\SedeExcepcionRepository;
use App\Repositories\Booking\CitaRepository;
use App\Repositories\Booking\CitaEventoRepository;
use App\Repositories\Catalog\TramiteRepository;

require_once APP_ROOT . '/app/Support/Database/Db.php';
require_once APP_ROOT . '/app/Repositories/Catalog/SedeTramiteRepository.php';
require_once APP_ROOT . '/app/Repositories/Catalog/FeriadoRepository.php';
require_once APP_ROOT . '/app/Repositories/Catalog/SedeExcepcionRepository.php';
require_once APP_ROOT . '/app/Repositories/Booking/CitaRepository.php';
require_once APP_ROOT . '/app/Repositories/Booking/CitaEventoRepository.php';
require_once APP_ROOT . '/app/Repositories/Catalog/TramiteRepository.php';

final class HoldService
{
   private DateTimeZone $tz;

   public function __construct()
   {
      $this->tz = new DateTimeZone('America/Mexico_City');
   }

   public function createHold(int $sedeId, int $tramiteId, string $fecha, string $hora, Request $req): array
   {
      $pdo = Db::pdo();

      // 1) Validar reglas sede_tramite
      $stRepo = new SedeTramiteRepository();
      $st = $stRepo->getActive($sedeId, $tramiteId);
      if (!$st) {
         return $this->fail('NOT_FOUND', 'Sede/trámite no disponible', 404);
      }

      // 2) Validar feriado / cerrado
      $ferRepo = new FeriadoRepository();
      if ($ferRepo->isFeriado($fecha, $sedeId)) {
         return $this->fail('NO_DISPONIBLE', 'Día inhábil', 409);
      }

      $exRepo = new SedeExcepcionRepository();
      $ex = $exRepo->getByDate($sedeId, $fecha);
      if ($ex && (int)$ex['cerrado'] === 1) {
         return $this->fail('NO_DISPONIBLE', 'Sede cerrada en esa fecha', 409);
      }

      $slotMin = (int)$st['slot_min'];
      $cupoPorSlot = (int)$st['cupo_por_slot'];
      if ($ex && $ex['cupo_por_slot'] !== null) {
         $cupoPorSlot = (int)$ex['cupo_por_slot'];
      }

      // Validación rápida: hora debe alinearse al slot (ej 15 => 09:00, 09:15, etc.)
      if (!$this->isAlignedToSlot($hora, $slotMin)) {
         return $this->fail('VALIDATION', 'Hora no alineada al tamaño de slot', 400);
      }

      // Duración real del trámite (para ocupar N slots)
      $tramRepo = new TramiteRepository();
      $tram = $tramRepo->getById($tramiteId);
      if (!$tram || (int)$tram['activo'] !== 1) {
         return $this->fail('NOT_FOUND', 'Trámite no disponible', 404);
      }

      $duracionMin = (int)$tram['duracion_min'];
      if ($duracionMin <= 0) $duracionMin = $slotMin;

      $slotsNecesarios = (int)ceil($duracionMin / max(1, $slotMin));
      $horaFin = $this->addMinutes($hora, $slotsNecesarios * $slotMin);

      // 3) Hold expira en 10 min
      $now = new DateTimeImmutable('now', $this->tz);
      $expiresAt = $now->add(new DateInterval('PT10M'));

      // 4) Insertar hold con transacción, validando cupo por CADA slot que ocupa
      $citaRepo = new CitaRepository();
      $evtRepo  = new CitaEventoRepository();

      try {
         $pdo->beginTransaction();

         // Limpieza "lazy"
         $citaRepo->expireHolds();

         // Validar cupo en cada slot involucrado (lock por slot)
         for ($i = 0; $i < $slotsNecesarios; $i++) {
            $slotHora = $this->addMinutes($hora, $i * $slotMin);

            $ocupadas = $citaRepo->countOccupiedForUpdate($sedeId, $tramiteId, $fecha, $slotHora);
            if ($ocupadas >= $cupoPorSlot) {
               $pdo->rollBack();
               return $this->fail('SIN_CUPO', 'Ya no hay cupo para ese horario', 409);
            }
         }

         $holdToken = $this->token64();

         $citaId = $citaRepo->insertHold([
            'sede_id' => $sedeId,
            'tramite_id' => $tramiteId,
            'fecha' => $fecha,
            'hora_inicio' => $hora,
            'hora_fin' => $horaFin,
            'hold_token' => $holdToken,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
         ]);

         $evtRepo->insert($citaId, 'hold_creado', $req);

         $pdo->commit();

         return [
            'ok' => true,
            'data' => [
               'hold_token' => $holdToken,
               'expires_at' => $expiresAt->format(DATE_ATOM),
               'slot_min' => $slotMin,
               'duracion_min' => $duracionMin,
               'slots_necesarios' => $slotsNecesarios,
            ],
         ];
      } catch (\Throwable $e) {
         if ($pdo->inTransaction()) $pdo->rollBack();
         return $this->fail('SERVER', 'No se pudo crear hold', 500);
      }
   }

   private function isAlignedToSlot(string $hhmm, int $slotMin): bool
   {
      $t = DateTimeImmutable::createFromFormat('H:i', $hhmm, $this->tz);
      if (!$t) return false;
      $min = (int)$t->format('i');
      return $slotMin > 0 ? ($min % $slotMin === 0) : true;
   }

   private function addMinutes(string $hhmm, int $min): string
   {
      $t = DateTimeImmutable::createFromFormat('H:i', $hhmm, $this->tz);
      if (!$t) return $hhmm;
      return $t->add(new DateInterval('PT' . $min . 'M'))->format('H:i');
   }

   private function token64(): string
   {
      return bin2hex(random_bytes(32)); // 64 hex chars
   }

   private function fail(string $code, string $message, int $status): array
   {
      return ['ok' => false, 'error' => ['code' => $code, 'message' => $message], 'status' => $status];
   }
}
