<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/books.php';

requireAdmin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$book = $id > 0 ? getBookById($id) : null;

if ($id > 0 && $book === null) {
    header('Location: index.php');
    exit;
}

$pageTitle = $book ? 'Редактирование книги' : 'Новая книга';
$error = '';
$categories = getAllCategories();

$data = $book ?: [
    'title' => '',
    'author' => '',
    'category_id' => $categories[0]['id'] ?? 0,
    'year_written' => '',
    'price' => '0.00',
    'status' => BOOK_STATUS_AVAILABLE,
    'in_library' => 1,
    'description' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $book
        ? updateBook($id, $_POST)
        : createBook($_POST);

    if ($result['ok']) {
        header('Location: index.php?saved=1');
        exit;
    }

    $error = $result['error'];
    $data = array_merge($data, $_POST);
    $data['in_library'] = isset($_POST['in_library']) ? 1 : 0;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h1><?= $book ? 'Редактирование' : 'Добавление' ?> книги</h1>

    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label for="title">Название *</label>
            <input type="text" id="title" name="title" value="<?= e($data['title']) ?>" required>
        </div>
        <div class="form-group">
            <label for="author">Автор *</label>
            <input type="text" id="author" name="author" value="<?= e($data['author']) ?>" required>
        </div>
        <div class="filters-grid">
            <div class="form-group">
                <label for="category_id">Категория *</label>
                <select id="category_id" name="category_id" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>" <?= (int) $data['category_id'] === (int) $cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="year_written">Год написания</label>
                <input type="number" id="year_written" name="year_written" min="1000" max="<?= (int) date('Y') ?>"
                       value="<?= e((string) ($data['year_written'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label for="price">Цена (₽) *</label>
                <input type="text" id="price" name="price" value="<?= e((string) $data['price']) ?>" required>
            </div>
        </div>
        <div class="filters-grid">
            <div class="form-group">
                <label for="status">Статус книги</label>
                <select id="status" name="status">
                    <?php foreach (bookStatusOptions() as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= ($data['status'] ?? '') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" name="in_library" value="1" <?= !empty($data['in_library']) ? 'checked' : '' ?>>
                    Доступна в библиотеке (видна пользователям)
                </label>
            </div>
        </div>
        <div class="form-group">
            <label for="description">Описание</label>
            <textarea id="description" name="description" rows="5"><?= e($data['description'] ?? '') ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Сохранить</button>
            <a href="index.php" class="btn btn-outline">Отмена</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
