<?php
/**
 * Motor de puntuacion de Galope.
 *
 * Reglas del juego (definidas por el cliente):
 *
 *  - Por cada carrera el jugador elige UNA modalidad de juego:
 *      Full Point  : 50 puntos a 1 caballo.
 *      Dual Point  : 25 puntos a cada uno de 2 caballos.
 *      Smart Point : 30 + 15 + 5 puntos a 3 caballos distintos.
 *
 *  - Todas las modalidades se juegan UNICAMENTE al ganador de la carrera.
 *    El segundo y el tercer puesto no entran en el calculo.
 *
 *  - Si el caballo ganador esta entre los caballos elegidos, el puntaje es:
 *      (puntos apostados a ese caballo) * (dividendo oficial del hipodromo)
 *
 *  - Si el ganador no esta entre los caballos elegidos, el jugador suma cero.
 *
 *  Ejemplo: Full Point con 50 puntos al caballo 4, el 4 gana con dividendo 5,
 *  el jugador suma 50 * 5 = 250 puntos en esa carrera.
 */
class Scoring
{
    /** Modalidades disponibles con sus stakes y cantidad de caballos. */
    public static function modes(): array
    {
        return [
            'full'  => [
                'label'       => 'Full Point',
                'short'       => 'Riesgo alto, premio alto',
                'description' => '50 puntos a un solo caballo.',
                'stakes'      => [50],
            ],
            'dual'  => [
                'label'       => 'Dual Point',
                'short'       => 'Riesgo medio',
                'description' => '25 puntos a cada uno de dos caballos.',
                'stakes'      => [25, 25],
            ],
            'smart' => [
                'label'       => 'Smart Point',
                'short'       => 'Riesgo bajo, premio escalonado',
                'description' => '30 + 15 + 5 puntos a tres caballos distintos.',
                'stakes'      => [30, 15, 5],
            ],
        ];
    }

    public static function modeLabel(string $mode): string
    {
        return self::modes()[$mode]['label'] ?? $mode;
    }

    /** Cantidad de caballos que se deben elegir para una modalidad. */
    public static function horseCount(string $mode): int
    {
        return count(self::stakes($mode));
    }

    /** Stakes (puntos apostados) ordenados por posicion (pick1, pick2, pick3). */
    public static function stakes(string $mode): array
    {
        return self::modes()[$mode]['stakes'] ?? [];
    }

    /** Puntos apostados en la posicion dada (1, 2 o 3) de la modalidad. */
    public static function stakeAt(string $mode, int $position): int
    {
        $stakes = self::stakes($mode);
        return (int) ($stakes[$position - 1] ?? 0);
    }

    /**
     * Calcula los puntos de una prediccion:
     *   apuesta_en_el_ganador * dividendo_oficial.
     *
     * @param string $mode      'full' | 'dual' | 'smart'.
     * @param array  $picks     IDs de caballo elegidos (pick1, pick2, pick3, con null en los no usados).
     * @param int    $winnerId  ID del caballo ganador.
     * @param float  $dividend  Dividendo oficial del hipodromo.
     */
    public static function score(string $mode, array $picks, int $winnerId, float $dividend): int
    {
        $stakes = self::stakes($mode);
        foreach (array_values($picks) as $i => $horseId) {
            if ($horseId === null || $horseId === '') {
                continue;
            }
            if ((int) $horseId === $winnerId) {
                $stake = (int) ($stakes[$i] ?? 0);
                return (int) round($stake * $dividend);
            }
        }
        return 0;
    }

    /**
     * Desglose para mostrar al jugador: por cada caballo elegido,
     * cuanto aposto, si fue el ganador y cuantos puntos sumo.
     */
    public static function breakdown(string $mode, array $picks, int $winnerId, float $dividend): array
    {
        $stakes = self::stakes($mode);
        $lines  = [];
        foreach (array_values($picks) as $i => $horseId) {
            if ($horseId === null || $horseId === '') {
                continue;
            }
            $stake    = (int) ($stakes[$i] ?? 0);
            $isWinner = ((int) $horseId === $winnerId);
            $lines[] = [
                'position'  => $i + 1,
                'horse_id'  => (int) $horseId,
                'stake'     => $stake,
                'is_winner' => $isWinner,
                'points'    => $isWinner ? (int) round($stake * $dividend) : 0,
            ];
        }
        return $lines;
    }
}
