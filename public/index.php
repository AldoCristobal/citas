<?php
// public/index.php
declare(strict_types=1);

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

// ✅ Cargar .env ANTES de usar Env::get()
App\Support\Helpers\Env::load($root . '/.env');

// ✅ Debug correcto
$debug = (string)\App\Support\Helpers\Env::get('APP_DEBUG', '0') === '1';
ini_set('display_errors', $debug ? '1' : '0');
error_reporting($debug ? E_ALL : 0);

// ✅ CORS: por defecto NO es necesario si todo está en el mismo dominio.
// Si quieres habilitar CORS para algún frontend externo, usa allowlist.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = [
    // agrega dominios reales si algún día lo necesitas:
    // 'https://tudominio.gob.mx',
    // 'https://admin.tudominio.gob.mx',
];

// CORS: por defecto NO habilitamos cross-site
// Solo respondemos preflight si alguna vez lo necesitas
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? ''));
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET,POST,PUT,PATCH,DELETE,OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
    http_response_code(204);
    exit;
}


// Preflight
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ✅ Sesión para admin (cookie PHPSESSID)
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$req = App\Http\Router\Request::fromGlobals();

// Router + Kernel
$router = new App\Http\Router\Router();
require_once $root . '/routes/api.php';
require_once $root . '/routes/web.php';

$kernel = new App\Http\Kernel\Kernel($router);
$kernel->handle($req);
