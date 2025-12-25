<?php

declare(strict_types=1);

namespace App\Repositories\Admin;

use App\Support\Database\Db;

require_once APP_ROOT . '/app/Support/Database/Db.php';

final class AgendaRepository
{
   public function list(array $f): array
   {
      $pdo = Db::pdo();

      $where = [];
      $params = [];

      // Requeridos
      $where[] = "c.fecha = :fecha";
      $params['fecha'] = $f['fecha'];

      $where[] = "c.sede_id = :sede_id";
      $params['sede_id'] = (int)$f['sede_id'];

      // Opcionales
      if (!empty($f['estado'])) {
         $where[] = "c.estado = :estado";
         $params['estado'] = $f['estado'];
      }

      if (!empty($f['q'])) {
         $where[] = "(c.folio_publico LIKE :q1 OR c.nombre LIKE :q2 OR c.curp_rfc LIKE :q3 OR c.email LIKE :q4)";
         $like = '%' . $f['q'] . '%';
         $params['q1'] = $like;
         $params['q2'] = $like;
         $params['q3'] = $like;
         $params['q4'] = $like;
      }

      $sql = "SELECT
                 c.id,
                 c.fecha,
                 c.hora_inicio,
                 c.hora_fin,
                 c.estado,
                 c.folio_publico,
                 c.reprogramada_a_folio,
                 c.nombre,
                 c.curp_rfc,
                 c.email,
                 c.telefono,
                 t.nombre AS tramite_nombre,
                 s.nombre AS sede_nombre
              FROM cita c
              INNER JOIN tramite t ON t.id = c.tramite_id
              INNER JOIN sede s ON s.id = c.sede_id
              WHERE " . implode(' AND ', $where) . "
              ORDER BY c.hora_inicio ASC, c.id ASC";

      $st = $pdo->prepare($sql);
      $st->execute($params);

      $rows = $st->fetchAll() ?: [];
      foreach ($rows as &$r) {
         $r['hora'] = substr((string)$r['hora_inicio'], 0, 5);
         unset($r['hora_inicio'], $r['hora_fin']);
      }
      return $rows;
   }

   public function findById(int $id): ?array
   {
      $pdo = Db::pdo();

      $sql = "SELECT
                 c.*,
                 t.nombre AS tramite_nombre,
                 s.nombre AS sede_nombre
              FROM cita c
              INNER JOIN tramite t ON t.id = c.tramite_id
              INNER JOIN sede s ON s.id = c.sede_id
              WHERE c.id = :id
              LIMIT 1";
      $st = $pdo->prepare($sql);
      $st->execute(['id' => $id]);
      $row = $st->fetch();
      if (!$row) return null;

      $row['hora'] = substr((string)$row['hora_inicio'], 0, 5);
      $row['hora_fin_hhmm'] = substr((string)$row['hora_fin'], 0, 5);

      return $row;
   }

   public function findForUpdate(int $id): ?array
   {
      $pdo = Db::pdo();
      $sql = "SELECT * FROM cita WHERE id=:id LIMIT 1 FOR UPDATE";
      $st = $pdo->prepare($sql);
      $st->execute(['id' => $id]);
      $row = $st->fetch();
      return $row ?: null;
   }

   public function updateEstado(int $id, string $estado): void
   {
      $pdo = Db::pdo();
      $sql = "UPDATE cita SET estado=:estado WHERE id=:id";
      $st = $pdo->prepare($sql);
      $st->execute(['id' => $id, 'estado' => $estado]);
   }

   public function listEventos(int $citaId): array
   {
      $pdo = Db::pdo();
      $sql = "SELECT id, tipo, ip, user_agent, payload, creado_en
              FROM cita_evento
              WHERE cita_id = :cita_id
              ORDER BY id DESC";
      $st = $pdo->prepare($sql);
      $st->execute(['cita_id' => $citaId]);
      return $st->fetchAll() ?: [];
   }

   public function listHoy(int $sedeId): array
   {
      $pdo = Db::pdo();

      $sql = "SELECT
              c.id,
              c.fecha,
              c.hora_inicio,
              c.estado,
              c.folio_publico,
              c.folio_num,
              c.nombre,
              c.curp_rfc,
              c.tramite_id,
              t.nombre AS tramite_nombre
           FROM cita c
           INNER JOIN tramite t ON t.id = c.tramite_id
           WHERE c.sede_id = :sede_id
             AND c.fecha = CURDATE()
             AND c.estado IN ('confirmada','en_atencion')  -- los que están “en fila”
           ORDER BY c.hora_inicio ASC, c.folio_num ASC, c.id ASC";

      $st = $pdo->prepare($sql);
      $st->execute(['sede_id' => $sedeId]);
      $rows = $st->fetchAll() ?: [];

      foreach ($rows as &$r) {
         $r['hora'] = substr((string)$r['hora_inicio'], 0, 5);
         unset($r['hora_inicio']);
      }
      return $rows;
   }
}
