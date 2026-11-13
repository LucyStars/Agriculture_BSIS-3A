<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/charts.php';
require_login();

$tables = [
    'farmer'   => ['Farmers',   'farmers.php'],
    'farm'     => ['Farms',     'farms.php'],
    'crop'     => ['Crops',     'crops.php'],
    'planting' => ['Plantings', 'plantings.php'],
    'harvest'  => ['Harvests',  'harvests.php'],
];
$counts = [];
$totalKg = 0;
$totalValue = 0;
$byStatus = [];
$byMonth = [];
$kgByCrop = [];
$valueByCrop = [];
$recentPlantings = [];
$recentHarvests = [];
$error = '';

try {
    $pdo = (new Database())->connect();

    foreach ($tables as $table => $info) {
        $counts[$table] = (int)$pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    }

    $totals = $pdo->query(
        'SELECT COALESCE(SUM(quantity_kg), 0) AS kg, COALESCE(SUM(quantity_kg * selling_price_per_kg), 0) AS value FROM harvest'
    )->fetch();
    $totalKg = (float)$totals['kg'];
    $totalValue = (float)$totals['value'];

    $byStatus = $pdo->query(
        'SELECT st.status_description, COUNT(p.planting_id) AS total
         FROM planting_status st
         LEFT JOIN planting p ON p.status_id = st.status_id
         GROUP BY st.status_id, st.status_description
         ORDER BY st.status_id'
    )->fetchAll();

    $byMonth = $pdo->query(
        "SELECT to_char(harvest_date, 'YYYY-MM') AS ym, SUM(quantity_kg) AS kg
         FROM harvest
         GROUP BY ym
         ORDER BY ym"
    )->fetchAll();
    $byMonth = array_slice($byMonth, -12);

    $kgByCrop = $pdo->query(
        "SELECT c.crop_name || COALESCE(' (' || c.variety || ')', '') AS label, SUM(h.quantity_kg) AS value
         FROM harvest h
         JOIN planting p ON p.planting_id = h.planting_id
         JOIN crop c ON c.crop_id = p.crop_id
         GROUP BY c.crop_id
         ORDER BY value DESC, label
         LIMIT 8"
    )->fetchAll();

    $valueByCrop = $pdo->query(
        "SELECT c.crop_name || COALESCE(' (' || c.variety || ')', '') AS label,
                SUM(h.quantity_kg * h.selling_price_per_kg) AS value
         FROM harvest h
         JOIN planting p ON p.planting_id = h.planting_id
         JOIN crop c ON c.crop_id = p.crop_id
         GROUP BY c.crop_id
         ORDER BY value DESC, label
         LIMIT 8"
    )->fetchAll();

    $recentPlantings = $pdo->query(
        'SELECT p.planting_id, f.farm_name, c.crop_name, p.planting_date, st.status_description
         FROM planting p
         JOIN farm f ON f.farm_id = p.farm_id
         JOIN crop c ON c.crop_id = p.crop_id
         JOIN planting_status st ON st.status_id = p.status_id
         ORDER BY p.planting_date DESC, p.planting_id DESC
         LIMIT 5'
    )->fetchAll();

    $recentHarvests = $pdo->query(
        'SELECT h.harvest_id, f.farm_name, c.crop_name, h.harvest_date, h.quantity_kg
         FROM harvest h
         JOIN planting p ON p.planting_id = h.planting_id
         JOIN farm f ON f.farm_id = p.farm_id
         JOIN crop c ON c.crop_id = p.crop_id
         ORDER BY h.harvest_date DESC, h.harvest_id DESC
         LIMIT 5'
    )->fetchAll();
} catch (Throwable $e) {
    error_log('Dashboard error: ' . $e->getMessage());
    $error = 'Could not load the dashboard numbers. Please try again later.';
}

$monthLabels = array_map(fn($ym) => date('M y', strtotime($ym . '-01')), array_column($byMonth, 'ym'));

$pageTitle = 'Dashboard';
include __DIR__ . '/layout_header.php';
?>
<p>Welcome, <?= e($_SESSION['full_name']) ?>.</p>
<?php if ($error): ?><p style="color:red"><?= e($error) ?></p><?php endif; ?>

<h2>Totals</h2>
<div style="overflow-x:auto">
<table border="1" cellpadding="6">
    <tr>
        <?php foreach ($tables as $table => [$label, $page]): ?>
            <th><a href="<?= e($page) ?>"><?= e($label) ?></a></th>
        <?php endforeach; ?>
        <th>Total harvested (kg)</th>
        <th>Estimated harvest value</th>
    </tr>
    <tr>
        <?php foreach ($tables as $table => $info): ?>
            <td align="center"><?= isset($counts[$table]) ? e($counts[$table]) : '-' ?></td>
        <?php endforeach; ?>
        <td align="center"><?= e(number_format($totalKg, 2)) ?></td>
        <td align="center"><?= e(number_format($totalValue, 2)) ?></td>
    </tr>
</table>
</div>

<h2>Charts</h2>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(400px,100%),1fr));gap:16px">
    <div style="border:1px solid #ccc;padding:10px">
        <h3>Plantings by status</h3>
        <?= chart_donut(array_column($byStatus, 'status_description'), array_map('intval', array_column($byStatus, 'total'))) ?>
    </div>
    <div style="border:1px solid #ccc;padding:10px">
        <h3>Harvested quantity per month (kg)</h3>
        <?= chart_columns($monthLabels, array_map('floatval', array_column($byMonth, 'kg')), CHART_COLORS[0]) ?>
    </div>
    <div style="border:1px solid #ccc;padding:10px">
        <h3>Harvested quantity by crop (kg)</h3>
        <?= chart_bars(array_column($kgByCrop, 'label'), array_map('floatval', array_column($kgByCrop, 'value')), CHART_COLORS[2], ' kg') ?>
    </div>
    <div style="border:1px solid #ccc;padding:10px">
        <h3>Estimated harvest value by crop</h3>
        <?= chart_bars(array_column($valueByCrop, 'label'), array_map('floatval', array_column($valueByCrop, 'value')), CHART_COLORS[1]) ?>
    </div>
</div>

<h2>Recent plantings</h2>
<div style="overflow-x:auto">
<table border="1" cellpadding="6">
    <tr><th>ID</th><th>Farm</th><th>Crop</th><th>Planting date</th><th>Status</th></tr>
    <?php foreach ($recentPlantings as $p): ?>
        <tr>
            <td><a href="planting_view.php?id=<?= e($p['planting_id']) ?>"><?= e($p['planting_id']) ?></a></td>
            <td><?= e($p['farm_name']) ?></td>
            <td><?= e($p['crop_name']) ?></td>
            <td><?= e($p['planting_date']) ?></td>
            <td><?= e($p['status_description']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$recentPlantings): ?><tr><td colspan="5">No plantings yet.</td></tr><?php endif; ?>
</table>
</div>
<p><a href="plantings.php">All plantings</a></p>

<h2>Recent harvests</h2>
<div style="overflow-x:auto">
<table border="1" cellpadding="6">
    <tr><th>ID</th><th>Farm</th><th>Crop</th><th>Harvest date</th><th>Quantity (kg)</th></tr>
    <?php foreach ($recentHarvests as $h): ?>
        <tr>
            <td><a href="harvest_view.php?id=<?= e($h['harvest_id']) ?>"><?= e($h['harvest_id']) ?></a></td>
            <td><?= e($h['farm_name']) ?></td>
            <td><?= e($h['crop_name']) ?></td>
            <td><?= e($h['harvest_date']) ?></td>
            <td align="right"><?= e(number_format((float)$h['quantity_kg'], 2)) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$recentHarvests): ?><tr><td colspan="5">No harvests yet.</td></tr><?php endif; ?>
</table>
</div>
<p><a href="harvests.php">All harvests</a> | <a href="reports.php">Reports</a></p>
<?php
include __DIR__ . '/layout_footer.php';
?>