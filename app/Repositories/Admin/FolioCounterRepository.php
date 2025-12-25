<?php

declare(strict_types=1);

namespace App\Repositories\Admin;

use App\Support\Database\Db;

require_once APP_ROOT . '/app/Support/Database/Db.php';

final class FolioCounterRepository
{
   public function nextForSede(int $sedeId): int
   {
      $pdo = Db::pdo();

      // Bloquea el contador de esa sede
      $sql = "SELECT last_number FROM sede_folio_counter WHERE sede_id=:sede_id FOR UPDATE";
      $st = $pdo->prepare($sql);
      $st->execute(['sede_id' => $sedeId]);
      $last = $st->fetchColumn();

      if ($last === false) {
         // Si no existe, lo creamos en 0 (bloqueado por la transacción)
         $pdo->prepare("INSERT INTO sede_folio_counter (sede_id, last_number) VALUES (:sede_id, 0)")
            ->execute(['sede_id' => $sedeId]);
         $last = 0;
      }

      $next = ((int)$last) + 1;

      $pdo->prepare("UPDATE sede_folio_counter SET last_number=:n WHERE sede_id=:sede_id")
         ->execute(['n' => $next, 'sede_id' => $sedeId]);

      return $next;
   }
}
