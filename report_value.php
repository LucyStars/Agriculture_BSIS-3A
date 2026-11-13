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
            "SELECT c.crop_name, c.variety, SUM(h.quantity_kg) AS total_kg,
                    SUM(h.quantity_kg * h.selling_price_per_kg) AS total_value
             FROM harvest h
             JOIN planting p ON p.planting_id = h.planting_id
             JOIN crop c ON c.crop_id = p.crop_id
             $where
             GROUP BY c.crop_id
             ORDER BY total_value DESC, c.crop_name"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
        error_log('Report value error: ' . $e->getMessage());
        $error = 'Could not load the report. Please try again later.';
    }
}

$totalKg = 0;
$totalValue = 0;
foreach ($rows as $r) {
    $totalKg += (float)$r['total_kg'];
    $totalValue += (float)$r['total_value'];
}

$pageTitle = 'Report: Estimated Harvest Value';
include __DIR__ . '/layout_header.php';
report_filter_form('report_value.php', $from, $to, 'Harvest date');
report_errors($errors, $error);
?>
<table border="1" cellpadding="6">
    <tr><th>Crop</th><th>Total quantity (kg)</th><th>Estimated value</th></tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['crop_name']) ?><?= $r['variety'] ? ' (' . e($r['variety']) . ')' : '' ?></td>
            <td align="right"><?= e(number_format((float)$r['total_kg'], 2)) ?></td>
            <td align="right"><?= e(number_format((float)$r['total_value'], 2)) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows && !$errors && !$error): ?>
        <tr><td colspan="3">No records found for this date range.</td></tr>
    <?php endif; ?>
    <?php if ($rows): ?>
        <tr><th align="right">Total</th><th align="right"><?= e(number_format($totalKg, 2)) ?></th><th align="right"><?= e(number_format($totalValue, 2)) ?></th></tr>
    <?php endif; ?>
</table>
<?php
include __DIR__ . '/layout_footer.php';
?>