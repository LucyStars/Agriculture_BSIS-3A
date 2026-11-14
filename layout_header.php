<?php
$pageTitle = $pageTitle ?? 'Agriculture Information System';
$script = basename($_SERVER['SCRIPT_NAME']);
$loggedIn = is_logged_in();

function nav_icon(string $inner): string
{
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . $inner . '</svg>';
}

$navItems = [
    ['Dashboard', 'index.php', '/^index/', '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>'],
    ['Farmers', 'farmers.php', '/^farmer(s|_)/', '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
    ['Farms', 'farms.php', '/^farm(s|_)/', '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>'],
    ['Crops', 'crops.php', '/^crop/', '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>'],
    ['Plantings', 'plantings.php', '/^planting/', '<path d="M7 20h10"/><path d="M10 20c5.5-2.5.8-6.4 3-10"/><path d="M9.5 9.4c1.1.8 1.8 2.2 2.3 3.7-2 .4-3.5.4-4.8-.3-1.2-.6-2.3-1.9-3-4.2 2.8-.5 4.4 0 5.5.8z"/><path d="M14.1 6a7 7 0 0 0-1.1 4c1.9-.1 3.3-.6 4.3-1.4 1-1 1.6-2.3 1.7-4.6-2.7.1-4 1-4.9 2z"/>'],
    ['Harvests', 'harvests.php', '/^harvest/', '<path d="M16.5 9.4l-9-5.19"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>'],
    ['Reports', 'reports.php', '/^report/', '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>'],
];
if (is_admin()) {
    $navItems[] = ['Users', 'users.php', '/^user(s|_)/', '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> | Agriculture IS</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="style.css">
</head>
<?php if ($loggedIn): ?>
<body>
<input type="checkbox" id="nav-toggle" class="nav-toggle" aria-label="Toggle menu">
<div class="app-shell">
    <label for="nav-toggle" class="topbar">
        <?= nav_icon('<line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>') ?>
        <span>Menu</span>
    </label>
    <aside class="sidebar">
        <div class="brand">Agriculture IS<small>AP3 Midterm Project</small></div>
        <nav class="side-nav">
            <?php foreach ($navItems as [$label, $href, $pattern, $icon]): ?>
                <a href="<?= e($href) ?>" class="<?= preg_match($pattern, $script) ? 'active' : '' ?>"><?= nav_icon($icon) ?><span><?= e($label) ?></span></a>
            <?php endforeach; ?>
        </nav>
        <div class="side-user">
            <div class="fw-semibold"><?= e($_SESSION['full_name']) ?></div>
            <div class="small opacity-75"><?= e($_SESSION['role_name']) ?></div>
            <nav class="side-nav mt-2">
                <a href="logout.php"><?= nav_icon('<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>') ?><span>Logout</span></a>
            </nav>
        </div>
    </aside>
    <main class="content">
        <h1><?= e($pageTitle) ?></h1>
        <?= flash_show() ?>
<?php else: ?>
<body class="guest">
<main class="guest-wrap">
    <?= flash_show() ?>
<?php endif; ?>