<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Support\Database\Db;
use App\Repositories\Admin\AgendaRepository;
use App\Repositories\Booking\CitaEventoRepository;
use App\Http\Router\Request;

require_once APP_ROOT . '/app/Support/Database/Db.php';
require_once APP_ROOT . '/app/Repositories/Admin/AgendaRepository.php';
require_once APP_ROOT . '/app/Repositories/Booking/CitaEventoRepository.php';

final class AgendaService
{
   private function fail(string $code, string $message, int $status): array
   {
      return ['ok' => false, 'error' => ['code' => $code, 'message' => $message], 'status' => $status];
   }

   public function list(array $f): array
   {
      if (empty($f['fecha']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['fecha'])) {
         return $this->fail('VALIDATION', 'fecha inválida (YYYY-MM-DD)', 400);
      }
      if (empty($f['sede_id']) || (int)$f['sede_id'] <= 0) {
         return $this->fail('VALIDATION', 'sede_id requerido', 400);
      }

      $repo = new AgendaRepository();
      $rows = $repo->list([
         'fecha' => $f['fecha'],
         'sede_id' => (int)$f['sede_id'],
         'estado' => trim((string)($f['estado'] ?? '')),
         'q' => trim((string)($f['q'] ?? '')),
      ]);

      return ['ok' => true, 'data' => $rows];
   }

   public function show(int $id): array
   {
      if ($id <= 0) return $this->fail('VALIDATION', 'id inválido', 400);

      $repo = new AgendaRepository();
      $cita = $repo->findById($id);
      if (!$cita) return $this->fail('NOT_FOUND', 'Cita no encontrada', 404);

      $eventos = $repo->listEventos($id);

      return ['ok' => true, 'data' => ['cita' => $cita, 'eventos' => $eventos]];
   }

   public function cambiarEstado(int $id, string $nuevoEstado, Request $req): array
   {
      $id = (int)$id;
      $nuevoEstado = trim($nuevoEstado);

      $allowed = [
         'confirmada',
         'en_atencion',
         'atendida',
         'no_asistio',
         'cancelada',
         'reprogramada',
         'hold',
         'expirada'
      ];
      if ($id <= 0) return $this->fail('VALIDATION', 'id inválido', 400);
      if ($nuevoEstado === '' || !in_array($nuevoEstado, $allowed, true)) {
         return $this->fail('VALIDATION', 'estado inválido', 400);
      }

      $pdo = Db::pdo();
      $repo = new AgendaRepository();
      $evtRepo = new CitaEventoRepository();

      try {
         $pdo->beginTransaction();

         $cita = $repo->findForUpdate($id);
         if (!$cita) {
            $pdo->rollBack();
            return $this->fail('NOT_FOUND', 'Cita no encontrada', 404);
         }

         $actual = (string)$cita['estado'];

         // Reglas operación (finales no cambian)
         $finales = ['atendida', 'no_asistio', 'cancelada', 'expirada'];
         if (in_array($actual, $finales, true)) {
            $pdo->rollBack();
            return $this->fail('INVALID', 'La cita ya está finalizada', 409);
         }

         // Transiciones permitidas
         $trans = [
            'confirmada'   => ['en_atencion', 'cancelada', 'no_asistio'],
            'en_atencion'  => ['atendida', 'no_asistio', 'cancelada'],
            'reprogramada' => [], // solo lectura
            'hold'         => [], // no se opera desde agenda
         ];

         $permitidas = $trans[$actual] ?? [];
         if (!in_array($nuevoEstado, $permitidas, true)) {
            $pdo->rollBack();
            return $this->fail('INVALID', "No se puede cambiar de {$actual} a {$nuevoEstado}", 409);
         }

         $repo->updateEstado($id, $nuevoEstado);
         $evtRepo->insert($id, 'admin_cambio_estado_' . $nuevoEstado, $req);

         $pdo->commit();

         return ['ok' => true, 'data' => ['id' => $id, 'estado' => $nuevoEstado]];
      } catch (\Throwable $e) {
         if ($pdo->inTransaction()) $pdo->rollBack();
         return $this->fail('SERVER', 'No se pudo cambiar el estado', 500);
      }
   }

   public function turnosHoy(int $sedeId): array
   {
      if ($sedeId <= 0) return $this->fail('VALIDATION', 'sede_id requerido', 400);

      $repo = new \App\Repositories\Admin\AgendaRepository();
      $rows = $repo->listHoy($sedeId);

      return ['ok' => true, 'data' => $rows];
   }
}
