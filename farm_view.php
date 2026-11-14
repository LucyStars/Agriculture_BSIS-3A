<?php
require_once __DIR__ . '/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);

try {
    $pdo = (new Database())->connect();

    $stmt = $pdo->prepare(
        'SELECT f.farm_id, f.farm_name, f.farmer_id, fr.first_name, fr.last_name, f.location, f.area_hectares,
                s.soil_type_description, i.irrigation_type_description, f.created_at, f.updated_at
         FROM farm f
         JOIN farmer fr ON fr.farmer_id = f.farmer_id
         JOIN farm_soil_type s ON s.soil_type_id = f.soil_type_id
         JOIN farm_irrigation_type i ON i.irrigation_type_id = f.irrigation_type_id
         WHERE f.farm_id = :id'
    );
    $stmt->execute([':id' => $id]);
    $farm = $stmt->fetch();
    if (!$farm) {
        flash_set('error', 'That farm was not found.');
        header('Location: farms.php');
        exit;
    }

    $stmt = $pdo->prepare(
        'SELECT p.planting_id, c.crop_name, c.variety, p.planting_date, p.area_planted, st.status_description
         FROM planting p
         JOIN crop c ON c.crop_id = p.crop_id
         JOIN planting_status st ON st.status_id = p.status_id
         WHERE p.farm_id = :id
         ORDER BY p.planting_date, p.planting_id'
    );
    $stmt->execute([':id' => $id]);
    $plantings = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('Farm view error: ' . $e->getMessage());
    flash_set('error', 'Could not load the farm. Please try again later.');
    header('Location: farms.php');
    exit;
}

$pageTitle = 'Farm Details';
include __DIR__ . '/layout_header.php';
?>
<div class="table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
    <tr><th class="text-nowrap">ID</th><td><?= e($farm['farm_id']) ?></td></tr>
    <tr><th class="text-nowrap">Farm</th><td><?= e($farm['farm_name']) ?></td></tr>
    <tr><th class="text-nowrap">Farmer</th><td><a href="farmer_view.php?id=<?= e($farm['farmer_id']) ?>"><?= e($farm['last_name']) ?>, <?= e($farm['first_name']) ?></a></td></tr>
    <tr><th class="text-nowrap">Location</th><td><?= e($farm['location']) ?></td></tr>
    <tr><th class="text-nowrap">Area (hectares)</th><td><?= e($farm['area_hectares']) ?></td></tr>
    <tr><th class="text-nowrap">Soil type</th><td><?= e($farm['soil_type_description']) ?></td></tr>
    <tr><th class="text-nowrap">Irrigation type</th><td><?= e($farm['irrigation_type_description']) ?></td></tr>
    <tr><th class="text-nowrap">Record created</th><td><?= e($farm['created_at']) ?></td></tr>
    <tr><th class="text-nowrap">Last updated</th><td><?= e($farm['updated_at']) ?></td></tr>
</table>
</div>

<h2>Plantings on this farm (<?= e(count($plantings)) ?>)</h2>
<?php if ($plantings): ?>
    <div class="table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
        <thead class="table-head"><tr><th>ID</th><th>Crop</th><th>Planting date</th><th>Area planted (ha)</th><th>Status</th></tr></thead>
        <?php foreach ($plantings as $p): ?>
            <tr>
                <td><a href="planting_view.php?id=<?= e($p['planting_id']) ?>"><?= e($p['planting_id']) ?></a></td>
                <td><?= e($p['crop_name']) ?><?= $p['variety'] ? ' (' . e($p['variety']) . ')' : '' ?></td>
                <td><?= e($p['planting_date']) ?></td>
                <td><?= e($p['area_planted']) ?></td>
                <td><?= e($p['status_description']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php else: ?>
    <p>Nothing has been planted on this farm yet.</p>
<?php endif; ?>

<p>
    <a class="btn btn-sm btn-warning" href="farm_form.php?id=<?= e($farm['farm_id']) ?>">Edit</a> |
    <a class="btn btn-outline-secondary" href="farms.php">Back to list</a>
</p>
<?php
include __DIR__ . '/layout_footer.php';
?>