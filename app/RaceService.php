<?php
/**
 * Ciclo de vida de las carreras: estado, bloqueo de predicciones, carga del
 * resultado oficial (ganador + dividendo) y tabla de clasificacion.
 *
 * El bloqueo de predicciones es el punto de integridad del juego: una vez que
 * la carrera arranca, ninguna prediccion puede crearse ni modificarse. Esa
 * regla se decide aca, en el servidor, nunca en el navegador.
 */
class RaceService
{
    /**
     * Estado efectivo de una carrera, combinando el estado guardado con el reloj:
     *  - scheduled : existe, las predicciones todavia no abrieron
     *  - open      : se pueden cargar y modificar predicciones
     *  - locked    : ya cerro (llego la hora), falta cargar el resultado
     *  - finished  : resultado cargado y puntos asignados
     */
    public static function effectiveStatus(array $race): string
    {
        if ($race['status'] === 'finished') {
            return 'finished';
        }
        if (now() >= $race['predictions_close_at']) {
            return 'locked';
        }
        if ($race['status'] === 'open') {
            return 'open';
        }
        return 'scheduled';
    }

    public static function statusLabel(string $effective): string
    {
        return [
            'scheduled' => 'Programada',
            'open'      => 'Predicciones abiertas',
            'locked'    => 'Bloqueada - esperando resultado',
            'finished'  => 'Finalizada',
        ][$effective] ?? $effective;
    }

    /** True solo si se pueden crear o modificar predicciones en este instante. */
    public static function predictionsOpen(array $race): bool
    {
        return self::effectiveStatus($race) === 'open';
    }

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM races WHERE id = ?', [$id]);
    }

    public static function all(): array
    {
        return Database::all('SELECT * FROM races ORDER BY predictions_close_at DESC');
    }

    public static function horses(int $raceId): array
    {
        return Database::all('SELECT * FROM horses WHERE race_id = ? ORDER BY number', [$raceId]);
    }

    public static function result(int $raceId): ?array
    {
        return Database::one('SELECT * FROM race_results WHERE race_id = ?', [$raceId]);
    }

    /**
     * Carga (o corrige) el resultado oficial de una carrera: el caballo ganador y
     * el dividendo oficial del hipodromo. Dispara el motor de puntuacion sobre
     * TODAS las predicciones cerradas en una sola transaccion atomica.
     *
     * Se puede volver a llamar para corregir un resultado mal cargado; en ese
     * caso vuelve a puntuar todas las predicciones con los datos correctos.
     *
     * @return int cantidad de predicciones evaluadas.
     */
    public static function loadResult(
        int $raceId,
        int $winnerHorseId,
        float $dividend,
        ?int $adminId,
        string $source = 'manual'
    ): int {
        return Database::tx(function () use ($raceId, $winnerHorseId, $dividend, $adminId, $source) {
            if (self::result($raceId) !== null) {
                Database::run(
                    'UPDATE race_results
                        SET winner_horse_id = ?, dividend = ?,
                            source = ?, entered_by = ?, entered_at = ?
                      WHERE race_id = ?',
                    [$winnerHorseId, $dividend, $source, $adminId, now(), $raceId]
                );
            } else {
                Database::run(
                    'INSERT INTO race_results
                        (race_id, winner_horse_id, dividend, source, entered_by, entered_at)
                     VALUES (?,?,?,?,?,?)',
                    [$raceId, $winnerHorseId, $dividend, $source, $adminId, now()]
                );
            }

            Database::run('UPDATE races SET status = ? WHERE id = ?', ['finished', $raceId]);

            $preds = Database::all('SELECT * FROM predictions WHERE race_id = ?', [$raceId]);
            foreach ($preds as $p) {
                $points = Scoring::score(
                    (string) $p['mode'],
                    [
                        $p['pick1_horse_id'],
                        $p['pick2_horse_id'],
                        $p['pick3_horse_id'],
                    ],
                    $winnerHorseId,
                    (float) $dividend
                );
                Database::run(
                    'UPDATE predictions SET points_awarded = ?, updated_at = ? WHERE id = ?',
                    [$points, now(), $p['id']]
                );
            }
            return count($preds);
        });
    }

    /** Tabla de clasificacion: suma de puntos por jugador. */
    public static function leaderboard(int $limit = 100): array
    {
        return Database::all(
            "SELECT u.id, u.name,
                    COALESCE(SUM(p.points_awarded), 0)                AS points,
                    COUNT(p.id)                                       AS races_played,
                    SUM(CASE WHEN p.points_awarded > 0 THEN 1 ELSE 0 END) AS races_scored
               FROM users u
               LEFT JOIN predictions p
                      ON p.user_id = u.id AND p.points_awarded IS NOT NULL
              WHERE u.role = 'player' AND u.status = 'active'
              GROUP BY u.id, u.name
              ORDER BY points DESC, races_played ASC, u.name ASC
              LIMIT " . (int) $limit
        );
    }

    /** Posicion de un jugador en la tabla (1 = lider). 0 si no esta. */
    public static function rankOf(int $userId): int
    {
        foreach (self::leaderboard(100000) as $i => $row) {
            if ((int) $row['id'] === $userId) {
                return $i + 1;
            }
        }
        return 0;
    }
}
