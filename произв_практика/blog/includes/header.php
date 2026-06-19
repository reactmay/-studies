<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/subscriptions.php';

$user = currentUser();
$pageTitle = $pageTitle ?? 'Простой блог';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — Простой блог</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a class="logo" href="index.php">Простой блог</a>
            <nav class="nav">
                <a href="index.php">Главная</a>
                <?php if ($user): ?>
                    <a href="feed.php">Лента</a>
                    <a href="dashboard.php">Личный кабинет</a>
                    <a href="create_post.php">Новый пост</a>
                    <span class="nav-user">
                        <?= renderAvatar($user, 'sm') ?>
                        Привет, <?= e($user['username']) ?>
                    </span>
                    <a href="logout.php" class="btn btn-outline">Выйти</a>
                <?php else: ?>
                    <a href="login.php">Вход</a>
                    <a href="register.php" class="btn btn-primary">Регистрация</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="container">
