<p class="text-muted">Welcome, <?= e($_SESSION['full_name']) ?>.</p>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<div class="stats">
    <?php foreach ($tables as $table => [$label, $page]): ?>
        <a class="stat" href="<?= url($page) ?>">
            <div class="num"><?= isset($counts[$table]) ? e($counts[$table]) : '-' ?></div>
            <div class="lbl"><?= e($label) ?></div>
        </a>
    <?php endforeach; ?>
    <div class="stat">
        <div class="num"><?= e(number_format($totalKg, 2)) ?></div>
        <div class="lbl">Total harvested (kg)</div>
    </div>
    <div class="stat">
        <div class="num"><?= e(number_format($totalValue, 2)) ?></div>
        <div class="lbl">Estimated harvest value</div>
    </div>
</div>

<div class="z-grid">
    <section class="panel">
        <h2>Plantings by status</h2>
        <?= chart_donut(array_column($byStatus, 'status_description'), array_map('intval', array_column($byStatus, 'total'))) ?>
    </section>

    <section class="panel">
        <h2>Recent plantings</h2>
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm align-middle mb-0">
                <thead class="table-head"><tr><th>ID</th><th>Farm</th><th>Crop</th><th>Planting date</th><th>Status</th></tr></thead>
                <?php foreach ($recentPlantings as $p): ?>
                    <tr>
                        <td><a href="<?= url('planting_view', ['id' => $p['planting_id']]) ?>"><?= e($p['planting_id']) ?></a></td>
                        <td><?= e($p['farm_name']) ?></td>
                        <td><?= e($p['crop_name']) ?></td>
                        <td><?= e($p['planting_date']) ?></td>
                        <td><?= e($p['status_description']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recentPlantings): ?><tr><td colspan="5">No plantings yet.</td></tr><?php endif; ?>
            </table>
        </div>
        <a class="btn btn-sm btn-outline-success" href="<?= url('plantings') ?>">All plantings</a>
    </section>

    <section class="panel">
        <h2>Recent harvests</h2>
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm align-middle mb-0">
                <thead class="table-head"><tr><th>ID</th><th>Farm</th><th>Crop</th><th>Harvest date</th><th class="text-end">Quantity (kg)</th></tr></thead>
                <?php foreach ($recentHarvests as $h): ?>
                    <tr>
                        <td><a href="<?= url('harvest_view', ['id' => $h['harvest_id']]) ?>"><?= e($h['harvest_id']) ?></a></td>
                        <td><?= e($h['farm_name']) ?></td>
                        <td><?= e($h['crop_name']) ?></td>
                        <td><?= e($h['harvest_date']) ?></td>
                        <td class="text-end"><?= e(number_format((float)$h['quantity_kg'], 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recentHarvests): ?><tr><td colspan="5">No harvests yet.</td></tr><?php endif; ?>
            </table>
        </div>
        <a class="btn btn-sm btn-outline-success" href="<?= url('harvests') ?>">All harvests</a>
        <a class="btn btn-sm btn-outline-success" href="<?= url('reports') ?>">Reports</a>
    </section>

    <section class="panel">
        <h2>Harvested quantity per month (kg)</h2>
        <?= chart_columns($monthLabels, array_map('floatval', array_column($byMonth, 'kg')), CHART_COLORS[0]) ?>
    </section>

    <section class="panel">
        <h2>Harvested quantity by crop (kg)</h2>
        <?= chart_bars(array_column($kgByCrop, 'label'), array_map('floatval', array_column($kgByCrop, 'value')), CHART_COLORS[2], ' kg') ?>
    </section>

    <section class="panel">
        <h2>Estimated harvest value by crop</h2>
        <?= chart_bars(array_column($valueByCrop, 'label'), array_map('floatval', array_column($valueByCrop, 'value')), CHART_COLORS[1]) ?>
    </section>
</div>
