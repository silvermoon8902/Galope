<?php
/**
 * Devuelve codigo de salida 0 si la base de datos MySQL acepta una conexion,
 * 1 si no. Lo usa el entrypoint del contenedor para esperar a la base.
 */
$cfg = require __DIR__ . '/../config/env.php';
$d = $cfg['db'];

try {
    new PDO(
        "mysql:host={$d['host']};port={$d['port']}",
        $d['username'],
        $d['password'],
        [PDO::ATTR_TIMEOUT => 3]
    );
    exit(0);
} catch (Throwable $e) {
    exit(1);
}
