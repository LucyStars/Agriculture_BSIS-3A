<?php
require_once __DIR__ . '/auth.php';
require_role('Administrator');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: farms.php');
    exit;
}
if (!csrf_check()) {
    flash_set('error', 'Your session expired. Please try again.');
    header('Location: farms.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

try {
    $pdo = (new Database())->connect();

    $check = $pdo->prepare('SELECT COUNT(*) FROM planting WHERE farm_id = :id');
    $check->execute([':id' => $id]);
    $plantings = (int)$check->fetchColumn();

    if ($plantings > 0) {
        flash_set('error', "Cannot delete: this farm has $plantings planting record(s). Delete those plantings first.");
    } else {
        $stmt = $pdo->prepare('DELETE FROM farm WHERE farm_id = :id');
        $stmt->execute([':id' => $id]);
        flash_set($stmt->rowCount() > 0 ? 'success' : 'error',
                  $stmt->rowCount() > 0 ? 'Farm deleted.' : 'That farm was not found.');
    }
} catch (Throwable $e) {
    error_log('Farm delete error: ' . $e->getMessage());
    flash_set('error', 'Could not delete the farm. Please try again later.');
}

header('Location: farms.php');
exit;