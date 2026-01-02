<?php

declare(strict_types=1);

use App\Http\Router\Router;
use App\Http\Router\Request;

require_once APP_ROOT . '/app/Controllers/Web/Admin/AdminPagesController.php';
require_once APP_ROOT . '/app/Controllers/Web/Public/PublicPagesController.php';

$router->group('/admin', function (Router $r) {
   $r->get('/login', function (Request $req) {
      (new \App\Controllers\Web\Admin\AdminPagesController())->login();
   });

   $r->get('/sedes', function (Request $req) {
      (new \App\Controllers\Web\Admin\AdminPagesController())->sedes();
   });
});

$router->get('/admin/tramites', function (\App\Http\Router\Request $req) {
   (new \App\Controllers\Web\Admin\AdminPagesController())->tramites();
});

$router->get('/admin/sede-tramites', function (\App\Http\Router\Request $req) {
   (new \App\Controllers\Web\Admin\AdminPagesController())->sedeTramites();
});

$router->get('/admin/sede-horarios', function (\App\Http\Router\Request $req) {
   (new \App\Controllers\Web\Admin\AdminPagesController())->sedeHorarios();
});

$router->get('/admin/feriados', function (\App\Http\Router\Request $req) {
   (new \App\Controllers\Web\Admin\AdminPagesController())->feriados();
});

$router->get('/admin/agenda', function (\App\Http\Router\Request $req) {
   (new \App\Controllers\Web\Admin\AdminPagesController())->agenda();
});

$router->get('/admin/turnos', function (\App\Http\Router\Request $req) {
   (new \App\Controllers\Web\Admin\AdminPagesController())->turnos();
});

$router->get('/turnos', function (\App\Http\Router\Request $req) {
   require APP_ROOT . '/resources/views/oper/turnos.php';
});

$router->group('', function (Router $r) {

   // Home pública (opcional)
   $r->get('/', function (Request $req) {
      (new \App\Controllers\Web\Public\PublicPagesController())->home();
   });

   // Agendar cita
   $r->get('/agendar', function (Request $req) {
      (new \App\Controllers\Web\Public\PublicPagesController())->citas();
   });

   // Mi cita (consultar/cancelar/reagendar)
   $r->get('/agendar/mi', function (Request $req) {
      (new \App\Controllers\Web\Public\PublicPagesController())->miCita();
   });
});
