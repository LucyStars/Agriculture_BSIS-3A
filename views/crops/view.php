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
                <td><a href="<?= url('planting_view', ['id' => $p['planting_id']]) ?>"><?= e($p['planting_id']) ?></a></td>
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
    <a class="btn btn-sm btn-warning" href="<?= url('crop_form', ['id' => $crop['crop_id']]) ?>">Edit</a> |
    <a class="btn btn-outline-secondary" href="<?= url('crops') ?>">Back to list</a>
</p>
