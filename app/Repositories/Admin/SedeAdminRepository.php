<?php

declare(strict_types=1);

namespace App\Repositories\Admin;

use App\Support\Database\Db;

require_once APP_ROOT . '/app/Support/Database/Db.php';

final class SedeAdminRepository
{
   public function list(): array
   {
      $pdo = Db::pdo();
      return $pdo->query("SELECT id, nombre, prefijo_folio, ciudad, direccion, telefono, activo
                            FROM sede ORDER BY id DESC")->fetchAll();
   }

   public function insert(array $d): int
   {
      $pdo = Db::pdo();
      $sql = "INSERT INTO sede (nombre, prefijo_folio, ciudad, direccion, telefono, activo)
                VALUES (:nombre,:prefijo,:ciudad,:direccion,:telefono,1)";
      $st = $pdo->prepare($sql);
      $st->execute([
         'nombre' => $d['nombre'],
         'prefijo' => $d['prefijo_folio'],
         'ciudad' => $d['ciudad'] ?? null,
         'direccion' => $d['direccion'] ?? null,
         'telefono' => $d['telefono'] ?? null,
      ]);
      return (int)$pdo->lastInsertId();
   }

   public function update(int $id, array $b): void
   {
      $pdo = Db::pdo();

      // updates parciales
      $fields = [];
      $params = ['id' => $id];

      foreach (['nombre', 'prefijo_folio', 'ciudad', 'direccion', 'telefono', 'activo'] as $k) {
         if (array_key_exists($k, $b)) {
            $fields[] = "$k=:$k";
            $params[$k] = $b[$k];
         }
      }
      if (!$fields) return;

      $sql = "UPDATE sede SET " . implode(',', $fields) . " WHERE id=:id";
      $st = $pdo->prepare($sql);
      $st->execute($params);
   }

   public function softDelete(int $id): void
   {
      $pdo = Db::pdo();
      $pdo->prepare("UPDATE sede SET activo=0 WHERE id=:id")->execute(['id' => $id]);
   }
   
}
