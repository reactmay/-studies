<?php

declare(strict_types=1);

$pageTitle = 'Регистрация';
require_once __DIR__ . '/includes/header.php';

$error = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if ($password !== $passwordConfirm) {
        $error = 'Пароли не совпадают.';
    } else {
        $result = registerUser($username, $email, $password);

        if ($result['ok']) {
            loginUser($result['user_id']);
            header('Location: dashboard.php');
            exit;
        }

        $error = $result['error'];
    }
}
?>

<div class="form-card card">
    <h1 class="page-title">Регистрация</h1>
    <p class="page-subtitle">Создайте аккаунт для публикации постов</p>

    <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" data-validate novalidate>
        <div class="form-group">
            <label for="username">Имя пользователя</label>
            <input type="text" id="username" name="username" value="<?= e($username) ?>" required data-validate-field="username" autocomplete="username">
            <div class="field-error"></div>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>" required data-validate-field="email" autocomplete="email">
            <div class="field-error"></div>
        </div>

        <div class="form-group">
            <label for="password">Пароль</label>
            <input type="password" id="password" name="password" required data-validate-field="password" autocomplete="new-password">
            <div class="field-error"></div>
        </div>

        <div class="form-group">
            <label for="password_confirm">Подтверждение пароля</label>
            <input type="password" id="password_confirm" name="password_confirm" required data-validate-field="password_confirm" autocomplete="new-password">
            <div class="field-error"></div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
            <a href="login.php" class="btn btn-outline">Уже есть аккаунт</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
