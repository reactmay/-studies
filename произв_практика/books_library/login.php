<?php

declare(strict_types=1);

$pageTitle = 'Вход';
require_once __DIR__ . '/includes/header.php';

if ($user) {
    header('Location: index.php');
    exit;
}

$error = '';
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    $result = attemptLogin($login, $password);
    if ($result['ok']) {
        loginUser($result['user_id']);
        $u = currentUser();
        header('Location: ' . ((int) $u['is_admin'] ? 'admin/index.php' : 'index.php'));
        exit;
    }
    $error = $result['error'];
}
?>

<div class="card form-card">
    <h1>Вход</h1>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <div class="form-group">
            <label for="login">Логин или email</label>
            <input type="text" id="login" name="login" value="<?= e($login) ?>" required>
        </div>
        <div class="form-group">
            <label for="password">Пароль</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary">Войти</button>
        <a href="register.php" class="btn btn-outline">Регистрация</a>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
