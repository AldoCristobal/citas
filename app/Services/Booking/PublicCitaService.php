<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Http\Router\Request;
use App\Support\Database\Db;
use App\Repositories\Booking\CitaRepository;
use App\Repositories\Booking\CitaEventoRepository;

require_once APP_ROOT . '/app/Support/Database/Db.php';
require_once APP_ROOT . '/app/Repositories/Booking/CitaRepository.php';
require_once APP_ROOT . '/app/Repositories/Booking/CitaEventoRepository.php';

final class PublicCitaService
{
   public function getByFolioAndToken(string $folio, string $token): array
   {
      $repo = new CitaRepository();
      $cita = $repo->findPublicByFolioAndToken($folio, $token);

      if (!$cita) {
         return $this->fail('NOT_FOUND', 'Cita no encontrada', 404);
      }

      return ['ok' => true, 'data' => $cita];
   }

   public function cancelByFolioAndToken(string $folio, string $token, Request $req): array
   {
      $pdo = Db::pdo();
      $repo = new CitaRepository();
      $evt = new CitaEventoRepository();

      try {
         $pdo->beginTransaction();

         $cita = $repo->findForUpdateByFolioAndToken($folio, $token);
         if (!$cita) {
            $pdo->rollBack();
            return $this->fail('NOT_FOUND', 'Cita no encontrada', 404);
         }

         // Reglas: solo cancelable si está confirmada (y no ya atendida)
         $estado = (string)$cita['estado'];
         if ($estado !== 'confirmada') {
            $pdo->rollBack();
            return $this->fail('INVALID', 'La cita no se puede cancelar en su estado actual', 409);
         }

         $repo->cancel((int)$cita['id']);
         $evt->insert((int)$cita['id'], 'cita_cancelada_publico', $req);

         $pdo->commit();

         return ['ok' => true, 'data' => ['folio' => $folio, 'estatus' => 'cancelada']];
      } catch (\Throwable $e) {
         if ($pdo->inTransaction()) $pdo->rollBack();
         return $this->fail('SERVER', 'No se pudo cancelar la cita', 500);
      }
   }

   private function fail(string $code, string $message, int $status): array
   {
      return ['ok' => false, 'error' => ['code' => $code, 'message' => $message], 'status' => $status];
   }
}
