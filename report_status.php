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
        $on = $conditions ? ' AND ' . implode(' AND ', $conditions) : '';

        $stmt = $pdo->prepare(
            "SELECT st.status_description, COUNT(p.planting_id) AS plantings,
                    COALESCE(SUM(p.area_planted), 0) AS area
             FROM planting_status st
             LEFT JOIN planting p ON p.status_id = st.status_id $on
             GROUP BY st.status_id, st.status_description
             ORDER BY st.status_id"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
        error_log('Report status error: ' . $e->getMessage());
        $error = 'Could not load the report. Please try again later.';
    }
}

$totalPlantings = 0;
$totalArea = 0;
foreach ($rows as $r) {
    $totalPlantings += (int)$r['plantings'];
    $totalArea += (float)$r['area'];
}

$pageTitle = 'Report: Plantings by Status';
include __DIR__ . '/layout_header.php';
report_filter_form('report_status.php', $from, $to, 'Planting date');
report_errors($errors, $error);
?>
<table border="1" cellpadding="6">
    <tr><th>Status</th><th>Plantings</th><th>Area planted (ha)</th></tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['status_description']) ?></td>
            <td align="right"><?= e($r['plantings']) ?></td>
            <td align="right"><?= e(number_format((float)$r['area'], 2)) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if ($rows): ?>
        <tr><th align="right">Total</th><th align="right"><?= e($totalPlantings) ?></th><th align="right"><?= e(number_format($totalArea, 2)) ?></th></tr>
    <?php endif; ?>
</table>
<?php
include __DIR__ . '/layout_footer.php';
?>