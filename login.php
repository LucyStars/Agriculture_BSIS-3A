<?php
require_once __DIR__ . '/auth.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!csrf_check()) {
        $error = 'Your session expired. Please try again.';
    } elseif ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        try {
            $pdo = (new Database())->connect();
            $stmt = $pdo->prepare(
                'SELECT u.user_id, u.username, u.full_name, u.password_hash, r.role_name
                 FROM users u JOIN user_role r ON r.role_id = u.role_id
                 WHERE u.username = :username'
            );
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            // Same message for unknown user and wrong password (no user enumeration)
            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role_name'] = $user['role_name'];
                header('Location: index.php');
                exit;
            }
            $error = 'Incorrect username or password.';
        } catch (Throwable $e) {
            error_log('Login error: ' . $e->getMessage());
            $error = 'Something went wrong. Please try again later.';
        }
    }
}
$pageTitle = 'Login';
include __DIR__ . '/layout_header.php';
?>
<?php if ($error): ?><p style="color:red"><?= e($error) ?></p><?php endif; ?>
<form method="post" autocomplete="off">
    <?= csrf_field() ?>
    <p>
        <label for="username">Username</label><br>
        <input type="text" id="username" name="username" value="<?= e($username) ?>" required autofocus>
    </p>
    <p>
        <label for="password">Password</label><br>
        <input type="password" id="password" name="password" required>
    </p>
    <button type="submit">Log in</button>
</form>
<?php include __DIR__ . '/layout_footer.php'; ?>