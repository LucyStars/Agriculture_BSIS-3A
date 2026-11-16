<?php
// User controller: the administrator-only user list and add/edit form.

class UserController extends Controller
{
    public function index(): void
    {
        $this->requireRole('Administrator');

        $search = trim($_GET['search'] ?? '');
        $roleFilter = (int)($_GET['role'] ?? 0);
        $users = [];
        $roles = [];
        $error = '';

        try {
            $model = new User();
            $roles = $model->roles();
            if (!($roleFilter > 0 && isset($roles[$roleFilter]))) {
                $roleFilter = 0;
            }
            $users = $model->search($search, $roleFilter);
        } catch (Throwable $e) {
            error_log('Users list error: ' . $e->getMessage());
            $error = 'Could not load the users. Please try again later.';
        }

        $this->render('users/index', compact('users', 'roles', 'search', 'roleFilter', 'error') + ['pageTitle' => 'Users']);
    }

    public function form(): void
    {
        $this->requireRole('Administrator');

        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        $isEdit = $id > 0;
        $isSelf = $isEdit && $id === (int)$_SESSION['user_id'];

        $values = ['username' => '', 'full_name' => '', 'role_id' => ''];
        $errors = [];
        $roles = [];
        $existing = null;

        try {
            $model = new User();
            $roles = $model->roles();

            if ($isEdit) {
                $existing = $model->find($id);
                if (!$existing) {
                    flash_set('error', 'That user was not found.');
                    $this->redirect('users');
                }
                if (!$this->isPost()) {
                    $values = ['username' => $existing['username'], 'full_name' => $existing['full_name'], 'role_id' => (string)$existing['role_id']];
                }
            }

            if ($this->isPost()) {
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
                $errors = array_merge($errors, $model->validate($values, $roles, $isEdit, $password, $confirm));

                if (!$errors) {
                    $hash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null;
                    if ($isEdit) {
                        $model->update($id, $values, $hash);
                        if ($isSelf) {
                            $_SESSION['full_name'] = $values['full_name'];
                        }
                        $roleChanged = (int)$values['role_id'] !== (int)$existing['role_id'];
                        flash_set('success', 'User updated.' . ($roleChanged ? ' The new role applies the next time that user logs in.' : ''));
                    } else {
                        $model->create($values, $hash);
                        flash_set('success', 'User added.');
                    }
                    $this->redirect('users');
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

        $this->render('users/form', compact('id', 'isEdit', 'isSelf', 'values', 'errors', 'roles')
                      + ['pageTitle' => $isEdit ? 'Edit User' : 'Add User']);
    }
}
