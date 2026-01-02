<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Public\AvailabilityRepository;

require_once APP_ROOT . '/app/Repositories/Public/AvailabilityRepository.php';

final class AvailabilityService
{
   /** @var AvailabilityRepository */
   private $repo;

   // ✅ permite new AvailabilityService() sin argumentos (compat con tu controller)
   public function __construct(?AvailabilityRepository $repo = null)
   {
      $this->repo = $repo ?: new AvailabilityRepository();
   }

   /**
    * ✅ Devuelve un resumen del mes (días habilitados + ventana)
    * $mes formato: YYYY-MM
    */
   public function monthAvailability(int $sedeId, int $tramiteId, string $mes): array
   {
      $cfg = $this->repo->getConfig($sedeId, $tramiteId);
      if (!$cfg || (int)$cfg['st_activo'] !== 1 || (int)$cfg['t_activo'] !== 1) {
         return ['ok' => false, 'error' => 'Configuración no disponible'];
      }

      if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
         return ['ok' => false, 'error' => 'Formato de mes inválido (YYYY-MM)'];
      }

      $ventana = (int)$cfg['ventana_dias'];

      $today = new \DateTimeImmutable('now', new \DateTimeZone('America/Mexico_City'));
      $max = $today->modify("+{$ventana} days");

      $first = new \DateTimeImmutable($mes . '-01', new \DateTimeZone('America/Mexico_City'));
      $daysInMonth = (int)$first->format('t');

      $diasEnVentana = [];
      $diasAgendables = [];

      for ($d = 1; $d <= $daysInMonth; $d++) {
         $dt = $first->setDate(
            (int)$first->format('Y'),
            (int)$first->format('m'),
            $d
         );

         if ($dt < $today->setTime(0, 0) || $dt > $max->setTime(23, 59)) {
            continue;
         }

         $fecha = $dt->format('Y-m-d');
         $diasEnVentana[] = $fecha;

         $day = $this->daySlots($sedeId, $tramiteId, $fecha);
         if ($day['ok'] && !empty($day['data']['horas_disponibles'])) {
            $diasAgendables[] = $fecha;
         }
      }

      return [
         'ok' => true,
         'data' => [
            'mes' => $mes,
            'ventana_dias' => $ventana,
            'min_date' => $today->format('Y-m-d'),
            'max_date' => $max->format('Y-m-d'),
            'dias_en_ventana' => $diasEnVentana,
            'dias_agendables' => $diasAgendables,
            'slot_min' => (int)$cfg['slot_min'],
            'cupo_por_slot' => (int)$cfg['cupo_por_slot'],
            'duracion_min' => (int)$cfg['duracion_min'],
         ]
      ];
   }

   /**
    * ✅ Devuelve slots del día con disponibilidad.
    * $fecha formato: YYYY-MM-DD
    */
   public function daySlots(int $sedeId, int $tramiteId, string $fecha): array
   {
      if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
         return ['ok' => false, 'error' => 'Formato de fecha inválido (YYYY-MM-DD)'];
      }

      $cfg = $this->repo->getConfig($sedeId, $tramiteId);
      if (!$cfg || (int)$cfg['st_activo'] !== 1 || (int)$cfg['t_activo'] !== 1) {
         return ['ok' => false, 'error' => 'Configuración no disponible'];
      }

      // Validar ventana
      $ventana = (int)$cfg['ventana_dias'];
      $today = new \DateTimeImmutable('now', new \DateTimeZone('America/Mexico_City'));
      $min = $today->setTime(0, 0);
      $max = $today->modify("+{$ventana} days")->setTime(23, 59);
      $dt = new \DateTimeImmutable($fecha, new \DateTimeZone('America/Mexico_City'));

      if ($dt < $min || $dt > $max) {
         return ['ok' => false, 'error' => 'Fecha fuera de ventana de disponibilidad'];
      }

      $slotMin = (int)$cfg['slot_min'];
      $cupo = (int)$cfg['cupo_por_slot'];
      $dur = (int)$cfg['duracion_min'];
      $slotsReq = (int)ceil($dur / max(1, $slotMin));

      if ($this->repo->isFeriado($sedeId, $fecha)) {
         return [
            'ok' => true,
            'data' => [
               'fecha' => $fecha,
               'slot_min' => $slotMin,
               'cupo_por_slot' => $cupo,
               'duracion_min' => $dur,
               'slots_necesarios' => $slotsReq,
               'horas_disponibles' => [],
               'bloqueado_por' => 'feriado',
            ]
         ];
      }

      // Jornada según horarios por día de semana
      $dow = (int)$dt->format('N'); // 1=Lun..7=Dom
      $rangos = $this->repo->listSedeHorarios($sedeId, $dow);

      if (!$rangos) {
         return [
            'ok' => true,
            'data' => [
               'fecha' => $fecha,
               'slot_min' => $slotMin,
               'cupo_por_slot' => $cupo,
               'duracion_min' => $dur,
               'slots_necesarios' => $slotsReq,
               'horas_disponibles' => [],
            ]
         ];
      }

      // Construir todos los starts posibles del día
      $allStarts = [];
      foreach ($rangos as $r) {
         $abre = substr((string)$r['abre'], 0, 5);
         $cierra = substr((string)$r['cierra'], 0, 5);
         $rs = $this->buildSlotStarts($abre, $cierra, $slotMin);
         foreach ($rs as $h) $allStarts[$h] = true;
      }
      $starts = array_keys($allStarts);
      sort($starts);

      // Mapa de ocupación por start del slot (HH:MM => count)
      $occ = array_fill_keys($starts, 0);

      // Traer citas y holds del día
      $citas = $this->repo->listCitaStarts($sedeId, $tramiteId, $fecha);
      $holds = $this->repo->listHoldStarts($sedeId, $tramiteId, $fecha);

      /**
       * ✅ FIX IMPORTANTE:
       * Si el trámite ocupa 1 slot, NO propagamos por intervalos.
       * Contamos solo por hora_inicio (slot exacto), para que 09:00 no “ensucie” 09:10.
       */
      if ($slotsReq <= 1) {
         foreach ($citas as $r) {
            $h = substr((string)$r['hora_inicio'], 0, 5);
            if (array_key_exists($h, $occ)) $occ[$h] += 1;
         }
         foreach ($holds as $r) {
            $h = substr((string)$r['hora_inicio'], 0, 5);
            if (array_key_exists($h, $occ)) $occ[$h] += 1;
         }
      } else {
         // Trámites largos: sí propagamos la ocupación por intervalos
         foreach ($citas as $r) $this->addOccupancyInterval($occ, (string)$r['hora_inicio'], (string)$r['hora_fin'], $slotMin);
         foreach ($holds as $r) $this->addOccupancyInterval($occ, (string)$r['hora_inicio'], (string)$r['hora_fin'], $slotMin);
      }

      // Calcular starts disponibles por rango, verificando N slots consecutivos < cupo
      $availableSet = [];

      foreach ($rangos as $r) {
         $startR = substr((string)$r['abre'], 0, 5);
         $endR   = substr((string)$r['cierra'], 0, 5);

         $rangeStarts = $this->buildSlotStarts($startR, $endR, $slotMin);
         foreach ($rangeStarts as $h) {
            if ($this->canStartAt($occ, $h, $slotMin, $slotsReq, $cupo, $endR)) {
               $availableSet[$h] = true;
            }
         }
      }

      $available = array_keys($availableSet);
      sort($available);

      return [
         'ok' => true,
         'data' => [
            'fecha' => $fecha,
            'slot_min' => $slotMin,
            'cupo_por_slot' => $cupo,
            'duracion_min' => $dur,
            'slots_necesarios' => $slotsReq,
            'horas_disponibles' => $available,
         ]
      ];
   }

   // ===== Helpers internos =====

   private function buildSlotStarts(string $start, string $end, int $slotMin): array
   {
      $out = [];
      $t = $this->toMinutes($start);
      $endM = $this->toMinutes($end);
      while ($t < $endM) {
         $out[] = $this->fromMinutes($t);
         $t += $slotMin;
      }
      return $out;
   }

   private function addOccupancyInterval(array &$occ, string $horaIni, string $horaFin, int $slotMin): void
   {
      $ini = substr($horaIni, 0, 5);
      $fin = substr($horaFin, 0, 5);

      $t0 = $this->toMinutes($ini);
      $t1 = $this->toMinutes($fin);

      $slots = (int)ceil(max(0, $t1 - $t0) / max(1, $slotMin));
      if ($slots <= 0) $slots = 1;

      for ($i = 0; $i < $slots; $i++) {
         $h = $this->fromMinutes($t0 + ($i * $slotMin));
         if (array_key_exists($h, $occ)) $occ[$h] += 1;
      }
   }

   private function canStartAt(array $occ, string $horaStart, int $slotMin, int $slotsReq, int $cupo, string $end): bool
   {
      $t0 = $this->toMinutes($horaStart);
      $endM = $this->toMinutes($end);

      // si requiere N slots, el último start debe caber antes del cierre
      $lastSlotStart = $t0 + (($slotsReq - 1) * $slotMin);
      if ($lastSlotStart >= $endM) return false;

      for ($i = 0; $i < $slotsReq; $i++) {
         $h = $this->fromMinutes($t0 + ($i * $slotMin));
         if (!isset($occ[$h])) return false;
         if ((int)$occ[$h] >= $cupo) return false;
      }
      return true;
   }

   private function toMinutes(string $hhmm): int
   {
      $parts = explode(':', $hhmm);
      $h = (int)($parts[0] ?? 0);
      $m = (int)($parts[1] ?? 0);
      return ($h * 60) + $m;
   }

   private function fromMinutes(int $m): string
   {
      $h = intdiv($m, 60);
      $mm = $m % 60;
      return str_pad((string)$h, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string)$mm, 2, '0', STR_PAD_LEFT);
   }

   public function availabilityDay(int $sedeId, int $tramiteId, string $fecha): array
   {
      // Alias para compatibilidad con código previo
      return $this->daySlots($sedeId, $tramiteId, $fecha);
   }
}
