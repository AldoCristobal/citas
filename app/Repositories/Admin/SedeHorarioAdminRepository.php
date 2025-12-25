<?php

declare(strict_types=1);

namespace App\Repositories\Admin;

use App\Support\Database\Db;

require_once APP_ROOT . '/app/Support/Database/Db.php';

final class SedeHorarioAdminRepository
{
   public function listBySede(int $sedeId): array
   {
      $pdo = Db::pdo();
      $sql = "SELECT id, sede_id, dow, orden, abre, cierra, activo
                FROM sede_horario
                WHERE sede_id = :sede_id
                ORDER BY dow ASC, orden ASC";
      $st = $pdo->prepare($sql);
      $st->execute(['sede_id' => $sedeId]);
      return $st->fetchAll() ?: [];
   }

   public function insert(array $d): int
   {
      $pdo = Db::pdo();
      $sql = "INSERT INTO sede_horario (sede_id, dow, orden, abre, cierra, activo)
                VALUES (:sede_id, :dow, :orden, :abre, :cierra, :activo)";
      $st = $pdo->prepare($sql);
      $st->execute([
         'sede_id' => (int)$d['sede_id'],
         'dow' => (int)$d['dow'],
         'orden' => (int)$d['orden'],
         'abre' => $d['abre'],
         'cierra' => $d['cierra'],
         'activo' => (int)$d['activo'],
      ]);
      return (int)$pdo->lastInsertId();
   }

   public function update(int $id, array $b): void
   {
      $pdo = Db::pdo();
      $fields = [];
      $params = ['id' => $id];

      foreach (['dow', 'orden', 'abre', 'cierra', 'activo'] as $k) {
         if (!array_key_exists($k, $b)) continue;
         $fields[] = "$k=:$k";
         $params[$k] = $b[$k];
      }
      if (!$fields) return;

      $sql = "UPDATE sede_horario SET " . implode(',', $fields) . " WHERE id=:id";
      $st = $pdo->prepare($sql);
      $st->execute($params);
   }

   public function softDelete(int $id): void
   {
      $pdo = Db::pdo();
      $pdo->prepare("UPDATE sede_horario SET activo=0 WHERE id=:id")->execute(['id' => $id]);
   }
}
