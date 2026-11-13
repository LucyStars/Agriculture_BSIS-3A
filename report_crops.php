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
        $conditions = report_conditions('p.planting_date', $from, $to, $params);
        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $stmt = $pdo->prepare(
            "SELECT f.farm_name, fr.last_name, fr.first_name, c.crop_name, c.variety,
                    COUNT(*) AS plantings, SUM(p.area_planted) AS area
             FROM planting p
             JOIN farm f ON f.farm_id = p.farm_id
             JOIN farmer fr ON fr.farmer_id = f.farmer_id
             JOIN crop c ON c.crop_id = p.crop_id
             $where
             GROUP BY f.farm_id, fr.farmer_id, c.crop_id
             ORDER BY f.farm_name, c.crop_name, c.variety"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
        error_log('Report crops error: ' . $e->getMessage());
        $error = 'Could not load the report. Please try again later.';
    }
}

$totalPlantings = 0;
$totalArea = 0;
foreach ($rows as $r) {
    $totalPlantings += (int)$r['plantings'];
    $totalArea += (float)$r['area'];
}

$pageTitle = 'Report: Crops Planted per Farm';
include __DIR__ . '/layout_header.php';
report_filter_form('report_crops.php', $from, $to, 'Planting date');
report_errors($errors, $error);
?>
<table border="1" cellpadding="6">
    <tr><th>Farm</th><th>Farmer</th><th>Crop</th><th>Plantings</th><th>Area planted (ha)</th></tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['farm_name']) ?></td>
            <td><?= e($r['last_name']) ?>, <?= e($r['first_name']) ?></td>
            <td><?= e($r['crop_name']) ?><?= $r['variety'] ? ' (' . e($r['variety']) . ')' : '' ?></td>
            <td align="right"><?= e($r['plantings']) ?></td>
            <td align="right"><?= e(number_format((float)$r['area'], 2)) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows && !$errors && !$error): ?>
        <tr><td colspan="5">No records found for this date range.</td></tr>
    <?php endif; ?>
    <?php if ($rows): ?>
        <tr><th colspan="3" align="right">Total</th><th align="right"><?= e($totalPlantings) ?></th><th align="right"><?= e(number_format($totalArea, 2)) ?></th></tr>
    <?php endif; ?>
</table>
<?php
include __DIR__ . '/layout_footer.php';
?>