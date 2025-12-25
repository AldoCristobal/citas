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
      $repo = new TramiteRepository();
      Response::json(true, $repo->listActivos());
   }

   public function sedes(): void
   {
      $tramiteId = isset($_GET['tramite_id']) ? (int)$_GET['tramite_id'] : 0;
      if ($tramiteId <= 0) {
         Response::badRequest('tramite_id requerido');
         return;
      }

      $repo = new SedeRepository();
      Response::json(true, $repo->listByTramite($tramiteId));
   }
}
