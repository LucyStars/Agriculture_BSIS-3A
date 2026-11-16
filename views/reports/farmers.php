<?php
report_filter_form('report_farmers', $from, $to, 'Date registered');
report_errors($errors, $error);
?>
<div class="table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
    <thead class="table-head"><tr>
        <th>Farmer</th><th>Contact</th><th>Registered</th><th>Farm</th>
        <th>Location</th><th>Area (ha)</th><th>Soil</th><th>Irrigation</th>
    </tr></thead>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['last_name']) ?>, <?= e($r['first_name']) ?></td>
            <td><?= e($r['contact_number']) ?></td>
            <td><?= e($r['date_registered']) ?></td>
            <td><?= $r['farm_name'] === null ? '<em>no farm yet</em>' : e($r['farm_name']) ?></td>
            <td><?= e($r['location']) ?></td>
            <td class="text-end"><?= $r['area_hectares'] === null ? '' : e(number_format((float)$r['area_hectares'], 2)) ?></td>
            <td><?= e($r['soil_type_description']) ?></td>
            <td><?= e($r['irrigation_type_description']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows && !$errors && !$error): ?>
        <tr><td colspan="8">No records found for this date range.</td></tr>
    <?php endif; ?>
    <?php if ($rows): ?>
        <tr><th colspan="5" align="right">Total area</th><th class="text-end"><?= e(number_format($totalArea, 2)) ?></th><td colspan="2"></td></tr>
    <?php endif; ?>
</table>
</div>
