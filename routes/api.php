<?php
// routes/api.php
declare(strict_types=1);

use App\Http\Router\Router;
use App\Http\Router\Request;
use App\Http\Responses\Response;
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

      $r->get('/disponibilidad', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Public\AvailabilityController())->disponibilidad($req);
      });

      $r->get('/slots', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Public\AvailabilityController())->slots($req);
      });

      $r->post('/holds', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Public\HoldController())->create($req);
      });

      $r->post('/citas/confirmar', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Public\BookingController())->confirm($req);
      });

      $r->get('/citas/{folio}', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Public\PublicCitaController())->show($req);
      });

      $r->post('/citas/{folio}/cancelar', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Public\PublicCitaController())->cancel($req);
      });

      $r->post('/citas/{folio}/reagendar/hold', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Public\ReagendaController())->createHold($req);
      });
      // Availability (Public)
      $r->get('/availability/month', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Public\AvailabilityController())->month($req);
      });

      $r->get('/availability/day', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Public\AvailabilityController())->day($req);
      });
   });

   $r->group('/admin', function (\App\Http\Router\Router $r) {

      // Auth
      $r->post('/login', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Admin\AuthController())->login($req);
      });

      $r->post('/logout', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Admin\AuthController())->logout($req);
      });

      $r->get('/me', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Admin\AuthController())->me($req);
      });

      $r->get('/catalogos/sedes', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Admin\AdminCatalogController())->sedes($req);
      });

      // Sedes (protegido por sesión - lo metemos en Kernel middleware después)
      $r->get('/sedes', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Admin\SedeController())->index($req);
      });

      $r->post('/sedes', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Admin\SedeController())->store($req);
      });

      $r->put('/sedes/{id}', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Admin\SedeController())->update($req);
      });

      $r->delete('/sedes/{id}', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Admin\SedeController())->delete($req);
      });

      $r->get('/tramites', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Admin\TramiteController())->index($req);
      });

      $r->post('/tramites', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Admin\TramiteController())->store($req);
      });

      $r->put('/tramites/{id}', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Admin\TramiteController())->update($req);
      });

      $r->delete('/tramites/{id}', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Admin\TramiteController())->delete($req);
      });

      $r->get('/sede-tramites', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Admin\SedeTramiteController())->index($req);
      });

      $r->post('/sede-tramites', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Admin\SedeTramiteController())->store($req);
      });

      $r->put('/sede-tramites/{id}', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Admin\SedeTramiteController())->update($req);
      });

      $r->delete('/sede-tramites/{id}', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Admin\SedeTramiteController())->delete($req);
      });

      $r->get('/sede-horarios', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Admin\SedeHorarioController())->index($req);
      });

      $r->post('/sede-horarios', function (\App\Http\Router\Request $req) {
         (new SedeHorarioController())->store($req);
      });

      $r->put('/sede-horarios/{id}', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Admin\SedeHorarioController())->update($req);
      });

      $r->delete('/sede-horarios/{id}', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Admin\SedeHorarioController())->delete($req);
      });

      $r->get('/feriados', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Admin\FeriadoController())->index($req);
      });

      $r->post('/feriados', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Admin\FeriadoController())->store($req);
      });

      $r->put('/feriados/{id}', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Admin\FeriadoController())->update($req);
      });

      $r->delete('/feriados/{id}', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Admin\FeriadoController())->delete($req);
      });

      $r->get('/agenda', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Admin\AgendaController())->index($req);
      });

      $r->get('/agenda/show', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Admin\AgendaController())->show($req);
      });

      $r->patch('/agenda/estado', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Admin\AgendaController())->estado($req);
      });

      $r->get('/turnos/hoy', function (\App\Http\Router\Request $req) {
         (new \App\Controllers\Api\Admin\AgendaController())->turnosHoy($req);
      });
   });
});
