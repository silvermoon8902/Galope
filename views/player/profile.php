<h1 class="page-title">Mi perfil</h1>
<div class="page-sub"><?= e($user['name']) ?> &middot; <?= e($user['email']) ?></div>

<div class="stats">
  <div class="stat"><div class="big"><?= $stats['rank'] ? '#' . $stats['rank'] : '-' ?></div><div class="lbl">Posicion</div></div>
  <div class="stat"><div class="big"><?= fmt_num($stats['points']) ?></div><div class="lbl">Puntos totales</div></div>
  <div class="stat"><div class="big"><?= (int) $stats['played'] ?></div><div class="lbl">Carreras jugadas</div></div>
  <div class="stat"><div class="big"><?= (int) $stats['accuracy'] ?>%</div><div class="lbl">Aciertos</div></div>
</div>

<div class="panel">
  <h3>Historial de predicciones</h3>
  <div class="sub">Todas tus carreras jugadas, de la mas reciente a la mas antigua</div>
  <?php if ($history): ?>
    <table class="table">
      <thead>
        <tr><th>Carrera</th><th>Cierre de predicciones</th><th>Estado</th><th class="num">Puntos</th></tr>
      </thead>
      <tbody>
        <?php foreach ($history as $h): ?>
          <tr>
            <td>
              <a href="<?= e(base('/race?id=' . $h['race_id'])) ?>"><?= e($h['race_name']) ?></a>
              <small><?= e($h['racetrack']) ?></small>
            </td>
            <td><?= e(fmt_dt($h['predictions_close_at'])) ?></td>
            <td>
              <?php if ($h['points_awarded'] !== null): ?>
                Puntuada
              <?php elseif ($h['race_status'] === 'finished'): ?>
                Finalizada
              <?php else: ?>
                <span class="muted">En juego</span>
              <?php endif; ?>
            </td>
            <td class="num"><?= $h['points_awarded'] !== null ? fmt_num($h['points_awarded']) : '-' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="muted">Todavia no jugaste ninguna carrera.</div>
  <?php endif; ?>
</div>
