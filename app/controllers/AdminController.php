<?php
/** Panel de administracion: carreras, resultados, reglas del juego y usuarios. */
class AdminController
{
    public function races(): void
    {
        require_admin();
        $rows = [];
        foreach (RaceService::all() as $race) {
            $race['effective']        = RaceService::effectiveStatus($race);
            $race['prediction_count'] = (int) Database::value(
                'SELECT COUNT(*) FROM predictions WHERE race_id = ?', [$race['id']]
            );
            $rows[] = $race;
        }

        $awaiting = 0;
        foreach ($rows as $r) {
            if ($r['effective'] === 'locked') {
                $awaiting++;
            }
        }

        view('admin/races', [
            'title' => 'Carreras - Panel',
            'rows'  => $rows,
            'stats' => [
                'users'       => (int) Database::value("SELECT COUNT(*) FROM users WHERE role = 'player'"),
                'races'       => count($rows),
                'predictions' => (int) Database::value('SELECT COUNT(*) FROM predictions'),
                'awaiting'    => $awaiting,
            ],
        ]);
    }

    public function raceForm(): void
    {
        require_admin();
        $id   = (int) ($_GET['id'] ?? 0);
        $race = $id ? RaceService::find($id) : null;

        view('admin/race_form', [
            'title'          => ($race ? 'Editar carrera' : 'Nueva carrera') . ' - Panel',
            'race'           => $race,
            'horses'         => $race ? RaceService::horses($id) : [],
            'hasPredictions' => $race ? $this->raceHasPredictions($id) : false,
        ]);
    }

    public function saveRace(): void
    {
        require_admin();
        csrf_check();

        $id        = (int) ($_POST['id'] ?? 0);
        $name      = trim((string) ($_POST['name'] ?? ''));
        $track     = trim((string) ($_POST['racetrack'] ?? ''));
        $distance  = (int) ($_POST['distance_m'] ?? 0);
        $scheduled = $this->parseDt($_POST['scheduled_at'] ?? '');
        $close     = $this->parseDt($_POST['predictions_close_at'] ?? '');

        $existing       = $id ? RaceService::find($id) : null;
        $hasPredictions = $id ? $this->raceHasPredictions($id) : false;

        $horses = [];
        foreach ((array) ($_POST['horse_name'] ?? []) as $i => $hn) {
            $hn = trim((string) $hn);
            if ($hn === '') {
                continue;
            }
            $horses[] = [
                'name'   => $hn,
                'jockey' => trim((string) ($_POST['horse_jockey'][$i] ?? '')),
                'form'   => trim((string) ($_POST['horse_form'][$i] ?? '')),
            ];
        }

        $errors = [];
        if ($name === '')  { $errors[] = 'El nombre de la carrera es obligatorio.'; }
        if ($track === '') { $errors[] = 'El hipodromo es obligatorio.'; }
        if ($scheduled === null) { $errors[] = 'La fecha y hora de la carrera son invalidas.'; }
        if ($close === null)     { $errors[] = 'La fecha y hora de cierre de predicciones son invalidas.'; }
        if ($scheduled !== null && $close !== null && $close > $scheduled) {
            $errors[] = 'El cierre de predicciones debe ser anterior o igual a la hora de la carrera.';
        }
        if (!$hasPredictions && count($horses) < 3) {
            $errors[] = 'Carga al menos 3 caballos para la carrera.';
        }

        if (!empty($errors)) {
            foreach ($errors as $msg) {
                flash('error', $msg);
            }
            keep_old($_POST);
            redirect($id ? '/admin/race?id=' . $id : '/admin/race');
        }

        Database::tx(function () use ($existing, $id, $name, $track, $distance, $scheduled, $close, $horses, $hasPredictions) {
            if ($existing !== null) {
                Database::run(
                    'UPDATE races
                        SET name = ?, racetrack = ?, distance_m = ?, scheduled_at = ?, predictions_close_at = ?
                      WHERE id = ?',
                    [$name, $track, $distance, $scheduled, $close, $id]
                );
                $raceId = $id;
            } else {
                $raceId = Database::insert(
                    'INSERT INTO races
                        (name, racetrack, distance_m, scheduled_at, predictions_close_at, status, created_at)
                     VALUES (?,?,?,?,?,?,?)',
                    [$name, $track, $distance, $scheduled, $close, 'scheduled', now()]
                );
            }

            if (!$hasPredictions) {
                Database::run('DELETE FROM horses WHERE race_id = ?', [$raceId]);
                $number = 1;
                foreach ($horses as $h) {
                    Database::run(
                        'INSERT INTO horses (race_id, number, name, jockey, form, created_at)
                         VALUES (?,?,?,?,?,?)',
                        [$raceId, $number, $h['name'], $h['jockey'], $h['form'], now()]
                    );
                    $number++;
                }
            }
        });

        flash('success', $existing !== null ? 'Carrera actualizada.' : 'Carrera creada.');
        redirect('/admin');
    }

    public function openRace(): void
    {
        require_admin();
        csrf_check();
        $raceId = (int) ($_POST['race_id'] ?? 0);
        $race   = RaceService::find($raceId);
        if ($race === null) {
            flash('error', 'Carrera no encontrada.');
            redirect('/admin');
        }
        if (RaceService::effectiveStatus($race) !== 'scheduled') {
            flash('error', 'Solo se pueden abrir carreras programadas que aun no cerraron.');
            redirect('/admin');
        }
        Database::run('UPDATE races SET status = ? WHERE id = ?', ['open', $raceId]);
        flash('success', 'Predicciones abiertas para "' . $race['name'] . '".');
        redirect('/admin');
    }

    public function resultForm(): void
    {
        require_admin();
        $id   = (int) ($_GET['id'] ?? 0);
        $race = RaceService::find($id);
        if ($race === null) {
            http_response_code(404);
            view('error', ['title' => 'No encontrado', 'code' => 404, 'message' => 'Esa carrera no existe.']);
            return;
        }
        view('admin/result_form', [
            'title'     => 'Cargar resultado - Panel',
            'race'      => $race,
            'horses'    => RaceService::horses($id),
            'result'    => RaceService::result($id),
            'effective' => RaceService::effectiveStatus($race),
        ]);
    }

    /**
     * Guarda el ganador + el dividendo oficial y dispara el motor de puntuacion.
     */
    public function saveResult(): void
    {
        require_admin();
        csrf_check();
        $raceId = (int) ($_POST['race_id'] ?? 0);
        $race   = RaceService::find($raceId);
        if ($race === null) {
            flash('error', 'Carrera no encontrada.');
            redirect('/admin');
        }

        $effective = RaceService::effectiveStatus($race);
        if ($effective === 'scheduled' || $effective === 'open') {
            flash('error', 'No se puede cargar el resultado: las predicciones todavia no cerraron.');
            redirect('/admin/result?id=' . $raceId);
        }

        $winnerId = (int) ($_POST['winner_horse_id'] ?? 0);
        $dividend = (float) str_replace(',', '.', (string) ($_POST['dividend'] ?? '0'));

        if ($winnerId === 0) {
            flash('error', 'Selecciona el caballo ganador.');
            redirect('/admin/result?id=' . $raceId);
        }
        if ($dividend <= 0) {
            flash('error', 'El dividendo oficial debe ser mayor a cero.');
            redirect('/admin/result?id=' . $raceId);
        }
        $validIds = array_map(static fn($h) => (int) $h['id'], RaceService::horses($raceId));
        if (!in_array($winnerId, $validIds, true)) {
            flash('error', 'El caballo ganador no pertenece a esta carrera.');
            redirect('/admin/result?id=' . $raceId);
        }

        $count = RaceService::loadResult(
            $raceId, $winnerId, $dividend, (int) current_user()['id'], 'manual'
        );
        flash(
            'success',
            "Resultado cargado con dividendo $dividend. El motor evaluo $count prediccion(es) y actualizo la clasificacion."
        );
        redirect('/admin');
    }

    public function scoring(): void
    {
        require_admin();
        view('admin/scoring', [
            'title' => 'Reglas del juego - Panel',
            'modes' => Scoring::modes(),
        ]);
    }

    /** Conservado por compatibilidad: en el modelo actual las reglas son fijas. */
    public function saveScoring(): void
    {
        require_admin();
        csrf_check();
        flash('info', 'Las reglas del juego estan definidas por las modalidades Full / Dual / Smart Point y el dividendo oficial del hipodromo. No hay valores editables aqui.');
        redirect('/admin/scoring');
    }

    public function users(): void
    {
        require_admin();
        $users = Database::all(
            "SELECT u.id, u.name, u.email, u.role, u.status, u.created_at,
                    COALESCE(SUM(p.points_awarded), 0) AS points,
                    COUNT(CASE WHEN p.points_awarded IS NOT NULL THEN 1 END) AS races_played
               FROM users u
               LEFT JOIN predictions p ON p.user_id = u.id
              GROUP BY u.id, u.name, u.email, u.role, u.status, u.created_at
              ORDER BY u.role DESC, u.name ASC"
        );
        view('admin/users', ['title' => 'Usuarios - Panel', 'users' => $users]);
    }

    public function toggleUser(): void
    {
        require_admin();
        csrf_check();
        $userId = (int) ($_POST['user_id'] ?? 0);
        $target = Database::one('SELECT * FROM users WHERE id = ?', [$userId]);

        if ($target === null) {
            flash('error', 'Usuario no encontrado.');
            redirect('/admin/users');
        }
        if ((int) $target['id'] === (int) current_user()['id']) {
            flash('error', 'No podes cambiar el estado de tu propia cuenta.');
            redirect('/admin/users');
        }
        if ($target['role'] === 'admin') {
            flash('error', 'No se puede suspender a un administrador.');
            redirect('/admin/users');
        }

        $newStatus = $target['status'] === 'active' ? 'suspended' : 'active';
        Database::run('UPDATE users SET status = ? WHERE id = ?', [$newStatus, $userId]);
        flash('success', $newStatus === 'suspended' ? 'Cuenta suspendida.' : 'Cuenta reactivada.');
        redirect('/admin/users');
    }

    // --- Apoyo ---

    private function raceHasPredictions(int $raceId): bool
    {
        return (int) Database::value('SELECT COUNT(*) FROM predictions WHERE race_id = ?', [$raceId]) > 0;
    }

    private function parseDt($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $ts = strtotime($value);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }
}
