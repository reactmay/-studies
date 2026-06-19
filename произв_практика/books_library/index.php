<?php

declare(strict_types=1);

$pageTitle = 'Каталог книг';
require_once __DIR__ . '/includes/header.php';

$page = max(1, (int) ($_GET['page'] ?? 1));
$categoryId = isset($_GET['category']) ? (int) $_GET['category'] : null;
$sort = normalizeBookSort($_GET['sort'] ?? SORT_NEWEST);
$search = isset($_GET['q']) ? trim((string) $_GET['q']) : null;

$list = generateBooksList($page, 12, $categoryId, $sort, true, $search);
$meta = $list['meta'];
$books = $list['items'];
$categories = getAllCategories();
?>

<h1 class="page-title">Библиотека книг</h1>
<p class="page-subtitle">Просмотр книг, доступных в библиотеке</p>

<form class="card filters-form" method="get" action="index.php">
    <div class="filters-grid">
        <div class="form-group">
            <label for="q">Поиск</label>
            <input type="search" id="q" name="q" value="<?= e($meta['search'] ?? '') ?>" placeholder="Название или автор">
        </div>
        <div class="form-group">
            <label for="category">Категория</label>
            <select id="category" name="category">
                <option value="">Все категории</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>" <?= $categoryId === (int) $cat['id'] ? 'selected' : '' ?>>
                        <?= e($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="sort">Сортировка</label>
            <select id="sort" name="sort">
                <option value="<?= SORT_NEWEST ?>" <?= $sort === SORT_NEWEST ? 'selected' : '' ?>>Сначала новые</option>
                <option value="<?= SORT_YEAR_DESC ?>" <?= $sort === SORT_YEAR_DESC ? 'selected' : '' ?>>Год (новые → старые)</option>
                <option value="<?= SORT_YEAR_ASC ?>" <?= $sort === SORT_YEAR_ASC ? 'selected' : '' ?>>Год (старые → новые)</option>
                <option value="<?= SORT_AUTHOR_ASC ?>" <?= $sort === SORT_AUTHOR_ASC ? 'selected' : '' ?>>Автор (А–Я)</option>
                <option value="<?= SORT_AUTHOR_DESC ?>" <?= $sort === SORT_AUTHOR_DESC ? 'selected' : '' ?>>Автор (Я–А)</option>
                <option value="<?= SORT_CATEGORY_ASC ?>" <?= $sort === SORT_CATEGORY_ASC ? 'selected' : '' ?>>Категория (А–Я)</option>
                <option value="<?= SORT_CATEGORY_DESC ?>" <?= $sort === SORT_CATEGORY_DESC ? 'selected' : '' ?>>Категория (Я–А)</option>
            </select>
        </div>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Применить</button>
        <a href="index.php" class="btn btn-outline">Сбросить</a>
    </div>
</form>

<?php if ($books === []): ?>
    <div class="card empty-state">
        <p>Книги не найдены.</p>
        <?php if ($user && (int) $user['is_admin']): ?>
            <p><a href="admin/book_form.php" class="btn btn-primary">Добавить книгу</a></p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <p class="feed-summary">Найдено <?= (int) $meta['total'] ?> · <?= e(bookSortLabel($sort)) ?></p>
    <div class="books-grid">
        <?php foreach ($books as $book): ?>
            <?php renderBookCard($book); ?>
        <?php endforeach; ?>
    </div>
    <?php if ($meta['total_pages'] > 1): ?>
        <nav class="pagination">
            <?php if ($meta['page'] > 1): ?>
                <a class="btn btn-outline" href="<?= e(booksUrl($meta['page'] - 1, $categoryId, $sort, $meta['search'])) ?>">← Назад</a>
            <?php endif; ?>
            <span>Страница <?= (int) $meta['page'] ?> из <?= (int) $meta['total_pages'] ?></span>
            <?php if ($meta['page'] < $meta['total_pages']): ?>
                <a class="btn btn-outline" href="<?= e(booksUrl($meta['page'] + 1, $categoryId, $sort, $meta['search'])) ?>">Вперёд →</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
