<?php
/**
 * Punto de entrada unico de Galope. Todas las peticiones pasan por aca.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require GALOPE_ROOT . '/app/controllers/AuthController.php';
require GALOPE_ROOT . '/app/controllers/PlayerController.php';
require GALOPE_ROOT . '/app/controllers/AdminController.php';

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Servidor embebido de PHP: deja que sirva los archivos estaticos que existan.
if (PHP_SAPI === 'cli-server' && $uri !== '/' && is_file(__DIR__ . $uri)) {
    return false;
}

// Quita el prefijo base_url si la app vive en un subdirectorio.
$base = rtrim((string) cfg('base_url', ''), '/');
if ($base !== '' && str_starts_with($uri, $base)) {
    $uri = substr($uri, strlen($base));
}
$uri = '/' . trim($uri, '/');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$routes = [
    'GET /'                    => [PlayerController::class, 'dashboard'],

    'GET /login'               => [AuthController::class, 'showLogin'],
    'POST /login'              => [AuthController::class, 'login'],
    'GET /register'            => [AuthController::class, 'showRegister'],
    'POST /register'           => [AuthController::class, 'register'],
    'POST /logout'             => [AuthController::class, 'logout'],

    'GET /race'                => [PlayerController::class, 'race'],
    'POST /predict'            => [PlayerController::class, 'predict'],
    'GET /leaderboard'         => [PlayerController::class, 'leaderboard'],
    'GET /profile'             => [PlayerController::class, 'profile'],

    'GET /admin'               => [AdminController::class, 'races'],
    'GET /admin/race'          => [AdminController::class, 'raceForm'],
    'POST /admin/race'         => [AdminController::class, 'saveRace'],
    'POST /admin/race/open'    => [AdminController::class, 'openRace'],
    'GET /admin/result'        => [AdminController::class, 'resultForm'],
    'POST /admin/result'       => [AdminController::class, 'saveResult'],
    'GET /admin/scoring'       => [AdminController::class, 'scoring'],
    'POST /admin/scoring'      => [AdminController::class, 'saveScoring'],
    'GET /admin/users'         => [AdminController::class, 'users'],
    'POST /admin/users/toggle' => [AdminController::class, 'toggleUser'],
];

$key = $method . ' ' . $uri;

if (!isset($routes[$key])) {
    http_response_code(404);
    view('error', [
        'title'   => 'Pagina no encontrada',
        'code'    => 404,
        'message' => 'La pagina que buscas no existe.',
    ]);
    exit;
}

try {
    [$class, $action] = $routes[$key];
    (new $class())->$action();
} catch (Throwable $e) {
    http_response_code(500);
    error_log('[Galope] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    view('error', [
        'title'   => 'Error',
        'code'    => 500,
        'message' => 'Ocurrio un error inesperado. Intenta de nuevo en unos minutos.',
    ]);
}
