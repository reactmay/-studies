<?php

declare(strict_types=1);

const BOOK_STATUS_AVAILABLE = 'available';
const BOOK_STATUS_BORROWED = 'borrowed';
const BOOK_STATUS_RESERVED = 'reserved';
const BOOK_STATUS_UNAVAILABLE = 'unavailable';

const SORT_YEAR_ASC = 'year_asc';
const SORT_YEAR_DESC = 'year_desc';
const SORT_AUTHOR_ASC = 'author_asc';
const SORT_AUTHOR_DESC = 'author_desc';
const SORT_CATEGORY_ASC = 'category_asc';
const SORT_CATEGORY_DESC = 'category_desc';
const SORT_NEWEST = 'newest';

function bookStatusLabel(string $status): string
{
    return match ($status) {
        BOOK_STATUS_BORROWED => 'Выдана',
        BOOK_STATUS_RESERVED => 'Забронирована',
        BOOK_STATUS_UNAVAILABLE => 'Недоступна',
        default => 'Доступна',
    };
}

function bookStatusOptions(): array
{
    return [
        BOOK_STATUS_AVAILABLE => 'Доступна',
        BOOK_STATUS_BORROWED => 'Выдана',
        BOOK_STATUS_RESERVED => 'Забронирована',
        BOOK_STATUS_UNAVAILABLE => 'Недоступна',
    ];
}

function normalizeBookSort(string $sort): string
{
    return match ($sort) {
        SORT_YEAR_ASC, SORT_YEAR_DESC, SORT_AUTHOR_ASC, SORT_AUTHOR_DESC,
        SORT_CATEGORY_ASC, SORT_CATEGORY_DESC, SORT_NEWEST => $sort,
        default => SORT_NEWEST,
    };
}

function bookSortLabel(string $sort): string
{
    return match (normalizeBookSort($sort)) {
        SORT_YEAR_ASC => 'Год (старые)',
        SORT_YEAR_DESC => 'Год (новые)',
        SORT_AUTHOR_ASC => 'Автор (А–Я)',
        SORT_AUTHOR_DESC => 'Автор (Я–А)',
        SORT_CATEGORY_ASC => 'Категория (А–Я)',
        SORT_CATEGORY_DESC => 'Категория (Я–А)',
        default => 'Сначала новые',
    };
}

function bookSortSql(string $sort): string
{
    return match (normalizeBookSort($sort)) {
        SORT_YEAR_ASC => 'books.year_written ASC, books.title ASC',
        SORT_YEAR_DESC => 'books.year_written DESC, books.title ASC',
        SORT_AUTHOR_ASC => 'books.author ASC, books.title ASC',
        SORT_AUTHOR_DESC => 'books.author DESC, books.title ASC',
        SORT_CATEGORY_ASC => 'categories.name ASC, books.title ASC',
        SORT_CATEGORY_DESC => 'categories.name DESC, books.title ASC',
        default => 'books.created_at DESC',
    };
}

function getAllCategories(): array
{
    $stmt = getDb()->query('SELECT id, name, slug FROM categories ORDER BY name ASC');
    return $stmt->fetchAll();
}

function getCategoryById(int $id): ?array
{
    $stmt = getDb()->prepare('SELECT id, name, slug FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getBookById(int $id, bool $libraryOnly = false): ?array
{
    $sql = '
        SELECT books.*, categories.name AS category_name, categories.slug AS category_slug
        FROM books
        JOIN categories ON categories.id = books.category_id
        WHERE books.id = ?
    ';
    if ($libraryOnly) {
        $sql .= ' AND books.in_library = 1';
    }

    $stmt = getDb()->prepare($sql);
    $stmt->execute([$id]);
    $book = $stmt->fetch();

    if ($book) {
        $book['id'] = (int) $book['id'];
        $book['category_id'] = (int) $book['category_id'];
        $book['in_library'] = (int) $book['in_library'];
        $book['price'] = (float) $book['price'];
    }

    return $book ?: null;
}

function generateBooksList(
    int $page = 1,
    int $perPage = 12,
    ?int $categoryId = null,
    string $sort = SORT_NEWEST,
    bool $libraryOnly = true,
    ?string $search = null
): array {
    $page = max(1, $page);
    $perPage = max(1, min(100, $perPage));
    $offset = ($page - 1) * $perPage;
    $sort = normalizeBookSort($sort);

    $conditions = ['1=1'];
    $params = [];

    if ($libraryOnly) {
        $conditions[] = 'books.in_library = 1';
    }

    if ($categoryId !== null && $categoryId > 0) {
        $conditions[] = 'books.category_id = ?';
        $params[] = $categoryId;
    }

    $search = $search !== null ? trim($search) : '';
    if ($search !== '') {
        $conditions[] = '(books.title LIKE ? OR books.author LIKE ?)';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
    }

    $where = implode(' AND ', $conditions);

    $countStmt = getDb()->prepare("SELECT COUNT(*) FROM books WHERE $where");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $items = [];
    if ($total > 0) {
        $listSql = "
            SELECT books.*, categories.name AS category_name, categories.slug AS category_slug
            FROM books
            JOIN categories ON categories.id = books.category_id
            WHERE $where
            ORDER BY " . bookSortSql($sort) . "
            LIMIT ? OFFSET ?
        ";
        $listStmt = getDb()->prepare($listSql);
        $i = 1;
        foreach ($params as $p) {
            $listStmt->bindValue($i++, $p);
        }
        $listStmt->bindValue($i++, $perPage, PDO::PARAM_INT);
        $listStmt->bindValue($i, $offset, PDO::PARAM_INT);
        $listStmt->execute();
        $items = $listStmt->fetchAll();
    }

    return [
        'meta' => [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $total > 0 ? (int) ceil($total / $perPage) : 0,
            'category_id' => $categoryId,
            'sort' => $sort,
            'search' => $search !== '' ? $search : null,
        ],
        'items' => $items,
    ];
}

function validateBookInput(array $data): array
{
    $title = trim($data['title'] ?? '');
    $author = trim($data['author'] ?? '');
    $categoryId = (int) ($data['category_id'] ?? 0);
    $year = ($data['year_written'] ?? '') !== '' ? (int) $data['year_written'] : null;
    $price = str_replace(',', '.', trim((string) ($data['price'] ?? '0')));
    $status = $data['status'] ?? BOOK_STATUS_AVAILABLE;
    $inLibrary = isset($data['in_library']) ? 1 : 0;
    $description = trim($data['description'] ?? '');

    if ($title === '' || mb_strlen($title) < 2) {
        return ['ok' => false, 'error' => 'Укажите название книги.'];
    }
    if ($author === '' || mb_strlen($author) < 2) {
        return ['ok' => false, 'error' => 'Укажите автора.'];
    }
    if ($categoryId <= 0 || getCategoryById($categoryId) === null) {
        return ['ok' => false, 'error' => 'Выберите категорию.'];
    }
    if ($year !== null && ($year < 1000 || $year > (int) date('Y'))) {
        return ['ok' => false, 'error' => 'Некорректный год написания.'];
    }
    if (!is_numeric($price) || (float) $price < 0) {
        return ['ok' => false, 'error' => 'Некорректная цена.'];
    }
    if (!isset(bookStatusOptions()[$status])) {
        $status = BOOK_STATUS_AVAILABLE;
    }

    return [
        'ok' => true,
        'data' => [
            'title' => $title,
            'author' => $author,
            'category_id' => $categoryId,
            'year_written' => $year,
            'price' => round((float) $price, 2),
            'status' => $status,
            'in_library' => $inLibrary,
            'description' => $description,
        ],
    ];
}

function createBook(array $data): array
{
    $result = validateBookInput($data);
    if (!$result['ok']) {
        return $result;
    }
    $d = $result['data'];

    $stmt = getDb()->prepare('
        INSERT INTO books (title, author, category_id, year_written, price, status, in_library, description)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $d['title'], $d['author'], $d['category_id'], $d['year_written'],
        $d['price'], $d['status'], $d['in_library'], $d['description'] ?: null,
    ]);

    return ['ok' => true, 'book_id' => (int) getDb()->lastInsertId()];
}

function updateBook(int $id, array $data): array
{
    if (getBookById($id) === null) {
        return ['ok' => false, 'error' => 'Книга не найдена.'];
    }

    $result = validateBookInput($data);
    if (!$result['ok']) {
        return $result;
    }
    $d = $result['data'];

    $stmt = getDb()->prepare('
        UPDATE books SET title = ?, author = ?, category_id = ?, year_written = ?,
            price = ?, status = ?, in_library = ?, description = ?, updated_at = NOW()
        WHERE id = ?
    ');
    $stmt->execute([
        $d['title'], $d['author'], $d['category_id'], $d['year_written'],
        $d['price'], $d['status'], $d['in_library'], $d['description'] ?: null, $id,
    ]);

    return ['ok' => true, 'book_id' => $id];
}

function deleteBook(int $id): bool
{
    $stmt = getDb()->prepare('DELETE FROM books WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->rowCount() > 0;
}

function booksUrl(int $page = 1, ?int $categoryId = null, string $sort = SORT_NEWEST, ?string $search = null): string
{
    $q = [];
    if ($page > 1) {
        $q['page'] = $page;
    }
    if ($categoryId) {
        $q['category'] = $categoryId;
    }
    if ($sort !== SORT_NEWEST) {
        $q['sort'] = $sort;
    }
    if ($search) {
        $q['q'] = $search;
    }
    return $q === [] ? 'index.php' : 'index.php?' . http_build_query($q);
}

function renderBookCard(array $book): void
{
    ?>
    <article class="book-card card">
        <div class="book-cover">
            <?php if (!empty($book['cover'])): ?>
                <img src="<?= e($book['cover']) ?>" alt="<?= e($book['title']) ?>">
            <?php else: ?>
                <div class="book-cover-placeholder"><?= e(mb_strtoupper(mb_substr($book['title'], 0, 1))) ?></div>
            <?php endif; ?>
        </div>
        <div class="book-body">
            <h3 class="book-title"><a href="book.php?id=<?= (int) $book['id'] ?>"><?= e($book['title']) ?></a></h3>
            <p class="book-meta"><?= e($book['author']) ?></p>
            <p class="book-meta"><?= e($book['category_name']) ?><?= $book['year_written'] ? ' · ' . (int) $book['year_written'] : '' ?></p>
            <p class="book-price"><?= number_format((float) $book['price'], 2, '.', ' ') ?> ₽</p>
            <span class="book-badge book-badge-<?= e($book['status']) ?>"><?= e(bookStatusLabel($book['status'])) ?></span>
        </div>
    </article>
    <?php
}
