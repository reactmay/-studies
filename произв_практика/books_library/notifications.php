<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/books.php';
require_once __DIR__ . '/includes/orders.php';
require_once __DIR__ . '/includes/reminders.php';

$user = requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'read_all') {
        markAllNotificationsRead((int) $user['id']);
    } elseif ($action === 'read_one') {
        markNotificationRead((int) ($_POST['id'] ?? 0), (int) $user['id']);
    }
    header('Location: notifications.php');
    exit;
}

$pageTitle = 'Уведомления';
require_once __DIR__ . '/includes/header.php';

$notifications = getUserNotifications((int) $user['id']);
$unreadCount = countUnreadNotifications((int) $user['id']);
?>

<h1 class="page-title">Уведомления</h1>

<?php if ($unreadCount > 0): ?>
    <form method="post" class="notifications-actions">
        <input type="hidden" name="action" value="read_all">
        <button type="submit" class="btn btn-outline btn-sm">Отметить все прочитанными</button>
    </form>
<?php endif; ?>

<?php if ($notifications === []): ?>
    <div class="card empty-state">
        <p>Уведомлений пока нет.</p>
    </div>
<?php else: ?>
    <div class="notifications-list">
        <?php foreach ($notifications as $n): ?>
            <article class="card notification-item <?= (int) $n['is_read'] ? 'notification-read' : 'notification-unread' ?>">
                <div class="notification-head">
                    <h2 class="notification-title"><?= e($n['title']) ?></h2>
                    <time class="notification-time"><?= e(formatDate($n['created_at'])) ?></time>
                </div>
                <p class="notification-message"><?= e($n['message']) ?></p>
                <?php if (!empty($n['book_id'])): ?>
                    <p><a href="book.php?id=<?= (int) $n['book_id'] ?>">Открыть книгу</a></p>
                <?php endif; ?>
                <?php if (!(int) $n['is_read']): ?>
                    <form method="post" class="notification-read-form">
                        <input type="hidden" name="action" value="read_one">
                        <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
                        <button type="submit" class="btn btn-outline btn-sm">Прочитано</button>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
