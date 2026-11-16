<?php if ($errors): ?>
    <div class="alert alert-danger">
<ul class="mb-0">
        <?php foreach ($errors as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="post" autocomplete="off" class="form-card">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e($id) ?>">
    <p>
        <label for="username">Username<?= $isEdit ? '' : ' *' ?></label><br>
        <?php if ($isEdit): ?>
            <strong><?= e($values['username']) ?></strong> (cannot be changed)
        <?php else: ?>
            <input class="form-control" type="text" id="username" name="username" maxlength="50" value="<?= e($values['username']) ?>" required>
        <?php endif; ?>
    </p>
    <p>
        <label for="full_name">Full name *</label><br>
        <input class="form-control" type="text" id="full_name" name="full_name" maxlength="150" value="<?= e($values['full_name']) ?>" required>
    </p>
    <p>
        <label for="role_id">Role *</label><br>
        <select class="form-select" id="role_id" name="role_id" <?= $isSelf ? 'disabled' : 'required' ?>>
            <option value="">-- choose --</option>
            <?php foreach ($roles as $rid => $label): ?>
                <option value="<?= e($rid) ?>" <?= (string)$rid === $values['role_id'] ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($isSelf): ?><br><em>You cannot change your own role.</em><?php endif; ?>
    </p>
    <p>
        <label for="password"><?= $isEdit ? 'New password (leave blank to keep the current one)' : 'Password *' ?></label><br>
        <input class="form-control" type="password" id="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" <?= $isEdit ? '' : 'required' ?>>
    </p>
    <p>
        <label for="confirm">Confirm password</label><br>
        <input class="form-control" type="password" id="confirm" name="confirm" minlength="8" maxlength="72" autocomplete="new-password" <?= $isEdit ? '' : 'required' ?>>
    </p>
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Add user' ?></button>
    <a class="btn btn-outline-secondary" href="<?= url('users') ?>">Cancel</a>
</form>
