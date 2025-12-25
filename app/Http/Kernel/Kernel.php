<?php
// app/Http/Kernel/Kernel.php
declare(strict_types=1);

namespace App\Http\Kernel;

use App\Http\Router\Request;
use App\Http\Router\Router;
use App\Http\Responses\Response;

final class Kernel
{
   public function __construct(private Router $router) {}

   public function handle(Request $req): void
   {
      try {
         $match = $this->router->match($req);

         if (!$match) {
            Response::json(false, null, ['code' => 'NOT_FOUND', 'message' => 'Ruta no encontrada'], 404);
            return;
         }

         $req->params = $match['params'];

         // Handler recibe Request
         ($match['handler'])($req);
      } catch (\Throwable $e) {
         // Log básico
         $logDir = APP_ROOT . '/storage/logs';
         if (!is_dir($logDir)) @mkdir($logDir, 0777, true);
         $msg = '[' . date('c') . '] ' . $e::class . ': ' . $e->getMessage() .
            ' in ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL .
            $e->getTraceAsString() . PHP_EOL . PHP_EOL;
         @file_put_contents($logDir . '/app.log', $msg, FILE_APPEND);

         $debug = getenv('APP_DEBUG') === '1';

         if ($debug) {
            \App\Http\Responses\Response::json(false, null, [
               'code' => 'SERVER',
               'message' => $e->getMessage(),
               'file' => $e->getFile(),
               'line' => $e->getLine(),
            ], 500);
            return;
         }

         \App\Http\Responses\Response::json(false, null, ['code' => 'SERVER', 'message' => 'Error interno'], 500);
      }
   }
}
