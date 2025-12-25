<?php

declare(strict_types=1);

namespace App\Repositories\Catalog;

use App\Support\Database\Db;

require_once APP_ROOT . '/app/Support/Database/Db.php';

final class FeriadoRepository
{
   public function isFeriado(string $fecha, int $sedeId): bool
   {
      $pdo = Db::pdo();
      $sql = "SELECT 1
                FROM feriado
                WHERE fecha = :fecha
                  AND (sede_id IS NULL OR sede_id = :sede_id)
                LIMIT 1";
      $st = $pdo->prepare($sql);
      $st->execute(['fecha' => $fecha, 'sede_id' => $sedeId]);
      return (bool)$st->fetchColumn();
   }
}
