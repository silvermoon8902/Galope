<div class="auth-wrap">
  <div class="form-card">
    <div class="auth-title">Crear cuenta</div>
    <div class="auth-sub">Sumate a Galope y empeza a predecir carreras.</div>

    <form method="post" action="<?= e(base('/register')) ?>">
      <?= csrf_field() ?>
      <div class="field">
        <label>Nombre</label>
        <input type="text" name="name" value="<?= e(old('name')) ?>" required autofocus>
      </div>
      <div class="field">
        <label>Email</label>
        <input type="email" name="email" value="<?= e(old('email')) ?>" required>
      </div>
      <div class="field">
        <label>Contrasena</label>
        <input type="password" name="password" required>
        <div class="hint">Al menos 8 caracteres.</div>
      </div>
      <div class="field">
        <label>Repetir contrasena</label>
        <input type="password" name="password2" required>
      </div>
      <button type="submit" class="btn btn-block">Crear cuenta</button>
    </form>

    <div class="auth-alt">Ya tenes cuenta? <a href="<?= e(base('/login')) ?>">Ingresar</a></div>
  </div>
</div>
