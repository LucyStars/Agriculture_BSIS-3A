<?php
require_once __DIR__ . '/report_helpers.php';
require_login();

[$from, $to, $errors] = report_range();
$rows = [];
$error = '';

if (!$errors) {
    try {
        $pdo = (new Database())->connect();
        $params = [];
        $conditions = report_conditions('h.harvest_date', $from, $to, $params);
        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $stmt = $pdo->prepare(
            "SELECT c.crop_name, c.variety, COUNT(h.harvest_id) AS harvests, SUM(h.quantity_kg) AS total_kg
             FROM harvest h
             JOIN planting p ON p.planting_id = h.planting_id
             JOIN crop c ON c.crop_id = p.crop_id
             $where
             GROUP BY c.crop_id
             ORDER BY total_kg DESC, c.crop_name"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
        error_log('Report quantity error: ' . $e->getMessage());
        $error = 'Could not load the report. Please try again later.';
    }
}

$totalHarvests = 0;
$totalKg = 0;
foreach ($rows as $r) {
    $totalHarvests += (int)$r['harvests'];
    $totalKg += (float)$r['total_kg'];
}

$pageTitle = 'Report: Total Harvested Quantity';
include __DIR__ . '/layout_header.php';
report_filter_form('report_quantity.php', $from, $to, 'Harvest date');
report_errors($errors, $error);
?>
<div class="table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
    <thead class="table-head"><tr><th>Crop</th><th>Harvests</th><th>Total quantity (kg)</th></tr></thead>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['crop_name']) ?><?= $r['variety'] ? ' (' . e($r['variety']) . ')' : '' ?></td>
            <td class="text-end"><?= e($r['harvests']) ?></td>
            <td class="text-end"><?= e(number_format((float)$r['total_kg'], 2)) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows && !$errors && !$error): ?>
        <tr><td colspan="3">No records found for this date range.</td></tr>
    <?php endif; ?>
    <?php if ($rows): ?>
        <tr><th class="text-end">Total</th><th class="text-end"><?= e($totalHarvests) ?></th><th class="text-end"><?= e(number_format($totalKg, 2)) ?></th></tr>
    <?php endif; ?>
</table>
</div>
<?php
include __DIR__ . '/layout_footer.php';
?>