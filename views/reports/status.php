<?php
report_filter_form('report_status', $from, $to, 'Planting date');
report_errors($errors, $error);
?>
<div class="table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
    <thead class="table-head"><tr><th>Status</th><th>Plantings</th><th>Area planted (ha)</th></tr></thead>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['status_description']) ?></td>
            <td class="text-end"><?= e($r['plantings']) ?></td>
            <td class="text-end"><?= e(number_format((float)$r['area'], 2)) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if ($rows): ?>
        <tr><th class="text-end">Total</th><th class="text-end"><?= e($totalPlantings) ?></th><th class="text-end"><?= e(number_format($totalArea, 2)) ?></th></tr>
    <?php endif; ?>
</table>
</div>
