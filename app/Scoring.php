<?php
/**
 * Motor de puntuacion de Galope.
 *
 * Toda la logica de "cuantos puntos vale una prediccion" vive aca. Las reglas
 * son configurables (tabla scoring_rules); este motor solo las aplica. No
 * depende de la base de datos: recibe la prediccion, el resultado y las reglas.
 */
class Scoring
{
    /**
     * Reglas por defecto que se cargan al instalar.
     * clave => [etiqueta, descripcion, puntos, orden].
     */
    public static function defaultRules(): array
    {
        return [
            'exact_1' => ['Acertar el 1.er puesto', 'El caballo ganador, en su posicion exacta', 50, 1],
            'exact_2' => ['Acertar el 2.do puesto', 'Posicion exacta', 30, 2],
            'exact_3' => ['Acertar el 3.er puesto', 'Posicion exacta', 20, 3],
            'bonus_exact_podium' => ['Bonus: podio exacto', 'Los tres caballos en el orden correcto', 40, 4],
            'bonus_any_order'    => ['Bonus: podio completo', 'Los tres caballos del podio, en cualquier orden', 15, 5],
            'misplaced'          => ['Caballo del podio fuera de lugar', 'Acertado, pero en otra posicion', 5, 6],
        ];
    }

    private static array $ordinals = ['1.er', '2.do', '3.er'];

    /**
     * Calcula los puntos de una prediccion contra el resultado oficial.
     *
     * @param array $pred   IDs de caballo elegidos, en orden [1ro, 2do, 3ro].
     * @param array $result IDs de caballo del resultado, en orden [1ro, 2do, 3ro].
     * @param array $rules  Mapa clave_de_regla => puntos.
     */
    public static function score(array $pred, array $result, array $rules): int
    {
        $total = 0;
        foreach (self::breakdown($pred, $result, $rules) as $line) {
            $total += $line['points'];
        }
        return $total;
    }

    /**
     * Desglose legible de la puntuacion: lista de [etiqueta, puntos].
     * Sirve para mostrarle al jugador exactamente por que sumo lo que sumo.
     */
    public static function breakdown(array $pred, array $result, array $rules): array
    {
        $pts = static fn(string $k): int => (int) ($rules[$k] ?? 0);
        $pred   = array_map('intval', array_values($pred));
        $result = array_map('intval', array_values($result));
        $lines  = [];

        // 1. Aciertos de posicion exacta.
        $exactHits = 0;
        for ($i = 0; $i < 3; $i++) {
            if ($pred[$i] === $result[$i]) {
                $exactHits++;
                $lines[] = [
                    'label'  => 'Acierto exacto del ' . self::$ordinals[$i] . ' puesto',
                    'points' => $pts('exact_' . ($i + 1)),
                ];
            }
        }

        // 2. Bonus por el podio completo en el orden exacto.
        if ($exactHits === 3 && $pts('bonus_exact_podium') !== 0) {
            $lines[] = ['label' => 'Bonus: podio exacto (1-2-3)', 'points' => $pts('bonus_exact_podium')];
        }

        // 3. Bonus por acertar los tres caballos del podio en cualquier orden.
        $sortedPred = $pred;
        $sortedRes  = $result;
        sort($sortedPred);
        sort($sortedRes);
        if ($sortedPred === $sortedRes && $pts('bonus_any_order') !== 0) {
            $lines[] = ['label' => 'Bonus: podio completo (cualquier orden)', 'points' => $pts('bonus_any_order')];
        }

        // 4. Caballos del podio acertados pero en una posicion equivocada.
        for ($i = 0; $i < 3; $i++) {
            $horse     = $pred[$i];
            $inResult  = in_array($horse, $result, true);
            $exactHere = ($result[$i] === $horse);
            if ($inResult && !$exactHere && $pts('misplaced') !== 0) {
                $lines[] = ['label' => 'Caballo del podio fuera de lugar', 'points' => $pts('misplaced')];
            }
        }

        return $lines;
    }
}
