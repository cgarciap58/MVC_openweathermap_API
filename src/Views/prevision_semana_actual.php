<?php
$location = is_array($data['location'] ?? null) ? $data['location'] : [];
$series = is_array($data['series'] ?? null) ? $data['series'] : [];
$summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
$lastUpdated = $data['last_updated'] ?? null;
$chartPath = $data['chart_path'] ?? null;
$chartIsPlaceholder = (bool) ($data['chart_is_placeholder'] ?? false);
$locationName = trim(implode(', ', array_filter([
    $location['city'] ?? null,
    $location['state'] ?? null,
    $location['country_code'] ?? $location['country'] ?? null,
])));

$weeklyChartPoints = [];
$weeklyChartLabels = [];
$weeklyChartMinValues = [];
$weeklyChartMaxValues = [];

foreach ($series as $entry) {
    if (!isset($entry['forecast_date'], $entry['temp_min'], $entry['temp_max'])) {
        continue;
    }

    $label = (string) $entry['forecast_date'];
    $min = (float) $entry['temp_min'];
    $max = (float) $entry['temp_max'];

    $weeklyChartPoints[] = [
        'label' => $label,
        'min' => $min,
        'max' => $max,
    ];
    $weeklyChartLabels[] = $label;
    $weeklyChartMinValues[] = $min;
    $weeklyChartMaxValues[] = $max;
}

$weeklyChartHasData = $weeklyChartPoints !== [];
$weeklyChartSvgWidth = 760;
$weeklyChartSvgHeight = 320;
$weeklyChartPaddingLeft = 56;
$weeklyChartPaddingRight = 24;
$weeklyChartPaddingTop = 24;
$weeklyChartPaddingBottom = 56;
$weeklyChartPlotWidth = $weeklyChartSvgWidth - $weeklyChartPaddingLeft - $weeklyChartPaddingRight;
$weeklyChartPlotHeight = $weeklyChartSvgHeight - $weeklyChartPaddingTop - $weeklyChartPaddingBottom;
$weeklyChartMinTemp = $weeklyChartHasData ? floor(min($weeklyChartMinValues)) : 0.0;
$weeklyChartMaxTemp = $weeklyChartHasData ? ceil(max($weeklyChartMaxValues)) : 1.0;

if ($weeklyChartHasData && $weeklyChartMinTemp === $weeklyChartMaxTemp) {
    $weeklyChartMaxTemp += 1;
}

$weeklyChartGridLines = 5;
$weeklyChartXAxisY = $weeklyChartSvgHeight - $weeklyChartPaddingBottom;
$weeklyChartCount = count($weeklyChartPoints);
$weeklyChartStepX = $weeklyChartCount > 1 ? $weeklyChartPlotWidth / ($weeklyChartCount - 1) : 0;
$weeklyChartScale = static function (float $value) use ($weeklyChartMinTemp, $weeklyChartMaxTemp, $weeklyChartPaddingTop, $weeklyChartPlotHeight): float {
    $range = max($weeklyChartMaxTemp - $weeklyChartMinTemp, 1);
    $normalized = ($value - $weeklyChartMinTemp) / $range;
    return $weeklyChartPaddingTop + ($weeklyChartPlotHeight - ($normalized * $weeklyChartPlotHeight));
};
$weeklyChartMinPath = [];
$weeklyChartMaxPath = [];
$weeklyChartPositions = [];

if ($weeklyChartHasData) {
    foreach ($weeklyChartPoints as $index => $point) {
        $x = $weeklyChartPaddingLeft + ($weeklyChartCount > 1 ? $weeklyChartStepX * $index : $weeklyChartPlotWidth / 2);
        $minY = $weeklyChartScale($point['min']);
        $maxY = $weeklyChartScale($point['max']);

        $weeklyChartPositions[] = [
            'x' => $x,
            'label' => $point['label'],
            'min' => $point['min'],
            'min_y' => $minY,
            'max' => $point['max'],
            'max_y' => $maxY,
        ];
        $weeklyChartMinPath[] = sprintf('%s%.2f %.2f', $index === 0 ? 'M ' : 'L ', $x, $minY);
        $weeklyChartMaxPath[] = sprintf('%s%.2f %.2f', $index === 0 ? 'M ' : 'L ', $x, $maxY);
    }
}
?>

<section class="d-flex justify-content-between align-items-start mb-4 gap-3 flex-wrap">
    <div>
        <h1 class="h2 mb-1"><?= htmlspecialchars((string) ($data['title'] ?? 'Resumen semanal'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-muted mb-0"><?= htmlspecialchars($locationName, ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <a href="index.php" class="btn btn-outline-secondary">Nueva búsqueda</a>
</section>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body d-flex flex-wrap gap-4 justify-content-between">
        <div>
            <p class="text-muted mb-1">Resumen</p>
            <strong><?= htmlspecialchars((string) ($summary['label'] ?? 'Próximos días'), ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div>
            <p class="text-muted mb-1">Días</p>
            <strong><?= htmlspecialchars((string) ($summary['count'] ?? count($series)), ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div>
            <p class="text-muted mb-1">Última actualización</p>
            <strong><?= htmlspecialchars((string) ($lastUpdated ?? 'Sin datos'), ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
    </div>
</div>


<div class="row g-3 mb-4">
    <?php if ($series === []): ?>
        <div class="col-12">
            <div class="alert alert-secondary mb-0">No hay datos semanales disponibles.</div>
        </div>
    <?php else: ?>
        <?php foreach ($series as $entry): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <p class="text-muted mb-1">Fecha</p>
                        <h2 class="h5"><?= htmlspecialchars((string) ($entry['forecast_date'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></h2>
                        <p class="mb-2"><?= htmlspecialchars((string) ($entry['description'] ?? 'Sin descripción'), ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="d-flex justify-content-between">
                            <span>Mín: <strong><?= htmlspecialchars((string) ($entry['temp_min'] ?? '—'), ENT_QUOTES, 'UTF-8') ?> °C</strong></span>
                            <span>Máx: <strong><?= htmlspecialchars((string) ($entry['temp_max'] ?? '—'), ENT_QUOTES, 'UTF-8') ?> °C</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Gráfica con SVG porque pChart no me funciona -->
<?php if ($weeklyChartHasData): ?> 
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">Evolución semanal de temperaturas</h2>
                    <p class="text-muted mb-0">Comparativa de temperaturas mínimas y máximas por día.</p>
                </div>
                <div class="d-flex flex-wrap gap-3 small">
                    <span class="d-inline-flex align-items-center gap-2"><span class="rounded-circle" style="display:inline-block;width:12px;height:12px;background:#0d6efd;"></span>Mínima</span>
                    <span class="d-inline-flex align-items-center gap-2"><span class="rounded-circle" style="display:inline-block;width:12px;height:12px;background:#dc3545;"></span>Máxima</span>
                </div>
            </div>

            <div class="table-responsive">
                <svg
                    viewBox="0 0 <?= $weeklyChartSvgWidth ?> <?= $weeklyChartSvgHeight ?>"
                    class="w-100"
                    role="img"
                    aria-label="Gráfica semanal con temperaturas mínimas y máximas"
                >
                    <rect x="0" y="0" width="<?= $weeklyChartSvgWidth ?>" height="<?= $weeklyChartSvgHeight ?>" fill="#ffffff"></rect>

                    <?php for ($line = 0; $line <= $weeklyChartGridLines; $line++): ?>
                        <?php
                        $y = $weeklyChartPaddingTop + (($weeklyChartPlotHeight / $weeklyChartGridLines) * $line);
                        $value = $weeklyChartMaxTemp - ((($weeklyChartMaxTemp - $weeklyChartMinTemp) / $weeklyChartGridLines) * $line);
                        ?>
                        <line
                            x1="<?= $weeklyChartPaddingLeft ?>"
                            y1="<?= number_format($y, 2, '.', '') ?>"
                            x2="<?= $weeklyChartSvgWidth - $weeklyChartPaddingRight ?>"
                            y2="<?= number_format($y, 2, '.', '') ?>"
                            stroke="#dee2e6"
                            stroke-width="1"
                        ></line>
                        <text
                            x="<?= $weeklyChartPaddingLeft - 10 ?>"
                            y="<?= number_format($y + 4, 2, '.', '') ?>"
                            text-anchor="end"
                            font-size="12"
                            fill="#6c757d"
                        ><?= htmlspecialchars((string) round($value, 1), ENT_QUOTES, 'UTF-8') ?>°</text>
                    <?php endfor; ?>

                    <line x1="<?= $weeklyChartPaddingLeft ?>" y1="<?= $weeklyChartPaddingTop ?>" x2="<?= $weeklyChartPaddingLeft ?>" y2="<?= $weeklyChartXAxisY ?>" stroke="#6c757d" stroke-width="1.5"></line>
                    <line x1="<?= $weeklyChartPaddingLeft ?>" y1="<?= $weeklyChartXAxisY ?>" x2="<?= $weeklyChartSvgWidth - $weeklyChartPaddingRight ?>" y2="<?= $weeklyChartXAxisY ?>" stroke="#6c757d" stroke-width="1.5"></line>

                    <?php foreach ($weeklyChartPositions as $position): ?>
                        <line
                            x1="<?= number_format($position['x'], 2, '.', '') ?>"
                            y1="<?= $weeklyChartPaddingTop ?>"
                            x2="<?= number_format($position['x'], 2, '.', '') ?>"
                            y2="<?= $weeklyChartXAxisY ?>"
                            stroke="#f1f3f5"
                            stroke-width="1"
                        ></line>
                    <?php endforeach; ?>

                    <path d="<?= htmlspecialchars(implode(' ', $weeklyChartMinPath), ENT_QUOTES, 'UTF-8') ?>" fill="none" stroke="#0d6efd" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path d="<?= htmlspecialchars(implode(' ', $weeklyChartMaxPath), ENT_QUOTES, 'UTF-8') ?>" fill="none" stroke="#dc3545" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>

                    <?php foreach ($weeklyChartPositions as $position): ?>
                        <circle cx="<?= number_format($position['x'], 2, '.', '') ?>" cy="<?= number_format($position['min_y'], 2, '.', '') ?>" r="4" fill="#0d6efd"></circle>
                        <circle cx="<?= number_format($position['x'], 2, '.', '') ?>" cy="<?= number_format($position['max_y'], 2, '.', '') ?>" r="4" fill="#dc3545"></circle>
                        <text x="<?= number_format($position['x'], 2, '.', '') ?>" y="<?= number_format($position['min_y'] - 10, 2, '.', '') ?>" text-anchor="middle" font-size="12" fill="#0d6efd"><?= htmlspecialchars((string) round($position['min'], 1), ENT_QUOTES, 'UTF-8') ?>°</text>
                        <text x="<?= number_format($position['x'], 2, '.', '') ?>" y="<?= number_format($position['max_y'] - 10, 2, '.', '') ?>" text-anchor="middle" font-size="12" fill="#dc3545"><?= htmlspecialchars((string) round($position['max'], 1), ENT_QUOTES, 'UTF-8') ?>°</text>
                        <text x="<?= number_format($position['x'], 2, '.', '') ?>" y="<?= $weeklyChartSvgHeight - 20 ?>" text-anchor="middle" font-size="12" fill="#495057"><?= htmlspecialchars($position['label'], ENT_QUOTES, 'UTF-8') ?></text>
                    <?php endforeach; ?>
                </svg>
            </div>

            <?php if (is_string($chartPath) && $chartPath !== ''): ?>
                <p class="text-muted small mt-3 mb-0">
                    También se ha generado la imagen del gráfico en servidor en
                    <code><?= htmlspecialchars($chartPath, ENT_QUOTES, 'UTF-8') ?></code>.
                    <?php if ($chartIsPlaceholder): ?>
                        Se trata de una imagen placeholder por falta de datos semanales persistidos.
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>