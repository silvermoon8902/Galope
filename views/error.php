<div class="empty-state">
  <div class="empty-code"><?= e($code ?? 'Error') ?></div>
  <p><?= e($message ?? 'Algo salio mal.') ?></p>
  <a class="btn" href="<?= e(base('/')) ?>">Volver al inicio</a>
</div>
