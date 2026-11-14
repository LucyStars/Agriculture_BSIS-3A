<?php
require_once __DIR__ . '/auth.php';
require_login();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$isEdit = $id > 0;

$values = ['farmer_id' => '', 'farm_name' => '', 'location' => '', 'area_hectares' => '',
           'soil_type_id' => '', 'irrigation_type_id' => ''];
$errors = [];
$farmers = $soils = $irrigations = [];

try {
    $pdo = (new Database())->connect();

    $farmers = $pdo->query("SELECT farmer_id, last_name || ', ' || first_name FROM farmer ORDER BY last_name, first_name")
                   ->fetchAll(PDO::FETCH_KEY_PAIR);
    $soils = lookup_options($pdo, 'farm_soil_type', 'soil_type_id', 'soil_type_description');
    $irrigations = lookup_options($pdo, 'farm_irrigation_type', 'irrigation_type_id', 'irrigation_type_description');

    if ($isEdit && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        $stmt = $pdo->prepare('SELECT farmer_id, farm_name, location, area_hectares, soil_type_id, irrigation_type_id
                               FROM farm WHERE farm_id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            flash_set('error', 'That farm was not found.');
            header('Location: farms.php');
            exit;
        }
        $values = [
            'farmer_id'          => (string)$row['farmer_id'],
            'farm_name'          => $row['farm_name'],
            'location'           => $row['location'],
            'area_hectares'      => (string)($row['area_hectares'] ?? ''),
            'soil_type_id'       => (string)$row['soil_type_id'],
            'irrigation_type_id' => (string)$row['irrigation_type_id'],
        ];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $values = [
            'farmer_id'          => trim($_POST['farmer_id'] ?? ''),
            'farm_name'          => trim($_POST['farm_name'] ?? ''),
            'location'           => trim($_POST['location'] ?? ''),
            'area_hectares'      => trim($_POST['area_hectares'] ?? ''),
            'soil_type_id'       => trim($_POST['soil_type_id'] ?? ''),
            'irrigation_type_id' => trim($_POST['irrigation_type_id'] ?? ''),
        ];

        if (!csrf_check()) {
            $errors[] = 'Your session expired. Please try again.';
        }
        if (!array_key_exists((int)$values['farmer_id'], $farmers)) {
            $errors[] = 'Please choose a farmer.';
        }
        if ($values['farm_name'] === '') {
            $errors[] = 'Farm name is required.';
        } elseif (mb_strlen($values['farm_name']) > 100) {
            $errors[] = 'Farm name must be 100 characters or fewer.';
        }
        if ($values['location'] === '') {
            $errors[] = 'Location is required.';
        } elseif (mb_strlen($values['location']) > 150) {
            $errors[] = 'Location must be 150 characters or fewer.';
        }
        if ($values['area_hectares'] === '') {
            $errors[] = 'Area is required.';
        } elseif (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $values['area_hectares']) || (float)$values['area_hectares'] <= 0) {
            $errors[] = 'Area must be a number greater than 0 (up to 2 decimal places).';
        }
        if (!array_key_exists((int)$values['soil_type_id'], $soils)) {
            $errors[] = 'Please choose a soil type.';
        }
        if (!array_key_exists((int)$values['irrigation_type_id'], $irrigations)) {
            $errors[] = 'Please choose an irrigation type.';
        }

        if (!$errors) {
            $data = [':fr' => (int)$values['farmer_id'], ':n' => $values['farm_name'], ':l' => $values['location'],
                     ':a' => $values['area_hectares'], ':s' => (int)$values['soil_type_id'],
                     ':i' => (int)$values['irrigation_type_id']];
            if ($isEdit) {
                $stmt = $pdo->prepare('UPDATE farm SET farmer_id = :fr, farm_name = :n, location = :l, area_hectares = :a,
                                       soil_type_id = :s, irrigation_type_id = :i WHERE farm_id = :id');
                $stmt->execute($data + [':id' => $id]);
                flash_set('success', 'Farm updated.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO farm (farmer_id, farm_name, location, area_hectares, soil_type_id, irrigation_type_id)
                                       VALUES (:fr, :n, :l, :a, :s, :i)');
                $stmt->execute($data);
                flash_set('success', 'Farm added.');
            }
            header('Location: farms.php');
            exit;
        }
    }
} catch (Throwable $e) {
    if (is_duplicate_error($e)) {
        $errors[] = 'This farmer already has a farm with that name.';
    } else {
        error_log('Farm form error: ' . $e->getMessage());
        $errors[] = 'Could not save the farm. Please try again later.';
    }
}

$pageTitle = $isEdit ? 'Edit Farm' : 'Add Farm';
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
        <label for="farmer_id">Farmer *</label><br>
        <select class="form-select" id="farmer_id" name="farmer_id" required>
            <option value="">-- choose --</option>
            <?php foreach ($farmers as $fid => $label): ?>
                <option value="<?= e($fid) ?>" <?= (string)$fid === $values['farmer_id'] ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>
        <label for="farm_name">Farm name *</label><br>
        <input class="form-control" type="text" id="farm_name" name="farm_name" maxlength="100" value="<?= e($values['farm_name']) ?>" required>
    </p>
    <p>
        <label for="location">Location *</label><br>
        <input class="form-control" type="text" id="location" name="location" maxlength="150" value="<?= e($values['location']) ?>" required>
    </p>
    <p>
        <label for="area_hectares">Area (hectares) *</label><br>
        <input class="form-control" type="number" id="area_hectares" name="area_hectares" step="0.01" min="0.01" value="<?= e($values['area_hectares']) ?>" required>
    </p>
    <p>
        <label for="soil_type_id">Soil type *</label><br>
        <select class="form-select" id="soil_type_id" name="soil_type_id" required>
            <option value="">-- choose --</option>
            <?php foreach ($soils as $sid => $label): ?>
                <option value="<?= e($sid) ?>" <?= (string)$sid === $values['soil_type_id'] ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>
        <label for="irrigation_type_id">Irrigation type *</label><br>
        <select class="form-select" id="irrigation_type_id" name="irrigation_type_id" required>
            <option value="">-- choose --</option>
            <?php foreach ($irrigations as $iid => $label): ?>
                <option value="<?= e($iid) ?>" <?= (string)$iid === $values['irrigation_type_id'] ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Add farm' ?></button>
    <a class="btn btn-outline-secondary" href="farms.php">Cancel</a>
</form>
<?php
include __DIR__ . '/layout_footer.php';
?>