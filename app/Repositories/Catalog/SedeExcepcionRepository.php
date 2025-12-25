<?php

declare(strict_types=1);

namespace App\Repositories\Catalog;

use App\Support\Database\Db;

require_once APP_ROOT . '/app/Support/Database/Db.php';

final class SedeExcepcionRepository
{
   public function getByDate(int $sedeId, string $fecha): ?array
   {
      $pdo = Db::pdo();
      $sql = "SELECT sede_id, fecha, cerrado, hora_inicio, hora_fin, cupo_por_slot
                FROM sede_excepcion
                WHERE sede_id = :sede_id AND fecha = :fecha
                LIMIT 1";
      $st = $pdo->prepare($sql);
      $st->execute(['sede_id' => $sedeId, 'fecha' => $fecha]);
      $row = $st->fetch();
      return $row ?: null;
   }
}
