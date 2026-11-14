<?php
require_once __DIR__ . '/auth.php';
require_login();

$reports = [
    'Farmers and their farms'        => ['report_farmers.php',  'Every farmer with their farms. Filter by date registered.'],
    'Crops planted per farm'         => ['report_crops.php',    'How many plantings and how much area of each crop, per farm. Filter by planting date.'],
    'Plantings by status'            => ['report_status.php',   'Number of plantings and area for each status. Filter by planting date.'],
    'Harvest records'                => ['report_harvests.php', 'Every harvest with quantity, price and value. Filter by harvest date.'],
    'Total harvested quantity'       => ['report_quantity.php', 'Kilograms harvested per crop. Filter by harvest date.'],
    'Estimated harvest value'        => ['report_value.php',    'Quantity x selling price per crop. Filter by harvest date.'],
];

$pageTitle = 'Reports';
include __DIR__ . '/layout_header.php';
?>
<div class="report-grid">
    <?php foreach ($reports as $title => [$page, $description]): ?>
        <a class="report-card" href="<?= e($page) ?>">
            <strong><?= e($title) ?></strong>
            <span><?= e($description) ?></span>
        </a>
    <?php endforeach; ?>
</div>
<?php
include __DIR__ . '/layout_footer.php';
?>