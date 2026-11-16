<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<form method="get" class="filter-bar">
    <input type="hidden" name="route" value="<?= e($route) ?>">
    <input class="form-control" type="text" name="search" placeholder="ID, username or name" value="<?= e($search) ?>">
    <select class="form-select" name="role">
        <option value="0">All roles</option>
        <?php foreach ($roles as $rid => $label): ?>
            <option value="<?= e($rid) ?>" <?= $rid === $roleFilter ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary">Filter</button>
    <a class="btn btn-outline-secondary" href="<?= url('users') ?>">Reset</a>
</form>

<p><a class="btn btn-primary" href="<?= url('user_form') ?>">+ Add User</a> <span class="ms-2 text-muted"><?= count($users) ?> user(s) shown</span></p>

<div class="table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
    <thead class="table-head"><tr><th>ID</th><th>Username</th><th>Full name</th><th>Role</th><th>Created</th><th>Last updated</th><th>Actions</th></tr></thead>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?= e($u['user_id']) ?></td>
            <td><?= e($u['username']) ?><?= (int)$u['user_id'] === (int)$_SESSION['user_id'] ? ' <em>(you)</em>' : '' ?></td>
            <td><?= e($u['full_name']) ?></td>
            <td><?= e($u['role_name']) ?></td>
            <td><?= e($u['created_at']) ?></td>
            <td><?= e($u['updated_at']) ?></td>
            <td><a class="btn btn-sm btn-warning" href="<?= url('user_form', ['id' => $u['user_id']]) ?>">Edit</a></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$users && !$error): ?>
        <tr><td colspan="7">No users match your filter.</td></tr>
    <?php endif; ?>
</table>
</div>

<p>Role changes take effect the next time that user logs in.</p>
