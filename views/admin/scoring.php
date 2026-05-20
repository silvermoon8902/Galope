<h1 class="page-title">Reglas de puntuacion</h1>
<div class="page-sub">Define cuantos puntos vale cada acierto. Las reglas se cambian desde aca, sin tocar codigo.</div>

<form method="post" action="<?= e(base('/admin/scoring')) ?>" id="rules-form" class="form-card">
  <?= csrf_field() ?>

  <?php foreach ($rules as $rule): ?>
    <div class="rule">
      <div class="rl">
        <?= e($rule['label']) ?>
        <small><?= e($rule['description']) ?></small>
      </div>
      <input type="number" min="0" name="points[<?= e($rule['rule_key']) ?>]"
             data-key="<?= e($rule['rule_key']) ?>" value="<?= (int) $rule['points'] ?>">
      <div class="unit">puntos</div>
    </div>
  <?php endforeach; ?>

  <div class="pv">
    <div class="pv-card">
      <div class="pv-lbl">Podio perfecto (1-2-3 exacto)</div>
      <div class="pv-val"><span id="pvPerfect">0</span> <span>puntos</span></div>
    </div>
    <div class="pv-card">
      <div class="pv-lbl">Solo acierta el ganador</div>
      <div class="pv-val"><span id="pvWinner">0</span> <span>puntos</span></div>
    </div>
  </div>

  <p class="muted" style="font-size:12.5px;margin-bottom:14px">
    La vista previa se actualiza al instante. Las reglas guardadas se aplican a las carreras
    que se resuelvan de ahora en mas; para volver a puntuar una carrera ya finalizada,
    se puede corregir su resultado.
  </p>

  <button type="submit" class="btn">Guardar reglas</button>
</form>
