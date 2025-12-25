<?php

declare(strict_types=1);

namespace App\Repositories\Catalog;

use App\Support\Database\Db;

require_once APP_ROOT . '/app/Support/Database/Db.php';

final class SedeTramiteRepository
{
   public function getActive(int $sedeId, int $tramiteId): ?array
   {
      $pdo = Db::pdo();
      $sql = "SELECT id, sede_id, tramite_id, cupo_por_slot, slot_min, ventana_dias
                FROM sede_tramite
                WHERE sede_id = :sede_id
                  AND tramite_id = :tramite_id
                  AND activo = 1
                LIMIT 1";
      $st = $pdo->prepare($sql);
      $st->execute(['sede_id' => $sedeId, 'tramite_id' => $tramiteId]);
      $row = $st->fetch();
      return $row ?: null;
   }
}
