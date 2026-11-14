<?php
require_once __DIR__ . '/auth.php';
require_role('Administrator');

$users = [];
$error = '';

try {
    $pdo = (new Database())->connect();
    $users = $pdo->query(
        'SELECT u.user_id, u.username, u.full_name, r.role_name, u.created_at, u.updated_at
         FROM users u
         JOIN user_role r ON r.role_id = u.role_id
         ORDER BY u.user_id'
    )->fetchAll();
} catch (Throwable $e) {
    error_log('Users list error: ' . $e->getMessage());
    $error = 'Could not load the users. Please try again later.';
}

$pageTitle = 'Users';
include __DIR__ . '/layout_header.php';
?>
<?php if ($error): ?><p style="color:red"><?= e($error) ?></p><?php endif; ?>

<p><a href="user_form.php">+ Add User</a></p>

<div style="overflow-x:auto">
<table border="1" cellpadding="6">
    <tr><th>ID</th><th>Username</th><th>Full name</th><th>Role</th><th>Created</th><th>Last updated</th><th>Actions</th></tr>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?= e($u['user_id']) ?></td>
            <td><?= e($u['username']) ?><?= (int)$u['user_id'] === (int)$_SESSION['user_id'] ? ' <em>(you)</em>' : '' ?></td>
            <td><?= e($u['full_name']) ?></td>
            <td><?= e($u['role_name']) ?></td>
            <td><?= e($u['created_at']) ?></td>
            <td><?= e($u['updated_at']) ?></td>
            <td><a href="user_form.php?id=<?= e($u['user_id']) ?>">Edit</a></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$users && !$error): ?>
        <tr><td colspan="7">No users found.</td></tr>
    <?php endif; ?>
</table>
</div>

<p>Role changes take effect the next time that user logs in.</p>
<?php
include __DIR__ . '/layout_footer.php';
?>