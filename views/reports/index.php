<div class="report-grid">
    <?php foreach ($reports as $title => [$page, $description]): ?>
        <a class="report-card" href="<?= url($page) ?>">
            <strong><?= e($title) ?></strong>
            <span><?= e($description) ?></span>
        </a>
    <?php endforeach; ?>
</div>
