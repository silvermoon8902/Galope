<?php
/** Panel de administracion: carreras, resultados, reglas de puntuacion y usuarios. */
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

        // Recolecta los caballos cargados en el formulario.
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

            // Los caballos solo se reescriben mientras la carrera no tenga predicciones,
            // para no dejar predicciones apuntando a caballos borrados.
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

        $picks = [
            (int) ($_POST['first'] ?? 0),
            (int) ($_POST['second'] ?? 0),
            (int) ($_POST['third'] ?? 0),
        ];
        if (in_array(0, $picks, true) || count(array_unique($picks)) !== 3) {
            flash('error', 'Selecciona tres caballos distintos para el podio.');
            redirect('/admin/result?id=' . $raceId);
        }
        $validIds = array_map(static fn($h) => (int) $h['id'], RaceService::horses($raceId));
        foreach ($picks as $pick) {
            if (!in_array($pick, $validIds, true)) {
                flash('error', 'Un caballo del resultado no pertenece a esta carrera.');
                redirect('/admin/result?id=' . $raceId);
            }
        }

        $count = RaceService::loadResult(
            $raceId, $picks[0], $picks[1], $picks[2], (int) current_user()['id'], 'manual'
        );
        flash('success', "Resultado cargado. El motor de puntuacion evaluo $count prediccion(es) y actualizo la clasificacion.");
        redirect('/admin');
    }

    public function scoring(): void
    {
        require_admin();
        view('admin/scoring', [
            'title' => 'Reglas de puntuacion - Panel',
            'rules' => Database::all('SELECT * FROM scoring_rules ORDER BY sort_order'),
        ]);
    }

    public function saveScoring(): void
    {
        require_admin();
        csrf_check();
        $points = (array) ($_POST['points'] ?? []);
        foreach (Database::all('SELECT rule_key FROM scoring_rules') as $rule) {
            $key = $rule['rule_key'];
            if (isset($points[$key])) {
                Database::run(
                    'UPDATE scoring_rules SET points = ?, updated_at = ? WHERE rule_key = ?',
                    [max(0, (int) $points[$key]), now(), $key]
                );
            }
        }
        flash('success', 'Reglas de puntuacion guardadas. Se aplican a las carreras que se resuelvan de ahora en mas.');
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
