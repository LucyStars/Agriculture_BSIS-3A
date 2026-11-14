<?php
require_once __DIR__ . '/auth.php';
require_role('Administrator');

$search = trim($_GET['search'] ?? '');
$roleFilter = (int)($_GET['role'] ?? 0);
$users = [];
$roles = [];
$error = '';

try {
    $pdo = (new Database())->connect();
    $roles = lookup_options($pdo, 'user_role', 'role_id', 'role_name');

    $where = [];
    $params = [];
    if ($search !== '') {
        $where[] = '(u.user_id::text = :s1 OR u.username ILIKE :s2 OR u.full_name ILIKE :s3)';
        $params[':s1'] = $search;
        $params[':s2'] = '%' . $search . '%';
        $params[':s3'] = '%' . $search . '%';
    }
    if ($roleFilter > 0 && isset($roles[$roleFilter])) {
        $where[] = 'u.role_id = :role';
        $params[':role'] = $roleFilter;
    } else {
        $roleFilter = 0;
    }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $pdo->prepare(
        "SELECT u.user_id, u.username, u.full_name, r.role_name, u.created_at, u.updated_at
         FROM users u
         JOIN user_role r ON r.role_id = u.role_id
         $whereSql
         ORDER BY r.role_id, u.user_id"
    );
    $stmt->execute($params);
    $users = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('Users list error: ' . $e->getMessage());
    $error = 'Could not load the users. Please try again later.';
}

$pageTitle = 'Users';
include __DIR__ . '/layout_header.php';
?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<form method="get" class="filter-bar">
    <input class="form-control" type="text" name="search" placeholder="ID, username or name" value="<?= e($search) ?>">
    <select class="form-select" name="role">
        <option value="0">All roles</option>
        <?php foreach ($roles as $rid => $label): ?>
            <option value="<?= e($rid) ?>" <?= $rid === $roleFilter ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary">Filter</button>
    <a class="btn btn-outline-secondary" href="users.php">Reset</a>
</form>

<p><a class="btn btn-primary" href="user_form.php">+ Add User</a> <span class="ms-2 text-muted"><?= count($users) ?> user(s) shown</span></p>

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
            <td><a class="btn btn-sm btn-warning" href="user_form.php?id=<?= e($u['user_id']) ?>">Edit</a></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$users && !$error): ?>
        <tr><td colspan="7">No users match your filter.</td></tr>
    <?php endif; ?>
</table>
</div>

<p>Role changes take effect the next time that user logs in.</p>
<?php
include __DIR__ . '/layout_footer.php';
?>