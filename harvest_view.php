<?php
require_once __DIR__ . '/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);

try {
    $pdo = (new Database())->connect();

    $stmt = $pdo->prepare(
        'SELECT h.harvest_id, h.planting_id, p.planting_date, f.farm_id, f.farm_name, fr.first_name, fr.last_name,
                c.crop_id, c.crop_name, c.variety, h.harvest_date, h.quantity_kg, h.selling_price_per_kg,
                (h.quantity_kg * h.selling_price_per_kg) AS estimated_value,
                g.quality_grade_description, r.remarks_description, h.created_at, h.updated_at
         FROM harvest h
         JOIN planting p ON p.planting_id = h.planting_id
         JOIN farm f ON f.farm_id = p.farm_id
         JOIN farmer fr ON fr.farmer_id = f.farmer_id
         JOIN crop c ON c.crop_id = p.crop_id
         JOIN harvest_quality_grade g ON g.quality_grade_id = h.quality_grade_id
         JOIN harvest_remark r ON r.remarks_id = h.remarks_id
         WHERE h.harvest_id = :id'
    );
    $stmt->execute([':id' => $id]);
    $h = $stmt->fetch();
    if (!$h) {
        flash_set('error', 'That harvest record was not found.');
        header('Location: harvests.php');
        exit;
    }
} catch (Throwable $e) {
    error_log('Harvest view error: ' . $e->getMessage());
    flash_set('error', 'Could not load the harvest record. Please try again later.');
    header('Location: harvests.php');
    exit;
}

$pageTitle = 'Harvest Details';
include __DIR__ . '/layout_header.php';
?>
<div class="table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
    <tr><th class="text-nowrap">ID</th><td><?= e($h['harvest_id']) ?></td></tr>
    <tr><th class="text-nowrap">Planting record</th><td><a href="planting_view.php?id=<?= e($h['planting_id']) ?>">#<?= e($h['planting_id']) ?></a> (planted <?= e($h['planting_date']) ?>)</td></tr>
    <tr><th class="text-nowrap">Farm</th><td><a href="farm_view.php?id=<?= e($h['farm_id']) ?>"><?= e($h['farm_name']) ?></a></td></tr>
    <tr><th class="text-nowrap">Farmer</th><td><?= e($h['last_name']) ?>, <?= e($h['first_name']) ?></td></tr>
    <tr><th class="text-nowrap">Crop</th><td><a href="crop_view.php?id=<?= e($h['crop_id']) ?>"><?= e($h['crop_name']) ?><?= $h['variety'] ? ' (' . e($h['variety']) . ')' : '' ?></a></td></tr>
    <tr><th class="text-nowrap">Harvest date</th><td><?= e($h['harvest_date']) ?></td></tr>
    <tr><th class="text-nowrap">Quantity (kg)</th><td><?= e(number_format((float)$h['quantity_kg'], 2)) ?></td></tr>
    <tr><th class="text-nowrap">Selling price per kg</th><td><?= e(number_format((float)$h['selling_price_per_kg'], 2)) ?></td></tr>
    <tr><th class="text-nowrap">Estimated value</th><td><?= e(number_format((float)$h['estimated_value'], 2)) ?></td></tr>
    <tr><th class="text-nowrap">Quality grade</th><td><?= e($h['quality_grade_description']) ?></td></tr>
    <tr><th class="text-nowrap">Remarks</th><td><?= e($h['remarks_description']) ?></td></tr>
    <tr><th class="text-nowrap">Record created</th><td><?= e($h['created_at']) ?></td></tr>
    <tr><th class="text-nowrap">Last updated</th><td><?= e($h['updated_at']) ?></td></tr>
</table>
</div>

<p>
    <a class="btn btn-sm btn-warning" href="harvest_form.php?id=<?= e($h['harvest_id']) ?>">Edit</a> |
    <a class="btn btn-outline-secondary" href="harvests.php">Back to list</a>
</p>
<?php
include __DIR__ . '/layout_footer.php';
?>