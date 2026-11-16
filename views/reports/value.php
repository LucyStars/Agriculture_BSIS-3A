<?php
report_filter_form('report_value', $from, $to, 'Harvest date');
report_errors($errors, $error);
?>
<div class="table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
    <thead class="table-head"><tr><th>Crop</th><th>Total quantity (kg)</th><th>Estimated value</th></tr></thead>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['crop_name']) ?><?= $r['variety'] ? ' (' . e($r['variety']) . ')' : '' ?></td>
            <td class="text-end"><?= e(number_format((float)$r['total_kg'], 2)) ?></td>
            <td class="text-end"><?= e(number_format((float)$r['total_value'], 2)) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows && !$errors && !$error): ?>
        <tr><td colspan="3">No records found for this date range.</td></tr>
    <?php endif; ?>
    <?php if ($rows): ?>
        <tr><th class="text-end">Total</th><th class="text-end"><?= e(number_format($totalKg, 2)) ?></th><th class="text-end"><?= e(number_format($totalValue, 2)) ?></th></tr>
    <?php endif; ?>
</table>
</div>
