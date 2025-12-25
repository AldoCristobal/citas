<?php

declare(strict_types=1);

namespace App\Controllers\Api\Admin;

use App\Http\Router\Request;
use App\Http\Responses\Response;
use App\Services\Admin\AgendaService;

require_once APP_ROOT . '/app/Http/Responses/Response.php';
require_once APP_ROOT . '/app/Services/Admin/AgendaService.php';

final class AgendaController
{
   public function index(Request $req): void
   {
      $q = $_GET; // ✅ tu Request no tiene query()

      $svc = new AgendaService();
      $res = $svc->list([
         'fecha' => (string)($q['fecha'] ?? ''),
         'sede_id' => (int)($q['sede_id'] ?? 0),
         'estado' => (string)($q['estado'] ?? ''),
         'q' => (string)($q['q'] ?? ''),
      ]);

      if (!$res['ok']) {
         Response::json(false, null, $res['error'], $res['status'] ?? 400);
         return;
      }

      Response::json(true, $res['data'], null, 200);
   }

   public function show(Request $req): void
   {
      $q = $_GET; // ✅

      $id = (int)($q['id'] ?? 0);

      $svc = new AgendaService();
      $res = $svc->show($id);

      if (!$res['ok']) {
         Response::json(false, null, $res['error'], $res['status'] ?? 400);
         return;
      }

      Response::json(true, $res['data'], null, 200);
   }

   public function estado(Request $req): void
   {
      $b = $req->json();

      $id = (int)($b['id'] ?? 0);
      $estado = (string)($b['estado'] ?? '');

      $svc = new AgendaService();
      $res = $svc->cambiarEstado($id, $estado, $req);

      if (!$res['ok']) {
         Response::json(false, null, $res['error'], $res['status'] ?? 400);
         return;
      }

      Response::json(true, $res['data'], null, 200);
   }

   public function turnosHoy(\App\Http\Router\Request $req): void
   {
      $q = $_GET;
      $sedeId = (int)($q['sede_id'] ?? 0);

      $svc = new \App\Services\Admin\AgendaService();
      $res = $svc->turnosHoy($sedeId);

      if (!$res['ok']) {
         \App\Http\Responses\Response::json(false, null, $res['error'], $res['status'] ?? 400);
         return;
      }

      \App\Http\Responses\Response::json(true, $res['data'], null, 200);
   }
}
