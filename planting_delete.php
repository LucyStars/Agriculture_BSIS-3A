<?php
require_once __DIR__ . '/auth.php';
require_role('Administrator');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: plantings.php');
    exit;
}
if (!csrf_check()) {
    flash_set('error', 'Your session expired. Please try again.');
    header('Location: plantings.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

try {
    $pdo = (new Database())->connect();

    $check = $pdo->prepare('SELECT COUNT(*) FROM harvest WHERE planting_id = :id');
    $check->execute([':id' => $id]);
    $harvests = (int)$check->fetchColumn();

    if ($harvests > 0) {
        flash_set('error', "Cannot delete: this planting has $harvests harvest record(s). Delete those harvests first.");
    } else {
        $stmt = $pdo->prepare('DELETE FROM planting WHERE planting_id = :id');
        $stmt->execute([':id' => $id]);
        flash_set($stmt->rowCount() > 0 ? 'success' : 'error',
                  $stmt->rowCount() > 0 ? 'Planting record deleted.' : 'That planting record was not found.');
    }
} catch (Throwable $e) {
    error_log('Planting delete error: ' . $e->getMessage());
    flash_set('error', 'Could not delete the planting record. Please try again later.');
}

header('Location: plantings.php');
exit;