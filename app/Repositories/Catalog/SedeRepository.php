<?php

declare(strict_types=1);

namespace App\Repositories\Catalog;

use App\Support\Database\Db;

require_once APP_ROOT . '/app/Support/Database/Db.php';

final class SedeRepository
{
   public function listByTramite(int $tramiteId): array
   {
      $pdo = Db::pdo();
      $sql = "SELECT s.id, s.nombre, s.ciudad, s.direccion, s.telefono, s.prefijo_folio
                FROM sede s
                INNER JOIN sede_tramite st ON st.sede_id = s.id
                WHERE s.activo = 1
                  AND st.activo = 1
                  AND st.tramite_id = :tramite_id
                ORDER BY s.nombre ASC";
      $stmt = $pdo->prepare($sql);
      $stmt->execute(['tramite_id' => $tramiteId]);
      return $stmt->fetchAll();
   }

   public function getById(int $id): ?array
   {
      $pdo = \App\Support\Database\Db::pdo();
      $sql = "SELECT id, nombre, prefijo_folio FROM sede WHERE id=:id LIMIT 1";
      $st = $pdo->prepare($sql);
      $st->execute(['id' => $id]);
      $row = $st->fetch();
      return $row ?: null;
   }

   public function listActivas(): array
   {
      $pdo = Db::pdo();

      $sql = "SELECT id, nombre
           FROM sede
           WHERE activo = 1
           ORDER BY nombre ASC";

      $st = $pdo->prepare($sql);
      $st->execute();

      return $st->fetchAll() ?: [];
   }
}
