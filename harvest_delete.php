<?php
require_once __DIR__ . '/auth.php';
require_role('Administrator');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: harvests.php');
    exit;
}
if (!csrf_check()) {
    flash_set('error', 'Your session expired. Please try again.');
    header('Location: harvests.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

try {
    $pdo = (new Database())->connect();

    $stmt = $pdo->prepare('DELETE FROM harvest WHERE harvest_id = :id');
    $stmt->execute([':id' => $id]);
    flash_set($stmt->rowCount() > 0 ? 'success' : 'error',
              $stmt->rowCount() > 0 ? 'Harvest record deleted.' : 'That harvest record was not found.');
} catch (Throwable $e) {
    error_log('Harvest delete error: ' . $e->getMessage());
    flash_set('error', 'Could not delete the harvest record. Please try again later.');
}

header('Location: harvests.php');
exit;