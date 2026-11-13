<?php
require_once __DIR__ . '/auth.php';
require_role('Administrator');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: crops.php');
    exit;
}
if (!csrf_check()) {
    flash_set('error', 'Your session expired. Please try again.');
    header('Location: crops.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

try {
    $pdo = (new Database())->connect();

    $check = $pdo->prepare('SELECT COUNT(*) FROM planting WHERE crop_id = :id');
    $check->execute([':id' => $id]);
    $plantings = (int)$check->fetchColumn();

    if ($plantings > 0) {
        flash_set('error', "Cannot delete: this crop is used in $plantings planting record(s). Delete those plantings first.");
    } else {
        $stmt = $pdo->prepare('DELETE FROM crop WHERE crop_id = :id');
        $stmt->execute([':id' => $id]);
        flash_set($stmt->rowCount() > 0 ? 'success' : 'error',
                  $stmt->rowCount() > 0 ? 'Crop deleted.' : 'That crop was not found.');
    }
} catch (Throwable $e) {
    error_log('Crop delete error: ' . $e->getMessage());
    flash_set('error', 'Could not delete the crop. Please try again later.');
}

header('Location: crops.php');
exit;