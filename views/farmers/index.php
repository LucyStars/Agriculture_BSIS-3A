<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<form method="get" class="filter-bar">
    <input type="hidden" name="route" value="<?= e($route) ?>">
    <input class="form-control" type="text" name="search" value="<?= e($search) ?>" placeholder="Search name, contact or address">
    <select class="form-select" name="address">
        <option value="">All addresses</option>
        <?php foreach ($addresses as $a): ?>
            <option value="<?= e($a) ?>" <?= $a === $address ? 'selected' : '' ?>><?= e($a) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="hidden" name="sort" value="<?= e($sort) ?>">
    <input type="hidden" name="dir" value="<?= e($dir) ?>">
    <button type="submit" class="btn btn-primary">Search</button>
    <?php if ($search !== '' || $address !== ''): ?><a class="btn btn-outline-secondary" href="<?= url('farmers') ?>">Clear</a><?php endif; ?>
</form>

<p><a class="btn btn-primary" href="<?= url('farmer_form') ?>">+ Add Farmer</a></p>

<div class="table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
    <thead class="table-head"><tr>
        <th><?= sort_header($state, 'ID', 'id') ?></th>
        <th><?= sort_header($state, 'Name', 'name') ?></th>
        <th><?= sort_header($state, 'Contact', 'contact') ?></th>
        <th><?= sort_header($state, 'Address', 'address') ?></th>
        <th><?= sort_header($state, 'Date Registered', 'date') ?></th>
        <th>Actions</th>
    </tr></thead>
    <?php foreach ($farmers as $f): ?>
        <tr>
            <td><?= e($f['farmer_id']) ?></td>
            <td><?= e($f['last_name']) ?>, <?= e($f['first_name']) ?></td>
            <td><?= e($f['contact_number']) ?></td>
            <td><?= e($f['address']) ?></td>
            <td><?= e($f['date_registered']) ?></td>
            <td>
                <a class="btn btn-sm btn-outline-success" href="<?= url('farmer_view', ['id' => $f['farmer_id']]) ?>">View</a>
                <a class="btn btn-sm btn-warning" href="<?= url('farmer_form', ['id' => $f['farmer_id']]) ?>">Edit</a>
                <?php if (is_admin()): ?>
                    <form method="post" action="<?= url('farmer_delete') ?>" style="display:inline"
                          onsubmit="return confirm('Delete this farmer?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= e($f['farmer_id']) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$farmers && !$error): ?>
        <tr><td colspan="6">No farmers found.</td></tr>
    <?php endif; ?>
</table>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
    <span>Page <?= e($page) ?> of <?= e($totalPages) ?> (<?= e($totalRows) ?> farmers)</span>
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
