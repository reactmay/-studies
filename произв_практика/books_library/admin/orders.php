<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/books.php';
require_once dirname(__DIR__) . '/includes/orders.php';

requireAdmin();

$pageTitle = 'Заказы';
$orders = getAllOrders();

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Заказы пользователей</h1>

<?php if ($orders === []): ?>
    <div class="card empty-state"><p>Заказов пока нет.</p></div>
<?php else: ?>
    <div class="table-wrap card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Пользователь</th>
                    <th>Книга</th>
                    <th>Тип</th>
                    <th>Срок</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th>Возврат до</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= e($order['username']) ?></td>
                        <td><?= e($order['title']) ?></td>
                        <td><?= e(orderTypeLabel($order['order_type'])) ?></td>
                        <td><?= e(rentalPeriodLabel($order['rental_period'])) ?></td>
                        <td><?= number_format((float) $order['price'], 2, '.', ' ') ?> ₽</td>
                        <td><?= e(orderStatusLabel($order['status'])) ?></td>
                        <td><?= $order['ends_at'] ? e(formatDate($order['ends_at'])) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
