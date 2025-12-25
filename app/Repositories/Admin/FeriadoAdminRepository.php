<?php

declare(strict_types=1);

namespace App\Repositories\Admin;

use App\Support\Database\Db;

require_once APP_ROOT . '/app/Support/Database/Db.php';

final class FeriadoAdminRepository
{
   public function list(array $f): array
   {
      $pdo = Db::pdo();

      $where = ["1=1"];
      $params = [];

      // filtro opcional por mes YYYY-MM
      if (!empty($f['mes']) && preg_match('/^\d{4}-\d{2}$/', (string)$f['mes'])) {
         $where[] = "DATE_FORMAT(fecha, '%Y-%m') = :mes";
         $params['mes'] = (string)$f['mes'];
      }

      // filtro opcional por sede_id (incluye globales)
      if (!empty($f['sede_id'])) {
         $where[] = "(sede_id IS NULL OR sede_id = :sede_id)";
         $params['sede_id'] = (int)$f['sede_id'];
      }

      $sql = "SELECT
                  f.id, f.fecha, f.descripcion, f.sede_id, f.activo,
                  s.nombre AS sede_nombre
                FROM feriado f
                LEFT JOIN sede s ON s.id = f.sede_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY f.fecha ASC, f.sede_id ASC";

      $st = $pdo->prepare($sql);
      $st->execute($params);
      return $st->fetchAll() ?: [];
   }

   public function insert(array $d): int
   {
      $pdo = Db::pdo();
      $sql = "INSERT INTO feriado (fecha, descripcion, sede_id, activo)
                VALUES (:fecha, :descripcion, :sede_id, :activo)";
      $st = $pdo->prepare($sql);
      $st->execute([
         'fecha' => $d['fecha'],
         'descripcion' => $d['descripcion'] ?? null,
         'sede_id' => $d['sede_id'] ?? null,
         'activo' => (int)($d['activo'] ?? 1),
      ]);
      return (int)$pdo->lastInsertId();
   }

   public function update(int $id, array $b): void
   {
      $pdo = Db::pdo();
      $fields = [];
      $params = ['id' => $id];

      foreach (['fecha', 'descripcion', 'sede_id', 'activo'] as $k) {
         if (!array_key_exists($k, $b)) continue;
         $fields[] = "$k=:$k";
         $params[$k] = $b[$k];
      }
      if (!$fields) return;

      $sql = "UPDATE feriado SET " . implode(',', $fields) . " WHERE id=:id";
      $st = $pdo->prepare($sql);
      $st->execute($params);
   }

   public function softDelete(int $id): void
   {
      $pdo = Db::pdo();
      $pdo->prepare("UPDATE feriado SET activo=0 WHERE id=:id")->execute(['id' => $id]);
   }
}
