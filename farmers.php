<?php
require_once __DIR__ . '/auth.php';
require_login();

$search = trim($_GET['search'] ?? '');
$perPage = 5;
$page = max(1, (int)($_GET['page'] ?? 1));

$farmers = [];
$totalRows = 0;
$totalPages = 1;
$error = '';

try {
    $pdo = (new Database())->connect();

    $like = '%' . $search . '%';
    $where = 'WHERE first_name ILIKE :s1 OR last_name ILIKE :s2
              OR contact_number ILIKE :s3 OR address ILIKE :s4';

    $count = $pdo->prepare("SELECT COUNT(*) FROM farmer $where");
    $count->execute([':s1' => $like, ':s2' => $like, ':s3' => $like, ':s4' => $like]);
    $totalRows = (int)$count->fetchColumn();
    $totalPages = max(1, (int)ceil($totalRows / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;

    $stmt = $pdo->prepare(
        "SELECT farmer_id, first_name, last_name, contact_number, address, date_registered
         FROM farmer $where
         ORDER BY last_name, first_name
         LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':s1', $like);
    $stmt->bindValue(':s2', $like);
    $stmt->bindValue(':s3', $like);
    $stmt->bindValue(':s4', $like);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $farmers = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('Farmers list error: ' . $e->getMessage());
    $error = 'Could not load the farmers. Please try again later.';
}

$pageTitle = 'Farmers';
include __DIR__ . '/layout_header.php';
?>
<?php if ($error): ?><p style="color:red"><?= e($error) ?></p><?php endif; ?>

<form method="get">
    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search name, contact or address">
    <button type="submit">Search</button>
    <?php if ($search !== ''): ?><a href="farmers.php">Clear</a><?php endif; ?>
</form>

<p><a href="farmer_form.php">+ Add Farmer</a></p>

<table border="1" cellpadding="6">
    <tr>
        <th>ID</th><th>Name</th><th>Contact</th><th>Address</th><th>Date Registered</th><th>Actions</th>
    </tr>
    <?php foreach ($farmers as $f): ?>
        <tr>
            <td><?= e($f['farmer_id']) ?></td>
            <td><?= e($f['last_name']) ?>, <?= e($f['first_name']) ?></td>
            <td><?= e($f['contact_number']) ?></td>
            <td><?= e($f['address']) ?></td>
            <td><?= e($f['date_registered']) ?></td>
            <td>
                <a href="farmer_form.php?id=<?= e($f['farmer_id']) ?>">Edit</a>
                <?php if (is_admin()): ?>
                    <form method="post" action="farmer_delete.php" style="display:inline"
                          onsubmit="return confirm('Delete this farmer?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= e($f['farmer_id']) ?>">
                        <button type="submit">Delete</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$farmers && !$error): ?>
        <tr><td colspan="6">No farmers found.</td></tr>
    <?php endif; ?>
</table>

<p>
    Page <?= e($page) ?> of <?= e($totalPages) ?> (<?= e($totalRows) ?> farmers)
    <?php if ($page > 1): ?>
        | <a href="?<?= e(http_build_query(['search' => $search, 'page' => $page - 1])) ?>">Previous</a>
    <?php endif; ?>
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i === $page): ?>
            | <strong><?= e($i) ?></strong>
        <?php else: ?>
            | <a href="?<?= e(http_build_query(['search' => $search, 'page' => $i])) ?>"><?= e($i) ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?>
        | <a href="?<?= e(http_build_query(['search' => $search, 'page' => $page + 1])) ?>">Next</a>
    <?php endif; ?>
</p>
<?php
include __DIR__ . '/layout_footer.php';
?>