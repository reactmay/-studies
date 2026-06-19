<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/books.php';

requireAdmin();

$id = (int) ($_GET['id'] ?? 0);

if ($id > 0) {
    deleteBook($id);
}

header('Location: index.php?deleted=1');
exit;
