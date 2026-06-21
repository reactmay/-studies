<?php

declare(strict_types=1);

/** @return array{0: int, 1: int} */
function resolveCalendarMonth(?string $calMonth, ?string $selectedDate = null): array
{
    if ($calMonth !== null && preg_match('/^(\d{4})-(\d{2})$/', $calMonth, $m)) {
        $year = (int) $m[1];
        $month = (int) $m[2];
        if ($month >= 1 && $month <= 12) {
            return [$year, $month];
        }
    }

    if ($selectedDate !== null && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $selectedDate, $m)) {
        return [(int) $m[1], (int) $m[2]];
    }

    return [(int) date('Y'), (int) date('m')];
}

function normalizeFilterDate(?string $date): ?string
{
    if ($date === null || $date === '') {
        return null;
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return null;
    }

    $parts = explode('-', $date);
    if (!checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
        return null;
    }

    return $date;
}

function calendarMonthLabel(int $year, int $month): string
{
    static $names = [
        1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
        5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
        9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь',
    ];

    return ($names[$month] ?? '') . ' ' . $year;
}

function calendarMonthKey(int $year, int $month): string
{
    return sprintf('%04d-%02d', $year, $month);
}

function calendarShiftMonth(int $year, int $month, int $delta): array
{
    $dt = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
    $shifted = $dt->modify(($delta >= 0 ? '+' : '') . $delta . ' month');

    return [(int) $shifted->format('Y'), (int) $shifted->format('m')];
}

/** @return list<list<int|null>> */
function buildCalendarWeeks(int $year, int $month): array
{
    $daysInMonth = (int) (new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->format('t');
    $startWeekday = (int) (new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->format('N');

    $weeks = [];
    $week = array_fill(0, $startWeekday - 1, null);

    for ($day = 1; $day <= $daysInMonth; $day++) {
        $week[] = $day;
        if (count($week) === 7) {
            $weeks[] = $week;
            $week = [];
        }
    }

    if ($week !== []) {
        $weeks[] = array_pad($week, 7, null);
    }

    return $weeks;
}

/**
 * @return array<string, int>
 */
function getPublicPostCountsByDay(
    int $year,
    int $month,
    ?int $authorId = null,
    ?string $search = null,
    ?string $tagSlug = null
): array {
    $start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
    $endDt = new DateTimeImmutable($start);
    $end = $endDt->modify('last day of this month')->format('Y-m-d') . ' 23:59:59';

    $conditions = [publicPostsVisibilitySql(), 'posts.created_at >= ?', 'posts.created_at <= ?'];
    $params = [$start, $end];
    $joins = '';

    if ($authorId !== null && $authorId > 0) {
        $conditions[] = 'posts.user_id = ?';
        $params[] = $authorId;
    }

    $search = $search !== null ? trim($search) : '';
    if ($search !== '') {
        $conditions[] = '(posts.title LIKE ? OR posts.content LIKE ?)';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
    }

    if ($tagSlug !== null && trim($tagSlug) !== '') {
        $activeTag = getTagBySlug($tagSlug);
        if ($activeTag !== null) {
            $joins .= ' JOIN post_tags ON post_tags.post_id = posts.id';
            $joins .= ' JOIN tags ON tags.id = post_tags.tag_id AND tags.slug = ?';
            $params[] = $activeTag['slug'];
        }
    }

    $whereSql = implode(' AND ', $conditions);

    $stmt = getDb()->prepare("
        SELECT DATE(posts.created_at) AS post_day, COUNT(DISTINCT posts.id) AS posts_count
        FROM posts
        JOIN users ON users.id = posts.user_id
        {$joins}
        WHERE {$whereSql}
        GROUP BY post_day
    ");
    $stmt->execute($params);

    $counts = [];
    foreach ($stmt->fetchAll() as $row) {
        $counts[$row['post_day']] = (int) $row['posts_count'];
    }

    return $counts;
}

/**
 * @return array<string, int>
 */
function getFeedPostCountsByDay(int $userId, int $year, int $month, ?int $filterAuthorId = null): array
{
    $start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
    $endDt = new DateTimeImmutable($start);
    $end = $endDt->modify('last day of this month')->format('Y-m-d') . ' 23:59:59';

    $params = [$userId, $start, $end];
    $authorFilterSql = '';

    if ($filterAuthorId !== null && $filterAuthorId > 0) {
        $authorFilterSql = ' AND posts.user_id = ?';
        $params[] = $filterAuthorId;
    }

    $stmt = getDb()->prepare('
        SELECT DATE(posts.created_at) AS post_day, COUNT(*) AS posts_count
        FROM posts
        JOIN subscriptions ON subscriptions.following_id = posts.user_id
        WHERE subscriptions.follower_id = ?
          AND ' . publicPostsVisibilitySql() . '
          AND posts.created_at >= ?
          AND posts.created_at <= ?
          ' . $authorFilterSql . '
        GROUP BY post_day
    ');
    $stmt->execute($params);

    $counts = [];
    foreach ($stmt->fetchAll() as $row) {
        $counts[$row['post_day']] = (int) $row['posts_count'];
    }

    return $counts;
}

/**
 * @param array<string, int> $dayCounts
 * @param callable(int, int, ?string): string $monthUrlBuilder
 * @param callable(string): string $dayUrlBuilder
 */
function renderPostsCalendarSidebar(
    int $year,
    int $month,
    array $dayCounts,
    ?string $selectedDate,
    callable $monthUrlBuilder,
    callable $dayUrlBuilder,
    ?callable $clearDateUrlBuilder = null
): void {
    [$prevYear, $prevMonth] = calendarShiftMonth($year, $month, -1);
    [$nextYear, $nextMonth] = calendarShiftMonth($year, $month, 1);
    $weeks = buildCalendarWeeks($year, $month);
    $today = date('Y-m-d');
    $weekdayLabels = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
    ?>
    <aside class="card calendar-sidebar" aria-label="Календарь публикаций">
        <div class="calendar-header">
            <a class="calendar-nav" href="<?= e($monthUrlBuilder($prevYear, $prevMonth, null)) ?>" aria-label="Предыдущий месяц">←</a>
            <h2 class="calendar-title"><?= e(calendarMonthLabel($year, $month)) ?></h2>
            <a class="calendar-nav" href="<?= e($monthUrlBuilder($nextYear, $nextMonth, null)) ?>" aria-label="Следующий месяц">→</a>
        </div>

        <div class="calendar-grid" role="grid">
            <?php foreach ($weekdayLabels as $label): ?>
                <div class="calendar-weekday" role="columnheader"><?= e($label) ?></div>
            <?php endforeach; ?>

            <?php foreach ($weeks as $week): ?>
                <?php foreach ($week as $day): ?>
                    <?php if ($day === null): ?>
                        <div class="calendar-day is-empty" aria-hidden="true"></div>
                        <?php continue; ?>
                    <?php endif; ?>

                    <?php
                    $dateKey = sprintf('%04d-%02d-%02d', $year, $month, $day);
                    $count = $dayCounts[$dateKey] ?? 0;
                    $classes = ['calendar-day'];
                    if ($dateKey === $today) {
                        $classes[] = 'is-today';
                    }
                    if ($selectedDate === $dateKey) {
                        $classes[] = 'is-selected';
                    }
                    if ($count > 0) {
                        $classes[] = 'has-posts';
                    }
                    ?>

                    <?php if ($count > 0): ?>
                        <a class="<?= e(implode(' ', $classes)) ?>"
                           href="<?= e($dayUrlBuilder($dateKey)) ?>"
                           title="<?= $count ?> <?= $count === 1 ? 'публикация' : 'публикаций' ?>">
                            <span class="calendar-day-num"><?= $day ?></span>
                            <span class="calendar-day-count"><?= $count ?></span>
                        </a>
                    <?php else: ?>
                        <div class="<?= e(implode(' ', $classes)) ?>">
                            <span class="calendar-day-num"><?= $day ?></span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>

        <?php if ($selectedDate !== null && $clearDateUrlBuilder !== null): ?>
            <p class="calendar-filter-note">
                Фильтр: <strong><?= e(formatDate($selectedDate . ' 12:00:00')) ?></strong>
                <a href="<?= e($clearDateUrlBuilder()) ?>">Сбросить</a>
            </p>
        <?php else: ?>
            <p class="calendar-hint card-meta">Дни с цифрой — есть публикации. Нажмите, чтобы отфильтровать.</p>
        <?php endif; ?>
    </aside>
    <?php
}
