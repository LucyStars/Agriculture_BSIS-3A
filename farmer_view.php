<?php
require_once __DIR__ . '/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);

try {
    $pdo = (new Database())->connect();

    $stmt = $pdo->prepare(
        'SELECT farmer_id, first_name, last_name, contact_number, address,
                date_registered, created_at, updated_at
         FROM farmer WHERE farmer_id = :id'
    );
    $stmt->execute([':id' => $id]);
    $farmer = $stmt->fetch();

    if (!$farmer) {
        flash_set('error', 'That farmer was not found.');
        header('Location: farmers.php');
        exit;
    }

    $stmt = $pdo->prepare(
        'SELECT f.farm_id, f.farm_name, f.location, f.area_hectares,
                s.soil_type_description, i.irrigation_type_description
         FROM farm f
         JOIN farm_soil_type s ON s.soil_type_id = f.soil_type_id
         JOIN farm_irrigation_type i ON i.irrigation_type_id = f.irrigation_type_id
         WHERE f.farmer_id = :id
         ORDER BY f.farm_id'
    );
    $stmt->execute([':id' => $id]);
    $farms = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('Farmer view error: ' . $e->getMessage());
    flash_set('error', 'Could not load the farmer. Please try again later.');
    header('Location: farmers.php');
    exit;
}

$pageTitle = 'Farmer Details';
include __DIR__ . '/layout_header.php';
?>
<table border="1" cellpadding="6">
    <tr><th align="left">ID</th><td><?= e($farmer['farmer_id']) ?></td></tr>
    <tr><th align="left">Name</th><td><?= e($farmer['first_name']) ?> <?= e($farmer['last_name']) ?></td></tr>
    <tr><th align="left">Contact number</th><td><?= e($farmer['contact_number']) ?></td></tr>
    <tr><th align="left">Address</th><td><?= e($farmer['address']) ?></td></tr>
    <tr><th align="left">Date registered</th><td><?= e($farmer['date_registered']) ?></td></tr>
    <tr><th align="left">Record created</th><td><?= e($farmer['created_at']) ?></td></tr>
    <tr><th align="left">Last updated</th><td><?= e($farmer['updated_at']) ?></td></tr>
</table>

<h2>Farms (<?= e(count($farms)) ?>)</h2>
<?php if ($farms): ?>
    <table border="1" cellpadding="6">
        <tr><th>ID</th><th>Farm</th><th>Location</th><th>Area (ha)</th><th>Soil type</th><th>Irrigation</th></tr>
        <?php foreach ($farms as $farm): ?>
            <tr>
                <td><?= e($farm['farm_id']) ?></td>
                <td><?= e($farm['farm_name']) ?></td>
                <td><?= e($farm['location']) ?></td>
                <td><?= e($farm['area_hectares']) ?></td>
                <td><?= e($farm['soil_type_description']) ?></td>
                <td><?= e($farm['irrigation_type_description']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>This farmer has no farms yet.</p>
<?php endif; ?>

<p>
    <a href="farmer_form.php?id=<?= e($farmer['farmer_id']) ?>">Edit</a> |
    <a href="farmers.php">Back to list</a>
</p>
<?php
include __DIR__ . '/layout_footer.php';
?>