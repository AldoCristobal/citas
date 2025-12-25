<?php
// public/index.php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = dirname(__DIR__);
define('APP_ROOT', $root);

// Autoload composer si existe (opcional)
$composer = $root . '/vendor/autoload.php';
if (is_file($composer)) {
    require $composer;
}

// Bootstrap mínimo (sin framework)
require_once $root . '/app/Support/Helpers/Env.php';
require_once $root . '/app/Support/Database/Db.php';
require_once $root . '/app/Http/Responses/Response.php';

require_once $root . '/app/Http/Router/Request.php';
require_once $root . '/app/Http/Router/Router.php';
require_once $root . '/app/Http/Kernel/Kernel.php';

App\Support\Helpers\Env::load($root . '/.env');

// CORS básico (luego lo restringimos por dominio)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST,PUT,PATCH,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$req = App\Http\Router\Request::fromGlobals();

// Router + Kernel
$router = new App\Http\Router\Router();
require_once $root . '/routes/api.php'; // aquí registras rutas con $router
require_once $root . '/routes/web.php';

$kernel = new App\Http\Kernel\Kernel($router);
$kernel->handle($req);
