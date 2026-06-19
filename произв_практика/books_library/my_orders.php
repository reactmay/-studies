<?php

declare(strict_types=1);

$pageTitle = 'Мои заказы';
require_once __DIR__ . '/includes/header.php';

$currentUser = requireAuth();
$orders = getUserOrders((int) $currentUser['id']);
$success = isset($_GET['success']);
?>

<h1 class="page-title">Мои заказы</h1>
<p class="page-subtitle">Покупки и аренда книг</p>

<?php if ($success): ?>
    <div class="alert alert-success">Заказ успешно оформлен.</div>
<?php endif; ?>

<?php if ($orders === []): ?>
    <div class="card empty-state">
        <p>У вас пока нет заказов.</p>
        <p><a href="index.php" class="btn btn-primary">Перейти в каталог</a></p>
    </div>
<?php else: ?>
    <div class="table-wrap card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Книга</th>
                    <th>Тип</th>
                    <th>Срок</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th>До</th>
                    <th>Дата</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <?php
                    $expiringSoon = $order['order_type'] === ORDER_RENT
                        && $order['status'] === ORDER_ACTIVE
                        && isRentalExpiringSoon($order['ends_at']);
                    ?>
                    <tr class="<?= $expiringSoon ? 'row-expiring' : '' ?>">
                        <td>
                            <a href="book.php?id=<?= (int) $order['book_id'] ?>"><?= e($order['title']) ?></a>
                            <div class="text-muted"><?= e($order['author']) ?></div>
                            <?php if ($expiringSoon): ?>
                                <div class="expiring-badge">Скоро вернуть</div>
                            <?php endif; ?>
                        </td>
                        <td><?= e(orderTypeLabel($order['order_type'])) ?></td>
                        <td><?= e(rentalPeriodLabel($order['rental_period'])) ?></td>
                        <td><?= number_format((float) $order['price'], 2, '.', ' ') ?> ₽</td>
                        <td><?= e(orderStatusLabel($order['status'])) ?></td>
                        <td>
                            <?php if ($order['order_type'] === ORDER_RENT && $order['ends_at']): ?>
                                <?= e(formatDate($order['ends_at'])) ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td><?= e(formatDate($order['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
