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
    <a class="btn btn-outline-secondary" href="<?= url('farms') ?>">Cancel</a>
</form>
