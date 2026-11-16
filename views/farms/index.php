<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<form method="get" class="filter-bar">
    <input type="hidden" name="route" value="<?= e($route) ?>">
    <input class="form-control" type="text" name="search" value="<?= e($search) ?>" placeholder="Search farm, location or farmer">
    <select class="form-select" name="irrigation">
        <option value="0">All irrigation types</option>
        <?php foreach ($irrigations as $id => $label): ?>
            <option value="<?= e($id) ?>" <?= $id === $irrigation ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <select class="form-select" name="soil">
        <option value="0">All soil types</option>
        <?php foreach ($soils as $id => $label): ?>
            <option value="<?= e($id) ?>" <?= $id === $soil ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="hidden" name="sort" value="<?= e($sort) ?>">
    <input type="hidden" name="dir" value="<?= e($dir) ?>">
    <button type="submit" class="btn btn-primary">Search</button>
    <?php if ($search !== '' || $irrigation > 0 || $soil > 0): ?><a class="btn btn-outline-secondary" href="<?= url('farms') ?>">Clear</a><?php endif; ?>
</form>

<p><a class="btn btn-primary" href="<?= url('farm_form') ?>">+ Add Farm</a></p>

<div class="table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
    <thead class="table-head"><tr>
        <th><?= sort_header($state, 'ID', 'id') ?></th>
        <th><?= sort_header($state, 'Farm', 'name') ?></th>
        <th><?= sort_header($state, 'Farmer', 'farmer') ?></th>
        <th><?= sort_header($state, 'Location', 'location') ?></th>
        <th><?= sort_header($state, 'Area (ha)', 'area') ?></th>
        <th><?= sort_header($state, 'Soil type', 'soil') ?></th>
        <th><?= sort_header($state, 'Irrigation', 'irrigation') ?></th>
        <th>Actions</th>
    </tr></thead>
    <?php foreach ($farms as $f): ?>
        <tr>
            <td><?= e($f['farm_id']) ?></td>
            <td><?= e($f['farm_name']) ?></td>
            <td><?= e($f['last_name']) ?>, <?= e($f['first_name']) ?></td>
            <td><?= e($f['location']) ?></td>
            <td><?= e($f['area_hectares']) ?></td>
            <td><?= e($f['soil_type_description']) ?></td>
            <td><?= e($f['irrigation_type_description']) ?></td>
            <td>
                <a class="btn btn-sm btn-outline-success" href="<?= url('farm_view', ['id' => $f['farm_id']]) ?>">View</a>
                <a class="btn btn-sm btn-warning" href="<?= url('farm_form', ['id' => $f['farm_id']]) ?>">Edit</a>
                <?php if (is_admin()): ?>
                    <form method="post" action="<?= url('farm_delete') ?>" style="display:inline"
                          onsubmit="return confirm('Delete this farm?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= e($f['farm_id']) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$farms && !$error): ?>
        <tr><td colspan="8">No farms found.</td></tr>
    <?php endif; ?>
</table>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
    <span>Page <?= e($page) ?> of <?= e($totalPages) ?> (<?= e($totalRows) ?> farms)</span>
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
