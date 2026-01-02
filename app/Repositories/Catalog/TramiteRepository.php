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

      $sql = "SELECT id, nombre, activo, duracion_min
           FROM tramite
           WHERE id = :id
           LIMIT 1";

      $st = $pdo->prepare($sql);
      $st->execute(['id' => $id]);

      $row = $st->fetch();
      return $row ?: null;
   }


   public function listBySede(int $sedeId): array
   {
      $pdo = \App\Support\Database\Db::pdo();

      $sql = "SELECT t.id, t.nombre
           FROM tramite t
           INNER JOIN sede_tramite st ON st.tramite_id = t.id
           WHERE st.sede_id = :sede_id
             AND st.activo = 1
             AND t.activo = 1
           ORDER BY t.nombre ASC";

      $st = $pdo->prepare($sql);
      $st->execute(['sede_id' => $sedeId]);

      return $st->fetchAll() ?: [];
   }
}
