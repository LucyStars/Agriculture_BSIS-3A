<?php
require_once __DIR__ . '/auth.php';
require_login();

$search = trim($_GET['search'] ?? '');
$status = (int)($_GET['status'] ?? 0);
$sortable = [
    'id'       => 'p.planting_id',
    'farm'     => 'f.farm_name',
    'crop'     => 'c.crop_name',
    'date'     => 'p.planting_date',
    'area'     => 'p.area_planted',
    'expected' => 'p.expected_harvest_date',
    'status'   => 'st.status_description',
];
$sort = array_key_exists($_GET['sort'] ?? '', $sortable) ? $_GET['sort'] : 'id';
$dir = (($_GET['dir'] ?? '') === 'desc') ? 'desc' : 'asc';
$perPage = 5;
$page = max(1, (int)($_GET['page'] ?? 1));

$plantings = [];
$statuses = [];
$totalRows = 0;
$totalPages = 1;
$error = '';

function link_with(array $change): string
{
    global $search, $status, $sort, $dir, $page;
    $query = array_merge(
        ['search' => $search, 'status' => $status, 'sort' => $sort, 'dir' => $dir, 'page' => $page],
        $change
    );
    return '?' . e(http_build_query($query));
}

try {
    $pdo = (new Database())->connect();

    $statuses = lookup_options($pdo, 'planting_status', 'status_id', 'status_description');

    $from = 'FROM planting p
             JOIN farm f ON f.farm_id = p.farm_id
             JOIN crop c ON c.crop_id = p.crop_id
             JOIN planting_status st ON st.status_id = p.status_id';

    $conditions = [];
    $params = [];
    if ($search !== '') {
        $conditions[] = '(f.farm_name ILIKE :s1 OR c.crop_name ILIKE :s2 OR c.variety ILIKE :s3)';
        $params[':s1'] = '%' . $search . '%';
        $params[':s2'] = '%' . $search . '%';
        $params[':s3'] = '%' . $search . '%';
    }
    if ($status > 0) {
        $conditions[] = 'p.status_id = :status';
        $params[':status'] = $status;
    }
    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    $count = $pdo->prepare("SELECT COUNT(*) $from $where");
    $count->execute($params);
    $totalRows = (int)$count->fetchColumn();
    $totalPages = max(1, (int)ceil($totalRows / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;
    $orderBy = $sortable[$sort] . ' ' . $dir . ', p.planting_id ASC';

    $stmt = $pdo->prepare(
        "SELECT p.planting_id, f.farm_name, c.crop_name, c.variety, p.planting_date, p.area_planted,
                p.expected_harvest_date, st.status_description
         $from $where
         ORDER BY $orderBy
         LIMIT :limit OFFSET :offset"
    );
    foreach ($params as $name => $value) {
        $stmt->bindValue($name, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $plantings = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('Plantings list error: ' . $e->getMessage());
    $error = 'Could not load the planting records. Please try again later.';
}

function sort_header(string $label, string $key): string
{
    global $sort, $dir;
    $nextDir = ($sort === $key && $dir === 'asc') ? 'desc' : 'asc';
    $arrow = $sort === $key ? ($dir === 'asc' ? ' ▲' : ' ▼') : '';
    return '<a href="' . link_with(['sort' => $key, 'dir' => $nextDir, 'page' => 1]) . '">' . e($label) . $arrow . '</a>';
}

$pageTitle = 'Planting Records';
include __DIR__ . '/layout_header.php';
?>
<?php if ($error): ?><p style="color:red"><?= e($error) ?></p><?php endif; ?>

<form method="get">
    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search farm or crop">
    <select name="status">
        <option value="0">All statuses</option>
        <?php foreach ($statuses as $id => $label): ?>
            <option value="<?= e($id) ?>" <?= $id === $status ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="hidden" name="sort" value="<?= e($sort) ?>">
    <input type="hidden" name="dir" value="<?= e($dir) ?>">
    <button type="submit">Search</button>
    <?php if ($search !== '' || $status > 0): ?><a href="plantings.php">Clear</a><?php endif; ?>
</form>

<p><a href="planting_form.php">+ Add Planting</a></p>

<table border="1" cellpadding="6">
    <tr>
        <th><?= sort_header('ID', 'id') ?></th>
        <th><?= sort_header('Farm', 'farm') ?></th>
        <th><?= sort_header('Crop', 'crop') ?></th>
        <th><?= sort_header('Planting date', 'date') ?></th>
        <th><?= sort_header('Area planted (ha)', 'area') ?></th>
        <th><?= sort_header('Expected harvest', 'expected') ?></th>
        <th><?= sort_header('Status', 'status') ?></th>
        <th>Actions</th>
    </tr>
    <?php foreach ($plantings as $p): ?>
        <tr>
            <td><?= e($p['planting_id']) ?></td>
            <td><?= e($p['farm_name']) ?></td>
            <td><?= e($p['crop_name']) ?><?= $p['variety'] ? ' (' . e($p['variety']) . ')' : '' ?></td>
            <td><?= e($p['planting_date']) ?></td>
            <td><?= e($p['area_planted']) ?></td>
            <td><?= e($p['expected_harvest_date']) ?></td>
            <td><?= e($p['status_description']) ?></td>
            <td>
                <a href="planting_view.php?id=<?= e($p['planting_id']) ?>">View</a>
                <a href="planting_form.php?id=<?= e($p['planting_id']) ?>">Edit</a>
                <?php if (is_admin()): ?>
                    <form method="post" action="planting_delete.php" style="display:inline"
                          onsubmit="return confirm('Delete this planting record?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= e($p['planting_id']) ?>">
                        <button type="submit">Delete</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$plantings && !$error): ?>
        <tr><td colspan="8">No planting records found.</td></tr>
    <?php endif; ?>
</table>

<p>
    Page <?= e($page) ?> of <?= e($totalPages) ?> (<?= e($totalRows) ?> planting records)
    <?php if ($page > 1): ?>| <a href="<?= link_with(['page' => $page - 1]) ?>">Previous</a><?php endif; ?>
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i === $page): ?>| <strong><?= e($i) ?></strong>
        <?php else: ?>| <a href="<?= link_with(['page' => $i]) ?>"><?= e($i) ?></a><?php endif; ?>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?>| <a href="<?= link_with(['page' => $page + 1]) ?>">Next</a><?php endif; ?>
</p>
<?php
include __DIR__ . '/layout_footer.php';
?>