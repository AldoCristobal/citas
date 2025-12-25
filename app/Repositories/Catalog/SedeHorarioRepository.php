<?php

declare(strict_types=1);

namespace App\Repositories\Catalog;

use App\Support\Database\Db;

require_once APP_ROOT . '/app/Support/Database/Db.php';

final class SedeHorarioRepository
{
   public function listByDay(int $sedeId, int $diaSemana): array
   {
      $pdo = Db::pdo();
      $sql = "SELECT hora_inicio, hora_fin
                FROM sede_horario
                WHERE sede_id = :sede_id
                  AND dia_semana = :dia
                  AND activo = 1
                ORDER BY hora_inicio ASC";
      $st = $pdo->prepare($sql);
      $st->execute(['sede_id' => $sedeId, 'dia' => $diaSemana]);
      return $st->fetchAll();
   }
}
