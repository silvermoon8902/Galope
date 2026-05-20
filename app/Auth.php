<?php
/**
 * Registro, verificacion de credenciales y manejo de la sesion.
 * Las contrasenas se guardan con password_hash (bcrypt por defecto).
 */
class Auth
{
    public static function findByEmail(string $email): ?array
    {
        return Database::one('SELECT * FROM users WHERE email = ?', [self::normEmail($email)]);
    }

    public static function emailTaken(string $email): bool
    {
        return self::findByEmail($email) !== null;
    }

    public static function createPlayer(string $name, string $email, string $password): int
    {
        return Database::insert(
            'INSERT INTO users (name, email, password_hash, role, status, created_at)
             VALUES (?,?,?,?,?,?)',
            [
                trim($name),
                self::normEmail($email),
                password_hash($password, PASSWORD_DEFAULT),
                'player',
                'active',
                now(),
            ]
        );
    }

    public static function verify(array $user, string $password): bool
    {
        return password_verify($password, $user['password_hash']);
    }

    /** Marca al usuario como autenticado en la sesion. */
    public static function login(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['uid'] = $userId;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    private static function normEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}
