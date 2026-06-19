<?php

declare(strict_types=1);

const ORDER_PURCHASE = 'purchase';
const ORDER_RENT = 'rent';

const RENT_2_WEEKS = '2_weeks';
const RENT_1_MONTH = '1_month';
const RENT_3_MONTHS = '3_months';

const ORDER_ACTIVE = 'active';
const ORDER_COMPLETED = 'completed';
const ORDER_CANCELLED = 'cancelled';

function rentalPeriodOptions(): array
{
    return [
        RENT_2_WEEKS => '2 недели',
        RENT_1_MONTH => '1 месяц',
        RENT_3_MONTHS => '3 месяца',
    ];
}

function rentalPeriodLabel(?string $period): string
{
    if ($period === null) {
        return '—';
    }
    return rentalPeriodOptions()[$period] ?? $period;
}

function orderTypeLabel(string $type): string
{
    return $type === ORDER_PURCHASE ? 'Покупка' : 'Аренда';
}

function orderStatusLabel(string $status): string
{
    return match ($status) {
        ORDER_COMPLETED => 'Завершён',
        ORDER_CANCELLED => 'Отменён',
        default => 'Активен',
    };
}

/** Коэффициенты аренды от цены книги */
function rentalPriceMultiplier(string $period): float
{
    return match ($period) {
        RENT_2_WEEKS => 0.15,
        RENT_1_MONTH => 0.25,
        RENT_3_MONTHS => 0.50,
        default => 0.15,
    };
}

function calculateOrderPrice(float $bookPrice, string $orderType, ?string $rentalPeriod = null): float
{
    if ($orderType === ORDER_PURCHASE) {
        return round($bookPrice, 2);
    }

    $multiplier = rentalPriceMultiplier($rentalPeriod ?? RENT_2_WEEKS);
    return round(max($bookPrice * $multiplier, 1.0), 2);
}

function calculateRentalEndDate(string $period, ?string $start = null): string
{
    $startDt = new DateTimeImmutable($start ?? 'now');

    $end = match ($period) {
        RENT_2_WEEKS => $startDt->modify('+14 days'),
        RENT_1_MONTH => $startDt->modify('+1 month'),
        RENT_3_MONTHS => $startDt->modify('+3 months'),
        default => $startDt->modify('+14 days'),
    };

    return $end->format('Y-m-d H:i:s');
}

function releaseExpiredRentals(): void
{
    $db = getDb();

    $stmt = $db->query("
        SELECT id, book_id FROM book_orders
        WHERE order_type = 'rent'
          AND status = 'active'
          AND ends_at IS NOT NULL
          AND ends_at < NOW()
    ");

    foreach ($stmt->fetchAll() as $order) {
        $db->prepare("UPDATE book_orders SET status = 'completed' WHERE id = ?")
            ->execute([(int) $order['id']]);

        $bookId = (int) $order['book_id'];
        if (!hasActiveBookOrder($bookId)) {
            $db->prepare("UPDATE books SET status = 'available', updated_at = NOW() WHERE id = ?")
                ->execute([$bookId]);
        }
    }
}

function hasActiveBookOrder(int $bookId): bool
{
    $stmt = getDb()->prepare("
        SELECT 1 FROM book_orders
        WHERE book_id = ? AND status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$bookId]);
    return (bool) $stmt->fetchColumn();
}

function isBookAvailableForOrder(array $book): bool
{
    releaseExpiredRentals();

    return (int) $book['in_library'] === 1
        && $book['status'] === BOOK_STATUS_AVAILABLE
        && !hasActiveBookOrder((int) $book['id']);
}

function createBookOrder(int $userId, int $bookId, string $orderType, ?string $rentalPeriod = null): array
{
    releaseExpiredRentals();

    $book = getBookById($bookId, true);
    if ($book === null) {
        return ['ok' => false, 'error' => 'Книга не найдена.'];
    }

    if (!isBookAvailableForOrder($book)) {
        return ['ok' => false, 'error' => 'Книга сейчас недоступна для заказа.'];
    }

    if ($orderType === ORDER_PURCHASE) {
        $rentalPeriod = null;
    } elseif ($orderType === ORDER_RENT) {
        if ($rentalPeriod === null || !isset(rentalPeriodOptions()[$rentalPeriod])) {
            return ['ok' => false, 'error' => 'Выберите срок аренды.'];
        }
    } else {
        return ['ok' => false, 'error' => 'Некорректный тип заказа.'];
    }

    $price = calculateOrderPrice((float) $book['price'], $orderType, $rentalPeriod);
    $endsAt = $orderType === ORDER_RENT ? calculateRentalEndDate($rentalPeriod) : null;

    $db = getDb();
    $db->beginTransaction();

    try {
        $insert = $db->prepare('
            INSERT INTO book_orders (user_id, book_id, order_type, rental_period, price, status, ends_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $insert->execute([
            $userId, $bookId, $orderType, $rentalPeriod, $price, ORDER_ACTIVE, $endsAt,
        ]);

        $newStatus = $orderType === ORDER_PURCHASE ? BOOK_STATUS_UNAVAILABLE : BOOK_STATUS_BORROWED;
        $db->prepare('UPDATE books SET status = ?, updated_at = NOW() WHERE id = ?')
            ->execute([$newStatus, $bookId]);

        $db->commit();

        return [
            'ok' => true,
            'order_id' => (int) $db->lastInsertId(),
            'price' => $price,
            'ends_at' => $endsAt,
        ];
    } catch (Throwable $e) {
        $db->rollBack();
        return ['ok' => false, 'error' => 'Не удалось оформить заказ.'];
    }
}

function getUserOrders(int $userId): array
{
    releaseExpiredRentals();

    $stmt = getDb()->prepare('
        SELECT book_orders.*, books.title, books.author
        FROM book_orders
        JOIN books ON books.id = book_orders.book_id
        WHERE book_orders.user_id = ?
        ORDER BY book_orders.created_at DESC
    ');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getAllOrders(): array
{
    releaseExpiredRentals();

    $stmt = getDb()->query('
        SELECT book_orders.*, books.title, books.author, users.username
        FROM book_orders
        JOIN books ON books.id = book_orders.book_id
        JOIN users ON users.id = book_orders.user_id
        ORDER BY book_orders.created_at DESC
    ');
    return $stmt->fetchAll();
}

function renderOrderPrices(float $bookPrice): void
{
    ?>
    <ul class="price-list">
        <li><strong>Покупка:</strong> <?= number_format(calculateOrderPrice($bookPrice, ORDER_PURCHASE), 2, '.', ' ') ?> ₽</li>
        <?php foreach (rentalPeriodOptions() as $key => $label): ?>
            <li><strong>Аренда (<?= e($label) ?>):</strong>
                <?= number_format(calculateOrderPrice($bookPrice, ORDER_RENT, $key), 2, '.', ' ') ?> ₽
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
}
