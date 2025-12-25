<?php

declare(strict_types=1);

namespace App\Controllers\Api\Admin;

use App\Http\Router\Request;
use App\Http\Responses\Response;
use App\Support\Database\Db;

require_once APP_ROOT . '/app/Http/Responses/Response.php';
require_once APP_ROOT . '/app/Support/Database/Db.php';

final class AdminCatalogController
{
   public function sedes(Request $req): void
   {
      $pdo = Db::pdo();
      $sql = "SELECT id, nombre
              FROM sede
              WHERE activo = 1
              ORDER BY nombre ASC";
      $rows = $pdo->query($sql)->fetchAll() ?: [];
      Response::json(true, $rows, null, 200);
   }
}
