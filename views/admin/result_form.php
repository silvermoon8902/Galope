<a class="muted" href="<?= e(base('/admin')) ?>">&larr; Volver a carreras</a>
<h1 class="page-title" style="margin-top:10px">Cargar resultado</h1>
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
      Al confirmar, el motor de puntuacion evalua todas las predicciones cerradas y actualiza
      la tabla de clasificacion.
      <?= $result ? ' Esta carrera ya tiene un resultado: volver a cargarlo lo corrige y vuelve a puntuar todas las predicciones.' : '' ?>
    </p>

    <div class="form-grid-3">
      <?php
      $slots = [
          ['first',  '1.er puesto', (int) ($result['first_horse_id'] ?? 0)],
          ['second', '2.do puesto', (int) ($result['second_horse_id'] ?? 0)],
          ['third',  '3.er puesto', (int) ($result['third_horse_id'] ?? 0)],
      ];
      foreach ($slots as [$name, $label, $current]):
      ?>
        <div class="field">
          <label><?= $label ?></label>
          <select name="<?= $name ?>" required>
            <option value="">Elegir caballo</option>
            <?php foreach ($horses as $h): ?>
              <option value="<?= (int) $h['id'] ?>"<?= $current === (int) $h['id'] ? ' selected' : '' ?>>
                <?= e($h['number'] . ' - ' . $h['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endforeach; ?>
    </div>

    <button type="submit" class="btn"><?= $result ? 'Corregir resultado' : 'Confirmar resultado' ?></button>
  </form>
<?php endif; ?>
