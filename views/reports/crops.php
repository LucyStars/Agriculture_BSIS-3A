<?php
report_filter_form('report_crops', $from, $to, 'Planting date');
report_errors($errors, $error);
?>
<div class="table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
    <thead class="table-head"><tr><th>Farm</th><th>Farmer</th><th>Crop</th><th>Plantings</th><th>Area planted (ha)</th></tr></thead>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['farm_name']) ?></td>
            <td><?= e($r['last_name']) ?>, <?= e($r['first_name']) ?></td>
            <td><?= e($r['crop_name']) ?><?= $r['variety'] ? ' (' . e($r['variety']) . ')' : '' ?></td>
            <td class="text-end"><?= e($r['plantings']) ?></td>
            <td class="text-end"><?= e(number_format((float)$r['area'], 2)) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows && !$errors && !$error): ?>
        <tr><td colspan="5">No records found for this date range.</td></tr>
    <?php endif; ?>
    <?php if ($rows): ?>
        <tr><th colspan="3" align="right">Total</th><th class="text-end"><?= e($totalPlantings) ?></th><th class="text-end"><?= e(number_format($totalArea, 2)) ?></th></tr>
    <?php endif; ?>
</table>
</div>
