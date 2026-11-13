<?php
require_once __DIR__ . '/auth.php';

function report_range(): array
{
    $from = trim($_GET['from'] ?? '');
    $to = trim($_GET['to'] ?? '');
    $errors = [];

    if ($from !== '' && !valid_date($from)) {
        $errors[] = 'The "from" date is not a valid date.';
    }
    if ($to !== '' && !valid_date($to)) {
        $errors[] = 'The "to" date is not a valid date.';
    }
    if (!$errors && $from !== '' && $to !== '' && $from > $to) {
        $errors[] = 'The "from" date cannot be after the "to" date.';
    }
    return [$from, $to, $errors];
}

function report_conditions(string $column, string $from, string $to, array &$params): array
{
    $parts = [];
    if ($from !== '') {
        $parts[] = "$column >= :from";
        $params[':from'] = $from;
    }
    if ($to !== '') {
        $parts[] = "$column <= :to";
        $params[':to'] = $to;
    }
    return $parts;
}

function report_filter_form(string $page, string $from, string $to, string $dateLabel): void
{
    ?>
    <form method="get">
        <label><?= e($dateLabel) ?> from</label>
        <input type="date" name="from" value="<?= e($from) ?>">
        <label>to</label>
        <input type="date" name="to" value="<?= e($to) ?>">
        <button type="submit">Apply</button>
        <?php if ($from !== '' || $to !== ''): ?><a href="<?= e($page) ?>">Clear</a><?php endif; ?>
    </form>
    <p><a href="reports.php">&laquo; All reports</a></p>
    <?php
}

function report_errors(array $errors, string $error): void
{
    foreach ($errors as $msg) {
        echo '<p style="color:red">' . e($msg) . '</p>';
    }
    if ($error !== '') {
        echo '<p style="color:red">' . e($error) . '</p>';
    }
}