<?php
/**
 * Arranque de la aplicacion: configuracion, base de datos, sesion y helpers.
 * Lo incluye public/index.php en cada request.
 */
declare(strict_types=1);

if (!defined('GALOPE_ROOT')) {
    define('GALOPE_ROOT', dirname(__DIR__));
}

$configFile = GALOPE_ROOT . '/config/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Falta config/config.php. Copia config/config.example.php y completalo.');
}
$GLOBALS['galope_config'] = require $configFile;

date_default_timezone_set($GLOBALS['galope_config']['timezone'] ?? 'UTC');

require GALOPE_ROOT . '/app/Database.php';
require GALOPE_ROOT . '/app/helpers.php';
require GALOPE_ROOT . '/app/Scoring.php';
require GALOPE_ROOT . '/app/RaceService.php';
require GALOPE_ROOT . '/app/Auth.php';

Database::init($GLOBALS['galope_config']['db']);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Recupera la entrada del formulario del request anterior para que old()
// pueda repoblar los campos despues de un redirect.
$GLOBALS['galope_old'] = $_SESSION['_old'] ?? [];
unset($_SESSION['_old']);
