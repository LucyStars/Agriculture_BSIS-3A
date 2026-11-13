<?php
require_once __DIR__ . '/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);

try {
    $pdo = (new Database())->connect();

    $stmt = $pdo->prepare(
        'SELECT p.planting_id, p.farm_id, f.farm_name, fr.first_name, fr.last_name, p.crop_id, c.crop_name, c.variety,
                p.planting_date, p.area_planted, p.seed_quantity, p.expected_harvest_date,
                st.status_description, p.created_at, p.updated_at
         FROM planting p
         JOIN farm f ON f.farm_id = p.farm_id
         JOIN farmer fr ON fr.farmer_id = f.farmer_id
         JOIN crop c ON c.crop_id = p.crop_id
         JOIN planting_status st ON st.status_id = p.status_id
         WHERE p.planting_id = :id'
    );
    $stmt->execute([':id' => $id]);
    $planting = $stmt->fetch();
    if (!$planting) {
        flash_set('error', 'That planting record was not found.');
        header('Location: plantings.php');
        exit;
    }

    $stmt = $pdo->prepare(
        'SELECT h.harvest_id, h.harvest_date, h.quantity_kg, h.selling_price_per_kg,
                h.quantity_kg * h.selling_price_per_kg AS estimated_value,
                g.quality_grade_description, r.remarks_description
         FROM harvest h
         JOIN harvest_quality_grade g ON g.quality_grade_id = h.quality_grade_id
         JOIN harvest_remark r ON r.remarks_id = h.remarks_id
         WHERE h.planting_id = :id
         ORDER BY h.harvest_date, h.harvest_id'
    );
    $stmt->execute([':id' => $id]);
    $harvests = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('Planting view error: ' . $e->getMessage());
    flash_set('error', 'Could not load the planting record. Please try again later.');
    header('Location: plantings.php');
    exit;
}

$pageTitle = 'Planting Details';
include __DIR__ . '/layout_header.php';
?>
<table border="1" cellpadding="6">
    <tr><th align="left">ID</th><td><?= e($planting['planting_id']) ?></td></tr>
    <tr><th align="left">Farm</th><td><a href="farm_view.php?id=<?= e($planting['farm_id']) ?>"><?= e($planting['farm_name']) ?></a></td></tr>
    <tr><th align="left">Farmer</th><td><?= e($planting['last_name']) ?>, <?= e($planting['first_name']) ?></td></tr>
    <tr><th align="left">Crop</th><td><a href="crop_view.php?id=<?= e($planting['crop_id']) ?>"><?= e($planting['crop_name']) ?><?= $planting['variety'] ? ' (' . e($planting['variety']) . ')' : '' ?></a></td></tr>
    <tr><th align="left">Planting date</th><td><?= e($planting['planting_date']) ?></td></tr>
    <tr><th align="left">Area planted (ha)</th><td><?= e($planting['area_planted']) ?></td></tr>
    <tr><th align="left">Seed quantity</th><td><?= e($planting['seed_quantity']) ?></td></tr>
    <tr><th align="left">Expected harvest date</th><td><?= e($planting['expected_harvest_date']) ?></td></tr>
    <tr><th align="left">Status</th><td><?= e($planting['status_description']) ?></td></tr>
    <tr><th align="left">Record created</th><td><?= e($planting['created_at']) ?></td></tr>
    <tr><th align="left">Last updated</th><td><?= e($planting['updated_at']) ?></td></tr>
</table>

<h2>Harvests (<?= e(count($harvests)) ?>)</h2>
<?php if ($harvests): ?>
    <table border="1" cellpadding="6">
        <tr><th>ID</th><th>Harvest date</th><th>Quantity (kg)</th><th>Price per kg</th><th>Estimated value</th><th>Grade</th><th>Remarks</th></tr>
        <?php foreach ($harvests as $h): ?>
            <tr>
                <td><a href="harvest_view.php?id=<?= e($h['harvest_id']) ?>"><?= e($h['harvest_id']) ?></a></td>
                <td><?= e($h['harvest_date']) ?></td>
                <td><?= e(number_format((float)$h['quantity_kg'], 2)) ?></td>
                <td><?= e(number_format((float)$h['selling_price_per_kg'], 2)) ?></td>
                <td><?= e(number_format((float)$h['estimated_value'], 2)) ?></td>
                <td><?= e($h['quality_grade_description']) ?></td>
                <td><?= e($h['remarks_description']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>No harvest has been recorded for this planting yet.</p>
<?php endif; ?>

<p>
    <a href="planting_form.php?id=<?= e($planting['planting_id']) ?>">Edit</a> |
    <a href="plantings.php">Back to list</a>
</p>
<?php
include __DIR__ . '/layout_footer.php';
?>