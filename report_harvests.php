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
            "SELECT h.harvest_id, h.harvest_date, f.farm_name, c.crop_name, h.quantity_kg,
                    h.selling_price_per_kg, (h.quantity_kg * h.selling_price_per_kg) AS value,
                    g.quality_grade_description, r.remarks_description
             FROM harvest h
             JOIN planting p ON p.planting_id = h.planting_id
             JOIN farm f ON f.farm_id = p.farm_id
             JOIN crop c ON c.crop_id = p.crop_id
             JOIN harvest_quality_grade g ON g.quality_grade_id = h.quality_grade_id
             JOIN harvest_remark r ON r.remarks_id = h.remarks_id
             $where
             ORDER BY h.harvest_date, h.harvest_id"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
        error_log('Report harvests error: ' . $e->getMessage());
        $error = 'Could not load the report. Please try again later.';
    }
}

$totalKg = 0;
$totalValue = 0;
foreach ($rows as $r) {
    $totalKg += (float)$r['quantity_kg'];
    $totalValue += (float)$r['value'];
}

$pageTitle = 'Report: Harvest Records';
include __DIR__ . '/layout_header.php';
report_filter_form('report_harvests.php', $from, $to, 'Harvest date');
report_errors($errors, $error);
?>
<div class="table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
    <thead class="table-head"><tr>
        <th>ID</th><th>Harvest date</th><th>Farm</th><th>Crop</th><th>Quantity (kg)</th>
        <th>Price per kg</th><th>Estimated value</th><th>Grade</th><th>Remarks</th>
    </tr></thead>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><a href="harvest_view.php?id=<?= e($r['harvest_id']) ?>"><?= e($r['harvest_id']) ?></a></td>
            <td><?= e($r['harvest_date']) ?></td>
            <td><?= e($r['farm_name']) ?></td>
            <td><?= e($r['crop_name']) ?></td>
            <td class="text-end"><?= e(number_format((float)$r['quantity_kg'], 2)) ?></td>
            <td class="text-end"><?= e(number_format((float)$r['selling_price_per_kg'], 2)) ?></td>
            <td class="text-end"><?= e(number_format((float)$r['value'], 2)) ?></td>
            <td><?= e($r['quality_grade_description']) ?></td>
            <td><?= e($r['remarks_description']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows && !$errors && !$error): ?>
        <tr><td colspan="9">No records found for this date range.</td></tr>
    <?php endif; ?>
    <?php if ($rows): ?>
        <tr>
            <th colspan="4" align="right">Total (<?= e(count($rows)) ?> harvests)</th>
            <th class="text-end"><?= e(number_format($totalKg, 2)) ?></th><td></td>
            <th class="text-end"><?= e(number_format($totalValue, 2)) ?></th><td colspan="2"></td>
        </tr>
    <?php endif; ?>
</table>
</div>
<?php
include __DIR__ . '/layout_footer.php';
?>