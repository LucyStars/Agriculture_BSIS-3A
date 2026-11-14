<?php
require_once __DIR__ . '/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);

try {
    $pdo = (new Database())->connect();

    $stmt = $pdo->prepare(
        'SELECT c.crop_id, c.crop_name, c.variety, cc.category_description, c.growing_period_days,
                s.recommended_season_description, c.created_at, c.updated_at
         FROM crop c
         JOIN crop_category cc ON cc.category_id = c.category_id
         JOIN crop_recommended_season s ON s.recommended_season_id = c.recommended_season_id
         WHERE c.crop_id = :id'
    );
    $stmt->execute([':id' => $id]);
    $crop = $stmt->fetch();
    if (!$crop) {
        flash_set('error', 'That crop was not found.');
        header('Location: crops.php');
        exit;
    }

    $stmt = $pdo->prepare(
        'SELECT p.planting_id, f.farm_name, p.planting_date, p.area_planted, st.status_description
         FROM planting p
         JOIN farm f ON f.farm_id = p.farm_id
         JOIN planting_status st ON st.status_id = p.status_id
         WHERE p.crop_id = :id
         ORDER BY p.planting_id'
    );
    $stmt->execute([':id' => $id]);
    $plantings = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('Crop view error: ' . $e->getMessage());
    flash_set('error', 'Could not load the crop. Please try again later.');
    header('Location: crops.php');
    exit;
}

$pageTitle = 'Crop Details';
include __DIR__ . '/layout_header.php';
?>
<div class="table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
    <tr><th class="text-nowrap">ID</th><td><?= e($crop['crop_id']) ?></td></tr>
    <tr><th class="text-nowrap">Crop</th><td><?= e($crop['crop_name']) ?></td></tr>
    <tr><th class="text-nowrap">Variety</th><td><?= e($crop['variety']) ?></td></tr>
    <tr><th class="text-nowrap">Category</th><td><?= e($crop['category_description']) ?></td></tr>
    <tr><th class="text-nowrap">Growing period (days)</th><td><?= e($crop['growing_period_days']) ?></td></tr>
    <tr><th class="text-nowrap">Recommended season</th><td><?= e($crop['recommended_season_description']) ?></td></tr>
    <tr><th class="text-nowrap">Record created</th><td><?= e($crop['created_at']) ?></td></tr>
    <tr><th class="text-nowrap">Last updated</th><td><?= e($crop['updated_at']) ?></td></tr>
</table>
</div>

<h2>Plantings of this crop (<?= e(count($plantings)) ?>)</h2>
<?php if ($plantings): ?>
    <div class="table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
        <thead class="table-head"><tr><th>ID</th><th>Farm</th><th>Planting date</th><th>Area planted (ha)</th><th>Status</th></tr></thead>
        <?php foreach ($plantings as $p): ?>
            <tr>
                <td><a href="planting_view.php?id=<?= e($p['planting_id']) ?>"><?= e($p['planting_id']) ?></a></td>
                <td><?= e($p['farm_name']) ?></td>
                <td><?= e($p['planting_date']) ?></td>
                <td><?= e($p['area_planted']) ?></td>
                <td><?= e($p['status_description']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php else: ?>
    <p>This crop has not been planted yet.</p>
<?php endif; ?>

<p>
    <a class="btn btn-sm btn-warning" href="crop_form.php?id=<?= e($crop['crop_id']) ?>">Edit</a> |
    <a class="btn btn-outline-secondary" href="crops.php">Back to list</a>
</p>
<?php
include __DIR__ . '/layout_footer.php';
?>