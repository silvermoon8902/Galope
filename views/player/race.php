<?php
/* Indice de caballos por id, para mostrar nombres. */
$byId = [];
foreach ($horses as $h) {
    $byId[(int) $h['id']] = $h;
}
$hn = static function ($id) use ($byId) {
    return isset($byId[(int) $id]) ? $byId[(int) $id]['name'] : '#' . (int) $id;
};
$fmtDiv = static function ($v) {
    return rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
};
?>

<a class="muted" href="<?= e(base('/')) ?>">&larr; Volver al inicio</a>

<div class="race" style="margin-top:12px">
  <div class="race-head">
    <div>
      <h2><?= e($race['name']) ?></h2>
      <div class="meta"><?= e($race['racetrack']) ?> &middot; <?= fmt_num($race['distance_m']) ?> metros &middot; <?= count($horses) ?> competidores</div>
    </div>
    <div class="countdown">
      <?php if ($effective === 'open'): ?>
        <div class="cd-lbl">Las predicciones cierran en</div>
        <div class="cd-time" data-remaining="<?= max(0, strtotime($race['predictions_close_at']) - time()) ?>">--:--:--</div>
        <div class="lock-pill">Bloqueo automatico al iniciar la carrera</div>
      <?php else: ?>
        <div class="cd-lbl">Estado</div>
        <div class="cd-time" style="font-size:19px"><?= e(RaceService::statusLabel($effective)) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="race-body">

    <?php if ($effective === 'open'): ?>
      <?php
        $currentMode = $myPrediction ? (string) $myPrediction['mode'] : '';
        $prePicks = $myPrediction
            ? array_values(array_filter([
                $myPrediction['pick1_horse_id'],
                $myPrediction['pick2_horse_id'],
                $myPrediction['pick3_horse_id'],
            ], static fn($v) => $v !== null))
            : [];
      ?>
      <?php if ($myPrediction): ?>
        <div class="notice locked" style="margin-bottom:14px">
          Ya tenes una jugada en modalidad <b><?= e(Scoring::modeLabel($currentMode)) ?></b>. Podes cambiarla hasta el cierre.
        </div>
      <?php endif; ?>

      <div class="instruct">
        Elegi una modalidad de juego y despues los caballos correspondientes. Tu jugada queda bloqueada cuando termina la cuenta regresiva.
      </div>

      <form id="predict-form" method="post" action="<?= e(base('/predict')) ?>"
            data-pre-mode="<?= e($currentMode) ?>"
            data-pre-picks="<?= e(implode(',', $prePicks)) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="race_id" value="<?= (int) $race['id'] ?>">
        <input type="hidden" name="mode" id="modeInput" value="">
        <input type="hidden" name="pick1" id="pick1">
        <input type="hidden" name="pick2" id="pick2">
        <input type="hidden" name="pick3" id="pick3">

        <div class="modes" id="modes">
          <?php foreach ($modes as $key => $m): ?>
            <div class="mode-card" data-mode="<?= e($key) ?>" data-horses="<?= count($m['stakes']) ?>">
              <div class="mode-name"><?= e($m['label']) ?></div>
              <div class="mode-stakes"><?= implode(' + ', $m['stakes']) ?> puntos</div>
              <div class="mode-desc"><?= e($m['short']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="slots" id="slots" style="display:none">
          <div class="slots-title">Tus apuestas</div>
          <div class="slots-row" id="slotsRow"></div>
        </div>

        <div id="horse-list">
          <?php foreach ($horses as $h): ?>
            <div class="horse selectable" data-id="<?= (int) $h['id'] ?>" data-name="<?= e($h['name']) ?>">
              <div class="num"><?= (int) $h['number'] ?></div>
              <div class="info">
                <div class="hname"><?= e($h['name']) ?></div>
                <div class="jockey">Jinete: <?= e($h['jockey']) ?></div>
              </div>
              <div class="form">Forma<b><?= e($h['form']) ?></b></div>
              <div class="pick-slot" data-slot>+</div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="confirm-row">
          <div class="pick-summary" id="pickSummary">Elegi una modalidad para empezar.</div>
          <button class="btn" id="confirmBtn" type="submit" data-lock-on-close disabled>
            <?= $myPrediction ? 'Actualizar jugada' : 'Confirmar jugada' ?>
          </button>
        </div>
        <div class="notice locked" id="lock-msg" style="display:none;margin-top:12px">
          Las predicciones se cerraron para esta carrera.
        </div>
      </form>

    <?php elseif ($effective === 'finished' && $result): ?>
      <div class="section-title">Resultado oficial</div>
      <div class="winner-card">
        <div class="winner-trophy">&#9733;</div>
        <div class="winner-info">
          <div class="winner-name"><?= e($hn($result['winner_horse_id'])) ?></div>
          <div class="winner-meta">Ganador &middot; dividendo oficial <b><?= e($fmtDiv($result['dividend'])) ?></b></div>
        </div>
      </div>

      <?php if ($myPrediction): ?>
        <?php $modeKey = (string) $myPrediction['mode']; ?>
        <div class="section-title">Tu jugada en <?= e(Scoring::modeLabel($modeKey)) ?></div>
        <div class="bd">
          <?php foreach ($breakdown as $line): ?>
            <div class="bd-row<?= $line['is_winner'] ? ' win' : '' ?>">
              <span>
                <span class="stake-pill"><?= $line['stake'] ?> pts</span>
                <?= e($hn($line['horse_id'])) ?>
              </span>
              <?php if ($line['is_winner']): ?>
                <b>+<?= $line['points'] ?></b>
              <?php else: ?>
                <span class="muted">no gano</span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="bd-total">
          <span>Total: <?= count($breakdown) ?> apuesta(s) por dividendo <?= e($fmtDiv($result['dividend'])) ?></span>
          <b><?= fmt_num((int) $myPrediction['points_awarded']) ?> puntos</b>
        </div>
      <?php else: ?>
        <div class="notice">No cargaste jugada para esta carrera.</div>
      <?php endif; ?>

    <?php elseif ($effective === 'locked'): ?>
      <div class="notice locked">Las predicciones de esta carrera estan cerradas. Esperando que se cargue el resultado oficial.</div>
      <?php if ($myPrediction): ?>
        <?php
          $modeKey = (string) $myPrediction['mode'];
          $stakes = Scoring::stakes($modeKey);
          $rawPicks = [$myPrediction['pick1_horse_id'], $myPrediction['pick2_horse_id'], $myPrediction['pick3_horse_id']];
          $myPicks = [];
          foreach ($rawPicks as $i => $pid) {
              if ($pid !== null) {
                  $myPicks[] = ['id' => (int) $pid, 'stake' => $stakes[$i] ?? 0];
              }
          }
        ?>
        <div class="section-title" style="margin-top:16px">Tu jugada (bloqueada) en <?= e(Scoring::modeLabel($modeKey)) ?></div>
        <div class="bd">
          <?php foreach ($myPicks as $pick): ?>
            <div class="bd-row">
              <span>
                <span class="stake-pill"><?= $pick['stake'] ?> pts</span>
                <?= e($hn($pick['id'])) ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="notice" style="margin-top:10px">No llegaste a cargar una jugada para esta carrera.</div>
      <?php endif; ?>

    <?php else: /* scheduled */ ?>
      <div class="notice">Las predicciones de esta carrera todavia no abrieron. Pronto vas a poder elegir tu modalidad y caballos.</div>
      <div class="section-title" style="margin-top:16px">Competidores</div>
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
    <?php endif; ?>

  </div>
</div>
