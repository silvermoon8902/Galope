<?php $messages = flashes(); ?>
<?php if (!empty($messages)): ?>
<div class="wrap flash-wrap">
  <?php foreach ($messages as $m): ?>
    <div class="flash flash-<?= e($m['type']) ?>"><?= e($m['msg']) ?></div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
