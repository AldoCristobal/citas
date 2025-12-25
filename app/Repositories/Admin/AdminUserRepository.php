<?php

declare(strict_types=1);

namespace App\Repositories\Admin;

use App\Support\Database\Db;

require_once APP_ROOT . '/app/Support/Database/Db.php';

final class AdminUserRepository
{
   public function findByEmail(string $email): ?array
   {
      $pdo = Db::pdo();
      $sql = "SELECT id, nombre, email, password_hash
                FROM admin_usuario
                WHERE email = :email AND activo = 1
                LIMIT 1";
      $st = $pdo->prepare($sql);
      $st->execute(['email' => $email]);
      $row = $st->fetch();
      return $row ?: null;
   }
}
