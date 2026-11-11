<?php

require_once __DIR__ . '/Database.php';

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

function current_user(): ?array
{
    return is_logged_in() ? $_SESSION : null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_role(string $role): void
{
    require_login();
    if ($_SESSION['role_name'] !== $role) {
        http_response_code(403);
        $pageTitle = 'Access denied';
        include __DIR__ . '/layout_header.php';
        echo '<p style="color:red">You do not have permission to open this page.</p>';
        include __DIR__ . '/layout_footer.php';
        exit;
    }
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
    $color = $flash['type'] === 'success' ? 'green' : 'red';
    return '<p style="color:' . $color . '"><strong>' . e($flash['message']) . '</strong></p>';
}