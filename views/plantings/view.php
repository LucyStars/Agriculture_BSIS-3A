<div class="table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
    <tr><th class="text-nowrap">ID</th><td><?= e($planting['planting_id']) ?></td></tr>
    <tr><th class="text-nowrap">Farm</th><td><a href="<?= url('farm_view', ['id' => $planting['farm_id']]) ?>"><?= e($planting['farm_name']) ?></a></td></tr>
    <tr><th class="text-nowrap">Farmer</th><td><?= e($planting['last_name']) ?>, <?= e($planting['first_name']) ?></td></tr>
    <tr><th class="text-nowrap">Crop</th><td><a href="<?= url('crop_view', ['id' => $planting['crop_id']]) ?>"><?= e($planting['crop_name']) ?><?= $planting['variety'] ? ' (' . e($planting['variety']) . ')' : '' ?></a></td></tr>
    <tr><th class="text-nowrap">Planting date</th><td><?= e($planting['planting_date']) ?></td></tr>
    <tr><th class="text-nowrap">Area planted (ha)</th><td><?= e($planting['area_planted']) ?></td></tr>
    <tr><th class="text-nowrap">Seed quantity</th><td><?= e($planting['seed_quantity']) ?></td></tr>
    <tr><th class="text-nowrap">Expected harvest date</th><td><?= e($planting['expected_harvest_date']) ?></td></tr>
    <tr><th class="text-nowrap">Status</th><td><?= e($planting['status_description']) ?></td></tr>
    <tr><th class="text-nowrap">Record created</th><td><?= e($planting['created_at']) ?></td></tr>
    <tr><th class="text-nowrap">Last updated</th><td><?= e($planting['updated_at']) ?></td></tr>
</table>
</div>

<h2>Harvests (<?= e(count($harvests)) ?>)</h2>
<?php if ($harvests): ?>
    <div class="table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
        <thead class="table-head"><tr><th>ID</th><th>Harvest date</th><th>Quantity (kg)</th><th>Price per kg</th><th>Estimated value</th><th>Grade</th><th>Remarks</th></tr></thead>
        <?php foreach ($harvests as $h): ?>
            <tr>
                <td><a href="<?= url('harvest_view', ['id' => $h['harvest_id']]) ?>"><?= e($h['harvest_id']) ?></a></td>
                <td><?= e($h['harvest_date']) ?></td>
                <td><?= e(number_format((float)$h['quantity_kg'], 2)) ?></td>
                <td><?= e(number_format((float)$h['selling_price_per_kg'], 2)) ?></td>
                <td><?= e(number_format((float)$h['estimated_value'], 2)) ?></td>
                <td><?= e($h['quality_grade_description']) ?></td>
                <td><?= e($h['remarks_description']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php else: ?>
    <p>No harvest has been recorded for this planting yet.</p>
<?php endif; ?>

<p>
    <a class="btn btn-sm btn-warning" href="<?= url('planting_form', ['id' => $planting['planting_id']]) ?>">Edit</a> |
    <a class="btn btn-outline-secondary" href="<?= url('plantings') ?>">Back to list</a>
</p>
