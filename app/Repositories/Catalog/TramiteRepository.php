<?php

declare(strict_types=1);

namespace App\Repositories\Catalog;

use App\Support\Database\Db;

require_once APP_ROOT . '/app/Support/Database/Db.php';

final class TramiteRepository
{
   public function listActivos(): array
   {
      $pdo = Db::pdo();
      $sql = "SELECT id, nombre, duracion_min
                FROM tramite
                WHERE activo = 1
                ORDER BY nombre ASC";
      return $pdo->query($sql)->fetchAll();
   }

   public function getById(int $id): ?array
   {
      $pdo = \App\Support\Database\Db::pdo();
      $sql = "SELECT id, nombre FROM tramite WHERE id=:id LIMIT 1";
      $st = $pdo->prepare($sql);
      $st->execute(['id' => $id]);
      $row = $st->fetch();
      return $row ?: null;
   }
}
