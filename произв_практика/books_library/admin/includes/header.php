<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/books.php';

$user = currentUser();
$pageTitle = $pageTitle ?? 'Админ-панель';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — Админ</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header class="site-header admin-header">
    <div class="container header-inner">
        <a class="logo" href="index.php">Админ · Книги</a>
        <nav class="nav">
            <a href="index.php">Книги</a>
            <a href="orders.php">Заказы</a>
            <a href="book_form.php">Добавить книгу</a>
            <a href="../index.php">Каталог</a>
            <a href="../logout.php" class="btn btn-outline">Выйти</a>
        </nav>
    </div>
</header>
<main class="container">
