<?php

declare(strict_types=1);

const REMINDER_3_DAYS = '3_days';
const REMINDER_1_DAY = '1_day';

const NOTIFICATION_RENTAL_EXPIRING = 'rental_expiring';

/** За сколько дней до окончания отправлять напоминания */
function reminderThresholds(): array
{
    return [
        REMINDER_3_DAYS => 3,
        REMINDER_1_DAY => 1,
    ];
}

function reminderTypeLabel(string $type): string
{
    return match ($type) {
        REMINDER_1_DAY => 'за 1 день',
        default => 'за 3 дня',
    };
}

function processRentalReminders(): int
{
    releaseExpiredRentals();

    $sent = 0;
    foreach (reminderThresholds() as $type => $days) {
        $sent += sendRentalRemindersForThreshold($type, $days);
    }

    return $sent;
}

function sendRentalRemindersForThreshold(string $reminderType, int $daysBefore): int
{
    $db = getDb();

    $extraCondition = '';
    if ($reminderType === REMINDER_3_DAYS) {
        $extraCondition = 'AND book_orders.ends_at > DATE_ADD(NOW(), INTERVAL 1 DAY)';
    }

    $stmt = $db->prepare("
        SELECT book_orders.id, book_orders.user_id, book_orders.ends_at,
               books.title, users.email, users.username
        FROM book_orders
        JOIN books ON books.id = book_orders.book_id
        JOIN users ON users.id = book_orders.user_id
        WHERE book_orders.order_type = 'rent'
          AND book_orders.status = 'active'
          AND book_orders.ends_at IS NOT NULL
          AND book_orders.ends_at > NOW()
          AND book_orders.ends_at <= DATE_ADD(NOW(), INTERVAL ? DAY)
          {$extraCondition}
          AND NOT EXISTS (
              SELECT 1 FROM rental_reminder_log
              WHERE rental_reminder_log.order_id = book_orders.id
                AND rental_reminder_log.reminder_type = ?
          )
    ");
    $stmt->execute([$daysBefore, $reminderType]);

    $sent = 0;
    foreach ($stmt->fetchAll() as $row) {
        if (createRentalReminderNotification($row, $reminderType, $daysBefore)) {
            $sent++;
        }
    }

    return $sent;
}

function createRentalReminderNotification(array $order, string $reminderType, int $daysBefore): bool
{
    $orderId = (int) $order['id'];
    $userId = (int) $order['user_id'];
    $title = $order['title'];
    $endsAt = formatDate($order['ends_at']);
    $whenLabel = reminderTypeLabel($reminderType);

    $message = "Срок аренды книги «{$title}» истекает {$endsAt}. Напоминание {$whenLabel} до возврата.";

    $db = getDb();
    $db->beginTransaction();

    try {
        $ins = $db->prepare('
            INSERT INTO user_notifications (user_id, order_id, type, title, message)
            VALUES (?, ?, ?, ?, ?)
        ');
        $ins->execute([
            $userId,
            $orderId,
            NOTIFICATION_RENTAL_EXPIRING,
            'Скоро истекает аренда',
            $message,
        ]);

        $db->prepare('
            INSERT INTO rental_reminder_log (order_id, reminder_type) VALUES (?, ?)
        ')->execute([$orderId, $reminderType]);

        $db->commit();

        sendRentalReminderEmail(
            $order['email'],
            $order['username'],
            $title,
            $endsAt,
            $whenLabel
        );

        return true;
    } catch (Throwable $e) {
        $db->rollBack();
        return false;
    }
}

function sendRentalReminderEmail(
    string $email,
    string $username,
    string $bookTitle,
    string $endsAt,
    string $whenLabel
): void
{
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    $subject = 'Напоминание: скоро истекает аренда книги';
    $body = "Здравствуйте, {$username}!\n\n"
        . "Напоминаем ({$whenLabel}), что срок аренды книги «{$bookTitle}» истекает {$endsAt}.\n"
        . "Пожалуйста, верните книгу вовремя.\n\n"
        . "— Библиотека книг\n";

    $headers = [
        'From: noreply@books-library.local',
        'Content-Type: text/plain; charset=UTF-8',
    ];

    @mail($email, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
}

function getUnreadNotifications(int $userId, int $limit = 5): array
{
    $stmt = getDb()->prepare('
        SELECT * FROM user_notifications
        WHERE user_id = ? AND is_read = 0
        ORDER BY created_at DESC
        LIMIT ?
    ');
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function countUnreadNotifications(int $userId): int
{
    $stmt = getDb()->prepare('
        SELECT COUNT(*) FROM user_notifications WHERE user_id = ? AND is_read = 0
    ');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function getUserNotifications(int $userId, int $limit = 50): array
{
    $stmt = getDb()->prepare('
        SELECT user_notifications.*, books.title AS book_title, book_orders.book_id
        FROM user_notifications
        LEFT JOIN book_orders ON book_orders.id = user_notifications.order_id
        LEFT JOIN books ON books.id = book_orders.book_id
        WHERE user_notifications.user_id = ?
        ORDER BY user_notifications.created_at DESC
        LIMIT ?
    ');
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function markNotificationRead(int $notificationId, int $userId): bool
{
    $stmt = getDb()->prepare('
        UPDATE user_notifications SET is_read = 1
        WHERE id = ? AND user_id = ?
    ');
    $stmt->execute([$notificationId, $userId]);
    return $stmt->rowCount() > 0;
}

function markAllNotificationsRead(int $userId): void
{
    $stmt = getDb()->prepare('
        UPDATE user_notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0
    ');
    $stmt->execute([$userId]);
}

function daysUntilRentalEnd(?string $endsAt): ?int
{
    if ($endsAt === null || $endsAt === '') {
        return null;
    }

    $end = new DateTimeImmutable($endsAt);
    $now = new DateTimeImmutable('today');
    $diff = (int) $now->diff($end->setTime(0, 0))->format('%r%a');

    return max(0, $diff);
}

function isRentalExpiringSoon(?string $endsAt, int $withinDays = 3): bool
{
    $days = daysUntilRentalEnd($endsAt);
    return $days !== null && $days <= $withinDays;
}
