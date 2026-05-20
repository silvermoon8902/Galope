<?php
/* Indice de caballos por id, para mostrar nombres. */
$byId = [];
foreach ($horses as $h) {
    $byId[(int) $h['id']] = $h;
}
$hn = static function ($id) use ($byId) {
    return isset($byId[(int) $id]) ? $byId[(int) $id]['name'] : '#' . (int) $id;
};
$medal = ['g', 's', 'b'];
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
      <?php if ($myPrediction): ?>
        <div class="notice locked" style="margin-bottom:14px">Ya tenes una prediccion cargada. Podes cambiarla hasta el cierre.</div>
      <?php endif; ?>
      <div class="instruct">Elegi tu podio: toca los caballos en orden, primero el ganador, despues el segundo y el tercero. Tu prediccion queda bloqueada cuando termina la cuenta regresiva.</div>

      <?php $pre = $myPrediction
          ? implode(',', [$myPrediction['pick1_horse_id'], $myPrediction['pick2_horse_id'], $myPrediction['pick3_horse_id']])
          : ''; ?>
      <form id="predict-form" method="post" action="<?= e(base('/predict')) ?>" data-pre="<?= e($pre) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="race_id" value="<?= (int) $race['id'] ?>">
        <input type="hidden" name="pick1" id="pick1">
        <input type="hidden" name="pick2" id="pick2">
        <input type="hidden" name="pick3" id="pick3">

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
          <div class="pick-summary" id="pickSummary">Todavia no elegiste tu podio.</div>
          <button class="btn" id="confirmBtn" type="submit" data-lock-on-close disabled>
            <?= $myPrediction ? 'Actualizar prediccion' : 'Confirmar prediccion' ?>
          </button>
        </div>
        <div class="notice locked" id="lock-msg" style="display:none;margin-top:12px">
          Las predicciones se cerraron para esta carrera.
        </div>
      </form>

    <?php elseif ($effective === 'finished' && $result): ?>
      <div class="section-title">Resultado oficial</div>
      <div class="podium">
        <div class="pod g"><span class="medal">1</span> <?= e($hn($result['first_horse_id'])) ?></div>
        <div class="pod s"><span class="medal">2</span> <?= e($hn($result['second_horse_id'])) ?></div>
        <div class="pod b"><span class="medal">3</span> <?= e($hn($result['third_horse_id'])) ?></div>
      </div>

      <?php if ($myPrediction): ?>
        <?php
          $resOrder = [$result['first_horse_id'], $result['second_horse_id'], $result['third_horse_id']];
          $myPicks  = [$myPrediction['pick1_horse_id'], $myPrediction['pick2_horse_id'], $myPrediction['pick3_horse_id']];
        ?>
        <div class="section-title">Tu prediccion</div>
        <div class="podium">
          <?php foreach ($myPicks as $i => $pid): $exact = (int) $resOrder[$i] === (int) $pid; ?>
            <div class="pod <?= $medal[$i] ?>">
              <span class="medal"><?= $i + 1 ?></span> <?= e($hn($pid)) ?>
              <span class="hit <?= $exact ? 'ok' : 'no' ?>"><?= $exact ? 'Acertaste' : 'Fallaste' ?></span>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="section-title">Como sumaste tus puntos</div>
        <?php if ($breakdown): ?>
          <?php foreach ($breakdown as $line): ?>
            <div class="bd-row"><span><?= e($line['label']) ?></span><b>+<?= (int) $line['points'] ?></b></div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="muted" style="font-size:13px;padding:8px 0">Esta vez tu prediccion no sumo puntos.</div>
        <?php endif; ?>
        <div class="bd-total"><span>Total de la carrera</span><b><?= fmt_num((int) $myPrediction['points_awarded']) ?> puntos</b></div>
      <?php else: ?>
        <div class="notice">No cargaste una prediccion para esta carrera.</div>
      <?php endif; ?>

    <?php elseif ($effective === 'locked'): ?>
      <div class="notice locked">Las predicciones de esta carrera estan cerradas. Esperando que se cargue el resultado oficial.</div>
      <?php if ($myPrediction): ?>
        <div class="section-title" style="margin-top:16px">Tu prediccion (bloqueada)</div>
        <div class="podium">
          <?php foreach ([$myPrediction['pick1_horse_id'], $myPrediction['pick2_horse_id'], $myPrediction['pick3_horse_id']] as $i => $pid): ?>
            <div class="pod <?= $medal[$i] ?>"><span class="medal"><?= $i + 1 ?></span> <?= e($hn($pid)) ?></div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="notice" style="margin-top:10px">No llegaste a cargar una prediccion para esta carrera.</div>
      <?php endif; ?>

    <?php else: /* scheduled */ ?>
      <div class="notice">Las predicciones de esta carrera todavia no abrieron. Pronto vas a poder cargar tu podio.</div>
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
