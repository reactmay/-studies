<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/books.php';

requireAdmin();

$pageTitle = 'Управление книгами';
$saved = isset($_GET['saved']);
$deleted = isset($_GET['deleted']);

$list = generateBooksList(1, 100, null, SORT_NEWEST, false, null);
$books = $list['items'];

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Книги в базе</h1>
<p class="page-subtitle">Добавление, редактирование, цена, статус и доступность в библиотеке</p>

<?php if ($saved): ?><div class="alert alert-success">Книга сохранена.</div><?php endif; ?>
<?php if ($deleted): ?><div class="alert alert-success">Книга удалена.</div><?php endif; ?>

<div class="form-actions" style="margin-bottom:1rem;">
    <a href="book_form.php" class="btn btn-primary">+ Добавить книгу</a>
</div>

<?php if ($books === []): ?>
    <div class="card empty-state"><p>Книг пока нет.</p></div>
<?php else: ?>
    <div class="table-wrap card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Название</th>
                    <th>Автор</th>
                    <th>Категория</th>
                    <th>Год</th>
                    <th>Цена</th>
                    <th>Статус</th>
                    <th>В библиотеке</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($books as $book): ?>
                    <tr>
                        <td><?= e($book['title']) ?></td>
                        <td><?= e($book['author']) ?></td>
                        <td><?= e($book['category_name']) ?></td>
                        <td><?= $book['year_written'] ? (int) $book['year_written'] : '—' ?></td>
                        <td><?= number_format((float) $book['price'], 2, '.', ' ') ?> ₽</td>
                        <td><span class="book-badge book-badge-<?= e($book['status']) ?>"><?= e(bookStatusLabel($book['status'])) ?></span></td>
                        <td><?= (int) $book['in_library'] ? 'Да' : 'Нет' ?></td>
                        <td class="table-actions">
                            <a href="book_form.php?id=<?= (int) $book['id'] ?>" class="btn btn-outline btn-sm">Изменить</a>
                            <a href="delete.php?id=<?= (int) $book['id'] ?>" class="btn btn-danger btn-sm" data-confirm="Удалить книгу?">Удалить</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<script>
document.querySelectorAll('[data-confirm]').forEach((el) => {
    el.addEventListener('click', (e) => {
        if (!confirm(el.dataset.confirm || 'Удалить?')) e.preventDefault();
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
