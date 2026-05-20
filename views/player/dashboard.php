<?php $eff = $next ? RaceService::effectiveStatus($next) : null; ?>

<h1 class="page-title">Hola, <?= e(explode(' ', trim($user['name']))[0]) ?></h1>
<div class="page-sub">Tu resumen en Galope.</div>

<div class="stats">
  <div class="stat"><div class="big"><?= $stats['rank'] ? '#' . $stats['rank'] : '-' ?></div><div class="lbl">Tu posicion</div></div>
  <div class="stat"><div class="big"><?= fmt_num($stats['points']) ?></div><div class="lbl">Puntos totales</div></div>
  <div class="stat"><div class="big"><?= (int) $stats['played'] ?></div><div class="lbl">Carreras jugadas</div></div>
  <div class="stat"><div class="big"><?= (int) $stats['accuracy'] ?>%</div><div class="lbl">Aciertos</div></div>
</div>

<div class="section-title">Proxima carrera</div>
<?php if ($next): ?>
  <div class="race">
    <div class="race-head">
      <div>
        <h2><?= e($next['name']) ?></h2>
        <div class="meta"><?= e($next['racetrack']) ?> &middot; <?= fmt_num($next['distance_m']) ?> metros &middot; <?= count($nextHorses) ?> competidores</div>
      </div>
      <div class="countdown">
        <?php if ($eff === 'open'): ?>
          <div class="cd-lbl">Las predicciones cierran en</div>
          <div class="cd-time" data-remaining="<?= max(0, strtotime($next['predictions_close_at']) - time()) ?>">--:--:--</div>
        <?php else: ?>
          <div class="cd-lbl">Estado</div>
          <div class="cd-time" style="font-size:19px"><?= e(RaceService::statusLabel($eff)) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <div class="race-body">
      <?php if ($eff === 'open'): ?>
        <p class="muted" style="margin-bottom:14px">
          <?= $myNextPrediction
              ? 'Ya cargaste tu prediccion. Podes cambiarla hasta que se cierre la carrera.'
              : 'Todavia no cargaste tu prediccion para esta carrera.' ?>
        </p>
        <a class="btn" href="<?= e(base('/race?id=' . $next['id'])) ?>">
          <?= $myNextPrediction ? 'Ver o cambiar mi prediccion' : 'Cargar mi prediccion' ?>
        </a>
      <?php elseif ($eff === 'scheduled'): ?>
        <p class="muted">Las predicciones todavia no abrieron. Vas a poder jugar pronto.</p>
        <a class="btn" href="<?= e(base('/race?id=' . $next['id'])) ?>">Ver la carrera</a>
      <?php else: ?>
        <p class="muted">Las predicciones de esta carrera estan cerradas.</p>
        <a class="btn" href="<?= e(base('/race?id=' . $next['id'])) ?>">Ver la carrera</a>
      <?php endif; ?>
    </div>
  </div>
<?php else: ?>
  <div class="notice">No hay carreras programadas en este momento. Volve mas tarde.</div>
<?php endif; ?>

<div class="grid2">
  <div class="panel">
    <h3>Tabla de clasificacion</h3>
    <div class="sub">Mejores jugadores de la temporada</div>
    <?php if ($leaderboard): ?>
      <?php foreach ($leaderboard as $i => $row): ?>
        <div class="lb-row<?= (int) $row['id'] === (int) $user['id'] ? ' me' : '' ?>">
          <div class="lb-rank<?= $i < 3 ? ' r' . ($i + 1) : '' ?>"><?= $i + 1 ?></div>
          <div class="lb-name"><?= e($row['name']) ?><?= (int) $row['id'] === (int) $user['id'] ? ' <small>(vos)</small>' : '' ?></div>
          <div class="lb-pts"><?= fmt_num($row['points']) ?></div>
        </div>
      <?php endforeach; ?>
      <div style="margin-top:12px"><a class="muted" href="<?= e(base('/leaderboard')) ?>">Ver tabla completa &rarr;</a></div>
    <?php else: ?>
      <div class="muted">Todavia no hay puntajes cargados.</div>
    <?php endif; ?>
  </div>

  <div class="panel">
    <h3>Resultado reciente</h3>
    <div class="sub">Tu ultima carrera puntuada</div>
    <?php if ($recent): ?>
      <div style="font-weight:700;font-size:14.5px"><?= e($recent['race_name']) ?></div>
      <div class="muted" style="font-size:12px;margin-bottom:12px"><?= e($recent['racetrack']) ?> &middot; <?= fmt_num($recent['distance_m']) ?> metros</div>
      <div class="res-score">
        <div class="pp">+<?= fmt_num($recent['points_awarded']) ?></div>
        <div class="pl">puntos ganados en esta carrera</div>
      </div>
      <div style="margin-top:12px"><a class="muted" href="<?= e(base('/race?id=' . $recent['race_id'])) ?>">Ver el detalle &rarr;</a></div>
    <?php else: ?>
      <div class="muted">Todavia no tenes carreras puntuadas. Carga tu primera prediccion.</div>
    <?php endif; ?>
  </div>
</div>
