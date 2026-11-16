<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<form method="get" class="filter-bar">
    <input type="hidden" name="route" value="<?= e($route) ?>">
    <input class="form-control" type="text" name="search" value="<?= e($search) ?>" placeholder="Search farm, crop or remarks">
    <select class="form-select" name="grade">
        <option value="0">All quality grades</option>
        <?php foreach ($grades as $id => $label): ?>
            <option value="<?= e($id) ?>" <?= $id === $grade ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <select class="form-select" name="remark">
        <option value="0">All remarks</option>
        <?php foreach ($remarks as $id => $label): ?>
            <option value="<?= e($id) ?>" <?= $id === $remark ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="hidden" name="sort" value="<?= e($sort) ?>">
    <input type="hidden" name="dir" value="<?= e($dir) ?>">
    <button type="submit" class="btn btn-primary">Search</button>
    <?php if ($search !== '' || $grade > 0 || $remark > 0): ?><a class="btn btn-outline-secondary" href="<?= url('harvests') ?>">Clear</a><?php endif; ?>
</form>

<p><a class="btn btn-primary" href="<?= url('harvest_form') ?>">+ Add Harvest</a></p>

<div class="table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
    <thead class="table-head"><tr>
        <th><?= sort_header($state, 'ID', 'id') ?></th>
        <th><?= sort_header($state, 'Farm', 'farm') ?></th>
        <th><?= sort_header($state, 'Crop', 'crop') ?></th>
        <th><?= sort_header($state, 'Harvest date', 'date') ?></th>
        <th><?= sort_header($state, 'Quantity (kg)', 'qty') ?></th>
        <th><?= sort_header($state, 'Price per kg', 'price') ?></th>
        <th><?= sort_header($state, 'Estimated value', 'value') ?></th>
        <th><?= sort_header($state, 'Grade', 'grade') ?></th>
        <th><?= sort_header($state, 'Remarks', 'remarks') ?></th>
        <th>Actions</th>
    </tr></thead>
    <?php foreach ($harvests as $h): ?>
        <tr>
            <td><?= e($h['harvest_id']) ?></td>
            <td><?= e($h['farm_name']) ?></td>
            <td><?= e($h['crop_name']) ?></td>
            <td><?= e($h['harvest_date']) ?></td>
            <td class="text-end"><?= e(number_format((float)$h['quantity_kg'], 2)) ?></td>
            <td class="text-end"><?= e(number_format((float)$h['selling_price_per_kg'], 2)) ?></td>
            <td class="text-end"><?= e(number_format((float)$h['estimated_value'], 2)) ?></td>
            <td><?= e($h['quality_grade_description']) ?></td>
            <td><?= e($h['remarks_description']) ?></td>
            <td>
                <a class="btn btn-sm btn-outline-success" href="<?= url('harvest_view', ['id' => $h['harvest_id']]) ?>">View</a>
                <a class="btn btn-sm btn-warning" href="<?= url('harvest_form', ['id' => $h['harvest_id']]) ?>">Edit</a>
                <?php if (is_admin()): ?>
                    <form method="post" action="<?= url('harvest_delete') ?>" style="display:inline"
                          onsubmit="return confirm('Delete this harvest record?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= e($h['harvest_id']) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$harvests && !$error): ?>
        <tr><td colspan="10">No harvest records found.</td></tr>
    <?php endif; ?>
</table>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
    <span>Page <?= e($page) ?> of <?= e($totalPages) ?> (<?= e($totalRows) ?> harvest records)</span>
    <nav aria-label="Pages">
        <ul class="pagination mb-0">
            <?php if ($page > 1): ?>
                <li class="page-item"><a class="page-link" href="<?= link_with($state, ['page' => $page - 1]) ?>">Previous</a></li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= link_with($state, ['page' => $i]) ?>"><?= e($i) ?></a></li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <li class="page-item"><a class="page-link" href="<?= link_with($state, ['page' => $page + 1]) ?>">Next</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</div>
