<?php
require_once __DIR__ . '/auth.php';
require_login();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$isEdit = $id > 0;

$values = ['first_name' => '', 'last_name' => '', 'contact_number' => '', 'address' => ''];
$errors = [];

try {
    $pdo = (new Database())->connect();

    if ($isEdit && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        $stmt = $pdo->prepare('SELECT first_name, last_name, contact_number, address FROM farmer WHERE farmer_id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            flash_set('error', 'That farmer was not found.');
            header('Location: farmers.php');
            exit;
        }
        $values = [
            'first_name'     => $row['first_name'],
            'last_name'      => $row['last_name'],
            'contact_number' => $row['contact_number'] ?? '',
            'address'        => $row['address'] ?? '',
        ];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $values = [
            'first_name'     => trim($_POST['first_name'] ?? ''),
            'last_name'      => trim($_POST['last_name'] ?? ''),
            'contact_number' => trim($_POST['contact_number'] ?? ''),
            'address'        => trim($_POST['address'] ?? ''),
        ];

        if (!csrf_check()) {
            $errors[] = 'Your session expired. Please try again.';
        }
        if ($values['first_name'] === '') {
            $errors[] = 'First name is required.';
        } elseif (mb_strlen($values['first_name']) > 50) {
            $errors[] = 'First name must be 50 characters or fewer.';
        }
        if ($values['last_name'] === '') {
            $errors[] = 'Last name is required.';
        } elseif (mb_strlen($values['last_name']) > 50) {
            $errors[] = 'Last name must be 50 characters or fewer.';
        }
        if ($values['contact_number'] !== '' && !preg_match('/^[0-9+\- ]{7,20}$/', $values['contact_number'])) {
            $errors[] = 'Contact number must be 7 to 20 characters: digits, spaces, + or - only.';
        }
        if (mb_strlen($values['address']) > 150) {
            $errors[] = 'Address must be 150 characters or fewer.';
        }

        if (!$errors) {
            $contact = $values['contact_number'] === '' ? null : $values['contact_number'];
            $address = $values['address'] === '' ? null : $values['address'];

            if ($isEdit) {
                $stmt = $pdo->prepare(
                    'UPDATE farmer SET first_name = :f, last_name = :l, contact_number = :c, address = :a
                     WHERE farmer_id = :id'
                );
                $stmt->execute([':f' => $values['first_name'], ':l' => $values['last_name'],
                                ':c' => $contact, ':a' => $address, ':id' => $id]);
                flash_set('success', 'Farmer updated.');
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO farmer (first_name, last_name, contact_number, address)
                     VALUES (:f, :l, :c, :a)'
                );
                $stmt->execute([':f' => $values['first_name'], ':l' => $values['last_name'],
                                ':c' => $contact, ':a' => $address]);
                flash_set('success', 'Farmer added.');
            }
            header('Location: farmers.php');
            exit;
        }
    }
} catch (Throwable $e) {
    error_log('Farmer form error: ' . $e->getMessage());
    $errors[] = 'Could not save the farmer. Please try again later.';
}

$pageTitle = $isEdit ? 'Edit Farmer' : 'Add Farmer';
include __DIR__ . '/layout_header.php';
?>
<?php if ($errors): ?>
    <div class="alert alert-danger">
<ul class="mb-0">
        <?php foreach ($errors as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="post" class="form-card">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e($id) ?>">
    <p>
        <label for="first_name">First name *</label><br>
        <input class="form-control" type="text" id="first_name" name="first_name" maxlength="50" value="<?= e($values['first_name']) ?>" required>
    </p>
    <p>
        <label for="last_name">Last name *</label><br>
        <input class="form-control" type="text" id="last_name" name="last_name" maxlength="50" value="<?= e($values['last_name']) ?>" required>
    </p>
    <p>
        <label for="contact_number">Contact number</label><br>
        <input class="form-control" type="text" id="contact_number" name="contact_number" maxlength="20" value="<?= e($values['contact_number']) ?>">
    </p>
    <p>
        <label for="address">Address</label><br>
        <input class="form-control" type="text" id="address" name="address" maxlength="150" value="<?= e($values['address']) ?>">
    </p>
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Add farmer' ?></button>
    <a class="btn btn-outline-secondary" href="farmers.php">Cancel</a>
</form>
<?php
include __DIR__ . '/layout_footer.php';
?>