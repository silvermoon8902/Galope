<div class="auth-wrap">
  <div class="form-card">
    <div class="auth-title">Ingresar a Galope</div>
    <div class="auth-sub">Predeci el podio, suma puntos y escala en la tabla.</div>

    <form method="post" action="<?= e(base('/login')) ?>">
      <?= csrf_field() ?>
      <div class="field">
        <label>Email</label>
        <input type="email" name="email" value="<?= e(old('email')) ?>" required autofocus>
      </div>
      <div class="field">
        <label>Contrasena</label>
        <input type="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-block">Ingresar</button>
    </form>

    <div class="auth-alt">No tenes cuenta? <a href="<?= e(base('/register')) ?>">Crear una</a></div>

    <div class="demo-box">
      <b>Cuentas de demostracion</b><br>
      Jugador: jugador1@galope.test / galope-demo<br>
      Admin: admin@galope.test / galope-admin
    </div>
  </div>
</div>
