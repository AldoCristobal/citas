<?php
// app/Controllers/Api/Public/CatalogController.php
declare(strict_types=1);

namespace App\Controllers\Api\Public;

use App\Http\Responses\Response;
use App\Repositories\Catalog\TramiteRepository;
use App\Repositories\Catalog\SedeRepository;

require_once APP_ROOT . '/app/Repositories/Catalog/TramiteRepository.php';
require_once APP_ROOT . '/app/Repositories/Catalog/SedeRepository.php';

final class CatalogController
{
   public function tramites(): void
   {
      $sedeId = isset($_GET['sede_id']) ? (int)$_GET['sede_id'] : 0;

      $repo = new TramiteRepository();

      // ✅ sede_id opcional:
      // - si viene: filtra por sede (sede_tramite)
      // - si no: regresa todos los trámites activos
      if ($sedeId > 0) {
         Response::json(true, $repo->listBySede($sedeId));
         return;
      }

      Response::json(true, $repo->listActivos());
   }

   public function sedes(): void
   {
      $tramiteId = isset($_GET['tramite_id']) ? (int)$_GET['tramite_id'] : 0;

      $repo = new SedeRepository();

      // ✅ tramite_id opcional:
      // - si viene: filtra por trámite
      // - si no: regresa todas las sedes activas
      if ($tramiteId > 0) {
         Response::json(true, $repo->listByTramite($tramiteId));
         return;
      }

      Response::json(true, $repo->listActivas());
   }
}
