<?php
require_once __DIR__ . '/auth.php';
require_login();

$search = trim($_GET['search'] ?? '');
$address = trim($_GET['address'] ?? '');
$sortable = [
    'id'      => 'farmer_id',
    'name'    => 'last_name',
    'contact' => 'contact_number',
    'address' => 'address',
    'date'    => 'date_registered',
];
$sort = array_key_exists($_GET['sort'] ?? '', $sortable) ? $_GET['sort'] : 'id';
$dir = (($_GET['dir'] ?? '') === 'desc') ? 'desc' : 'asc';
$perPage = 5;
$page = max(1, (int)($_GET['page'] ?? 1));

$farmers = [];
$addresses = [];
$totalRows = 0;
$totalPages = 1;
$error = '';

function link_with(array $change): string
{
    global $search, $address, $sort, $dir, $page;
    $query = array_merge(
        ['search' => $search, 'address' => $address, 'sort' => $sort, 'dir' => $dir, 'page' => $page],
        $change
    );
    return '?' . e(http_build_query($query));
}

try {
    $pdo = (new Database())->connect();

    $addresses = $pdo->query(
        'SELECT DISTINCT address FROM farmer WHERE address IS NOT NULL ORDER BY address'
    )->fetchAll(PDO::FETCH_COLUMN);

    $conditions = [];
    $params = [];
    if ($search !== '') {
        $conditions[] = '(first_name ILIKE :s1 OR last_name ILIKE :s2
                          OR contact_number ILIKE :s3 OR address ILIKE :s4)';
        $like = '%' . $search . '%';
        $params[':s1'] = $like;
        $params[':s2'] = $like;
        $params[':s3'] = $like;
        $params[':s4'] = $like;
    }
    if ($address !== '') {
        $conditions[] = 'address = :address';
        $params[':address'] = $address;
    }
    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    $count = $pdo->prepare("SELECT COUNT(*) FROM farmer $where");
    $count->execute($params);
    $totalRows = (int)$count->fetchColumn();
    $totalPages = max(1, (int)ceil($totalRows / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;

    $orderBy = $sortable[$sort] . ' ' . $dir . ', farmer_id ASC';

    $stmt = $pdo->prepare(
        "SELECT farmer_id, first_name, last_name, contact_number, address, date_registered
         FROM farmer $where
         ORDER BY $orderBy
         LIMIT :limit OFFSET :offset"
    );
    foreach ($params as $name => $value) {
        $stmt->bindValue($name, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $farmers = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('Farmers list error: ' . $e->getMessage());
    $error = 'Could not load the farmers. Please try again later.';
}

function sort_header(string $label, string $key): string
{
    global $sort, $dir;
    $nextDir = ($sort === $key && $dir === 'asc') ? 'desc' : 'asc';
    $arrow = $sort === $key ? ($dir === 'asc' ? ' ▲' : ' ▼') : '';
    return '<a href="' . link_with(['sort' => $key, 'dir' => $nextDir, 'page' => 1]) . '">' . e($label) . $arrow . '</a>';
}

$pageTitle = 'Farmers';
include __DIR__ . '/layout_header.php';
?>
<?php if ($error): ?><p style="color:red"><?= e($error) ?></p><?php endif; ?>

<form method="get">
    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search name, contact or address">
    <select name="address">
        <option value="">All addresses</option>
        <?php foreach ($addresses as $a): ?>
            <option value="<?= e($a) ?>" <?= $a === $address ? 'selected' : '' ?>><?= e($a) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="hidden" name="sort" value="<?= e($sort) ?>">
    <input type="hidden" name="dir" value="<?= e($dir) ?>">
    <button type="submit">Search</button>
    <?php if ($search !== '' || $address !== ''): ?><a href="farmers.php">Clear</a><?php endif; ?>
</form>

<p><a href="farmer_form.php">+ Add Farmer</a></p>

<table border="1" cellpadding="6">
    <tr>
        <th><?= sort_header('ID', 'id') ?></th>
        <th><?= sort_header('Name', 'name') ?></th>
        <th><?= sort_header('Contact', 'contact') ?></th>
        <th><?= sort_header('Address', 'address') ?></th>
        <th><?= sort_header('Date Registered', 'date') ?></th>
        <th>Actions</th>
    </tr>
    <?php foreach ($farmers as $f): ?>
        <tr>
            <td><?= e($f['farmer_id']) ?></td>
            <td><?= e($f['last_name']) ?>, <?= e($f['first_name']) ?></td>
            <td><?= e($f['contact_number']) ?></td>
            <td><?= e($f['address']) ?></td>
            <td><?= e($f['date_registered']) ?></td>
            <td>
                <a href="farmer_view.php?id=<?= e($f['farmer_id']) ?>">View</a>
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
        | <a href="<?= link_with(['page' => $page - 1]) ?>">Previous</a>
    <?php endif; ?>
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i === $page): ?>
            | <strong><?= e($i) ?></strong>
        <?php else: ?>
            | <a href="<?= link_with(['page' => $i]) ?>"><?= e($i) ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?>
        | <a href="<?= link_with(['page' => $page + 1]) ?>">Next</a>
    <?php endif; ?>
</p>
<?php
include __DIR__ . '/layout_footer.php';
?>