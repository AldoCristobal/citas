<?php

declare(strict_types=1);

namespace App\Repositories\Admin;

use App\Support\Database\Db;

require_once APP_ROOT . '/app/Support/Database/Db.php';

final class TramiteAdminRepository
{
   public function list(): array
   {
      $pdo = Db::pdo();
      return $pdo->query("SELECT id, nombre, descripcion, duracion_min, activo
                        FROM tramite
                        ORDER BY id DESC")->fetchAll();
   }

   public function insert(array $d): int
   {
      $pdo = Db::pdo();
      $sql = "INSERT INTO tramite (nombre, descripcion, duracion_min, activo)
            VALUES (:nombre, :descripcion, :duracion_min, :activo)";
      $st = $pdo->prepare($sql);
      $st->execute([
         'nombre' => $d['nombre'],
         'descripcion' => $d['descripcion'], // null permitido
         'duracion_min' => (int)$d['duracion_min'],
         'activo' => (int)$d['activo'],
      ]);
      return (int)$pdo->lastInsertId();
   }

   public function update(int $id, array $b): void
   {
      $pdo = Db::pdo();
      $fields = [];
      $params = ['id' => $id];

      foreach (['nombre', 'descripcion', 'duracion_min', 'activo'] as $k) {
         if (!array_key_exists($k, $b)) continue;

         $fields[] = "$k=:$k";

         $v = $b[$k];
         if ($k === 'nombre') $v = trim((string)$v);
         if ($k === 'descripcion') {
            $v = is_string($v) ? trim($v) : '';
            $v = $v === '' ? null : $v;
         }
         if ($k === 'duracion_min') $v = (int)$v;
         if ($k === 'activo') $v = (int)$v ? 1 : 0;

         $params[$k] = $v;
      }

      if (!$fields) return;

      $sql = "UPDATE tramite SET " . implode(',', $fields) . " WHERE id=:id";
      $st = $pdo->prepare($sql);
      $st->execute($params);
   }

   public function softDelete(int $id): void
   {
      $pdo = Db::pdo();
      $pdo->prepare("UPDATE tramite SET activo=0 WHERE id=:id")->execute(['id' => $id]);
   }
}
