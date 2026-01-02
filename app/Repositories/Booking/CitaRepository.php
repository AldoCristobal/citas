<?php

declare(strict_types=1);

namespace App\Repositories\Booking;

use App\Support\Database\Db;

require_once APP_ROOT . '/app/Support/Database/Db.php';

final class CitaRepository
{
   private function t(string $hhmm): string
   {
      // Normaliza 'HH:MM' => 'HH:MM:SS' para comparar TIME en SQL
      return preg_match('/^\d{2}:\d{2}:\d{2}$/', $hhmm) ? $hhmm : ($hhmm . ':00');
   }

   // Conteo con bloqueo "ligero" por slot exacto (hora_inicio)
   public function countOccupiedForUpdate(int $sedeId, int $tramiteId, string $fecha, string $hora): int
   {
      $pdo = Db::pdo();

      $sql = "SELECT id
                FROM cita
                WHERE sede_id = :sede_id
                  AND tramite_id = :tramite_id
                  AND fecha = :fecha
                  AND hora_inicio = :hora
                  AND (
                        estado = 'confirmada'
                        OR (estado = 'hold' AND expires_at IS NOT NULL AND expires_at > NOW())
                      )
                FOR UPDATE";

      $st = $pdo->prepare($sql);
      $st->execute([
         'sede_id' => $sedeId,
         'tramite_id' => $tramiteId,
         'fecha' => $fecha,
         'hora' => $this->t($hora),
      ]);

      return count($st->fetchAll() ?: []);
   }

   public function insertHold(array $d): int
   {
      $pdo = Db::pdo();

      $sql = "INSERT INTO cita
                (sede_id, tramite_id, fecha, hora_inicio, hora_fin, estado, hold_token, expires_at)
                VALUES
                (:sede_id, :tramite_id, :fecha, :hora_inicio, :hora_fin, 'hold', :hold_token, :expires_at)";

      $st = $pdo->prepare($sql);
      $st->execute([
         'sede_id' => $d['sede_id'],
         'tramite_id' => $d['tramite_id'],
         'fecha' => $d['fecha'],
         'hora_inicio' => $this->t($d['hora_inicio']),
         'hora_fin' => $this->t($d['hora_fin']),
         'hold_token' => $d['hold_token'],
         'expires_at' => $d['expires_at'],
      ]);

      return (int)$pdo->lastInsertId();
   }

   public function findHoldForUpdateByToken(string $holdToken): ?array
   {
      $pdo = Db::pdo();

      $sql = "SELECT *
                FROM cita
                WHERE hold_token = :hold_token
                  AND estado = 'hold'
                LIMIT 1
                FOR UPDATE";
      $st = $pdo->prepare($sql);
      $st->execute(['hold_token' => $holdToken]);
      $row = $st->fetch();
      return $row ?: null;
   }

   public function markExpired(int $citaId): void
   {
      $pdo = Db::pdo();
      $sql = "UPDATE cita SET estado='expirada' WHERE id=:id AND estado='hold'";
      $st = $pdo->prepare($sql);
      $st->execute(['id' => $citaId]);
   }

   /**
    * ✅ Confirma hold y guarda datos del ciudadano.
    * Requiere que existan las columnas nuevas en la tabla `cita`.
    */
   public function confirmHold(int $citaId, array $d): void
   {
      $pdo = Db::pdo();

      $sql = "UPDATE cita
                SET estado='confirmada',

                    nombre=:nombre,
                    apellido_paterno=:apellido_paterno,
                    apellido_materno=:apellido_materno,

                    curp_rfc=:curp_rfc,
                    email=:email,
                    telefono=:telefono,

                    calle=:calle,
                    numero_exterior=:numero_exterior,
                    numero_interior=:numero_interior,
                    colonia=:colonia,
                    codigo_postal=:codigo_postal,
                    estado_dir=:estado_dir,
                    municipio=:municipio,

                    fecha_nacimiento=:fecha_nacimiento,
                    edad=:edad,

                    folio_num=:folio_num,
                    folio_publico=:folio_publico,
                    access_token=:access_token,

                    hold_token=NULL,
                    expires_at=NULL
                WHERE id=:id AND estado='hold'";

      $st = $pdo->prepare($sql);
      $st->execute([
         'id' => $citaId,

         'nombre' => $d['nombre'] ?? null,
         'apellido_paterno' => $d['apellido_paterno'] ?? null,
         'apellido_materno' => $d['apellido_materno'] ?? null,

         'curp_rfc' => $d['curp_rfc'] ?? null,
         'email' => $d['email'] ?? null,
         'telefono' => $d['telefono'] ?? null,

         'calle' => $d['calle'] ?? null,
         'numero_exterior' => $d['numero_exterior'] ?? null,
         'numero_interior' => $d['numero_interior'] ?? null,
         'colonia' => $d['colonia'] ?? null,
         'codigo_postal' => $d['codigo_postal'] ?? null,
         'estado_dir' => $d['estado'] ?? null,    // 👈 OJO: columna sugerida: estado_dir (evita choque con enum estado)
         'municipio' => $d['municipio'] ?? null,

         'fecha_nacimiento' => $d['fecha_nacimiento'] ?? null, // YYYY-MM-DD
         'edad' => $d['edad'] ?? null,

         'folio_num' => $d['folio_num'],
         'folio_publico' => $d['folio_publico'],
         'access_token' => $d['access_token'],
      ]);
   }

   public function countOccupied(int $sedeId, int $tramiteId, string $fecha, string $hora): int
   {
      $pdo = Db::pdo();

      $sql = "SELECT COUNT(*)
                FROM cita
                WHERE sede_id = :sede_id
                  AND tramite_id = :tramite_id
                  AND fecha = :fecha
                  AND hora_inicio = :hora
                  AND (
                        estado = 'confirmada'
                        OR (estado = 'hold' AND expires_at IS NOT NULL AND expires_at > NOW())
                      )";

      $st = $pdo->prepare($sql);
      $st->execute([
         'sede_id' => $sedeId,
         'tramite_id' => $tramiteId,
         'fecha' => $fecha,
         'hora' => $this->t($hora),
      ]);

      return (int)$st->fetchColumn();
   }

   public function findPublicByFolioAndToken(string $folio, string $token): ?array
   {
      $pdo = Db::pdo();
      $sql = "SELECT id, folio_publico AS folio, fecha, hora_inicio, estado
            FROM cita
            WHERE folio_publico = :folio
              AND access_token = :token
            LIMIT 1";
      $st = $pdo->prepare($sql);
      $st->execute(['folio' => $folio, 'token' => $token]);
      $row = $st->fetch();
      if (!$row) return null;

      $row['hora'] = substr((string)$row['hora_inicio'], 0, 5);
      unset($row['hora_inicio']);

      return $row;
   }

   public function findForUpdateByFolioAndToken(string $folio, string $token): ?array
   {
      $pdo = Db::pdo();
      $sql = "SELECT id, estado
            FROM cita
            WHERE folio_publico = :folio
              AND access_token = :token
            LIMIT 1
            FOR UPDATE";
      $st = $pdo->prepare($sql);
      $st->execute(['folio' => $folio, 'token' => $token]);
      $row = $st->fetch();
      return $row ?: null;
   }

   public function cancel(int $id): void
   {
      $pdo = Db::pdo();
      $sql = "UPDATE cita
            SET estado='cancelada'
            WHERE id=:id AND estado='confirmada'";
      $st = $pdo->prepare($sql);
      $st->execute(['id' => $id]);
   }

   public function markReprogramada(int $idAnterior, string $nuevoFolio): void
   {
      $pdo = Db::pdo();
      $sql = "UPDATE cita
            SET estado='reprogramada',
                reprogramada_a_folio=:nuevo
            WHERE id=:id AND estado='confirmada'";
      $st = $pdo->prepare($sql);
      $st->execute(['id' => $idAnterior, 'nuevo' => $nuevoFolio]);
   }

   public function countOverlaps(
      int $sedeId,
      int $tramiteId,
      string $fecha,
      string $horaInicio,
      string $horaFin,
      int $excludeId
   ): int {
      $pdo = Db::pdo();

      $sql = "SELECT COUNT(*)
            FROM cita
            WHERE sede_id = :sede_id
              AND tramite_id = :tramite_id
              AND fecha = :fecha
              AND id <> :id
              AND (
                estado IN ('confirmada','reprogramada','en_atencion')
                OR (estado='hold' AND expires_at IS NOT NULL AND expires_at > NOW())
              )
              AND hora_inicio < :hora_fin
              AND hora_fin > :hora_inicio";

      $st = $pdo->prepare($sql);
      $st->execute([
         'sede_id' => $sedeId,
         'tramite_id' => $tramiteId,
         'fecha' => $fecha,
         'id' => $excludeId,
         'hora_inicio' => $this->t($horaInicio),
         'hora_fin' => $this->t($horaFin),
      ]);

      return (int)$st->fetchColumn();
   }

   public function expireHolds(): int
   {
      $pdo = Db::pdo();
      $sql = "UPDATE cita
            SET estado='expirada'
            WHERE estado='hold'
              AND expires_at IS NOT NULL
              AND expires_at <= NOW()";
      $st = $pdo->prepare($sql);
      $st->execute();
      return (int)$st->rowCount();
   }

   public function getSedeTramiteConfig(int $sedeId, int $tramiteId): ?array
   {
      $pdo = Db::pdo();
      $st = $pdo->prepare("SELECT cupo_por_slot, slot_min, ventana_dias, activo
                           FROM sede_tramite
                           WHERE sede_id=:sede_id AND tramite_id=:tramite_id
                           LIMIT 1");
      $st->execute(['sede_id' => $sedeId, 'tramite_id' => $tramiteId]);
      $row = $st->fetch();
      return $row ?: null;
   }
}
