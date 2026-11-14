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
<p class="text-muted">Welcome, <?= e($_SESSION['full_name']) ?>.</p>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<div class="stats">
    <?php foreach ($tables as $table => [$label, $page]): ?>
        <a class="stat" href="<?= e($page) ?>">
            <div class="num"><?= isset($counts[$table]) ? e($counts[$table]) : '-' ?></div>
            <div class="lbl"><?= e($label) ?></div>
        </a>
    <?php endforeach; ?>
    <div class="stat">
        <div class="num"><?= e(number_format($totalKg, 2)) ?></div>
        <div class="lbl">Total harvested (kg)</div>
    </div>
    <div class="stat">
        <div class="num"><?= e(number_format($totalValue, 2)) ?></div>
        <div class="lbl">Estimated harvest value</div>
    </div>
</div>

<div class="z-grid">
    <section class="panel">
        <h2>Plantings by status</h2>
        <?= chart_donut(array_column($byStatus, 'status_description'), array_map('intval', array_column($byStatus, 'total'))) ?>
    </section>

    <section class="panel">
        <h2>Recent plantings</h2>
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm align-middle mb-0">
                <thead class="table-head"><tr><th>ID</th><th>Farm</th><th>Crop</th><th>Planting date</th><th>Status</th></tr></thead>
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
        <a class="btn btn-sm btn-outline-success" href="plantings.php">All plantings</a>
    </section>

    <section class="panel">
        <h2>Recent harvests</h2>
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm align-middle mb-0">
                <thead class="table-head"><tr><th>ID</th><th>Farm</th><th>Crop</th><th>Harvest date</th><th class="text-end">Quantity (kg)</th></tr></thead>
                <?php foreach ($recentHarvests as $h): ?>
                    <tr>
                        <td><a href="harvest_view.php?id=<?= e($h['harvest_id']) ?>"><?= e($h['harvest_id']) ?></a></td>
                        <td><?= e($h['farm_name']) ?></td>
                        <td><?= e($h['crop_name']) ?></td>
                        <td><?= e($h['harvest_date']) ?></td>
                        <td class="text-end"><?= e(number_format((float)$h['quantity_kg'], 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recentHarvests): ?><tr><td colspan="5">No harvests yet.</td></tr><?php endif; ?>
            </table>
        </div>
        <a class="btn btn-sm btn-outline-success" href="harvests.php">All harvests</a>
        <a class="btn btn-sm btn-outline-success" href="reports.php">Reports</a>
    </section>

    <section class="panel">
        <h2>Harvested quantity per month (kg)</h2>
        <?= chart_columns($monthLabels, array_map('floatval', array_column($byMonth, 'kg')), CHART_COLORS[0]) ?>
    </section>

    <section class="panel">
        <h2>Harvested quantity by crop (kg)</h2>
        <?= chart_bars(array_column($kgByCrop, 'label'), array_map('floatval', array_column($kgByCrop, 'value')), CHART_COLORS[2], ' kg') ?>
    </section>

    <section class="panel">
        <h2>Estimated harvest value by crop</h2>
        <?= chart_bars(array_column($valueByCrop, 'label'), array_map('floatval', array_column($valueByCrop, 'value')), CHART_COLORS[1]) ?>
    </section>
</div>
<?php
include __DIR__ . '/layout_footer.php';
?>