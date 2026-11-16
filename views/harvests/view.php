<div class="table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
    <tr><th class="text-nowrap">ID</th><td><?= e($h['harvest_id']) ?></td></tr>
    <tr><th class="text-nowrap">Planting record</th><td><a href="<?= url('planting_view', ['id' => $h['planting_id']]) ?>">#<?= e($h['planting_id']) ?></a> (planted <?= e($h['planting_date']) ?>)</td></tr>
    <tr><th class="text-nowrap">Farm</th><td><a href="<?= url('farm_view', ['id' => $h['farm_id']]) ?>"><?= e($h['farm_name']) ?></a></td></tr>
    <tr><th class="text-nowrap">Farmer</th><td><?= e($h['last_name']) ?>, <?= e($h['first_name']) ?></td></tr>
    <tr><th class="text-nowrap">Crop</th><td><a href="<?= url('crop_view', ['id' => $h['crop_id']]) ?>"><?= e($h['crop_name']) ?><?= $h['variety'] ? ' (' . e($h['variety']) . ')' : '' ?></a></td></tr>
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
    <a class="btn btn-sm btn-warning" href="<?= url('harvest_form', ['id' => $h['harvest_id']]) ?>">Edit</a> |
    <a class="btn btn-outline-secondary" href="<?= url('harvests') ?>">Back to list</a>
</p>
