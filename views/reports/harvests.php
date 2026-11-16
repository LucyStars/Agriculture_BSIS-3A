<?php
report_filter_form('report_harvests', $from, $to, 'Harvest date');
report_errors($errors, $error);
?>
<div class="table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
    <thead class="table-head"><tr>
        <th>ID</th><th>Harvest date</th><th>Farm</th><th>Crop</th><th>Quantity (kg)</th>
        <th>Price per kg</th><th>Estimated value</th><th>Grade</th><th>Remarks</th>
    </tr></thead>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><a href="<?= url('harvest_view', ['id' => $r['harvest_id']]) ?>"><?= e($r['harvest_id']) ?></a></td>
            <td><?= e($r['harvest_date']) ?></td>
            <td><?= e($r['farm_name']) ?></td>
            <td><?= e($r['crop_name']) ?></td>
            <td class="text-end"><?= e(number_format((float)$r['quantity_kg'], 2)) ?></td>
            <td class="text-end"><?= e(number_format((float)$r['selling_price_per_kg'], 2)) ?></td>
            <td class="text-end"><?= e(number_format((float)$r['value'], 2)) ?></td>
            <td><?= e($r['quality_grade_description']) ?></td>
            <td><?= e($r['remarks_description']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows && !$errors && !$error): ?>
        <tr><td colspan="9">No records found for this date range.</td></tr>
    <?php endif; ?>
    <?php if ($rows): ?>
        <tr>
            <th colspan="4" align="right">Total (<?= e(count($rows)) ?> harvests)</th>
            <th class="text-end"><?= e(number_format($totalKg, 2)) ?></th><td></td>
            <th class="text-end"><?= e(number_format($totalValue, 2)) ?></th><td colspan="2"></td>
        </tr>
    <?php endif; ?>
</table>
</div>
