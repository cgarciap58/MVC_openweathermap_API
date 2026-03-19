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
        <h1 class="h2 mb-1"><?= htmlspecialchars((string) ($data['title'] ?? 'Previsión por horas'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-muted mb-0"><?= htmlspecialchars($locationName, ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <a href="index.php" class="btn btn-outline-secondary">Nueva búsqueda</a>
</section>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body d-flex flex-wrap gap-4 justify-content-between">
        <div>
            <p class="text-muted mb-1">Resumen</p>
            <strong><?= htmlspecialchars((string) ($summary['label'] ?? 'Próximas horas'), ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div>
            <p class="text-muted mb-1">Registros</p>
            <strong><?= htmlspecialchars((string) ($summary['count'] ?? count($series)), ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div>
            <p class="text-muted mb-1">Última actualización</p>
            <strong><?= htmlspecialchars((string) ($lastUpdated ?? 'Sin datos'), ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Fecha y hora</th>
                        <th scope="col">Temperatura</th>
                        <th scope="col">Descripción</th>
                        <th scope="col">Humedad</th>
                        <th scope="col">Viento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($series === []): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">No hay datos horarios disponibles.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($series as $entry): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) ($entry['forecast_at'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($entry['temperature'] ?? '—'), ENT_QUOTES, 'UTF-8') ?> °C</td>
                                <td><?= htmlspecialchars((string) ($entry['description'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($entry['humidity'] ?? '—'), ENT_QUOTES, 'UTF-8') ?> %</td>
                                <td><?= htmlspecialchars((string) ($entry['wind_speed'] ?? '—'), ENT_QUOTES, 'UTF-8') ?> m/s</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>