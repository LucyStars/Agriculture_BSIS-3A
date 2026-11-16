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
    <a class="btn btn-outline-secondary" href="<?= url('plantings') ?>">Cancel</a>
</form>
