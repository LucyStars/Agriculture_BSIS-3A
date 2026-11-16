<div class="table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
    <tr><th class="text-nowrap">ID</th><td><?= e($farmer['farmer_id']) ?></td></tr>
    <tr><th class="text-nowrap">Name</th><td><?= e($farmer['first_name']) ?> <?= e($farmer['last_name']) ?></td></tr>
    <tr><th class="text-nowrap">Contact number</th><td><?= e($farmer['contact_number']) ?></td></tr>
    <tr><th class="text-nowrap">Address</th><td><?= e($farmer['address']) ?></td></tr>
    <tr><th class="text-nowrap">Date registered</th><td><?= e($farmer['date_registered']) ?></td></tr>
    <tr><th class="text-nowrap">Record created</th><td><?= e($farmer['created_at']) ?></td></tr>
    <tr><th class="text-nowrap">Last updated</th><td><?= e($farmer['updated_at']) ?></td></tr>
</table>
</div>

<h2>Farms (<?= e(count($farms)) ?>)</h2>
<?php if ($farms): ?>
    <div class="table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
        <thead class="table-head"><tr><th>ID</th><th>Farm</th><th>Location</th><th>Area (ha)</th><th>Soil type</th><th>Irrigation</th></tr></thead>
        <?php foreach ($farms as $farm): ?>
            <tr>
                <td><?= e($farm['farm_id']) ?></td>
                <td><?= e($farm['farm_name']) ?></td>
                <td><?= e($farm['location']) ?></td>
                <td><?= e($farm['area_hectares']) ?></td>
                <td><?= e($farm['soil_type_description']) ?></td>
                <td><?= e($farm['irrigation_type_description']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php else: ?>
    <p>This farmer has no farms yet.</p>
<?php endif; ?>

<p>
    <a class="btn btn-sm btn-warning" href="<?= url('farmer_form', ['id' => $farmer['farmer_id']]) ?>">Edit</a> |
    <a class="btn btn-outline-secondary" href="<?= url('farmers') ?>">Back to list</a>
</p>
