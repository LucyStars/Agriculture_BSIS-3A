<?php
require_once __DIR__ . '/auth.php';
require_login();

$search = trim($_GET['search'] ?? '');
$grade = (int)($_GET['grade'] ?? 0);
$sortable = [
    'id'    => 'h.harvest_id',
    'farm'  => 'f.farm_name',
    'crop'  => 'c.crop_name',
    'date'  => 'h.harvest_date',
    'qty'   => 'h.quantity_kg',
    'price' => 'h.selling_price_per_kg',
    'value' => '(h.quantity_kg * h.selling_price_per_kg)',
    'grade' => 'g.quality_grade_description',
];
$sort = array_key_exists($_GET['sort'] ?? '', $sortable) ? $_GET['sort'] : 'id';
$dir = (($_GET['dir'] ?? '') === 'desc') ? 'desc' : 'asc';
$perPage = 5;
$page = max(1, (int)($_GET['page'] ?? 1));

$harvests = [];
$grades = [];
$totalRows = 0;
$totalPages = 1;
$error = '';

function link_with(array $change): string
{
    global $search, $grade, $sort, $dir, $page;
    $query = array_merge(
        ['search' => $search, 'grade' => $grade, 'sort' => $sort, 'dir' => $dir, 'page' => $page],
        $change
    );
    return '?' . e(http_build_query($query));
}

try {
    $pdo = (new Database())->connect();

    $grades = lookup_options($pdo, 'harvest_quality_grade', 'quality_grade_id', 'quality_grade_description');

    $from = 'FROM harvest h
             JOIN planting p ON p.planting_id = h.planting_id
             JOIN farm f ON f.farm_id = p.farm_id
             JOIN crop c ON c.crop_id = p.crop_id
             JOIN harvest_quality_grade g ON g.quality_grade_id = h.quality_grade_id
             JOIN harvest_remark r ON r.remarks_id = h.remarks_id';

    $conditions = [];
    $params = [];
    if ($search !== '') {
        $conditions[] = '(f.farm_name ILIKE :s1 OR c.crop_name ILIKE :s2 OR r.remarks_description ILIKE :s3)';
        $params[':s1'] = '%' . $search . '%';
        $params[':s2'] = '%' . $search . '%';
        $params[':s3'] = '%' . $search . '%';
    }
    if ($grade > 0) {
        $conditions[] = 'h.quality_grade_id = :grade';
        $params[':grade'] = $grade;
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
    $orderBy = $sortable[$sort] . ' ' . $dir . ', h.harvest_id ASC';

    $stmt = $pdo->prepare(
        "SELECT h.harvest_id, f.farm_name, c.crop_name, h.harvest_date, h.quantity_kg, h.selling_price_per_kg,
                (h.quantity_kg * h.selling_price_per_kg) AS estimated_value,
                g.quality_grade_description, r.remarks_description
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
    $harvests = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('Harvests list error: ' . $e->getMessage());
    $error = 'Could not load the harvest records. Please try again later.';
}

function sort_header(string $label, string $key): string
{
    global $sort, $dir;
    $nextDir = ($sort === $key && $dir === 'asc') ? 'desc' : 'asc';
    $arrow = $sort === $key ? ($dir === 'asc' ? ' ▲' : ' ▼') : '';
    return '<a href="' . link_with(['sort' => $key, 'dir' => $nextDir, 'page' => 1]) . '">' . e($label) . $arrow . '</a>';
}

$pageTitle = 'Harvest Records';
include __DIR__ . '/layout_header.php';
?>
<?php if ($error): ?><p style="color:red"><?= e($error) ?></p><?php endif; ?>

<form method="get">
    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search farm, crop or remarks">
    <select name="grade">
        <option value="0">All quality grades</option>
        <?php foreach ($grades as $id => $label): ?>
            <option value="<?= e($id) ?>" <?= $id === $grade ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="hidden" name="sort" value="<?= e($sort) ?>">
    <input type="hidden" name="dir" value="<?= e($dir) ?>">
    <button type="submit">Search</button>
    <?php if ($search !== '' || $grade > 0): ?><a href="harvests.php">Clear</a><?php endif; ?>
</form>

<p><a href="harvest_form.php">+ Add Harvest</a></p>

<table border="1" cellpadding="6">
    <tr>
        <th><?= sort_header('ID', 'id') ?></th>
        <th><?= sort_header('Farm', 'farm') ?></th>
        <th><?= sort_header('Crop', 'crop') ?></th>
        <th><?= sort_header('Harvest date', 'date') ?></th>
        <th><?= sort_header('Quantity (kg)', 'qty') ?></th>
        <th><?= sort_header('Price per kg', 'price') ?></th>
        <th><?= sort_header('Estimated value', 'value') ?></th>
        <th><?= sort_header('Grade', 'grade') ?></th>
        <th>Remarks</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($harvests as $h): ?>
        <tr>
            <td><?= e($h['harvest_id']) ?></td>
            <td><?= e($h['farm_name']) ?></td>
            <td><?= e($h['crop_name']) ?></td>
            <td><?= e($h['harvest_date']) ?></td>
            <td align="right"><?= e(number_format((float)$h['quantity_kg'], 2)) ?></td>
            <td align="right"><?= e(number_format((float)$h['selling_price_per_kg'], 2)) ?></td>
            <td align="right"><?= e(number_format((float)$h['estimated_value'], 2)) ?></td>
            <td><?= e($h['quality_grade_description']) ?></td>
            <td><?= e($h['remarks_description']) ?></td>
            <td>
                <a href="harvest_view.php?id=<?= e($h['harvest_id']) ?>">View</a>
                <a href="harvest_form.php?id=<?= e($h['harvest_id']) ?>">Edit</a>
                <?php if (is_admin()): ?>
                    <form method="post" action="harvest_delete.php" style="display:inline"
                          onsubmit="return confirm('Delete this harvest record?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= e($h['harvest_id']) ?>">
                        <button type="submit">Delete</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$harvests && !$error): ?>
        <tr><td colspan="10">No harvest records found.</td></tr>
    <?php endif; ?>
</table>

<p>
    Page <?= e($page) ?> of <?= e($totalPages) ?> (<?= e($totalRows) ?> harvest records)
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