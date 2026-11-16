<div class="table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
    <tr><th class="text-nowrap">ID</th><td><?= e($farm['farm_id']) ?></td></tr>
    <tr><th class="text-nowrap">Farm</th><td><?= e($farm['farm_name']) ?></td></tr>
    <tr><th class="text-nowrap">Farmer</th><td><a href="<?= url('farmer_view', ['id' => $farm['farmer_id']]) ?>"><?= e($farm['last_name']) ?>, <?= e($farm['first_name']) ?></a></td></tr>
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
                <td><a href="<?= url('planting_view', ['id' => $p['planting_id']]) ?>"><?= e($p['planting_id']) ?></a></td>
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
    <a class="btn btn-sm btn-warning" href="<?= url('farm_form', ['id' => $farm['farm_id']]) ?>">Edit</a> |
    <a class="btn btn-outline-secondary" href="<?= url('farms') ?>">Back to list</a>
</p>
