<a class="muted" href="<?= e(base('/admin')) ?>">&larr; Volver a carreras</a>
<h1 class="page-title" style="margin-top:10px"><?= $race ? 'Editar carrera' : 'Nueva carrera' ?></h1>
<div class="page-sub"><?= $race ? 'Modifica los datos de la carrera.' : 'Carga una carrera nueva y sus competidores.' ?></div>

<form method="post" action="<?= e(base('/admin/race')) ?>" class="form-card">
  <?= csrf_field() ?>
  <?php if ($race): ?><input type="hidden" name="id" value="<?= (int) $race['id'] ?>"><?php endif; ?>

  <div class="field">
    <label>Nombre de la carrera</label>
    <input type="text" name="name" value="<?= e(old('name', $race['name'] ?? '')) ?>" required>
  </div>

  <div class="form-grid">
    <div class="field">
      <label>Hipodromo</label>
      <input type="text" name="racetrack" value="<?= e(old('racetrack', $race['racetrack'] ?? '')) ?>" required>
    </div>
    <div class="field">
      <label>Distancia (metros)</label>
      <input type="number" name="distance_m" min="0" value="<?= e(old('distance_m', (string) ($race['distance_m'] ?? ''))) ?>">
    </div>
  </div>

  <div class="form-grid">
    <div class="field">
      <label>Fecha y hora de la carrera</label>
      <input type="datetime-local" name="scheduled_at" value="<?= e(old('scheduled_at', dtlocal($race['scheduled_at'] ?? ''))) ?>" required>
    </div>
    <div class="field">
      <label>Cierre de predicciones</label>
      <input type="datetime-local" name="predictions_close_at" value="<?= e(old('predictions_close_at', dtlocal($race['predictions_close_at'] ?? ''))) ?>" required>
      <div class="hint">A esta hora las predicciones se bloquean de forma automatica.</div>
    </div>
  </div>

  <div class="section-title" style="margin-top:10px">Caballos</div>
  <?php if ($hasPredictions): ?>
    <div class="notice">Esta carrera ya tiene predicciones cargadas, por eso los caballos no se pueden modificar.</div>
    <div style="margin-top:10px">
      <?php foreach ($horses as $h): ?>
        <div class="horse">
          <div class="num"><?= (int) $h['number'] ?></div>
          <div class="info">
            <div class="hname"><?= e($h['name']) ?></div>
            <div class="jockey">Jinete: <?= e($h['jockey']) ?></div>
          </div>
          <div class="form">Forma<b><?= e($h['form']) ?></b></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div id="horse-rows" class="horse-rows">
      <?php
      $rowsToShow = $horses ?: [['name' => '', 'jockey' => '', 'form' => '']];
      foreach ($rowsToShow as $h):
      ?>
        <div class="hrow">
          <input type="text" name="horse_name[]" placeholder="Nombre del caballo" value="<?= e($h['name']) ?>">
          <input type="text" name="horse_jockey[]" placeholder="Jinete" value="<?= e($h['jockey']) ?>">
          <input type="text" name="horse_form[]" placeholder="Forma (ej. 2-1-3)" value="<?= e($h['form']) ?>">
          <button type="button" class="row-remove" title="Quitar">&times;</button>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="mini-btn ghost" id="addHorse" style="margin-top:6px">+ Agregar caballo</button>
    <div class="hint" style="margin-top:6px">Carga al menos 3 caballos. Las filas vacias se ignoran.</div>
  <?php endif; ?>

  <div style="margin-top:20px">
    <button type="submit" class="btn"><?= $race ? 'Guardar cambios' : 'Crear carrera' ?></button>
  </div>
</form>
