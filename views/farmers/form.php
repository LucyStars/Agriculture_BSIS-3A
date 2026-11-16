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
    <a class="btn btn-outline-secondary" href="<?= url('farmers') ?>">Cancel</a>
</form>
