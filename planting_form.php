<?php
require_once __DIR__ . '/auth.php';
require_login();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$isEdit = $id > 0;

$values = ['farm_id' => '', 'crop_id' => '', 'planting_date' => '', 'area_planted' => '',
           'seed_quantity' => '', 'expected_harvest_date' => '', 'status_id' => ''];
$errors = [];
$farms = $crops = $statuses = [];

try {
    $pdo = (new Database())->connect();

    $farms = $pdo->query("SELECT f.farm_id, f.farm_name || ' (' || fr.last_name || ')'
                          FROM farm f JOIN farmer fr ON fr.farmer_id = f.farmer_id
                          ORDER BY f.farm_name")->fetchAll(PDO::FETCH_KEY_PAIR);
    $crops = $pdo->query("SELECT crop_id, crop_name || COALESCE(' (' || variety || ')', '')
                          FROM crop ORDER BY crop_name, variety")->fetchAll(PDO::FETCH_KEY_PAIR);
    $statuses = lookup_options($pdo, 'planting_status', 'status_id', 'status_description');

    if ($isEdit && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        $stmt = $pdo->prepare('SELECT farm_id, crop_id, planting_date, area_planted, seed_quantity,
                                      expected_harvest_date, status_id
                               FROM planting WHERE planting_id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            flash_set('error', 'That planting record was not found.');
            header('Location: plantings.php');
            exit;
        }
        $values = [
            'farm_id'               => (string)$row['farm_id'],
            'crop_id'               => (string)$row['crop_id'],
            'planting_date'         => (string)$row['planting_date'],
            'area_planted'          => (string)($row['area_planted'] ?? ''),
            'seed_quantity'         => (string)($row['seed_quantity'] ?? ''),
            'expected_harvest_date' => (string)($row['expected_harvest_date'] ?? ''),
            'status_id'             => (string)$row['status_id'],
        ];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $values = [
            'farm_id'               => trim($_POST['farm_id'] ?? ''),
            'crop_id'               => trim($_POST['crop_id'] ?? ''),
            'planting_date'         => trim($_POST['planting_date'] ?? ''),
            'area_planted'          => trim($_POST['area_planted'] ?? ''),
            'seed_quantity'         => trim($_POST['seed_quantity'] ?? ''),
            'expected_harvest_date' => trim($_POST['expected_harvest_date'] ?? ''),
            'status_id'             => trim($_POST['status_id'] ?? ''),
        ];

        if (!csrf_check()) {
            $errors[] = 'Your session expired. Please try again.';
        }
        if (!array_key_exists((int)$values['farm_id'], $farms)) {
            $errors[] = 'Please choose a farm.';
        }
        if (!array_key_exists((int)$values['crop_id'], $crops)) {
            $errors[] = 'Please choose a crop.';
        }
        $plantingOk = false;
        if ($values['planting_date'] === '') {
            $errors[] = 'Planting date is required.';
        } elseif (!valid_date($values['planting_date'])) {
            $errors[] = 'Planting date is not a valid date.';
        } else {
            $plantingOk = true;
        }
        if ($values['area_planted'] === '') {
            $errors[] = 'Area planted is required.';
        } elseif (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $values['area_planted']) || (float)$values['area_planted'] <= 0) {
            $errors[] = 'Area planted must be a number greater than 0 (up to 2 decimal places).';
        }
        if ($values['seed_quantity'] !== '' && !preg_match('/^\d{1,6}(\.\d{1,2})?$/', $values['seed_quantity'])) {
            $errors[] = 'Seed quantity must be 0 or more (up to 2 decimal places).';
        }
        if ($values['expected_harvest_date'] !== '') {
            if (!valid_date($values['expected_harvest_date'])) {
                $errors[] = 'Expected harvest date is not a valid date.';
            } elseif ($plantingOk && $values['expected_harvest_date'] < $values['planting_date']) {
                $errors[] = 'Expected harvest date cannot be before the planting date.';
            }
        }
        if (!array_key_exists((int)$values['status_id'], $statuses)) {
            $errors[] = 'Please choose a status.';
        }

        if (!$errors) {
            $data = [
                ':f' => (int)$values['farm_id'], ':c' => (int)$values['crop_id'], ':pd' => $values['planting_date'],
                ':a' => $values['area_planted'],
                ':sq' => $values['seed_quantity'] === '' ? null : $values['seed_quantity'],
                ':eh' => $values['expected_harvest_date'] === '' ? null : $values['expected_harvest_date'],
                ':st' => (int)$values['status_id'],
            ];
            if ($isEdit) {
                $stmt = $pdo->prepare('UPDATE planting SET farm_id = :f, crop_id = :c, planting_date = :pd, area_planted = :a,
                                       seed_quantity = :sq, expected_harvest_date = :eh, status_id = :st
                                       WHERE planting_id = :id');
                $stmt->execute($data + [':id' => $id]);
                flash_set('success', 'Planting record updated.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO planting (farm_id, crop_id, planting_date, area_planted, seed_quantity,
                                       expected_harvest_date, status_id)
                                       VALUES (:f, :c, :pd, :a, :sq, :eh, :st)');
                $stmt->execute($data);
                flash_set('success', 'Planting record added.');
            }
            header('Location: plantings.php');
            exit;
        }
    }
} catch (Throwable $e) {
    error_log('Planting form error: ' . $e->getMessage());
    $errors[] = 'Could not save the planting record. Please try again later.';
}

$pageTitle = $isEdit ? 'Edit Planting Record' : 'Add Planting Record';
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
        <label for="farm_id">Farm *</label><br>
        <select class="form-select" id="farm_id" name="farm_id" required>
            <option value="">-- choose --</option>
            <?php foreach ($farms as $fid => $label): ?>
                <option value="<?= e($fid) ?>" <?= (string)$fid === $values['farm_id'] ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>
        <label for="crop_id">Crop *</label><br>
        <select class="form-select" id="crop_id" name="crop_id" required>
            <option value="">-- choose --</option>
            <?php foreach ($crops as $cid => $label): ?>
                <option value="<?= e($cid) ?>" <?= (string)$cid === $values['crop_id'] ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>
        <label for="planting_date">Planting date *</label><br>
        <input class="form-control" type="date" id="planting_date" name="planting_date" value="<?= e($values['planting_date']) ?>" required>
    </p>
    <p>
        <label for="area_planted">Area planted (hectares) *</label><br>
        <input class="form-control" type="number" id="area_planted" name="area_planted" step="0.01" min="0.01" value="<?= e($values['area_planted']) ?>" required>
    </p>
    <p>
        <label for="seed_quantity">Seed quantity</label><br>
        <input class="form-control" type="number" id="seed_quantity" name="seed_quantity" step="0.01" min="0" value="<?= e($values['seed_quantity']) ?>">
    </p>
    <p>
        <label for="expected_harvest_date">Expected harvest date</label><br>
        <input class="form-control" type="date" id="expected_harvest_date" name="expected_harvest_date" value="<?= e($values['expected_harvest_date']) ?>">
    </p>
    <p>
        <label for="status_id">Status *</label><br>
        <select class="form-select" id="status_id" name="status_id" required>
            <option value="">-- choose --</option>
            <?php foreach ($statuses as $sid => $label): ?>
                <option value="<?= e($sid) ?>" <?= (string)$sid === $values['status_id'] ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Add planting record' ?></button>
    <a class="btn btn-outline-secondary" href="plantings.php">Cancel</a>
</form>
<?php
include __DIR__ . '/layout_footer.php';
?>