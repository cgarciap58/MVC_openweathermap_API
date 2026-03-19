<?php
$location = is_array($data['location'] ?? null) ? $data['location'] : [];
$series = is_array($data['series'] ?? null) ? $data['series'] : [];
$summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
$lastUpdated = $data['last_updated'] ?? null;
$locationName = trim(implode(', ', array_filter([
    $location['city'] ?? null,
    $location['state'] ?? null,
    $location['country_code'] ?? $location['country'] ?? null,
])));
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

<div class="row g-3">
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