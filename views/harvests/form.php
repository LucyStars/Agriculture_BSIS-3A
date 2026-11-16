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
        <label for="planting_id">Planting record *</label><br>
        <select class="form-select" id="planting_id" name="planting_id" required>
            <option value="">-- choose --</option>
            <?php foreach ($plantings as $pid => $label): ?>
                <option value="<?= e($pid) ?>" <?= (string)$pid === $values['planting_id'] ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>
        <label for="harvest_date">Harvest date *</label><br>
        <input class="form-control" type="date" id="harvest_date" name="harvest_date" value="<?= e($values['harvest_date']) ?>" required>
    </p>
    <p>
        <label for="quantity_kg">Quantity (kg) *</label><br>
        <input class="form-control" type="number" id="quantity_kg" name="quantity_kg" step="0.01" min="0" value="<?= e($values['quantity_kg']) ?>" required>
    </p>
    <p>
        <label for="selling_price_per_kg">Selling price per kg *</label><br>
        <input class="form-control" type="number" id="selling_price_per_kg" name="selling_price_per_kg" step="0.01" min="0" value="<?= e($values['selling_price_per_kg']) ?>" required>
    </p>
    <p>
        <label for="quality_grade_id">Quality grade *</label><br>
        <select class="form-select" id="quality_grade_id" name="quality_grade_id" required>
            <option value="">-- choose --</option>
            <?php foreach ($grades as $gid => $label): ?>
                <option value="<?= e($gid) ?>" <?= (string)$gid === $values['quality_grade_id'] ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>
        <label for="remarks_id">Remarks *</label><br>
        <select class="form-select" id="remarks_id" name="remarks_id" required>
            <option value="">-- choose --</option>
            <?php foreach ($remarks as $rid => $label): ?>
                <option value="<?= e($rid) ?>" <?= (string)$rid === $values['remarks_id'] ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Add harvest record' ?></button>
    <a class="btn btn-outline-secondary" href="<?= url('harvests') ?>">Cancel</a>
</form>
