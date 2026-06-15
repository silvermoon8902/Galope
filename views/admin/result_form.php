<a class="muted" href="<?= e(base('/admin')) ?>">&larr; Volver a carreras</a>
<h1 class="page-title" style="margin-top:10px">Cargar resultado oficial</h1>
<div class="page-sub"><?= e($race['name']) ?> &middot; <?= e($race['racetrack']) ?></div>

<?php if ($effective === 'scheduled' || $effective === 'open'): ?>
  <div class="notice">
    Las predicciones de esta carrera todavia no cerraron. El resultado se puede cargar
    una vez que la carrera arranca y las predicciones quedan bloqueadas.
  </div>
<?php else: ?>
  <form method="post" action="<?= e(base('/admin/result')) ?>" class="form-card">
    <?= csrf_field() ?>
    <input type="hidden" name="race_id" value="<?= (int) $race['id'] ?>">

    <p class="muted" style="font-size:13px;margin-bottom:16px">
      Carga el caballo ganador y el dividendo oficial del hipodromo. El motor de
      puntuacion calcula automaticamente para cada jugador: puntos apostados al
      ganador, multiplicados por el dividendo.
      <?= $result ? ' Esta carrera ya tiene un resultado: volver a cargarlo lo corrige y vuelve a puntuar todas las jugadas.' : '' ?>
    </p>

    <div class="form-grid">
      <div class="field">
        <label>Caballo ganador</label>
        <select name="winner_horse_id" required>
          <option value="">Elegir caballo</option>
          <?php foreach ($horses as $h):
            $sel = (isset($result['winner_horse_id']) && (int) $result['winner_horse_id'] === (int) $h['id']) ? ' selected' : '';
          ?>
            <option value="<?= (int) $h['id'] ?>"<?= $sel ?>>
              <?= e($h['number'] . ' - ' . $h['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Dividendo oficial</label>
        <input type="number" step="0.01" min="0.01" name="dividend"
               value="<?= e($result['dividend'] ?? '') ?>"
               placeholder="Ej. 5.00" required>
        <div class="hint">Multiplicador con el que se calculan los puntos. Ejemplo: dividendo 5 sobre 50 apostados da 250 puntos.</div>
      </div>
    </div>

    <button type="submit" class="btn"><?= $result ? 'Corregir resultado' : 'Confirmar resultado' ?></button>
  </form>
<?php endif; ?>
