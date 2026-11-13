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
        $conditions = report_conditions('fr.date_registered', $from, $to, $params);
        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $stmt = $pdo->prepare(
            "SELECT fr.last_name, fr.first_name, fr.contact_number, fr.date_registered,
                    f.farm_name, f.location, f.area_hectares,
                    s.soil_type_description, i.irrigation_type_description
             FROM farmer fr
             LEFT JOIN farm f ON f.farmer_id = fr.farmer_id
             LEFT JOIN farm_soil_type s ON s.soil_type_id = f.soil_type_id
             LEFT JOIN farm_irrigation_type i ON i.irrigation_type_id = f.irrigation_type_id
             $where
             ORDER BY fr.last_name, fr.first_name, f.farm_name"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
        error_log('Report farmers error: ' . $e->getMessage());
        $error = 'Could not load the report. Please try again later.';
    }
}

$totalArea = 0;
foreach ($rows as $r) {
    $totalArea += (float)$r['area_hectares'];
}

$pageTitle = 'Report: Farmers and Their Farms';
include __DIR__ . '/layout_header.php';
report_filter_form('report_farmers.php', $from, $to, 'Date registered');
report_errors($errors, $error);
?>
<table border="1" cellpadding="6">
    <tr>
        <th>Farmer</th><th>Contact</th><th>Registered</th><th>Farm</th>
        <th>Location</th><th>Area (ha)</th><th>Soil</th><th>Irrigation</th>
    </tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['last_name']) ?>, <?= e($r['first_name']) ?></td>
            <td><?= e($r['contact_number']) ?></td>
            <td><?= e($r['date_registered']) ?></td>
            <td><?= $r['farm_name'] === null ? '<em>no farm yet</em>' : e($r['farm_name']) ?></td>
            <td><?= e($r['location']) ?></td>
            <td align="right"><?= $r['area_hectares'] === null ? '' : e(number_format((float)$r['area_hectares'], 2)) ?></td>
            <td><?= e($r['soil_type_description']) ?></td>
            <td><?= e($r['irrigation_type_description']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows && !$errors && !$error): ?>
        <tr><td colspan="8">No records found for this date range.</td></tr>
    <?php endif; ?>
    <?php if ($rows): ?>
        <tr><th colspan="5" align="right">Total area</th><th align="right"><?= e(number_format($totalArea, 2)) ?></th><td colspan="2"></td></tr>
    <?php endif; ?>
</table>
<?php
include __DIR__ . '/layout_footer.php';
?>