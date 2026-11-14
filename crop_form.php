<?php
require_once __DIR__ . '/auth.php';
require_login();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$isEdit = $id > 0;

$values = ['crop_name' => '', 'variety' => '', 'category_id' => '', 'growing_period_days' => '', 'recommended_season_id' => ''];
$errors = [];

try {
    $pdo = (new Database())->connect();

    $categories = lookup_options($pdo, 'crop_category', 'category_id', 'category_description');
    $seasons = lookup_options($pdo, 'crop_recommended_season', 'recommended_season_id', 'recommended_season_description');

    if ($isEdit && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        $stmt = $pdo->prepare('SELECT crop_name, variety, category_id, growing_period_days, recommended_season_id
                               FROM crop WHERE crop_id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            flash_set('error', 'That crop was not found.');
            header('Location: crops.php');
            exit;
        }
        $values = [
            'crop_name'             => $row['crop_name'],
            'variety'               => $row['variety'] ?? '',
            'category_id'           => (string)$row['category_id'],
            'growing_period_days'   => (string)($row['growing_period_days'] ?? ''),
            'recommended_season_id' => (string)$row['recommended_season_id'],
        ];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $values = [
            'crop_name'             => trim($_POST['crop_name'] ?? ''),
            'variety'               => trim($_POST['variety'] ?? ''),
            'category_id'           => trim($_POST['category_id'] ?? ''),
            'growing_period_days'   => trim($_POST['growing_period_days'] ?? ''),
            'recommended_season_id' => trim($_POST['recommended_season_id'] ?? ''),
        ];

        if (!csrf_check()) {
            $errors[] = 'Your session expired. Please try again.';
        }
        if ($values['crop_name'] === '') {
            $errors[] = 'Crop name is required.';
        } elseif (mb_strlen($values['crop_name']) > 80) {
            $errors[] = 'Crop name must be 80 characters or fewer.';
        }
        if (mb_strlen($values['variety']) > 80) {
            $errors[] = 'Variety must be 80 characters or fewer.';
        }
        if (!array_key_exists((int)$values['category_id'], $categories)) {
            $errors[] = 'Please choose a category.';
        }
        if ($values['growing_period_days'] !== '' &&
            (!ctype_digit($values['growing_period_days']) || (int)$values['growing_period_days'] < 1 || (int)$values['growing_period_days'] > 3650)) {
            $errors[] = 'Growing period must be a whole number of days from 1 to 3650.';
        }
        if (!array_key_exists((int)$values['recommended_season_id'], $seasons)) {
            $errors[] = 'Please choose a recommended season.';
        }

        if (!$errors) {
            $variety = $values['variety'] === '' ? null : $values['variety'];
            $days = $values['growing_period_days'] === '' ? null : (int)$values['growing_period_days'];
            $data = [':n' => $values['crop_name'], ':v' => $variety, ':c' => (int)$values['category_id'],
                     ':d' => $days, ':s' => (int)$values['recommended_season_id']];

            if ($isEdit) {
                $stmt = $pdo->prepare('UPDATE crop SET crop_name = :n, variety = :v, category_id = :c,
                                       growing_period_days = :d, recommended_season_id = :s WHERE crop_id = :id');
                $stmt->execute($data + [':id' => $id]);
                flash_set('success', 'Crop updated.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO crop (crop_name, variety, category_id, growing_period_days, recommended_season_id)
                                       VALUES (:n, :v, :c, :d, :s)');
                $stmt->execute($data);
                flash_set('success', 'Crop added.');
            }
            header('Location: crops.php');
            exit;
        }
    }
} catch (Throwable $e) {
    if (is_duplicate_error($e)) {
        $errors[] = 'That crop and variety already exist.';
    } else {
        error_log('Crop form error: ' . $e->getMessage());
        $errors[] = 'Could not save the crop. Please try again later.';
    }
    $categories = $categories ?? [];
    $seasons = $seasons ?? [];
}

$pageTitle = $isEdit ? 'Edit Crop' : 'Add Crop';
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
        <label for="crop_name">Crop name *</label><br>
        <input class="form-control" type="text" id="crop_name" name="crop_name" maxlength="80" value="<?= e($values['crop_name']) ?>" required>
    </p>
    <p>
        <label for="variety">Variety</label><br>
        <input class="form-control" type="text" id="variety" name="variety" maxlength="80" value="<?= e($values['variety']) ?>">
    </p>
    <p>
        <label for="category_id">Category *</label><br>
        <select class="form-select" id="category_id" name="category_id" required>
            <option value="">-- choose --</option>
            <?php foreach ($categories as $cid => $label): ?>
                <option value="<?= e($cid) ?>" <?= (string)$cid === $values['category_id'] ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>
        <label for="growing_period_days">Growing period (days)</label><br>
        <input class="form-control" type="number" id="growing_period_days" name="growing_period_days" min="1" max="3650" value="<?= e($values['growing_period_days']) ?>">
    </p>
    <p>
        <label for="recommended_season_id">Recommended season *</label><br>
        <select class="form-select" id="recommended_season_id" name="recommended_season_id" required>
            <option value="">-- choose --</option>
            <?php foreach ($seasons as $sid => $label): ?>
                <option value="<?= e($sid) ?>" <?= (string)$sid === $values['recommended_season_id'] ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Add crop' ?></button>
    <a class="btn btn-outline-secondary" href="crops.php">Cancel</a>
</form>
<?php
include __DIR__ . '/layout_footer.php';
?>