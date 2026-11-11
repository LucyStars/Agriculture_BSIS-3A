<?php
$pageTitle = $pageTitle ?? 'Agriculture Information System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= e($pageTitle) ?> | Agriculture IS</title>
</head>
<body>
<?php if (is_logged_in()): ?>
<p>
    <a href="index.php">Dashboard</a> |
    <a href="farmers.php">Farmers</a> |
    <a href="farms.php">Farms</a> |
    <a href="crops.php">Crops</a> |
    <a href="plantings.php">Plantings</a> |
    <a href="harvests.php">Harvests</a> |
    <a href="reports.php">Reports</a>
    &nbsp;&nbsp;
    Logged in as <?= e($_SESSION['full_name']) ?> (<?= e($_SESSION['role_name']) ?>)
    | <a href="logout.php">Logout</a>
</p>
<hr>
<?php endif; ?>
<h1><?= e($pageTitle) ?></h1>