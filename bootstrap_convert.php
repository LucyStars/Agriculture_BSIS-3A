<?php
$skip = ['index.php', 'reports.php', 'login.php', 'layout_header.php', 'layout_footer.php', 'charts.php',
         'Database.php', 'logout.php', 'bootstrap_convert.php'];

function convert_tables(string $s): string
{
    $s = preg_replace('#<div style="overflow-x:auto">\s*(<table border="1" cellpadding="6">.*?</table>)\s*</div>#s', '$1', $s);
    return preg_replace_callback('#<table border="1" cellpadding="6">.*?</table>#s', function ($m) {
        $t = str_replace('<table border="1" cellpadding="6">', '<table class="table table-striped table-hover align-middle mb-0">', $m[0]);
        $t = preg_replace_callback('#(<table[^>]*>\s*(?:<!--.*?-->\s*)*)(<tr>(?:(?!</tr>).)*?</tr>)#s', function ($h) {
            if (strpos($h[2], '<th') !== false && strpos($h[2], '<td') === false) {
                return $h[1] . '<thead class="table-head">' . $h[2] . '</thead>';
            }
            return $h[0];
        }, $t, 1);
        return "<div class=\"table-responsive\">\n" . $t . "\n</div>";
    }, $s);
}

function convert_pagination(string $s): string
{
    $template = <<<'HTML'
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
    <span>Page <?= e($page) ?> of <?= e($totalPages) ?> (<?= e($totalRows) ?> {LABEL})</span>
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
HTML;
    return preg_replace_callback(
        '#<p>\s*Page <\?= e\(\$page\) \?> of <\?= e\(\$totalPages\) \?> \(<\?= e\(\$totalRows\) \?> ([a-z ]+)\).*?</p>#s',
        fn($m) => str_replace('{LABEL}', $m[1], $template),
        $s
    );
}

function convert_page(string $s): string
{
    $s = str_replace('<p style="color:red"><?= e($error) ?></p>', '<div class="alert alert-danger"><?= e($error) ?></div>', $s);
    $s = preg_replace('#<ul style="color:red">(.*?)</ul>#s', "<div class=\"alert alert-danger\">\n<ul class=\"mb-0\">$1</ul>\n</div>", $s);
    $s = str_replace("echo '<p style=\"color:red\">You do not have permission to open this page.</p>';",
        "echo '<div class=\"alert alert-danger\">You do not have permission to open this page.</div>';", $s);
    $s = str_replace("echo '<p style=\"color:red\">' . e(\$msg) . '</p>';", "echo '<div class=\"alert alert-danger\">' . e(\$msg) . '</div>';", $s);
    $s = str_replace("echo '<p style=\"color:red\">' . e(\$error) . '</p>';", "echo '<div class=\"alert alert-danger\">' . e(\$error) . '</div>';", $s);

    $s = convert_tables($s);
    $s = convert_pagination($s);

    $s = str_replace('<th align="left">', '<th class="text-nowrap">', $s);
    $s = preg_replace('#<(td|th) align="right">#', '<$1 class="text-end">', $s);
    $s = preg_replace('#<(td|th) align="center">#', '<$1 class="text-center">', $s);
    $s = preg_replace('#<input type="(text|number|date|password)"#', '<input class="form-control" type="$1"', $s);
    $s = preg_replace('# size="\d+"#', '', $s);
    $s = preg_replace('#<select\b#', '<select class="form-select"', $s);
    $s = str_replace('<button type="submit">Delete</button>', '<button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>', $s);
    $s = str_replace('<button type="submit">', '<button type="submit" class="btn btn-primary">', $s);
    $s = preg_replace('#<a href="([^"]*)">(View)</a>#', '<a class="btn btn-sm btn-outline-success" href="$1">$2</a>', $s);
    $s = preg_replace('#<a href="([^"]*)">(Edit)</a>#', '<a class="btn btn-sm btn-warning" href="$1">$2</a>', $s);
    $s = preg_replace('#<a href="([^"]*)">(Cancel|Clear|Reset|Back to list|&laquo; All reports)</a>#', '<a class="btn btn-outline-secondary" href="$1">$2</a>', $s);
    $s = preg_replace('#<a href="([^"]*)">(\+ Add [^<]*)</a>#', '<a class="btn btn-primary" href="$1">$2</a>', $s);
    $s = preg_replace('#</a> \|\n(\s*<a class="btn btn-outline-secondary" href="[^"]*">Back to list</a>)#', "</a>\n$1", $s);

    $s = preg_replace_callback('#<p>\n(\s*)<label for="([^"]+)">(.*?)</label><br>\n(.*?)\n\s*</p>#s',
        fn($m) => "<div class=\"mb-3\">\n" . $m[1] . '<label for="' . $m[2] . '" class="form-label">' . $m[3] . "</label>\n" . $m[4] . "\n</div>", $s);

    $s = str_replace('<form method="get">', '<form method="get" class="filter-bar">', $s);
    $s = str_replace('<form method="post">', '<form method="post" class="form-card">', $s);
    $s = str_replace('<form method="post" autocomplete="off">', '<form method="post" autocomplete="off" class="form-card">', $s);
    return $s;
}

function convert_extras(string $name, string $s): string
{
    if ($name === 'auth.php') {
        $s = preg_replace("#[ \t]*\\\$color = \\\$flash\['type'\] === 'success' \? 'green' : 'red';\n#",
            "    \$class = \$flash['type'] === 'success' ? 'success' : 'danger';\n", $s);
        $s = str_replace("return '<p style=\"color:' . \$color . '\"><strong>' . e(\$flash['message']) . '</strong></p>';",
            "return '<div class=\"alert alert-' . \$class . '\">' . e(\$flash['message']) . '</div>';", $s);
    }
    if ($name === 'users.php') {
        $s = str_replace('<?= count($users) ?> user(s) shown</p>', '<span class="ms-2 text-muted"><?= count($users) ?> user(s) shown</span></p>', $s);
        $s = str_replace('+ Add User</a> | <span', '+ Add User</a> <span', $s);
    }
    return $s;
}

foreach (glob(__DIR__ . '/*.php') as $path) {
    $name = basename($path);
    if (in_array($name, $skip, true)) {
        continue;
    }
    $old = file_get_contents($path);
    if (strpos($old, 'table-head') !== false || strpos($old, 'form-card') !== false || strpos($old, 'filter-bar') !== false
        || strpos($old, 'alert alert-') !== false) {
        echo "skipped (already converted): $name\n";
        continue;
    }
    $new = convert_extras($name, convert_page($old));
    if ($new !== $old) {
        file_put_contents($path, $new);
        echo "converted: $name\n";
    }
}