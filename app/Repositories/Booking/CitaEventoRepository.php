<?php

declare(strict_types=1);

namespace App\Repositories\Booking;

use App\Support\Database\Db;
use App\Http\Router\Request;

require_once APP_ROOT . '/app/Support/Database/Db.php';

final class CitaEventoRepository
{
   public function insert(int $citaId, string $tipo, Request $req): void
   {
      $pdo = Db::pdo();

      $ip = $_SERVER['REMOTE_ADDR'] ?? null;
      $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

      $payload = [
         'path' => $req->path,
         'method' => $req->method,
         'query' => $req->query,
      ];

      $sql = "INSERT INTO cita_evento (cita_id, tipo, ip, user_agent, payload)
                VALUES (:cita_id, :tipo, :ip, :ua, :payload)";
      $st = $pdo->prepare($sql);
      $st->execute([
         'cita_id' => $citaId,
         'tipo' => $tipo,
         'ip' => $ip,
         'ua' => $ua,
         'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
      ]);
   }
}
