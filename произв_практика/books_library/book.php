<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/header.php';

$id = (int) ($_GET['id'] ?? 0);
$book = $id > 0 ? getBookById($id, true) : null;
$orderError = $_GET['error'] ?? '';

if ($book === null) {
    http_response_code(404);
    $pageTitle = 'Книга не найдена';
    ?>
    <div class="card empty-state">
        <h1>Книга не найдена</h1>
        <p><a href="index.php">Вернуться в каталог</a></p>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $book['title'];
$canOrder = isBookAvailableForOrder($book);
?>

<article class="card book-detail">
    <div class="book-detail-grid">
        <div class="book-cover book-cover-lg">
            <?php if (!empty($book['cover'])): ?>
                <img src="<?= e($book['cover']) ?>" alt="<?= e($book['title']) ?>">
            <?php else: ?>
                <div class="book-cover-placeholder"><?= e(mb_strtoupper(mb_substr($book['title'], 0, 1))) ?></div>
            <?php endif; ?>
        </div>
        <div>
            <h1 class="page-title"><?= e($book['title']) ?></h1>
            <p class="book-meta"><strong>Автор:</strong> <?= e($book['author']) ?></p>
            <p class="book-meta"><strong>Категория:</strong> <?= e($book['category_name']) ?></p>
            <?php if ($book['year_written']): ?>
                <p class="book-meta"><strong>Год:</strong> <?= (int) $book['year_written'] ?></p>
            <?php endif; ?>
            <p class="book-price-lg">Базовая цена: <?= number_format((float) $book['price'], 2, '.', ' ') ?> ₽</p>
            <p>
                <span class="book-badge book-badge-<?= e($book['status']) ?>"><?= e(bookStatusLabel($book['status'])) ?></span>
                <span class="book-badge book-badge-library">В библиотеке</span>
            </p>
            <?php renderOrderPrices((float) $book['price']); ?>
            <?php if ($book['description']): ?>
                <div class="book-description"><?= nl2br(e($book['description'])) ?></div>
            <?php endif; ?>
        </div>
    </div>
</article>

<?php if ($orderError !== ''): ?>
    <div class="alert alert-error"><?= e($orderError) ?></div>
<?php endif; ?>

<?php if ($canOrder): ?>
    <section class="card order-section">
        <h2>Купить или взять в аренду</h2>

        <?php if ($user): ?>
            <form method="post" action="order_book.php" class="order-form">
                <input type="hidden" name="book_id" value="<?= (int) $book['id'] ?>">

                <div class="form-group">
                    <label>Тип заказа</label>
                    <div class="radio-group">
                        <label><input type="radio" name="order_type" value="<?= ORDER_PURCHASE ?>" checked data-order-type> Покупка</label>
                        <label><input type="radio" name="order_type" value="<?= ORDER_RENT ?>" data-order-type> Аренда</label>
                    </div>
                </div>

                <div class="form-group" id="rental-period-group" hidden>
                    <label for="rental_period">Срок аренды</label>
                    <select id="rental_period" name="rental_period">
                        <?php foreach (rentalPeriodOptions() as $key => $label): ?>
                            <option value="<?= e($key) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <p class="form-hint" id="order-price-hint">
                    К оплате: <strong><?= number_format(calculateOrderPrice((float) $book['price'], ORDER_PURCHASE), 2, '.', ' ') ?> ₽</strong>
                </p>

                <button type="submit" class="btn btn-primary">Оформить заказ</button>
            </form>

            <script>
            (() => {
                const basePrice = <?= json_encode((float) $book['price']) ?>;
                const multipliers = <?= json_encode([
                    'purchase' => 1,
                    RENT_2_WEEKS => rentalPriceMultiplier(RENT_2_WEEKS),
                    RENT_1_MONTH => rentalPriceMultiplier(RENT_1_MONTH),
                    RENT_3_MONTHS => rentalPriceMultiplier(RENT_3_MONTHS),
                ]) ?>;
                const form = document.querySelector('.order-form');
                const rentalGroup = document.getElementById('rental-period-group');
                const rentalSelect = document.getElementById('rental_period');
                const hint = document.getElementById('order-price-hint');

                const updatePrice = () => {
                    const type = form.querySelector('[name="order_type"]:checked')?.value;
                    rentalGroup.hidden = type !== 'rent';
                    let price = basePrice;
                    if (type === 'rent') {
                        const m = multipliers[rentalSelect.value] || 0.15;
                        price = Math.max(basePrice * m, 1);
                    }
                    hint.innerHTML = 'К оплате: <strong>' + price.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ').replace('.', ',') + ' ₽</strong>';
                };

                form.querySelectorAll('[data-order-type]').forEach((el) => el.addEventListener('change', updatePrice));
                rentalSelect.addEventListener('change', updatePrice);
            })();
            </script>
        <?php else: ?>
            <p><a href="login.php" class="btn btn-primary">Войдите</a>, чтобы оформить покупку или аренду.</p>
        <?php endif; ?>
    </section>
<?php else: ?>
    <div class="card empty-state">
        <p>Книга сейчас недоступна для заказа (продана, выдана или зарезервирована).</p>
    </div>
<?php endif; ?>

<p><a href="index.php">← Каталог</a></p>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
