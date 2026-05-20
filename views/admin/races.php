<div class="toolbar">
  <div>
    <h1 class="page-title">Carreras</h1>
    <div class="page-sub" style="margin-bottom:0">Gestion del ciclo de vida de cada carrera.</div>
  </div>
  <a class="btn-sm" href="<?= e(base('/admin/race')) ?>">+ Nueva carrera</a>
</div>

<div class="stats">
  <div class="stat"><div class="big"><?= (int) $stats['users'] ?></div><div class="lbl">Jugadores</div></div>
  <div class="stat"><div class="big"><?= (int) $stats['races'] ?></div><div class="lbl">Carreras</div></div>
  <div class="stat"><div class="big"><?= fmt_num($stats['predictions']) ?></div><div class="lbl">Predicciones</div></div>
  <div class="stat"><div class="big"><?= (int) $stats['awaiting'] ?></div><div class="lbl">Esperando resultado</div></div>
</div>

<div class="panel">
  <h3>Gestion de carreras</h3>
  <div class="sub">Programada, predicciones abiertas, bloqueada y finalizada.</div>

  <?php if ($rows): ?>
    <?php foreach ($rows as $r): ?>
      <div class="adm-race">
        <div class="rinfo">
          <div class="rname"><?= e($r['name']) ?></div>
          <div class="rmeta">
            <?= e($r['racetrack']) ?> &middot;
            cierre <?= e(fmt_dt($r['predictions_close_at'])) ?> &middot;
            <?= (int) $r['prediction_count'] ?> predicciones
          </div>
        </div>
        <span class="status <?= e($r['effective']) ?>"><?= e(RaceService::statusLabel($r['effective'])) ?></span>

        <?php if ($r['effective'] === 'scheduled'): ?>
          <form method="post" action="<?= e(base('/admin/race/open')) ?>" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="race_id" value="<?= (int) $r['id'] ?>">
            <button class="mini-btn" type="submit">Abrir predicciones</button>
          </form>
          <a class="mini-btn ghost" href="<?= e(base('/admin/race?id=' . $r['id'])) ?>">Editar</a>
        <?php elseif ($r['effective'] === 'open'): ?>
          <a class="mini-btn ghost" href="<?= e(base('/admin/race?id=' . $r['id'])) ?>">Editar</a>
        <?php elseif ($r['effective'] === 'locked'): ?>
          <a class="mini-btn" href="<?= e(base('/admin/result?id=' . $r['id'])) ?>">Cargar resultado</a>
        <?php else: ?>
          <a class="mini-btn ghost" href="<?= e(base('/admin/result?id=' . $r['id'])) ?>">Ver / corregir resultado</a>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="muted">No hay carreras cargadas. Crea la primera.</div>
  <?php endif; ?>

  <div class="legend">
    <b>Programada</b>: la carrera existe pero las predicciones todavia no abrieron &middot;
    <b>Predicciones abiertas</b>: los jugadores pueden predecir &middot;
    <b>Bloqueada</b>: cerro al iniciar la carrera, falta cargar el resultado &middot;
    <b>Finalizada</b>: resultado cargado y puntos asignados.
  </div>
</div>
