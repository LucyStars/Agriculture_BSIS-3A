<?php
require_once __DIR__ . '/auth.php';
require_role('Administrator');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: farmers.php');
    exit;
}
if (!csrf_check()) {
    flash_set('error', 'Your session expired. Please try again.');
    header('Location: farmers.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

try {
    $pdo = (new Database())->connect();

    $check = $pdo->prepare('SELECT COUNT(*) FROM farm WHERE farmer_id = :id');
    $check->execute([':id' => $id]);
    $farms = (int)$check->fetchColumn();

    if ($farms > 0) {
        flash_set('error', "Cannot delete: this farmer still has $farms farm(s). Delete or reassign the farms first.");
    } else {
        $stmt = $pdo->prepare('DELETE FROM farmer WHERE farmer_id = :id');
        $stmt->execute([':id' => $id]);
        flash_set($stmt->rowCount() > 0 ? 'success' : 'error',
                  $stmt->rowCount() > 0 ? 'Farmer deleted.' : 'That farmer was not found.');
    }
} catch (Throwable $e) {
    error_log('Farmer delete error: ' . $e->getMessage());
    flash_set('error', 'Could not delete the farmer. Please try again later.');
}

header('Location: farmers.php');
exit;