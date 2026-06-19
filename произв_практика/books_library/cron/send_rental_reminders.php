<?php

declare(strict_types=1);

/**
 * Фоновая отправка напоминаний об окончании аренды.
 * Запускайте по расписанию (Windows Task Scheduler / cron), например раз в час:
 *
 * php C:\Users\Xeon\books-library\cron\send_rental_reminders.php
 */
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/books.php';
require_once dirname(__DIR__) . '/includes/orders.php';
require_once dirname(__DIR__) . '/includes/reminders.php';

$sent = processRentalReminders();

if (PHP_SAPI === 'cli') {
    echo date('Y-m-d H:i:s') . " — отправлено напоминаний: {$sent}\n";
} else {
    header('Content-Type: text/plain; charset=UTF-8');
    echo "OK, sent: {$sent}\n";
}
