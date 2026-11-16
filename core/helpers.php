<?php
// Shared helper functions: session, escaping, CSRF, flash messages, URLs, and list-page helpers.

if (session_status() === PHP_SESSION_NONE) {
    session_save_path(sys_get_temp_dir());
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

function is_admin(): bool
{
    return is_logged_in() && $_SESSION['role_name'] === 'Administrator';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_show(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $class = $flash['type'] === 'success' ? 'success' : 'danger';
    return '<div class="alert alert-' . $class . '">' . e($flash['message']) . '</div>';
}

function valid_date(string $date): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d !== false && $d->format('Y-m-d') === $date;
}

function is_duplicate_error(Throwable $e): bool
{
    return $e instanceof PDOException && $e->getCode() === '23505';
}

// The route name of the current request (the page the front controller is showing).
function current_route(): string
{
    $route = $_GET['route'] ?? 'index';
    return is_string($route) ? $route : 'index';
}

// Builds a plain address such as index.php?route=farmers&id=3 (used for redirects).
function route_path(string $route, array $params = []): string
{
    return 'index.php?' . http_build_query(['route' => $route] + $params);
}

// The same address, escaped so it is safe inside an HTML attribute.
function url(string $route, array $params = []): string
{
    return e(route_path($route, $params));
}

// List pages: the address of the current list with some values changed (page number, sort column).
function link_with(array $state, array $change): string
{
    return 'index.php?' . e(http_build_query(array_merge($state, $change)));
}

// List pages: a column heading that sorts the table when clicked.
function sort_header(array $state, string $label, string $key): string
{
    $nextDir = ($state['sort'] === $key && $state['dir'] === 'asc') ? 'desc' : 'asc';
    $arrow = $state['sort'] === $key ? ($state['dir'] === 'asc' ? ' ▲' : ' ▼') : '';
    return '<a href="' . link_with($state, ['sort' => $key, 'dir' => $nextDir, 'page' => 1]) . '">' . e($label) . $arrow . '</a>';
}

// Reports: the date filter shown above every report.
function report_filter_form(string $route, string $from, string $to, string $dateLabel): void
{
    ?>
    <form method="get" class="filter-bar">
        <input type="hidden" name="route" value="<?= e($route) ?>">
        <label><?= e($dateLabel) ?> from</label>
        <input class="form-control" type="date" name="from" value="<?= e($from) ?>">
        <label>to</label>
        <input class="form-control" type="date" name="to" value="<?= e($to) ?>">
        <button type="submit" class="btn btn-primary">Apply</button>
        <?php if ($from !== '' || $to !== ''): ?><a class="btn btn-outline-secondary" href="<?= url($route) ?>">Clear</a><?php endif; ?>
    </form>
    <p><a class="btn btn-outline-secondary" href="<?= url('reports') ?>">&laquo; All reports</a></p>
    <?php
}

// Reports: shows the validation or loading errors above the table.
function report_errors(array $errors, string $error): void
{
    foreach ($errors as $msg) {
        echo '<div class="alert alert-danger">' . e($msg) . '</div>';
    }
    if ($error !== '') {
        echo '<div class="alert alert-danger">' . e($error) . '</div>';
    }
}
