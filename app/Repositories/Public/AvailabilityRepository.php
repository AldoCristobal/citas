<?php

declare(strict_types=1);

namespace App\Repositories\Public;

use App\Support\Database\Db;

require_once APP_ROOT . '/app/Support/Database/Db.php';

final class AvailabilityRepository
{
   public function getConfig(int $sedeId, int $tramiteId): ?array
   {
      $pdo = Db::pdo();
      $sql = "SELECT
                  st.cupo_por_slot, st.slot_min, st.ventana_dias, st.activo AS st_activo,
                  t.duracion_min, t.activo AS t_activo
                FROM sede_tramite st
                INNER JOIN tramite t ON t.id = st.tramite_id
                WHERE st.sede_id = :sede_id AND st.tramite_id = :tramite_id
                LIMIT 1";
      $st = $pdo->prepare($sql);
      $st->execute(['sede_id' => $sedeId, 'tramite_id' => $tramiteId]);
      $row = $st->fetch();
      return $row ?: null;
   }

   /**
    * Citas que ocupan agenda (NO holds).
    * Devuelve hora_inicio y hora_fin para ocupación real.
    */
   public function listCitaStarts(int $sedeId, int $tramiteId, string $fecha): array
   {
      $pdo = Db::pdo();

      // Estados que ocupan capacidad:
      // - confirmada: ocupa
      // - en_atencion: ocupa
      // Nota: reprogramada normalmente NO debería ocupar (la cita ya no es atendible).
      $sql = "SELECT hora_inicio, hora_fin
                FROM cita
                WHERE sede_id = :sede_id
                  AND tramite_id = :tramite_id
                  AND fecha = :fecha
                  AND estado IN ('confirmada','en_atencion')";

      $st = $pdo->prepare($sql);
      $st->execute([
         'sede_id' => $sedeId,
         'tramite_id' => $tramiteId,
         'fecha' => $fecha,
      ]);

      return $st->fetchAll() ?: [];
   }

   /**
    * Holds vigentes (estado='hold' y expires_at futuro).
    * Devuelve hora_inicio y hora_fin para ocupación real.
    */
   public function listHoldStarts(int $sedeId, int $tramiteId, string $fecha): array
   {
      $pdo = Db::pdo();

      $sql = "SELECT hora_inicio, hora_fin
                FROM cita
                WHERE sede_id = :sede_id
                  AND tramite_id = :tramite_id
                  AND fecha = :fecha
                  AND estado = 'hold'
                  AND expires_at IS NOT NULL
                  AND expires_at > NOW()";

      $st = $pdo->prepare($sql);
      $st->execute([
         'sede_id' => $sedeId,
         'tramite_id' => $tramiteId,
         'fecha' => $fecha,
      ]);

      return $st->fetchAll() ?: [];
   }

   public function listSedeHorarios(int $sedeId, int $dow): array
   {
      $pdo = Db::pdo();
      $sql = "SELECT abre, cierra
            FROM sede_horario
            WHERE sede_id = :sede_id
              AND dow = :dow
              AND activo = 1
            ORDER BY orden ASC";
      $st = $pdo->prepare($sql);
      $st->execute(['sede_id' => $sedeId, 'dow' => $dow]);
      return $st->fetchAll() ?: [];
   }

   public function isFeriado(int $sedeId, string $fecha): bool
   {
      $pdo = Db::pdo();
      $sql = "SELECT 1
            FROM feriado
            WHERE fecha=:fecha
              AND activo=1
              AND (sede_id IS NULL OR sede_id=:sede_id)
            LIMIT 1";
      $st = $pdo->prepare($sql);
      $st->execute(['fecha' => $fecha, 'sede_id' => $sedeId]);
      return (bool)$st->fetchColumn();
   }
}
