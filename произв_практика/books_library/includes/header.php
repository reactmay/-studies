<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/books.php';
require_once __DIR__ . '/orders.php';
require_once __DIR__ . '/reminders.php';

$user = currentUser();
$unreadNotifications = [];
$unreadCount = 0;

if ($user) {
    processRentalReminders();
    $unreadCount = countUnreadNotifications((int) $user['id']);
    $unreadNotifications = getUnreadNotifications((int) $user['id'], 3);
}

$pageTitle = $pageTitle ?? 'Библиотека книг';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — Мои любимые книги</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="logo" href="index.php">Мои любимые книги</a>
        <nav class="nav">
            <a href="index.php">Каталог</a>
            <?php if ($user): ?>
                <a href="my_orders.php">Мои заказы</a>
                <a href="notifications.php" class="nav-notifications">
                    Уведомления
                    <?php if ($unreadCount > 0): ?>
                        <span class="nav-badge"><?= $unreadCount ?></span>
                    <?php endif; ?>
                </a>
                <?php if ((int) $user['is_admin']): ?>
                    <a href="admin/index.php">Админ-панель</a>
                <?php endif; ?>
                <span class="nav-user">Привет, <?= e($user['username']) ?></span>
                <a href="logout.php" class="btn btn-outline">Выйти</a>
            <?php else: ?>
                <a href="login.php">Вход</a>
                <a href="register.php" class="btn btn-primary">Регистрация</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<?php if ($user && $unreadNotifications !== []): ?>
    <div class="container">
        <div class="alert alert-warning notifications-banner">
            <strong>Напоминание об аренде:</strong>
            <ul class="notifications-banner-list">
                <?php foreach ($unreadNotifications as $n): ?>
                    <li><?= e($n['message']) ?></li>
                <?php endforeach; ?>
            </ul>
            <a href="notifications.php">Все уведомления<?= $unreadCount > 3 ? ' (' . $unreadCount . ')' : '' ?></a>
        </div>
    </div>
<?php endif; ?>
<main class="container">
