<?php
/**
 * Configuracion desde variables de entorno (Railway, Docker, etc.).
 *
 * Se usa automaticamente cuando config/config.php no existe, es decir, en
 * cualquier despliegue gestionado. Acepta una URL de conexion completa
 * (DATABASE_URL o MYSQL_URL) o variables sueltas (DB_HOST, DB_USER, ...).
 */

$url = getenv('DATABASE_URL') ?: getenv('MYSQL_URL') ?: '';
$parts = $url !== '' ? parse_url($url) : false;

if (is_array($parts) && isset($parts['host'])) {
    $db = [
        'driver'      => 'mysql',
        'host'        => $parts['host'],
        'port'        => (int) ($parts['port'] ?? 3306),
        'database'    => ltrim($parts['path'] ?? '', '/') ?: 'railway',
        'username'    => urldecode($parts['user'] ?? ''),
        'password'    => urldecode($parts['pass'] ?? ''),
        'charset'     => 'utf8mb4',
        'sqlite_path' => '',
    ];
} else {
    $db = [
        'driver'      => getenv('DB_DRIVER') ?: 'mysql',
        'host'        => getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: '127.0.0.1',
        'port'        => (int) (getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: 3306),
        'database'    => getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: 'railway',
        'username'    => getenv('DB_USER') ?: getenv('MYSQLUSER') ?: 'root',
        'password'    => getenv('DB_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: '',
        'charset'     => 'utf8mb4',
        'sqlite_path' => getenv('SQLITE_PATH') ?: '',
    ];
}

return [
    'app_name' => getenv('APP_NAME') ?: 'Galope',
    'base_url' => getenv('BASE_URL') ?: '',
    'db'       => $db,
    'timezone' => getenv('APP_TIMEZONE') ?: 'America/Argentina/Buenos_Aires',
];
