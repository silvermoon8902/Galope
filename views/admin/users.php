<h1 class="page-title">Usuarios</h1>
<div class="page-sub">Perfiles registrados, puntaje acumulado y estado de la cuenta.</div>

<div class="panel">
  <table class="table">
    <thead>
      <tr>
        <th>Jugador</th>
        <th>Rol</th>
        <th class="num">Puntos</th>
        <th class="num">Carreras</th>
        <th>Estado</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td>
            <?= e($u['name']) ?>
            <small><?= e($u['email']) ?></small>
          </td>
          <td>
            <?php if ($u['role'] === 'admin'): ?>
              <span class="tag role">Admin</span>
            <?php else: ?>
              <span class="muted">Jugador</span>
            <?php endif; ?>
          </td>
          <td class="num"><?= fmt_num($u['points']) ?></td>
          <td class="num"><?= (int) $u['races_played'] ?></td>
          <td><span class="tag <?= e($u['status']) ?>"><?= $u['status'] === 'active' ? 'Activo' : 'Suspendido' ?></span></td>
          <td class="num">
            <?php if ($u['role'] !== 'admin' && (int) $u['id'] !== (int) current_user()['id']): ?>
              <form method="post" action="<?= e(base('/admin/users/toggle')) ?>" class="inline-form">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                <button class="mini-btn <?= $u['status'] === 'active' ? 'danger' : 'ghost' ?>" type="submit">
                  <?= $u['status'] === 'active' ? 'Suspender' : 'Reactivar' ?>
                </button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
