<?php

declare(strict_types=1);

namespace App\Repositories\Admin;

use App\Support\Database\Db;

require_once APP_ROOT . '/app/Support/Database/Db.php';

final class SedeTramiteAdminRepository
{
   public function listBySede(int $sedeId): array
   {
      $pdo = Db::pdo();
      $sql = "SELECT
                  st.id,
                  st.sede_id,
                  st.tramite_id,
                  t.nombre AS tramite_nombre,
                  t.duracion_min AS tramite_duracion_min,
                  st.cupo_por_slot,
                  st.slot_min,
                  st.ventana_dias,
                  st.activo
                FROM sede_tramite st
                INNER JOIN tramite t ON t.id = st.tramite_id
                WHERE st.sede_id = :sede_id
                ORDER BY t.nombre ASC";
      $stt = $pdo->prepare($sql);
      $stt->execute(['sede_id' => $sedeId]);
      return $stt->fetchAll();
   }

   public function insert(array $d): int
   {
      $pdo = Db::pdo();
      $sql = "INSERT INTO sede_tramite
                (sede_id, tramite_id, cupo_por_slot, slot_min, ventana_dias, activo)
                VALUES (:sede_id, :tramite_id, :cupo_por_slot, :slot_min, :ventana_dias, :activo)";
      $st = $pdo->prepare($sql);
      $st->execute([
         'sede_id' => (int)$d['sede_id'],
         'tramite_id' => (int)$d['tramite_id'],
         'cupo_por_slot' => (int)$d['cupo_por_slot'],
         'slot_min' => (int)$d['slot_min'],
         'ventana_dias' => (int)$d['ventana_dias'],
         'activo' => (int)$d['activo'],
      ]);
      return (int)$pdo->lastInsertId();
   }

   public function update(int $id, array $b): void
   {
      $pdo = Db::pdo();
      $fields = [];
      $params = ['id' => $id];

      foreach (['cupo_por_slot', 'slot_min', 'ventana_dias', 'activo'] as $k) {
         if (!array_key_exists($k, $b)) continue;
         $fields[] = "$k=:$k";
         $params[$k] = (int)$b[$k];
      }

      if (!$fields) return;

      $sql = "UPDATE sede_tramite SET " . implode(',', $fields) . " WHERE id=:id";
      $st = $pdo->prepare($sql);
      $st->execute($params);
   }

   public function softDelete(int $id): void
   {
      $pdo = Db::pdo();
      $pdo->prepare("UPDATE sede_tramite SET activo=0 WHERE id=:id")->execute(['id' => $id]);
   }
}
