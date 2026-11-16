<?php
// User model: queries and validation rules for the users and user_role tables.

class User extends Model
{
    public function roles(): array
    {
        return $this->lookup('user_role', 'role_id', 'role_name');
    }

    // The account and role used to check a login.
    public function findForLogin(string $username): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.user_id, u.username, u.full_name, u.password_hash, r.role_name
             FROM users u JOIN user_role r ON r.role_id = u.role_id
             WHERE u.username = :username'
        );
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    // The user list, filtered by search text (id, username or name) and role.
    public function search(string $search, int $roleFilter): array
    {
        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = '(u.user_id::text = :s1 OR u.username ILIKE :s2 OR u.full_name ILIKE :s3)';
            $params = [':s1' => $search, ':s2' => '%' . $search . '%', ':s3' => '%' . $search . '%'];
        }
        if ($roleFilter > 0) {
            $where[] = 'u.role_id = :role';
            $params[':role'] = $roleFilter;
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->pdo->prepare(
            "SELECT u.user_id, u.username, u.full_name, r.role_name, u.created_at, u.updated_at
             FROM users u
             JOIN user_role r ON r.role_id = u.role_id
             $whereSql
             ORDER BY r.role_id, u.user_id"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne('SELECT username, full_name, role_id FROM users WHERE user_id = :id', $id);
    }

    // Returns the list of error messages for the submitted values.
    public function validate(array $values, array $roles, bool $isEdit, string $password, string $confirm): array
    {
        $errors = [];
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
        return $errors;
    }

    public function create(array $values, string $passwordHash): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO users (username, full_name, password_hash, role_id) VALUES (:u, :n, :h, :r)');
        $stmt->execute([':u' => $values['username'], ':n' => $values['full_name'], ':h' => $passwordHash, ':r' => (int)$values['role_id']]);
    }

    // Updates the name and role, and the password only when a new hash is given.
    public function update(int $id, array $values, ?string $passwordHash): void
    {
        if ($passwordHash !== null) {
            $stmt = $this->pdo->prepare('UPDATE users SET full_name = :n, role_id = :r, password_hash = :h WHERE user_id = :id');
            $stmt->execute([':n' => $values['full_name'], ':r' => (int)$values['role_id'], ':h' => $passwordHash, ':id' => $id]);
        } else {
            $stmt = $this->pdo->prepare('UPDATE users SET full_name = :n, role_id = :r WHERE user_id = :id');
            $stmt->execute([':n' => $values['full_name'], ':r' => (int)$values['role_id'], ':id' => $id]);
        }
    }
}
