<?php
// routes/api.php
declare(strict_types=1);

use App\Http\Router\Router;
use App\Http\Router\Request;
use App\Support\Security\Csrf;
use App\Support\Security\CsrfGuard;
use App\Http\Responses\Response;
use App\Support\Auth\AdminGuard;
use App\Controllers\Api\Admin\SedeHorarioController;

// Controllers Public
require_once APP_ROOT . '/app/Controllers/Api/Public/CatalogController.php';
require_once APP_ROOT . '/app/Controllers/Api/Public/AvailabilityController.php';
require_once APP_ROOT . '/app/Controllers/Api/Public/HoldController.php';
require_once APP_ROOT . '/app/Controllers/Api/Public/BookingController.php';
require_once APP_ROOT . '/app/Controllers/Api/Public/PublicCitaController.php';
require_once APP_ROOT . '/app/Controllers/Api/Public/ReagendaController.php';

// Controllers Admin
require_once APP_ROOT . '/app/Controllers/Api/Admin/AuthController.php';
require_once APP_ROOT . '/app/Controllers/Api/Admin/SedeController.php';
require_once APP_ROOT . '/app/Controllers/Api/Admin/TramiteController.php';
require_once APP_ROOT . '/app/Controllers/Api/Admin/SedeTramiteController.php';
require_once APP_ROOT . '/app/Controllers/Api/Admin/SedeHorarioController.php';
require_once APP_ROOT . '/app/Controllers/Api/Admin/FeriadoController.php';
require_once APP_ROOT . '/app/Controllers/Api/Admin/AgendaController.php';
require_once APP_ROOT . '/app/Controllers/Api/Admin/AdminCatalogController.php';

// ✅ Asegura includes de Csrf/AdminGuard/CsrfGuard si no tienes autoload composer
require_once APP_ROOT . '/app/Support/Security/Csrf.php';
require_once APP_ROOT . '/app/Support/Security/CsrfGuard.php';
require_once APP_ROOT . '/app/Support/Auth/AdminGuard.php';

$admin = function (callable $handler) {
   return function (Request $req) use ($handler) {
      AdminGuard::require();
      CsrfGuard::requireForMutation($_SERVER['REQUEST_METHOD'] ?? 'GET');
      $handler($req);
   };
};


$router->group('/api/v1', function (Router $r) {

   $r->get('/health', function (Request $req) {
      Response::json(true, ['status' => 'ok', 'ts' => date('c')]);
   });

   $r->group('/public', function (Router $r) {
      $r->get('/tramites', function (Request $req) {
         (new App\Controllers\Api\Public\CatalogController())->tramites();
      });

      $r->get('/sedes', function (Request $req) {
         (new App\Controllers\Api\Public\CatalogController())->sedes();
      });

      $r->get('/disponibilidad', function (Request $req) {
         (new App\Controllers\Api\Public\AvailabilityController())->disponibilidad($req);
      });

      $r->get('/slots', function (Request $req) {
         (new App\Controllers\Api\Public\AvailabilityController())->slots($req);
      });

      $r->post('/holds', function (Request $req) {
         (new App\Controllers\Api\Public\HoldController())->create($req);
      });

      $r->post('/citas/confirmar', function (Request $req) {
         (new App\Controllers\Api\Public\BookingController())->confirm($req);
      });

      $r->get('/citas/{folio}', function (Request $req) {
         (new App\Controllers\Api\Public\PublicCitaController())->show($req);
      });

      $r->post('/citas/{folio}/cancelar', function (Request $req) {
         (new App\Controllers\Api\Public\PublicCitaController())->cancel($req);
      });

      $r->post('/citas/{folio}/reagendar/hold', function (Request $req) {
         (new App\Controllers\Api\Public\ReagendaController())->createHold($req);
      });

      $r->get('/availability/month', function (Request $req) {
         (new App\Controllers\Api\Public\AvailabilityController())->month($req);
      });

      $r->get('/availability/day', function (Request $req) {
         (new App\Controllers\Api\Public\AvailabilityController())->day($req);
      });
   });

   $r->group('/admin', function (Router $r) {

      // =========================
      // ADMIN - PÚBLICO
      // =========================
      $r->get('/csrf', function () {
         $t = Csrf::token();
         Response::json(true, ['token' => $t], null, 200);
      });

      $r->post('/login', function (Request $req) {
         (new \App\Controllers\Api\Admin\AuthController())->login($req);
      });

      $r->get('/me', function (Request $req) {
         (new \App\Controllers\Api\Admin\AuthController())->me($req);
      });

      // =========================
      // ADMIN - PROTEGIDO
      // =========================
      $r->group('', function (Router $r) {

         $r->post('/logout', function (Request $req) {
            (new \App\Controllers\Api\Admin\AuthController())->logout($req);
         });

         $r->get('/catalogos/sedes', function (Request $req) {
            (new \App\Controllers\Api\Admin\AdminCatalogController())->sedes($req);
         });

         // Sedes
         $r->get('/sedes', function (Request $req) {
            (new \App\Controllers\Api\Admin\SedeController())->index($req);
         });

         $r->post('/sedes', function (Request $req) {
            (new \App\Controllers\Api\Admin\SedeController())->store($req);
         });

         $r->put('/sedes/{id}', function (Request $req) {
            (new \App\Controllers\Api\Admin\SedeController())->update($req);
         });

         $r->delete('/sedes/{id}', function (Request $req) {
            (new \App\Controllers\Api\Admin\SedeController())->delete($req);
         });

         // Trámites
         $r->get('/tramites', function (Request $req) {
            (new \App\Controllers\Api\Admin\TramiteController())->index($req);
         });

         $r->post('/tramites', function (Request $req) {
            (new \App\Controllers\Api\Admin\TramiteController())->store($req);
         });

         $r->put('/tramites/{id}', function (Request $req) {
            (new \App\Controllers\Api\Admin\TramiteController())->update($req);
         });

         $r->delete('/tramites/{id}', function (Request $req) {
            (new \App\Controllers\Api\Admin\TramiteController())->delete($req);
         });

         // Sede-Trámites
         $r->get('/sede-tramites', function (Request $req) {
            (new \App\Controllers\Api\Admin\SedeTramiteController())->index($req);
         });

         $r->post('/sede-tramites', function (Request $req) {
            (new \App\Controllers\Api\Admin\SedeTramiteController())->store($req);
         });

         $r->put('/sede-tramites/{id}', function (Request $req) {
            (new \App\Controllers\Api\Admin\SedeTramiteController())->update($req);
         });

         $r->delete('/sede-tramites/{id}', function (Request $req) {
            (new \App\Controllers\Api\Admin\SedeTramiteController())->delete($req);
         });

         // Sede-Horarios
         $r->get('/sede-horarios', function (Request $req) {
            (new \App\Controllers\Api\Admin\SedeHorarioController())->index($req);
         });

         $r->post('/sede-horarios', function (Request $req) {
            (new \App\Controllers\Api\Admin\SedeHorarioController())->store($req);
         });

         $r->put('/sede-horarios/{id}', function (Request $req) {
            (new \App\Controllers\Api\Admin\SedeHorarioController())->update($req);
         });

         $r->delete('/sede-horarios/{id}', function (Request $req) {
            (new \App\Controllers\Api\Admin\SedeHorarioController())->delete($req);
         });

         // Feriados
         $r->get('/feriados', function (Request $req) {
            (new \App\Controllers\Api\Admin\FeriadoController())->index($req);
         });

         $r->post('/feriados', function (Request $req) {
            (new \App\Controllers\Api\Admin\FeriadoController())->store($req);
         });

         $r->put('/feriados/{id}', function (Request $req) {
            (new \App\Controllers\Api\Admin\FeriadoController())->update($req);
         });

         $r->delete('/feriados/{id}', function (Request $req) {
            (new \App\Controllers\Api\Admin\FeriadoController())->delete($req);
         });

         // Agenda
         $r->get('/agenda', function (Request $req) {
            (new \App\Controllers\Api\Admin\AgendaController())->index($req);
         });

         $r->get('/agenda/show', function (Request $req) {
            (new \App\Controllers\Api\Admin\AgendaController())->show($req);
         });

         $r->patch('/agenda/estado', function (Request $req) {
            (new \App\Controllers\Api\Admin\AgendaController())->estado($req);
         });

         // Turnos
         $r->get('/turnos/hoy', function (Request $req) {
            (new \App\Controllers\Api\Admin\AgendaController())->turnosHoy($req);
         });
      });
   });
});
