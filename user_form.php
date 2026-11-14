<?php
require_once __DIR__ . '/auth.php';
require_role('Administrator');

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$isEdit = $id > 0;
$isSelf = $isEdit && $id === (int)$_SESSION['user_id'];

$values = ['username' => '', 'full_name' => '', 'role_id' => ''];
$errors = [];
$roles = [];
$existing = null;

try {
    $pdo = (new Database())->connect();
    $roles = lookup_options($pdo, 'user_role', 'role_id', 'role_name');

    if ($isEdit) {
        $stmt = $pdo->prepare('SELECT username, full_name, role_id FROM users WHERE user_id = :id');
        $stmt->execute([':id' => $id]);
        $existing = $stmt->fetch();
        if (!$existing) {
            flash_set('error', 'That user was not found.');
            header('Location: users.php');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $values = ['username' => $existing['username'], 'full_name' => $existing['full_name'], 'role_id' => (string)$existing['role_id']];
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $values = [
            'username'  => $isEdit ? $existing['username'] : trim($_POST['username'] ?? ''),
            'full_name' => trim($_POST['full_name'] ?? ''),
            'role_id'   => $isSelf ? (string)$existing['role_id'] : trim($_POST['role_id'] ?? ''),
        ];
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';

        if (!csrf_check()) {
            $errors[] = 'Your session expired. Please try again.';
        }
        if (!$isEdit) {
            if ($values['username'] === '') {
                $errors[] = 'Username is required.';
            } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $values['username'])) {
                $errors[] = 'Username must be 3 to 50 characters: letters, numbers, dot, dash or underscore.';
            }
        }
        if ($values['full_name'] === '') {
            $errors[] = 'Full name is required.';
        } elseif (mb_strlen($values['full_name']) > 150) {
            $errors[] = 'Full name must be 150 characters or fewer.';
        }
        if (!array_key_exists((int)$values['role_id'], $roles)) {
            $errors[] = 'Please choose a role.';
        }
        if (!$isEdit && $password === '') {
            $errors[] = 'Password is required.';
        }
        if ($password !== '') {
            if (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters.';
            } elseif (strlen($password) > 72) {
                $errors[] = 'Password must be 72 characters or fewer.';
            } elseif ($password !== $confirm) {
                $errors[] = 'The two passwords do not match.';
            }
        }

        if (!$errors) {
            $hash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null;
            if ($isEdit) {
                if ($hash !== null) {
                    $stmt = $pdo->prepare('UPDATE users SET full_name = :n, role_id = :r, password_hash = :h WHERE user_id = :id');
                    $stmt->execute([':n' => $values['full_name'], ':r' => (int)$values['role_id'], ':h' => $hash, ':id' => $id]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET full_name = :n, role_id = :r WHERE user_id = :id');
                    $stmt->execute([':n' => $values['full_name'], ':r' => (int)$values['role_id'], ':id' => $id]);
                }
                if ($isSelf) {
                    $_SESSION['full_name'] = $values['full_name'];
                }
                $roleChanged = (int)$values['role_id'] !== (int)$existing['role_id'];
                flash_set('success', 'User updated.' . ($roleChanged ? ' The new role applies the next time that user logs in.' : ''));
            } else {
                $stmt = $pdo->prepare('INSERT INTO users (username, full_name, password_hash, role_id) VALUES (:u, :n, :h, :r)');
                $stmt->execute([':u' => $values['username'], ':n' => $values['full_name'], ':h' => $hash, ':r' => (int)$values['role_id']]);
                flash_set('success', 'User added.');
            }
            header('Location: users.php');
            exit;
        }
    }
} catch (Throwable $e) {
    if (is_duplicate_error($e)) {
        $errors[] = 'That username is already taken.';
    } else {
        error_log('User form error: ' . $e->getMessage());
        $errors[] = 'Could not save the user. Please try again later.';
    }
}

$pageTitle = $isEdit ? 'Edit User' : 'Add User';
include __DIR__ . '/layout_header.php';
?>
<?php if ($errors): ?>
    <ul style="color:red">
        <?php foreach ($errors as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post" autocomplete="off">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e($id) ?>">
    <p>
        <label for="username">Username<?= $isEdit ? '' : ' *' ?></label><br>
        <?php if ($isEdit): ?>
            <strong><?= e($values['username']) ?></strong> (cannot be changed)
        <?php else: ?>
            <input type="text" id="username" name="username" maxlength="50" value="<?= e($values['username']) ?>" required>
        <?php endif; ?>
    </p>
    <p>
        <label for="full_name">Full name *</label><br>
        <input type="text" id="full_name" name="full_name" maxlength="150" size="40" value="<?= e($values['full_name']) ?>" required>
    </p>
    <p>
        <label for="role_id">Role *</label><br>
        <select id="role_id" name="role_id" <?= $isSelf ? 'disabled' : 'required' ?>>
            <option value="">-- choose --</option>
            <?php foreach ($roles as $rid => $label): ?>
                <option value="<?= e($rid) ?>" <?= (string)$rid === $values['role_id'] ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($isSelf): ?><br><em>You cannot change your own role.</em><?php endif; ?>
    </p>
    <p>
        <label for="password"><?= $isEdit ? 'New password (leave blank to keep the current one)' : 'Password *' ?></label><br>
        <input type="password" id="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" <?= $isEdit ? '' : 'required' ?>>
    </p>
    <p>
        <label for="confirm">Confirm password</label><br>
        <input type="password" id="confirm" name="confirm" minlength="8" maxlength="72" autocomplete="new-password" <?= $isEdit ? '' : 'required' ?>>
    </p>
    <button type="submit"><?= $isEdit ? 'Save changes' : 'Add user' ?></button>
    <a href="users.php">Cancel</a>
</form>
<?php
include __DIR__ . '/layout_footer.php';
?>