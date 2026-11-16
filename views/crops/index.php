<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<form method="get" class="filter-bar">
    <input type="hidden" name="route" value="<?= e($route) ?>">
    <input class="form-control" type="text" name="search" value="<?= e($search) ?>" placeholder="Search crop or variety">
    <select class="form-select" name="category">
        <option value="0">All categories</option>
        <?php foreach ($categories as $id => $label): ?>
            <option value="<?= e($id) ?>" <?= $id === $category ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <select class="form-select" name="season">
        <option value="0">All seasons</option>
        <?php foreach ($seasons as $id => $label): ?>
            <option value="<?= e($id) ?>" <?= $id === $season ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="hidden" name="sort" value="<?= e($sort) ?>">
    <input type="hidden" name="dir" value="<?= e($dir) ?>">
    <button type="submit" class="btn btn-primary">Search</button>
    <?php if ($search !== '' || $category > 0 || $season > 0): ?><a class="btn btn-outline-secondary" href="<?= url('crops') ?>">Clear</a><?php endif; ?>
</form>

<p><a class="btn btn-primary" href="<?= url('crop_form') ?>">+ Add Crop</a></p>

<div class="table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
    <thead class="table-head"><tr>
        <th><?= sort_header($state, 'ID', 'id') ?></th>
        <th><?= sort_header($state, 'Crop', 'name') ?></th>
        <th><?= sort_header($state, 'Variety', 'variety') ?></th>
        <th><?= sort_header($state, 'Category', 'category') ?></th>
        <th><?= sort_header($state, 'Growing days', 'days') ?></th>
        <th><?= sort_header($state, 'Recommended season', 'season') ?></th>
        <th>Actions</th>
    </tr></thead>
    <?php foreach ($crops as $c): ?>
        <tr>
            <td><?= e($c['crop_id']) ?></td>
            <td><?= e($c['crop_name']) ?></td>
            <td><?= e($c['variety']) ?></td>
            <td><?= e($c['category_description']) ?></td>
            <td><?= e($c['growing_period_days']) ?></td>
            <td><?= e($c['recommended_season_description']) ?></td>
            <td>
                <a class="btn btn-sm btn-outline-success" href="<?= url('crop_view', ['id' => $c['crop_id']]) ?>">View</a>
                <a class="btn btn-sm btn-warning" href="<?= url('crop_form', ['id' => $c['crop_id']]) ?>">Edit</a>
                <?php if (is_admin()): ?>
                    <form method="post" action="<?= url('crop_delete') ?>" style="display:inline"
                          onsubmit="return confirm('Delete this crop?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= e($c['crop_id']) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$crops && !$error): ?>
        <tr><td colspan="7">No crops found.</td></tr>
    <?php endif; ?>
</table>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
    <span>Page <?= e($page) ?> of <?= e($totalPages) ?> (<?= e($totalRows) ?> crops)</span>
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
