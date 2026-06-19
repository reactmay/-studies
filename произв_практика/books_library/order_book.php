<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/books.php';
require_once __DIR__ . '/includes/orders.php';

$user = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$bookId = (int) ($_POST['book_id'] ?? 0);
$orderType = $_POST['order_type'] ?? '';
$rentalPeriod = $_POST['rental_period'] ?? null;

if ($rentalPeriod === '') {
    $rentalPeriod = null;
}

$result = createBookOrder((int) $user['id'], $bookId, $orderType, $rentalPeriod);

if ($result['ok']) {
    header('Location: my_orders.php?success=1');
    exit;
}

header('Location: book.php?id=' . $bookId . '&error=' . urlencode($result['error']));
exit;
