<?php
require_once __DIR__ . '/auth.php';
require_login();

$search = trim($_GET['search'] ?? '');
$category = (int)($_GET['category'] ?? 0);
$season = (int)($_GET['season'] ?? 0);
$sortable = [
    'id'       => 'c.crop_id',
    'name'     => 'c.crop_name',
    'variety'  => 'c.variety',
    'category' => 'cc.category_description',
    'days'     => 'c.growing_period_days',
    'season'   => 's.recommended_season_description',
];
$sort = array_key_exists($_GET['sort'] ?? '', $sortable) ? $_GET['sort'] : 'id';
$dir = (($_GET['dir'] ?? '') === 'desc') ? 'desc' : 'asc';
$perPage = 5;
$page = max(1, (int)($_GET['page'] ?? 1));

$crops = [];
$categories = [];
$seasons = [];
$totalRows = 0;
$totalPages = 1;
$error = '';

function link_with(array $change): string
{
    global $search, $category, $season, $sort, $dir, $page;
    $query = array_merge(
        ['search' => $search, 'category' => $category, 'season' => $season, 'sort' => $sort, 'dir' => $dir, 'page' => $page],
        $change
    );
    return '?' . e(http_build_query($query));
}

try {
    $pdo = (new Database())->connect();

    $categories = lookup_options($pdo, 'crop_category', 'category_id', 'category_description');
    $seasons = lookup_options($pdo, 'crop_recommended_season', 'recommended_season_id', 'recommended_season_description');

    $from = 'FROM crop c
             JOIN crop_category cc ON cc.category_id = c.category_id
             JOIN crop_recommended_season s ON s.recommended_season_id = c.recommended_season_id';

    $conditions = [];
    $params = [];
    if ($search !== '') {
        $conditions[] = '(c.crop_name ILIKE :s1 OR c.variety ILIKE :s2)';
        $params[':s1'] = '%' . $search . '%';
        $params[':s2'] = '%' . $search . '%';
    }
    if ($category > 0) {
        $conditions[] = 'c.category_id = :category';
        $params[':category'] = $category;
    }
    if ($season > 0) {
        $conditions[] = 'c.recommended_season_id = :season';
        $params[':season'] = $season;
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

    $orderBy = $sortable[$sort] . ' ' . $dir . ', c.crop_id ASC';

    $stmt = $pdo->prepare(
        "SELECT c.crop_id, c.crop_name, c.variety, cc.category_description,
                c.growing_period_days, s.recommended_season_description
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
    $crops = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('Crops list error: ' . $e->getMessage());
    $error = 'Could not load the crops. Please try again later.';
}

function sort_header(string $label, string $key): string
{
    global $sort, $dir;
    $nextDir = ($sort === $key && $dir === 'asc') ? 'desc' : 'asc';
    $arrow = $sort === $key ? ($dir === 'asc' ? ' ▲' : ' ▼') : '';
    return '<a href="' . link_with(['sort' => $key, 'dir' => $nextDir, 'page' => 1]) . '">' . e($label) . $arrow . '</a>';
}

$pageTitle = 'Crops';
include __DIR__ . '/layout_header.php';
?>
<?php if ($error): ?><p style="color:red"><?= e($error) ?></p><?php endif; ?>

<form method="get">
    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search crop or variety">
    <select name="category">
        <option value="0">All categories</option>
        <?php foreach ($categories as $id => $label): ?>
            <option value="<?= e($id) ?>" <?= $id === $category ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="season">
        <option value="0">All seasons</option>
        <?php foreach ($seasons as $id => $label): ?>
            <option value="<?= e($id) ?>" <?= $id === $season ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="hidden" name="sort" value="<?= e($sort) ?>">
    <input type="hidden" name="dir" value="<?= e($dir) ?>">
    <button type="submit">Search</button>
    <?php if ($search !== '' || $category > 0 || $season > 0): ?><a href="crops.php">Clear</a><?php endif; ?>
</form>

<p><a href="crop_form.php">+ Add Crop</a></p>

<table border="1" cellpadding="6">
    <tr>
        <th><?= sort_header('ID', 'id') ?></th>
        <th><?= sort_header('Crop', 'name') ?></th>
        <th><?= sort_header('Variety', 'variety') ?></th>
        <th><?= sort_header('Category', 'category') ?></th>
        <th><?= sort_header('Growing days', 'days') ?></th>
        <th><?= sort_header('Recommended season', 'season') ?></th>
        <th>Actions</th>
    </tr>
    <?php foreach ($crops as $c): ?>
        <tr>
            <td><?= e($c['crop_id']) ?></td>
            <td><?= e($c['crop_name']) ?></td>
            <td><?= e($c['variety']) ?></td>
            <td><?= e($c['category_description']) ?></td>
            <td><?= e($c['growing_period_days']) ?></td>
            <td><?= e($c['recommended_season_description']) ?></td>
            <td>
                <a href="crop_view.php?id=<?= e($c['crop_id']) ?>">View</a>
                <a href="crop_form.php?id=<?= e($c['crop_id']) ?>">Edit</a>
                <?php if (is_admin()): ?>
                    <form method="post" action="crop_delete.php" style="display:inline"
                          onsubmit="return confirm('Delete this crop?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= e($c['crop_id']) ?>">
                        <button type="submit">Delete</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$crops && !$error): ?>
        <tr><td colspan="7">No crops found.</td></tr>
    <?php endif; ?>
</table>

<p>
    Page <?= e($page) ?> of <?= e($totalPages) ?> (<?= e($totalRows) ?> crops)
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