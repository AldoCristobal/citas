<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Http\Router\Request;
use App\Support\Database\Db;
use App\Repositories\Booking\CitaRepository;
use App\Repositories\Booking\CitaEventoRepository;
use App\Services\Booking\HoldService;

require_once APP_ROOT . '/app/Support/Database/Db.php';
require_once APP_ROOT . '/app/Repositories/Booking/CitaRepository.php';
require_once APP_ROOT . '/app/Repositories/Booking/CitaEventoRepository.php';
require_once APP_ROOT . '/app/Services/Booking/HoldService.php';

final class ReagendaService
{
   public function createReagendaHold(
      string $folioAnterior,
      string $tokenAnterior,
      int $sedeId,
      int $tramiteId,
      string $fecha,
      string $hora,
      Request $req
   ): array {
      $pdo = Db::pdo();
      $repo = new CitaRepository();
      $evt  = new CitaEventoRepository();

      try {
         // ✅ TX corta: lock REAL + validación + auditoría
         $pdo->beginTransaction();

         $cita = $repo->findForUpdateByFolioAndToken($folioAnterior, $tokenAnterior);
         if (!$cita) {
            $pdo->rollBack();
            return $this->fail('NOT_FOUND', 'Cita no encontrada', 404);
         }
         if ($cita['estado'] !== 'confirmada') {
            $pdo->rollBack();
            return $this->fail('INVALID', 'Solo se puede reagendar una cita confirmada', 409);
         }

         $evt->insert((int)$cita['id'], 'reagenda_hold_solicitado', $req);

         $pdo->commit();

         // ✅ Creamos hold nuevo usando HoldService (su propia TX + cupo)
         $holdSvc = new HoldService();
         $holdRes = $holdSvc->createHold($sedeId, $tramiteId, $fecha, $hora, $req);

         if (!$holdRes['ok']) {
            return $holdRes;
         }

         // ✅ Auditoría adicional: hold creado (sin lock requerido)
         $evt->insert((int)$cita['id'], 'reagenda_hold_creado', $req);

         // Respuesta: devolvemos info para que al confirmar mande los datos anteriores
         $data = $holdRes['data'];
         $data['reagenda_folio'] = $folioAnterior;
         $data['reagenda_token'] = $tokenAnterior;

         return ['ok' => true, 'data' => $data];
      } catch (\Throwable $e) {
         if ($pdo->inTransaction()) $pdo->rollBack();
         return $this->fail('SERVER', 'No se pudo crear hold de reagenda', 500);
      }
   }

   private function fail(string $code, string $message, int $status): array
   {
      return ['ok' => false, 'error' => ['code' => $code, 'message' => $message], 'status' => $status];
   }
}
