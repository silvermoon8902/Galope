<?php
/** Vistas y acciones del jugador: inicio, predicciones, clasificacion, perfil. */
class PlayerController
{
    public function dashboard(): void
    {
        require_login();
        $user = current_user();

        // Carrera destacada: la proxima cuyas predicciones todavia no cerraron.
        $next = Database::one(
            "SELECT * FROM races
              WHERE status != 'finished' AND predictions_close_at > ?
              ORDER BY predictions_close_at ASC LIMIT 1",
            [now()]
        );
        $nextHorses       = $next ? RaceService::horses((int) $next['id']) : [];
        $myNextPrediction = $next ? $this->predictionFor((int) $user['id'], (int) $next['id']) : null;

        // Ultima carrera puntuada del jugador.
        $recent = Database::one(
            "SELECT p.points_awarded, r.id AS race_id, r.name AS race_name,
                    r.racetrack, r.distance_m
               FROM predictions p
               JOIN races r ON r.id = p.race_id
              WHERE p.user_id = ? AND p.points_awarded IS NOT NULL
              ORDER BY r.predictions_close_at DESC LIMIT 1",
            [$user['id']]
        );

        view('player/dashboard', [
            'title'            => 'Inicio - ' . cfg('app_name'),
            'user'             => $user,
            'stats'            => $this->statsFor((int) $user['id']),
            'next'             => $next,
            'nextHorses'       => $nextHorses,
            'myNextPrediction' => $myNextPrediction,
            'recent'           => $recent,
            'leaderboard'      => RaceService::leaderboard(8),
        ]);
    }

    public function race(): void
    {
        require_login();
        $id   = (int) ($_GET['id'] ?? 0);
        $race = RaceService::find($id);
        if ($race === null) {
            http_response_code(404);
            view('error', ['title' => 'No encontrado', 'code' => 404, 'message' => 'Esa carrera no existe.']);
            return;
        }

        $myPrediction = $this->predictionFor((int) current_user()['id'], $id);
        $effective    = RaceService::effectiveStatus($race);
        $result       = $effective === 'finished' ? RaceService::result($id) : null;

        $breakdown = [];
        if ($result !== null && $myPrediction !== null) {
            $breakdown = Scoring::breakdown(
                [$myPrediction['pick1_horse_id'], $myPrediction['pick2_horse_id'], $myPrediction['pick3_horse_id']],
                [$result['first_horse_id'], $result['second_horse_id'], $result['third_horse_id']],
                RaceService::scoringRules()
            );
        }

        view('player/race', [
            'title'        => $race['name'] . ' - ' . cfg('app_name'),
            'race'         => $race,
            'horses'       => RaceService::horses($id),
            'effective'    => $effective,
            'myPrediction' => $myPrediction,
            'result'       => $result,
            'breakdown'    => $breakdown,
        ]);
    }

    /**
     * Registra o modifica una prediccion.
     *
     * PUNTO DE INTEGRIDAD DEL JUEGO: el servidor verifica que las predicciones
     * sigan abiertas en este preciso instante. Si la carrera ya cerro, la
     * jugada se rechaza, sin importar lo que diga el navegador.
     */
    public function predict(): void
    {
        require_login();
        csrf_check();
        $user   = current_user();
        $raceId = (int) ($_POST['race_id'] ?? 0);
        $race   = RaceService::find($raceId);

        if ($race === null) {
            flash('error', 'Carrera no encontrada.');
            redirect('/');
        }

        if (!RaceService::predictionsOpen($race)) {
            flash('error', 'Las predicciones de esta carrera estan cerradas. Tu jugada no fue registrada.');
            redirect('/race?id=' . $raceId);
        }

        $picks = [
            (int) ($_POST['pick1'] ?? 0),
            (int) ($_POST['pick2'] ?? 0),
            (int) ($_POST['pick3'] ?? 0),
        ];
        if (in_array(0, $picks, true) || count(array_unique($picks)) !== 3) {
            flash('error', 'Elegi tres caballos distintos para tu podio.');
            redirect('/race?id=' . $raceId);
        }

        $validIds = array_map(static fn($h) => (int) $h['id'], RaceService::horses($raceId));
        foreach ($picks as $pick) {
            if (!in_array($pick, $validIds, true)) {
                flash('error', 'Uno de los caballos elegidos no pertenece a esta carrera.');
                redirect('/race?id=' . $raceId);
            }
        }

        $existing = $this->predictionFor((int) $user['id'], $raceId);
        if ($existing !== null) {
            Database::run(
                'UPDATE predictions
                    SET pick1_horse_id = ?, pick2_horse_id = ?, pick3_horse_id = ?, updated_at = ?
                  WHERE id = ?',
                [$picks[0], $picks[1], $picks[2], now(), $existing['id']]
            );
            flash('success', 'Tu prediccion fue actualizada.');
        } else {
            Database::run(
                'INSERT INTO predictions
                    (user_id, race_id, pick1_horse_id, pick2_horse_id, pick3_horse_id, points_awarded, created_at, updated_at)
                 VALUES (?,?,?,?,?,?,?,?)',
                [$user['id'], $raceId, $picks[0], $picks[1], $picks[2], null, now(), now()]
            );
            flash('success', 'Tu prediccion fue registrada.');
        }
        redirect('/race?id=' . $raceId);
    }

    public function leaderboard(): void
    {
        require_login();
        view('player/leaderboard', [
            'title' => 'Clasificacion - ' . cfg('app_name'),
            'rows'  => RaceService::leaderboard(100),
            'meId'  => (int) current_user()['id'],
        ]);
    }

    public function profile(): void
    {
        require_login();
        $user = current_user();
        $history = Database::all(
            "SELECT p.points_awarded, p.created_at,
                    r.id AS race_id, r.name AS race_name, r.racetrack,
                    r.predictions_close_at, r.status AS race_status
               FROM predictions p
               JOIN races r ON r.id = p.race_id
              WHERE p.user_id = ?
              ORDER BY r.predictions_close_at DESC",
            [$user['id']]
        );
        view('player/profile', [
            'title'   => 'Mi perfil - ' . cfg('app_name'),
            'user'    => $user,
            'stats'   => $this->statsFor((int) $user['id']),
            'history' => $history,
        ]);
    }

    // --- Apoyo ---

    private function predictionFor(int $userId, int $raceId): ?array
    {
        return Database::one(
            'SELECT * FROM predictions WHERE user_id = ? AND race_id = ?',
            [$userId, $raceId]
        );
    }

    private function statsFor(int $userId): array
    {
        $row = Database::one(
            "SELECT COALESCE(SUM(points_awarded), 0) AS points,
                    COUNT(CASE WHEN points_awarded IS NOT NULL THEN 1 END) AS played,
                    COUNT(CASE WHEN points_awarded > 0 THEN 1 END) AS scored
               FROM predictions WHERE user_id = ?",
            [$userId]
        );
        $played = (int) $row['played'];
        return [
            'rank'     => RaceService::rankOf($userId),
            'points'   => (int) $row['points'],
            'played'   => $played,
            'accuracy' => $played > 0 ? (int) round(100 * (int) $row['scored'] / $played) : 0,
        ];
    }
}
