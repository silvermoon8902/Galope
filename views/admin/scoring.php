<h1 class="page-title">Reglas del juego</h1>
<div class="page-sub">Modalidades y formula de puntuacion. Las reglas son las definidas con el cliente.</div>

<div class="panel">
  <h3>Modalidades de juego</h3>
  <div class="sub">Por cada carrera, el jugador elige UNA modalidad. Todas se juegan unicamente al caballo ganador.</div>

  <?php foreach ($modes as $key => $m): ?>
    <div class="rule">
      <div class="rl">
        <?= e($m['label']) ?>
        <small><?= e($m['description']) ?></small>
      </div>
      <div class="mode-stakes-inline"><?= implode(' + ', $m['stakes']) ?> pts</div>
    </div>
  <?php endforeach; ?>
</div>

<div class="panel">
  <h3>Calculo de puntos</h3>
  <div class="sub">La formula que aplica el motor a cada jugada cuando se carga el resultado.</div>

  <p style="font-size:14px;line-height:1.7">
    Cuando se publica el dividendo oficial del hipodromo del caballo ganador, los puntos del jugador son:
  </p>

  <div class="pv">
    <div class="pv-card" style="grid-column:1 / -1;text-align:center">
      <div class="pv-lbl">Formula</div>
      <div class="pv-val" style="font-size:22px">
        puntos apostados al ganador
        <span style="color:var(--muted);font-weight:600">x</span>
        dividendo oficial
      </div>
    </div>
  </div>

  <p style="font-size:13px;color:var(--muted);margin-top:14px">
    Si el caballo ganador no esta entre los caballos elegidos, la jugada suma cero, sin importar
    la modalidad ni cuantos caballos se hayan elegido.
  </p>

  <div style="background:#f7f4ec;border-left:3px solid var(--gold);padding:12px 14px;border-radius:0 8px 8px 0;margin-top:14px;font-size:13.5px">
    <b>Ejemplo:</b> el jugador apuesta Full Point con 50 puntos al caballo 4.
    El 4 gana con dividendo oficial 5. El jugador suma 50 x 5 = 250 puntos en esa carrera.
  </div>
</div>
