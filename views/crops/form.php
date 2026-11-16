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
    <a class="btn btn-outline-secondary" href="<?= url('crops') ?>">Cancel</a>
</form>
