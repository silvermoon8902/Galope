<h1 class="page-title">Tabla de clasificacion</h1>
<div class="page-sub">Suma de puntos de todos los jugadores a lo largo de la temporada.</div>

<div class="panel">
  <?php if ($rows): ?>
    <?php foreach ($rows as $i => $row): ?>
      <div class="lb-row<?= (int) $row['id'] === $meId ? ' me' : '' ?>">
        <div class="lb-rank<?= $i < 3 ? ' r' . ($i + 1) : '' ?>"><?= $i + 1 ?></div>
        <div class="lb-name"><?= e($row['name']) ?><?= (int) $row['id'] === $meId ? ' <small>(vos)</small>' : '' ?></div>
        <div class="lb-meta"><?= (int) $row['races_played'] ?> carreras</div>
        <div class="lb-pts"><?= fmt_num($row['points']) ?></div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="muted">Todavia no hay puntajes cargados.</div>
  <?php endif; ?>
</div>
