<?php
/**
 * Instala el esquema de Galope y carga datos de ejemplo.
 *
 *   php bin/migrate.php            crea el esquema (falla si ya existe)
 *   php bin/migrate.php --fresh    borra todo y recrea desde cero
 *
 * Funciona con MySQL o SQLite segun config/config.php.
 */
declare(strict_types=1);

define('GALOPE_ROOT', dirname(__DIR__));

// config/config.php para desarrollo local; si no existe, variables de entorno.
$configFile = GALOPE_ROOT . '/config/config.php';
$cfg = is_file($configFile)
    ? require $configFile
    : require GALOPE_ROOT . '/config/env.php';
date_default_timezone_set($cfg['timezone'] ?? 'UTC');

require GALOPE_ROOT . '/app/Database.php';
require GALOPE_ROOT . '/app/helpers.php';
require GALOPE_ROOT . '/app/Scoring.php';
require GALOPE_ROOT . '/app/RaceService.php';

$driver = $cfg['db']['driver'] ?? 'mysql';
$fresh  = in_array('--fresh', $argv, true);

// Orden inverso a las claves foraneas para poder borrar.
$tables = ['settings', 'race_results', 'predictions', 'horses', 'races', 'users'];

if ($driver === 'sqlite' && $fresh && is_file($cfg['db']['sqlite_path'])) {
    unlink($cfg['db']['sqlite_path']);
}

Database::init($cfg['db']);
$pdo = Database::pdo();

$schemaExists = false;
try {
    Database::value('SELECT 1 FROM users LIMIT 1');
    $schemaExists = true;
} catch (Throwable $e) {
    $schemaExists = false;
}

if ($schemaExists && !$fresh) {
    fwrite(STDERR, "La base ya tiene tablas. Usa --fresh para recrearla desde cero.\n");
    exit(1);
}

if ($schemaExists && $fresh && $driver !== 'sqlite') {
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach ($tables as $table) {
        $pdo->exec('DROP TABLE IF EXISTS ' . $table);
    }
    // tabla heredada del modelo anterior, por si existe en la base
    $pdo->exec('DROP TABLE IF EXISTS scoring_rules');
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}

// Aplica el esquema correspondiente al driver.
$schemaSql = file_get_contents(GALOPE_ROOT . '/db/schema.' . $driver . '.sql');
foreach (split_sql($schemaSql) as $statement) {
    $pdo->exec($statement);
}
echo "Esquema aplicado ($driver).\n";

seed();

echo "Datos de ejemplo cargados.\n";
echo "------------------------------------------------\n";
echo "Admin:    admin@galope.test  /  galope-admin\n";
echo "Jugador:  jugador1@galope.test  /  galope-demo\n";
echo "------------------------------------------------\n";

// --- Funciones de apoyo ---

function split_sql(string $sql): array
{
    $lines = [];
    foreach (explode("\n", $sql) as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '--')) {
            continue;
        }
        $lines[] = $line;
    }
    $statements = [];
    foreach (explode(';', implode("\n", $lines)) as $piece) {
        if (trim($piece) !== '') {
            $statements[] = $piece;
        }
    }
    return $statements;
}

function seed(): void
{
    $now = time();

    // --- Usuarios ---
    $admin   = mkUser('Operador', 'admin@galope.test', 'galope-admin', 'admin');
    $players = [];
    foreach (['Carlos M.', 'Martin Ferreyra', 'Lucia Bravo', 'Diego Salinas', 'Paula Rios'] as $i => $name) {
        $players[] = mkUser($name, 'jugador' . ($i + 1) . '@galope.test', 'galope-demo', 'player');
    }

    // --- Ajustes generales ---
    foreach (['data_source' => 'manual', 'season_name' => 'Temporada 2026'] as $k => $v) {
        Database::run('INSERT INTO settings (setting_key, setting_value) VALUES (?,?)', [$k, $v]);
    }

    // --- Carrera 1: finalizada (en el pasado, con ganador y dividendo cargado) ---
    $r1 = mkRace('Clasico Velocidad', 'Hipodromo de San Isidro', 1400,
        date('Y-m-d H:i:s', $now - 86400), date('Y-m-d H:i:s', $now - 86400), 'finished');
    $h1 = mkHorses($r1, [
        ['Halcon Blanco', 'J. Medina'],
        ['Trueno del Norte', 'A. Rocha'],
        ['Maravilla', 'M. Duarte'],
        ['Don Lucero', 'P. Sosa'],
        ['Rayo de Plata', 'R. Vega'],
        ['Aurora Dorada', 'N. Cabrera'],
    ]);
    // Predicciones de jugadores con distintas modalidades.
    mkPrediction($players[0], $r1, 'full',  [$h1[0]]);                        // Carlos apuesta full a Halcon Blanco (el ganador)
    mkPrediction($players[1], $r1, 'dual',  [$h1[0], $h1[1]]);                // Martin cubre dual con el ganador
    mkPrediction($players[2], $r1, 'smart', [$h1[2], $h1[0], $h1[3]]);        // Lucia: smart con el ganador en pick2 (15)
    mkPrediction($players[3], $r1, 'full',  [$h1[3]]);                        // Diego apuesta full a un caballo que no gana
    mkPrediction($players[4], $r1, 'smart', [$h1[0], $h1[4], $h1[5]]);        // Paula: smart con el ganador en pick1 (30)

    // Resultado oficial: Halcon Blanco gana con dividendo 3.0 -> dispara el motor.
    RaceService::loadResult($r1, $h1[0], 3.0, $admin, 'manual');

    // --- Carrera 2: abierta (las predicciones cierran en ~2 horas) ---
    $r2 = mkRace('Gran Premio Clasico de Otono', 'Hipodromo de Palermo', 2000,
        date('Y-m-d H:i:s', $now + 7800), date('Y-m-d H:i:s', $now + 7200), 'open');
    $h2 = mkHorses($r2, [
        ['Relampago Azul', 'J. Medina'],
        ['Viento del Sur', 'A. Rocha'],
        ['Corazon Valiente', 'M. Duarte'],
        ['Estrella Negra', 'L. Fuentes'],
        ['Don Lucero', 'P. Sosa'],
        ['Tormenta Real', 'C. Ibanez'],
        ['Rayo de Plata', 'R. Vega'],
        ['Aurora Dorada', 'N. Cabrera'],
    ]);
    mkPrediction($players[1], $r2, 'dual',  [$h2[0], $h2[2]]);
    mkPrediction($players[2], $r2, 'full',  [$h2[3]]);

    // --- Carrera 3: bloqueada (cerro hace 30 min, falta cargar el resultado) ---
    $r3 = mkRace('Premio Independencia', 'Hipodromo de Palermo', 1800,
        date('Y-m-d H:i:s', $now - 1500), date('Y-m-d H:i:s', $now - 1800), 'open');
    $h3 = mkHorses($r3, [
        ['Lucero del Alba', 'J. Medina'],
        ['Bravio', 'A. Rocha'],
        ['Gran Capitan', 'M. Duarte'],
        ['Nube Roja', 'P. Sosa'],
        ['Salto del Tigre', 'R. Vega'],
        ['Mistral', 'N. Cabrera'],
    ]);
    mkPrediction($players[0], $r3, 'full',  [$h3[0]]);
    mkPrediction($players[3], $r3, 'smart', [$h3[2], $h3[0], $h3[5]]);

    // --- Carrera 4: programada (en 2 dias, predicciones aun cerradas) ---
    $r4 = mkRace('Handicap de Primavera', 'Hipodromo de La Plata', 1600,
        date('Y-m-d H:i:s', $now + 172800), date('Y-m-d H:i:s', $now + 172500), 'scheduled');
    mkHorses($r4, [
        ['Nube Roja', 'P. Sosa'],
        ['Salto del Tigre', 'R. Vega'],
        ['Mistral', 'N. Cabrera'],
        ['Gran Capitan', 'M. Duarte'],
        ['Bravio', 'A. Rocha'],
        ['Lucero del Alba', 'J. Medina'],
    ]);
}

function mkUser(string $name, string $email, string $password, string $role): int
{
    return Database::insert(
        'INSERT INTO users (name, email, password_hash, role, status, created_at)
         VALUES (?,?,?,?,?,?)',
        [$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, 'active', now()]
    );
}

function mkRace(string $name, string $track, int $distance, string $scheduled, string $close, string $status): int
{
    return Database::insert(
        'INSERT INTO races (name, racetrack, distance_m, scheduled_at, predictions_close_at, status, created_at)
         VALUES (?,?,?,?,?,?,?)',
        [$name, $track, $distance, $scheduled, $close, $status, now()]
    );
}

function mkHorses(int $raceId, array $list): array
{
    $ids    = [];
    $number = 1;
    foreach ($list as [$name, $jockey]) {
        $ids[] = Database::insert(
            'INSERT INTO horses (race_id, number, name, jockey, form, created_at)
             VALUES (?,?,?,?,?,?)',
            [$raceId, $number, $name, $jockey, randForm(), now()]
        );
        $number++;
    }
    return $ids;
}

function randForm(): string
{
    $parts = [];
    for ($i = 0; $i < 3; $i++) {
        $parts[] = (string) random_int(1, 6);
    }
    return implode('-', $parts);
}

function mkPrediction(int $userId, int $raceId, string $mode, array $picks): void
{
    $picks = array_pad(array_slice($picks, 0, 3), 3, null);
    Database::run(
        'INSERT INTO predictions
            (user_id, race_id, mode, pick1_horse_id, pick2_horse_id, pick3_horse_id, points_awarded, created_at, updated_at)
         VALUES (?,?,?,?,?,?,?,?,?)',
        [$userId, $raceId, $mode, $picks[0], $picks[1], $picks[2], null, now(), now()]
    );
}
