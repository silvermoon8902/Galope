<?php
/** Registro, ingreso y cierre de sesion. */
class AuthController
{
    public function showLogin(): void
    {
        if (current_user()) {
            redirect('/');
        }
        view('auth/login', ['title' => 'Ingresar - ' . cfg('app_name')]);
    }

    public function login(): void
    {
        csrf_check();
        $email    = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $user = $email !== '' ? Auth::findByEmail($email) : null;

        if ($user === null || !Auth::verify($user, $password)) {
            flash('error', 'Email o contrasena incorrectos.');
            keep_old($_POST);
            redirect('/login');
        }
        if ($user['status'] !== 'active') {
            flash('error', 'Tu cuenta esta suspendida. Escribi al administrador.');
            redirect('/login');
        }

        Auth::login((int) $user['id']);
        flash('success', 'Hola de nuevo, ' . $user['name'] . '.');
        redirect($user['role'] === 'admin' ? '/admin' : '/');
    }

    public function showRegister(): void
    {
        if (current_user()) {
            redirect('/');
        }
        view('auth/register', ['title' => 'Crear cuenta - ' . cfg('app_name')]);
    }

    public function register(): void
    {
        csrf_check();
        $name      = trim((string) ($_POST['name'] ?? ''));
        $email     = trim((string) ($_POST['email'] ?? ''));
        $password  = (string) ($_POST['password'] ?? '');
        $password2 = (string) ($_POST['password2'] ?? '');

        $errors = [];
        if (mb_strlen($name) < 2) {
            $errors[] = 'Ingresa tu nombre.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Ingresa un email valido.';
        }
        if (mb_strlen($password) < 8) {
            $errors[] = 'La contrasena debe tener al menos 8 caracteres.';
        }
        if ($password !== $password2) {
            $errors[] = 'Las contrasenas no coinciden.';
        }
        if (empty($errors) && Auth::emailTaken($email)) {
            $errors[] = 'Ya existe una cuenta con ese email.';
        }

        if (!empty($errors)) {
            foreach ($errors as $msg) {
                flash('error', $msg);
            }
            keep_old($_POST);
            redirect('/register');
        }

        $userId = Auth::createPlayer($name, $email, $password);
        Auth::login($userId);
        flash('success', 'Cuenta creada. Te damos la bienvenida a Galope.');
        redirect('/');
    }

    public function logout(): void
    {
        csrf_check();
        Auth::logout();
        flash('success', 'Cerraste sesion.');
        redirect('/login');
    }
}
