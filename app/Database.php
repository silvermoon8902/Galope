<?php
/**
 * Capa de acceso a datos de Galope.
 *
 * Envoltorio fino sobre PDO. Soporta MySQL (produccion) y SQLite (pruebas
 * locales) con la misma interfaz, eligiendo el driver desde la configuracion.
 */
class Database
{
    private static ?PDO $pdo = null;

    public static function init(array $db): void
    {
        if (self::$pdo !== null) {
            return;
        }
        $driver = $db['driver'] ?? 'mysql';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        if ($driver === 'sqlite') {
            self::$pdo = new PDO('sqlite:' . $db['sqlite_path'], null, null, $options);
            self::$pdo->exec('PRAGMA foreign_keys = ON');
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $db['host'] ?? '127.0.0.1',
                (int) ($db['port'] ?? 3306),
                $db['database'] ?? '',
                $db['charset'] ?? 'utf8mb4'
            );
            self::$pdo = new PDO($dsn, $db['username'] ?? '', $db['password'] ?? '', $options);
        }
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            throw new RuntimeException('La base de datos no fue inicializada.');
        }
        return self::$pdo;
    }

    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Todas las filas de una consulta. */
    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /** Primera fila, o null si no hay resultados. */
    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** Primer valor de la primera fila (consultas escalares). */
    public static function value(string $sql, array $params = [])
    {
        $value = self::run($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    /** Ejecuta un INSERT y devuelve el id generado. */
    public static function insert(string $sql, array $params = []): int
    {
        self::run($sql, $params);
        return (int) self::pdo()->lastInsertId();
    }

    /** Ejecuta una funcion dentro de una transaccion (commit / rollback). */
    public static function tx(callable $fn)
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $fn();
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
