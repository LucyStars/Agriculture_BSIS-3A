<?php
require_once __DIR__ . '/auth.php';
require_login();

$search = trim($_GET['search'] ?? '');
$irrigation = (int)($_GET['irrigation'] ?? 0);
$soil = (int)($_GET['soil'] ?? 0);
$sortable = [
    'id'         => 'f.farm_id',
    'name'       => 'f.farm_name',
    'farmer'     => 'fr.last_name',
    'location'   => 'f.location',
    'area'       => 'f.area_hectares',
    'soil'       => 's.soil_type_description',
    'irrigation' => 'i.irrigation_type_description',
];
$sort = array_key_exists($_GET['sort'] ?? '', $sortable) ? $_GET['sort'] : 'id';
$dir = (($_GET['dir'] ?? '') === 'desc') ? 'desc' : 'asc';
$perPage = 5;
$page = max(1, (int)($_GET['page'] ?? 1));

$farms = [];
$irrigations = [];
$soils = [];
$totalRows = 0;
$totalPages = 1;
$error = '';

function link_with(array $change): string
{
    global $search, $irrigation, $soil, $sort, $dir, $page;
    $query = array_merge(
        ['search' => $search, 'irrigation' => $irrigation, 'soil' => $soil, 'sort' => $sort, 'dir' => $dir, 'page' => $page],
        $change
    );
    return '?' . e(http_build_query($query));
}

try {
    $pdo = (new Database())->connect();

    $irrigations = lookup_options($pdo, 'farm_irrigation_type', 'irrigation_type_id', 'irrigation_type_description');
    $soils = lookup_options($pdo, 'farm_soil_type', 'soil_type_id', 'soil_type_description');

    $from = 'FROM farm f
             JOIN farmer fr ON fr.farmer_id = f.farmer_id
             JOIN farm_soil_type s ON s.soil_type_id = f.soil_type_id
             JOIN farm_irrigation_type i ON i.irrigation_type_id = f.irrigation_type_id';

    $conditions = [];
    $params = [];
    if ($search !== '') {
        $conditions[] = '(f.farm_name ILIKE :s1 OR f.location ILIKE :s2
                          OR fr.first_name ILIKE :s3 OR fr.last_name ILIKE :s4)';
        $params[':s1'] = '%' . $search . '%';
        $params[':s2'] = '%' . $search . '%';
        $params[':s3'] = '%' . $search . '%';
        $params[':s4'] = '%' . $search . '%';
    }
    if ($irrigation > 0) {
        $conditions[] = 'f.irrigation_type_id = :irrigation';
        $params[':irrigation'] = $irrigation;
    }
    if ($soil > 0) {
        $conditions[] = 'f.soil_type_id = :soil';
        $params[':soil'] = $soil;
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
    $orderBy = $sortable[$sort] . ' ' . $dir . ', f.farm_id ASC';

    $stmt = $pdo->prepare(
        "SELECT f.farm_id, f.farm_name, fr.first_name, fr.last_name, f.location, f.area_hectares,
                s.soil_type_description, i.irrigation_type_description
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
    $farms = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('Farms list error: ' . $e->getMessage());
    $error = 'Could not load the farms. Please try again later.';
}

function sort_header(string $label, string $key): string
{
    global $sort, $dir;
    $nextDir = ($sort === $key && $dir === 'asc') ? 'desc' : 'asc';
    $arrow = $sort === $key ? ($dir === 'asc' ? ' ▲' : ' ▼') : '';
    return '<a href="' . link_with(['sort' => $key, 'dir' => $nextDir, 'page' => 1]) . '">' . e($label) . $arrow . '</a>';
}

$pageTitle = 'Farms';
include __DIR__ . '/layout_header.php';
?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<form method="get" class="filter-bar">
    <input class="form-control" type="text" name="search" value="<?= e($search) ?>" placeholder="Search farm, location or farmer">
    <select class="form-select" name="irrigation">
        <option value="0">All irrigation types</option>
        <?php foreach ($irrigations as $id => $label): ?>
            <option value="<?= e($id) ?>" <?= $id === $irrigation ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <select class="form-select" name="soil">
        <option value="0">All soil types</option>
        <?php foreach ($soils as $id => $label): ?>
            <option value="<?= e($id) ?>" <?= $id === $soil ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="hidden" name="sort" value="<?= e($sort) ?>">
    <input type="hidden" name="dir" value="<?= e($dir) ?>">
    <button type="submit" class="btn btn-primary">Search</button>
    <?php if ($search !== '' || $irrigation > 0 || $soil > 0): ?><a class="btn btn-outline-secondary" href="farms.php">Clear</a><?php endif; ?>
</form>

<p><a class="btn btn-primary" href="farm_form.php">+ Add Farm</a></p>

<div class="table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
    <thead class="table-head"><tr>
        <th><?= sort_header('ID', 'id') ?></th>
        <th><?= sort_header('Farm', 'name') ?></th>
        <th><?= sort_header('Farmer', 'farmer') ?></th>
        <th><?= sort_header('Location', 'location') ?></th>
        <th><?= sort_header('Area (ha)', 'area') ?></th>
        <th><?= sort_header('Soil type', 'soil') ?></th>
        <th><?= sort_header('Irrigation', 'irrigation') ?></th>
        <th>Actions</th>
    </tr></thead>
    <?php foreach ($farms as $f): ?>
        <tr>
            <td><?= e($f['farm_id']) ?></td>
            <td><?= e($f['farm_name']) ?></td>
            <td><?= e($f['last_name']) ?>, <?= e($f['first_name']) ?></td>
            <td><?= e($f['location']) ?></td>
            <td><?= e($f['area_hectares']) ?></td>
            <td><?= e($f['soil_type_description']) ?></td>
            <td><?= e($f['irrigation_type_description']) ?></td>
            <td>
                <a class="btn btn-sm btn-outline-success" href="farm_view.php?id=<?= e($f['farm_id']) ?>">View</a>
                <a class="btn btn-sm btn-warning" href="farm_form.php?id=<?= e($f['farm_id']) ?>">Edit</a>
                <?php if (is_admin()): ?>
                    <form method="post" action="farm_delete.php" style="display:inline"
                          onsubmit="return confirm('Delete this farm?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= e($f['farm_id']) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$farms && !$error): ?>
        <tr><td colspan="8">No farms found.</td></tr>
    <?php endif; ?>
</table>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
    <span>Page <?= e($page) ?> of <?= e($totalPages) ?> (<?= e($totalRows) ?> farms)</span>
    <nav aria-label="Pages">
        <ul class="pagination mb-0">
            <?php if ($page > 1): ?>
                <li class="page-item"><a class="page-link" href="<?= link_with(['page' => $page - 1]) ?>">Previous</a></li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= link_with(['page' => $i]) ?>"><?= e($i) ?></a></li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <li class="page-item"><a class="page-link" href="<?= link_with(['page' => $page + 1]) ?>">Next</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</div>
<?php
include __DIR__ . '/layout_footer.php';
?>