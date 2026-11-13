<?php
require_once __DIR__ . '/auth.php';
require_login();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$isEdit = $id > 0;

$values = ['planting_id' => '', 'harvest_date' => '', 'quantity_kg' => '', 'selling_price_per_kg' => '',
           'quality_grade_id' => '', 'remarks_id' => ''];
$errors = [];
$plantings = $grades = $remarks = [];
$plantingDates = [];

try {
    $pdo = (new Database())->connect();

    $rows = $pdo->query("SELECT p.planting_id, p.planting_date, f.farm_name, c.crop_name
                         FROM planting p
                         JOIN farm f ON f.farm_id = p.farm_id
                         JOIN crop c ON c.crop_id = p.crop_id
                         ORDER BY p.planting_id")->fetchAll();
    foreach ($rows as $r) {
        $plantings[$r['planting_id']] = '#' . $r['planting_id'] . ' ' . $r['farm_name'] . ' - ' . $r['crop_name']
                                       . ' (planted ' . $r['planting_date'] . ')';
        $plantingDates[$r['planting_id']] = $r['planting_date'];
    }
    $grades = lookup_options($pdo, 'harvest_quality_grade', 'quality_grade_id', 'quality_grade_description');
    $remarks = lookup_options($pdo, 'harvest_remark', 'remarks_id', 'remarks_description');

    if ($isEdit && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        $stmt = $pdo->prepare('SELECT planting_id, harvest_date, quantity_kg, selling_price_per_kg, quality_grade_id, remarks_id
                               FROM harvest WHERE harvest_id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            flash_set('error', 'That harvest record was not found.');
            header('Location: harvests.php');
            exit;
        }
        $values = [
            'planting_id'          => (string)$row['planting_id'],
            'harvest_date'         => (string)$row['harvest_date'],
            'quantity_kg'          => (string)($row['quantity_kg'] ?? ''),
            'selling_price_per_kg' => (string)($row['selling_price_per_kg'] ?? ''),
            'quality_grade_id'     => (string)$row['quality_grade_id'],
            'remarks_id'           => (string)$row['remarks_id'],
        ];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $values = [
            'planting_id'          => trim($_POST['planting_id'] ?? ''),
            'harvest_date'         => trim($_POST['harvest_date'] ?? ''),
            'quantity_kg'          => trim($_POST['quantity_kg'] ?? ''),
            'selling_price_per_kg' => trim($_POST['selling_price_per_kg'] ?? ''),
            'quality_grade_id'     => trim($_POST['quality_grade_id'] ?? ''),
            'remarks_id'           => trim($_POST['remarks_id'] ?? ''),
        ];

        if (!csrf_check()) {
            $errors[] = 'Your session expired. Please try again.';
        }
        $plantingOk = array_key_exists((int)$values['planting_id'], $plantings);
        if (!$plantingOk) {
            $errors[] = 'Please choose a planting record.';
        }
        if ($values['harvest_date'] === '') {
            $errors[] = 'Harvest date is required.';
        } elseif (!valid_date($values['harvest_date'])) {
            $errors[] = 'Harvest date is not a valid date.';
        } elseif ($plantingOk && $values['harvest_date'] < $plantingDates[(int)$values['planting_id']]) {
            $errors[] = 'Harvest date cannot be before the planting date (' . $plantingDates[(int)$values['planting_id']] . ').';
        }
        if ($values['quantity_kg'] === '') {
            $errors[] = 'Quantity is required.';
        } elseif (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $values['quantity_kg'])) {
            $errors[] = 'Quantity must be 0 or more (up to 2 decimal places).';
        }
        if ($values['selling_price_per_kg'] === '') {
            $errors[] = 'Selling price is required.';
        } elseif (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $values['selling_price_per_kg'])) {
            $errors[] = 'Selling price must be 0 or more (up to 2 decimal places).';
        }
        if (!array_key_exists((int)$values['quality_grade_id'], $grades)) {
            $errors[] = 'Please choose a quality grade.';
        }
        if (!array_key_exists((int)$values['remarks_id'], $remarks)) {
            $errors[] = 'Please choose the remarks.';
        }

        if (!$errors) {
            $data = [':p' => (int)$values['planting_id'], ':d' => $values['harvest_date'], ':q' => $values['quantity_kg'],
                     ':pr' => $values['selling_price_per_kg'], ':g' => (int)$values['quality_grade_id'],
                     ':r' => (int)$values['remarks_id']];
            if ($isEdit) {
                $stmt = $pdo->prepare('UPDATE harvest SET planting_id = :p, harvest_date = :d, quantity_kg = :q,
                                       selling_price_per_kg = :pr, quality_grade_id = :g, remarks_id = :r
                                       WHERE harvest_id = :id');
                $stmt->execute($data + [':id' => $id]);
                flash_set('success', 'Harvest record updated.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO harvest (planting_id, harvest_date, quantity_kg, selling_price_per_kg,
                                       quality_grade_id, remarks_id) VALUES (:p, :d, :q, :pr, :g, :r)');
                $stmt->execute($data);
                flash_set('success', 'Harvest record added.');
            }
            header('Location: harvests.php');
            exit;
        }
    }
} catch (Throwable $e) {
    error_log('Harvest form error: ' . $e->getMessage());
    $errors[] = 'Could not save the harvest record. Please try again later.';
}

$pageTitle = $isEdit ? 'Edit Harvest Record' : 'Add Harvest Record';
include __DIR__ . '/layout_header.php';
?>
<?php if ($errors): ?>
    <ul style="color:red">
        <?php foreach ($errors as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e($id) ?>">
    <p>
        <label for="planting_id">Planting record *</label><br>
        <select id="planting_id" name="planting_id" required>
            <option value="">-- choose --</option>
            <?php foreach ($plantings as $pid => $label): ?>
                <option value="<?= e($pid) ?>" <?= (string)$pid === $values['planting_id'] ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>
        <label for="harvest_date">Harvest date *</label><br>
        <input type="date" id="harvest_date" name="harvest_date" value="<?= e($values['harvest_date']) ?>" required>
    </p>
    <p>
        <label for="quantity_kg">Quantity (kg) *</label><br>
        <input type="number" id="quantity_kg" name="quantity_kg" step="0.01" min="0" value="<?= e($values['quantity_kg']) ?>" required>
    </p>
    <p>
        <label for="selling_price_per_kg">Selling price per kg *</label><br>
        <input type="number" id="selling_price_per_kg" name="selling_price_per_kg" step="0.01" min="0" value="<?= e($values['selling_price_per_kg']) ?>" required>
    </p>
    <p>
        <label for="quality_grade_id">Quality grade *</label><br>
        <select id="quality_grade_id" name="quality_grade_id" required>
            <option value="">-- choose --</option>
            <?php foreach ($grades as $gid => $label): ?>
                <option value="<?= e($gid) ?>" <?= (string)$gid === $values['quality_grade_id'] ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>
        <label for="remarks_id">Remarks *</label><br>
        <select id="remarks_id" name="remarks_id" required>
            <option value="">-- choose --</option>
            <?php foreach ($remarks as $rid => $label): ?>
                <option value="<?= e($rid) ?>" <?= (string)$rid === $values['remarks_id'] ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <button type="submit"><?= $isEdit ? 'Save changes' : 'Add harvest record' ?></button>
    <a href="harvests.php">Cancel</a>
</form>
<?php
include __DIR__ . '/layout_footer.php';
?>