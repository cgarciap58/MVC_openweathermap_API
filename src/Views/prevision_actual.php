<?php
$location = is_array($data['location'] ?? null) ? $data['location'] : [];
$series = is_array($data['series'] ?? null) ? $data['series'] : [];
$summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
$lastUpdated = $data['last_updated'] ?? null;
$current = $series[0] ?? [];
$locationName = trim(implode(', ', array_filter([
    $location['city'] ?? null,
    $location['state'] ?? null,
    $location['country_code'] ?? $location['country'] ?? null,
])));
?>
<section class="d-flex justify-content-between align-items-start mb-4 gap-3 flex-wrap">
    <div>
        <h1 class="h2 mb-1"><?= htmlspecialchars((string) ($data['title'] ?? 'Tiempo actual'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-muted mb-0"><?= htmlspecialchars($locationName, ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <a href="index.php" class="btn btn-outline-secondary">Nueva búsqueda</a>
</section>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <div class="row g-4 align-items-center">
            <div class="col-md-4">
                <p class="text-muted mb-1">Temperatura</p>
                <p class="display-5 mb-0"><?= htmlspecialchars((string) ($summary['temperature'] ?? '—'), ENT_QUOTES, 'UTF-8') ?> °C</p>
            </div>
            <div class="col-md-8">
                <div class="row g-3">
                    <div class="col-sm-6 col-lg-4">
                        <div class="border rounded p-3 h-100 bg-body-tertiary">
                            <p class="text-muted mb-1">Descripción</p>
                            <strong><?= htmlspecialchars((string) ($summary['description'] ?? 'Sin datos'), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="border rounded p-3 h-100 bg-body-tertiary">
                            <p class="text-muted mb-1">Humedad</p>
                            <strong><?= htmlspecialchars((string) ($summary['humidity'] ?? '—'), ENT_QUOTES, 'UTF-8') ?> %</strong>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="border rounded p-3 h-100 bg-body-tertiary">
                            <p class="text-muted mb-1">Presión</p>
                            <strong><?= htmlspecialchars((string) ($summary['pressure'] ?? '—'), ENT_QUOTES, 'UTF-8') ?> hPa</strong>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="border rounded p-3 h-100 bg-body-tertiary">
                            <p class="text-muted mb-1">Viento</p>
                            <strong><?= htmlspecialchars((string) ($summary['wind_speed'] ?? '—'), ENT_QUOTES, 'UTF-8') ?> m/s</strong>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="border rounded p-3 h-100 bg-body-tertiary">
                            <p class="text-muted mb-1">Observado</p>
                            <strong><?= htmlspecialchars((string) ($current['observed_at'] ?? 'Sin datos'), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="border rounded p-3 h-100 bg-body-tertiary">
                            <p class="text-muted mb-1">Última actualización</p>
                            <strong><?= htmlspecialchars((string) ($lastUpdated ?? 'Sin datos'), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>