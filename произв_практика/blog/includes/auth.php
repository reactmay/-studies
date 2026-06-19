<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function currentUser(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = getDb()->prepare('SELECT id, username, email, avatar, created_at FROM users WHERE id = ?');
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
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
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
        return ['ok' => false, 'error' => 'Пользователь с таким именем или email уже существует.'];
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

function uploadUserAvatar(int $userId, array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Выберите файл для загрузки.'];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Не удалось загрузить файл.'];
    }

    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        return ['ok' => false, 'error' => 'Размер файла не должен превышать 2 МБ.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name'] ?? '');

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'error' => 'Допустимы только JPG, PNG, WEBP и GIF.'];
    }

    $uploadDir = avatarUploadDir();

    $filename = sprintf('user_%d_%s.%s', $userId, bin2hex(random_bytes(8)), $allowed[$mime]);
    $destination = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['ok' => false, 'error' => 'Не удалось сохранить файл.'];
    }

    return persistUserAvatar($userId, $destination, 'uploads/avatars/' . $filename);
}

function avatarUploadDir(): string
{
    $uploadDir = dirname(__DIR__) . '/uploads/avatars';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    return $uploadDir;
}

function persistUserAvatar(int $userId, string $absolutePath, string $relativePath): array
{
    $db = getDb();
    $stmt = $db->prepare('SELECT avatar FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $current = $stmt->fetch();

    $update = $db->prepare('UPDATE users SET avatar = ? WHERE id = ?');
    $update->execute([$relativePath, $userId]);

    if (!empty($current['avatar']) && $current['avatar'] !== $relativePath) {
        $oldPath = dirname(__DIR__) . '/' . $current['avatar'];
        if (is_file($oldPath)) {
            unlink($oldPath);
        }
    }

    return ['ok' => true, 'avatar' => $relativePath];
}
