<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function formatDate(string $date): string
{
    $ts = strtotime($date);
    return $ts ? date('d.m.Y H:i', $ts) : $date;
}

function currentUser(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = getDb()->prepare('SELECT id, username, email, is_admin, created_at FROM users WHERE id = ?');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function requireAuth(): array
{
    $user = currentUser();
    if ($user === null) {
        header('Location: login.php');
        exit;
    }
    return $user;
}

function requireAdmin(): array
{
    $user = requireAuth();
    if (!(int) $user['is_admin']) {
        header('Location: index.php');
        exit;
    }
    return $user;
}

function loginUser(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}

function registerUser(string $username, string $email, string $password): array
{
    $username = trim($username);
    $email = trim(strtolower($email));

    if ($username === '' || strlen($username) < 3) {
        return ['ok' => false, 'error' => 'Имя пользователя должно быть не короче 3 символов.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Введите корректный email.'];
    }
    if (strlen($password) < 6) {
        return ['ok' => false, 'error' => 'Пароль должен быть не короче 6 символов.'];
    }

    $db = getDb();
    $check = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
    $check->execute([$username, $email]);
    if ($check->fetch()) {
        return ['ok' => false, 'error' => 'Пользователь уже существует.'];
    }

    $stmt = $db->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
    $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);

    return ['ok' => true, 'user_id' => (int) $db->lastInsertId()];
}

function attemptLogin(string $login, string $password): array
{
    $login = trim($login);
    if ($login === '' || $password === '') {
        return ['ok' => false, 'error' => 'Заполните все поля.'];
    }

    $stmt = getDb()->prepare('SELECT id, password_hash FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$login, strtolower($login)]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['ok' => false, 'error' => 'Неверный логин или пароль.'];
    }

    return ['ok' => true, 'user_id' => (int) $user['id']];
}
