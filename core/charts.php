<?php

const CHART_COLORS = ['#1b5e20', '#b8860b', '#558b2f', '#8d6e00', '#33691e', '#c9a227', '#2e7d32', '#7a5c00'];

function chart_nice_max(float $max): float
{
    if ($max <= 0) {
        return 1;
    }
    $power = 10 ** floor(log10($max));
    foreach ([1, 2, 2.5, 5, 10] as $step) {
        if ($step * $power >= $max) {
            return $step * $power;
        }
    }
    return 10 * $power;
}

function chart_short(string $text, int $width): string
{
    return mb_strimwidth($text, 0, $width, '...');
}

function chart_bars(array $labels, array $values, string $color, string $unit = ''): string
{
    if (!$labels || max($values) <= 0) {
        return '<p>No data yet.</p>';
    }
    $rowHeight = 28;
    $left = 150;
    $right = 80;
    $width = 400;
    $height = count($labels) * $rowHeight + 10;
    $barArea = $width - $left - $right;
    $max = (float)max($values);

    $svg = '<svg viewBox="0 0 ' . $width . ' ' . $height . '" style="width:100%;height:auto" role="img">';
    foreach ($labels as $i => $label) {
        $y = $i * $rowHeight + 5;
        $barWidth = $values[$i] / $max * $barArea;
        $text = number_format((float)$values[$i], 2) . $unit;
        $svg .= '<text x="' . ($left - 8) . '" y="' . ($y + 15) . '" text-anchor="end" font-size="12" fill="#333">'
              . e(chart_short((string)$label, 22)) . '</text>';
        $svg .= '<rect x="' . $left . '" y="' . $y . '" width="' . round($barWidth, 1) . '" height="20" rx="2" fill="' . $color . '">'
              . '<title>' . e($label . ': ' . $text) . '</title></rect>';
        $svg .= '<text x="' . round($left + $barWidth + 6, 1) . '" y="' . ($y + 15) . '" font-size="12" fill="#333">'
              . e($text) . '</text>';
    }
    return $svg . '</svg>';
}

function chart_columns(array $labels, array $values, string $color): string
{
    if (!$labels || max($values) <= 0) {
        return '<p>No data yet.</p>';
    }
    $width = 400;
    $height = 250;
    $left = 52;
    $bottom = 40;
    $top = 15;
    $plotWidth = $width - $left - 10;
    $plotHeight = $height - $top - $bottom;
    $niceMax = chart_nice_max((float)max($values));
    $slot = $plotWidth / count($labels);
    $barWidth = min(40, $slot * 0.6);

    $svg = '<svg viewBox="0 0 ' . $width . ' ' . $height . '" style="width:100%;height:auto" role="img">';
    for ($t = 0; $t <= 4; $t++) {
        $value = $niceMax * $t / 4;
        $y = $top + $plotHeight - ($plotHeight * $t / 4);
        $svg .= '<line x1="' . $left . '" y1="' . $y . '" x2="' . ($width - 10) . '" y2="' . $y . '" stroke="#ddd"/>';
        $svg .= '<text x="' . ($left - 6) . '" y="' . ($y + 4) . '" text-anchor="end" font-size="11" fill="#555">'
              . e(number_format($value)) . '</text>';
    }
    foreach ($labels as $i => $label) {
        $barHeight = $values[$i] / $niceMax * $plotHeight;
        $x = $left + $i * $slot + ($slot - $barWidth) / 2;
        $y = $top + $plotHeight - $barHeight;
        $svg .= '<rect x="' . round($x, 1) . '" y="' . round($y, 1) . '" width="' . round($barWidth, 1)
              . '" height="' . round($barHeight, 1) . '" rx="2" fill="' . $color . '">'
              . '<title>' . e($label . ': ' . number_format((float)$values[$i], 2) . ' kg') . '</title></rect>';
        $svg .= '<text x="' . round($x + $barWidth / 2, 1) . '" y="' . ($height - 20) . '" text-anchor="middle" font-size="11" fill="#333">'
              . e($label) . '</text>';
    }
    return $svg . '</svg>';
}

function chart_donut(array $labels, array $values): string
{
    $total = array_sum($values);
    if (!$labels || $total <= 0) {
        return '<p>No data yet.</p>';
    }
    $radius = 60;
    $circumference = 2 * M_PI * $radius;
    $offset = 0;

    $svg = '<svg viewBox="0 0 160 160" style="width:160px;height:160px" role="img">';
    foreach ($labels as $i => $label) {
        $length = $values[$i] / $total * $circumference;
        $color = CHART_COLORS[$i % count(CHART_COLORS)];
        $svg .= '<circle cx="80" cy="80" r="' . $radius . '" fill="none" stroke="' . $color . '" stroke-width="28"'
              . ' stroke-dasharray="' . round($length, 2) . ' ' . round($circumference - $length, 2) . '"'
              . ' stroke-dashoffset="' . round(-$offset, 2) . '" transform="rotate(-90 80 80)">'
              . '<title>' . e($label . ': ' . $values[$i]) . '</title></circle>';
        $offset += $length;
    }
    $svg .= '<text x="80" y="86" text-anchor="middle" font-size="20" font-weight="bold" fill="#333">' . e($total) . '</text></svg>';

    $legend = '<ul style="list-style:none;margin:0;padding:0">';
    foreach ($labels as $i => $label) {
        $color = CHART_COLORS[$i % count(CHART_COLORS)];
        $percent = round($values[$i] / $total * 100);
        $legend .= '<li><span style="display:inline-block;width:12px;height:12px;background:' . $color . ';margin-right:6px"></span>'
                 . e($label) . ': ' . e($values[$i]) . ' (' . $percent . '%)</li>';
    }
    $legend .= '</ul>';

    return '<div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">' . $svg . $legend . '</div>';
}