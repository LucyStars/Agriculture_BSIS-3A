<?php
require_once __DIR__ . '/auth.php';
require_login();

$tables = ['farmer' => 'Farmers', 'farm' => 'Farms', 'crop' => 'Crops',
           'planting' => 'Plantings', 'harvest' => 'Harvests'];
$counts = [];
$error = '';
try {
    $pdo = (new Database())->connect();
    foreach ($tables as $table => $label) {
        $counts[$table] = (int)$pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    }
} catch (Throwable $e) {
    error_log('Dashboard error: ' . $e->getMessage());
    $error = 'Could not load the dashboard numbers. Please try again later.';
}

$pageTitle = 'Dashboard';
include __DIR__ . '/layout_header.php';
?>
<p>Welcome, <?= e($_SESSION['full_name']) ?>.</p>
<?php if ($error): ?><p style="color:red"><?= e($error) ?></p><?php endif; ?>
<table border="1" cellpadding="6">
    <tr><th>Record</th><th>Total</th></tr>
    <?php foreach ($tables as $table => $label): ?>
        <tr><td><?= e($label) ?></td><td><?= isset($counts[$table]) ? e($counts[$table]) : '-' ?></td></tr>
    <?php endforeach; ?>
</table>
<?php
include __DIR__ . '/layout_footer.php';
?>