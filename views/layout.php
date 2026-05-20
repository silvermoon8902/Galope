<?php /** Layout principal. Recibe $content (HTML ya renderizado) y $page_title. */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_title) ?></title>
<link rel="stylesheet" href="<?= e(base('assets/css/app.css')) ?>">
</head>
<body>
<?php $u = current_user(); ?>
<header>
  <div class="wrap nav">
    <a class="brand" href="<?= e(base('/')) ?>"><span class="mark">G</span> Galope</a>

    <?php if ($u): ?>
      <nav class="topnav">
        <a href="<?= e(base('/')) ?>">Inicio</a>
        <a href="<?= e(base('/leaderboard')) ?>">Clasificacion</a>
        <a href="<?= e(base('/profile')) ?>">Mi perfil</a>
        <?php if ($u['role'] === 'admin'): ?>
          <a href="<?= e(base('/admin')) ?>">Panel</a>
        <?php endif; ?>
      </nav>
    <?php endif; ?>

    <div class="user">
      <?php if ($u): ?>
        <div class="avatar"><?= e(initials($u['name'])) ?></div>
        <div class="name"><?= e($u['name']) ?><span><?= $u['role'] === 'admin' ? 'Administracion' : 'Jugador' ?></span></div>
        <form method="post" action="<?= e(base('/logout')) ?>" class="inline-form">
          <?= csrf_field() ?>
          <button type="submit" class="link-btn">Salir</button>
        </form>
      <?php else: ?>
        <a class="btn-ghost-sm" href="<?= e(base('/login')) ?>">Ingresar</a>
        <a class="btn-sm" href="<?= e(base('/register')) ?>">Crear cuenta</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<?php partial('flash'); ?>

<main class="wrap page">
<?= $content ?>
</main>

<footer>Galope - juego de predicciones de carreras de caballos.</footer>
<script src="<?= e(base('assets/js/app.js')) ?>"></script>
</body>
</html>
