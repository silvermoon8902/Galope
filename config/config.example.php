<?php
/**
 * Galope - configuracion de la aplicacion.
 *
 * Copiar este archivo a config/config.php y completar con los datos reales.
 * config/config.php no se versiona (ver .gitignore).
 */
return [
    'app_name' => 'Galope',

    // Prefijo de URL. Vacio si la app vive en la raiz del dominio,
    // o por ejemplo '/galope' si se sirve desde un subdirectorio.
    'base_url' => '',

    'db' => [
        // 'mysql' en produccion. 'sqlite' solo para pruebas locales.
        'driver'   => 'mysql',

        // --- Parametros MySQL ---
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'database' => 'galope',
        'username' => 'galope',
        'password' => 'cambiar-esta-clave',
        'charset'  => 'utf8mb4',

        // --- Parametro SQLite (solo si driver = sqlite) ---
        'sqlite_path' => __DIR__ . '/../db/galope.sqlite',
    ],

    // Zona horaria usada para el cierre de predicciones y los resultados.
    'timezone' => 'America/Argentina/Buenos_Aires',
];
