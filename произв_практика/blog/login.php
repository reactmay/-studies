<?php

declare(strict_types=1);

$pageTitle = 'Вход';
require_once __DIR__ . '/includes/header.php';

if ($user) {
    header('Location: dashboard.php');
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
        header('Location: dashboard.php');
        exit;
    }

    $error = $result['error'];
}
?>

<div class="form-card card">
    <h1 class="page-title">Вход</h1>
    <p class="page-subtitle">Войдите в личный кабинет</p>

    <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label for="login">Логин или email</label>
            <input type="text" id="login" name="login" value="<?= e($login) ?>" required autocomplete="username">
        </div>

        <div class="form-group">
            <label for="password">Пароль</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Войти</button>
            <a href="register.php" class="btn btn-outline">Создать аккаунт</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
