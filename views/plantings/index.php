<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<form method="get" class="filter-bar">
    <input type="hidden" name="route" value="<?= e($route) ?>">
    <input class="form-control" type="text" name="search" value="<?= e($search) ?>" placeholder="Search farm or crop">
    <select class="form-select" name="status">
        <option value="0">All statuses</option>
        <?php foreach ($statuses as $id => $label): ?>
            <option value="<?= e($id) ?>" <?= $id === $status ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="hidden" name="sort" value="<?= e($sort) ?>">
    <input type="hidden" name="dir" value="<?= e($dir) ?>">
    <button type="submit" class="btn btn-primary">Search</button>
    <?php if ($search !== '' || $status > 0): ?><a class="btn btn-outline-secondary" href="<?= url('plantings') ?>">Clear</a><?php endif; ?>
</form>

<p><a class="btn btn-primary" href="<?= url('planting_form') ?>">+ Add Planting</a></p>

<div class="table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
    <thead class="table-head"><tr>
        <th><?= sort_header($state, 'ID', 'id') ?></th>
        <th><?= sort_header($state, 'Farm', 'farm') ?></th>
        <th><?= sort_header($state, 'Crop', 'crop') ?></th>
        <th><?= sort_header($state, 'Planting date', 'date') ?></th>
        <th><?= sort_header($state, 'Area planted (ha)', 'area') ?></th>
        <th><?= sort_header($state, 'Expected harvest', 'expected') ?></th>
        <th><?= sort_header($state, 'Status', 'status') ?></th>
        <th>Actions</th>
    </tr></thead>
    <?php foreach ($plantings as $p): ?>
        <tr>
            <td><?= e($p['planting_id']) ?></td>
            <td><?= e($p['farm_name']) ?></td>
            <td><?= e($p['crop_name']) ?><?= $p['variety'] ? ' (' . e($p['variety']) . ')' : '' ?></td>
            <td><?= e($p['planting_date']) ?></td>
            <td><?= e($p['area_planted']) ?></td>
            <td><?= e($p['expected_harvest_date']) ?></td>
            <td><?= e($p['status_description']) ?></td>
            <td>
                <a class="btn btn-sm btn-outline-success" href="<?= url('planting_view', ['id' => $p['planting_id']]) ?>">View</a>
                <a class="btn btn-sm btn-warning" href="<?= url('planting_form', ['id' => $p['planting_id']]) ?>">Edit</a>
                <?php if (is_admin()): ?>
                    <form method="post" action="<?= url('planting_delete') ?>" style="display:inline"
                          onsubmit="return confirm('Delete this planting record?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= e($p['planting_id']) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$plantings && !$error): ?>
        <tr><td colspan="8">No planting records found.</td></tr>
    <?php endif; ?>
</table>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
    <span>Page <?= e($page) ?> of <?= e($totalPages) ?> (<?= e($totalRows) ?> planting records)</span>
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
