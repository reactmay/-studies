<?php

declare(strict_types=1);

$pageTitle = 'Регистрация';
require_once __DIR__ . '/includes/header.php';

if ($user) {
    header('Location: index.php');
    exit;
}

$error = '';
$username = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if ($password !== $confirm) {
        $error = 'Пароли не совпадают.';
    } else {
        $result = registerUser($username, $email, $password);
        if ($result['ok']) {
            loginUser($result['user_id']);
            header('Location: index.php');
            exit;
        }
        $error = $result['error'];
    }
}
?>

<div class="card form-card">
    <h1>Регистрация</h1>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <div class="form-group">
            <label for="username">Имя пользователя</label>
            <input type="text" id="username" name="username" value="<?= e($username) ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>" required>
        </div>
        <div class="form-group">
            <label for="password">Пароль</label>
            <input type="password" id="password" name="password" required minlength="6">
        </div>
        <div class="form-group">
            <label for="password_confirm">Подтверждение</label>
            <input type="password" id="password_confirm" name="password_confirm" required>
        </div>
        <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
