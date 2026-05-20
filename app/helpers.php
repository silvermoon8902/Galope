<?php
/**
 * Funciones de apoyo: configuracion, vistas, flash, CSRF y autenticacion.
 */

function cfg(?string $key = null, $default = null)
{
    $config = $GLOBALS['galope_config'] ?? [];
    if ($key === null) {
        return $config;
    }
    return $config[$key] ?? $default;
}

/** Escapa texto para insertar en HTML. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Construye una URL respetando el prefijo base_url. */
function base(string $path = ''): string
{
    $b = rtrim((string) cfg('base_url', ''), '/');
    if ($path === '') {
        return $b === '' ? '/' : $b;
    }
    return $b . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . base($path));
    exit;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function fmt_dt($value): string
{
    $ts = strtotime((string) $value);
    return $ts ? date('d/m/Y H:i', $ts) : (string) $value;
}

/** Formatea un numero entero con punto de miles (estilo es-AR). */
function fmt_num($n): string
{
    return number_format((float) $n, 0, ',', '.');
}

// --- Render de vistas ---

function view(string $name, array $data = [], string $layout = 'layout'): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require GALOPE_ROOT . '/views/' . $name . '.php';
    $content = ob_get_clean();
    $page_title = $data['title'] ?? cfg('app_name');
    require GALOPE_ROOT . '/views/' . $layout . '.php';
}

function partial(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require GALOPE_ROOT . '/views/partials/' . $name . '.php';
}

// --- Mensajes flash y repoblado de formularios ---

function flash(string $type, string $msg): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'msg' => $msg];
}

function flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

/** Guarda la entrada del formulario para repoblarlo tras un redirect. */
function keep_old(array $input): void
{
    unset($input['password'], $input['password2'], $input['_csrf']);
    $_SESSION['_old'] = $input;
}

function old(string $key, string $default = ''): string
{
    return (string) ($GLOBALS['galope_old'][$key] ?? $default);
}

// --- Proteccion CSRF ---

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!is_string($token) || $token === '' || !hash_equals($_SESSION['_csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('Sesion expirada o token de seguridad invalido. Volve atras y reintenta.');
    }
}

// --- Autenticacion ---

function current_user(): ?array
{
    static $cached = false;
    if ($cached !== false) {
        return $cached;
    }
    $id = $_SESSION['uid'] ?? null;
    $user = $id ? Database::one('SELECT * FROM users WHERE id = ?', [$id]) : null;
    if ($user && $user['status'] !== 'active') {
        $user = null; // una cuenta suspendida no tiene sesion activa
    }
    $cached = $user;
    return $cached;
}

function is_admin(): bool
{
    $u = current_user();
    return $u !== null && $u['role'] === 'admin';
}

function require_login(): void
{
    if (current_user() === null) {
        flash('error', 'Inicia sesion para continuar.');
        redirect('/login');
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        exit('Acceso restringido al panel de administracion.');
    }
}
