<?php
// Auth controller: the login page and logout.

class AuthController extends Controller
{
    public function login(): void
    {
        if (is_logged_in()) {
            $this->redirect('index');
        }

        $error = '';
        $username = '';

        if ($this->isPost()) {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (!csrf_check()) {
                $error = 'Your session expired. Please try again.';
            } elseif ($username === '' || $password === '') {
                $error = 'Please enter your username and password.';
            } else {
                try {
                    $user = (new User())->findForLogin($username);

                    if ($user && password_verify($password, $user['password_hash'])) {
                        session_regenerate_id(true);
                        $_SESSION['user_id']   = $user['user_id'];
                        $_SESSION['username']  = $user['username'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['role_name'] = $user['role_name'];
                        $this->redirect('index');
                    }
                    $error = 'Incorrect username or password.';
                } catch (Throwable $e) {
                    error_log('Login error: ' . $e->getMessage());
                    $error = 'Something went wrong. Please try again later.';
                }
            }
        }

        $this->render('auth/login', compact('error', 'username') + ['pageTitle' => 'Login']);
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        $this->redirect('login');
    }
}
