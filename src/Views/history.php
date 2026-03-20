<?php
$history = is_array($data['history'] ?? null) ? $data['history'] : [];
$error = isset($data['error']) ? (string) $data['error'] : null;
$viewLabels = [
    'current' => 'Tiempo actual',
    '24h' => 'Próximas 24 horas',
    'weekly' => 'Resumen semanal',
];
?>
<section class="d-flex justify-content-between align-items-start mb-4 gap-3 flex-wrap">
    <div>
        <h1 class="h2 mb-1"><?= htmlspecialchars((string) ($data['title'] ?? 'Historial de consultas'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-muted mb-0">Últimas 20 consultas válidas realizadas desde la web.</p>
    </div>
    <a href="index.php" class="btn btn-outline-secondary">Nueva búsqueda</a>
</section>

<?php if ($error !== null && $error !== ''): ?>
    <div class="alert alert-danger" role="alert">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Ciudad buscada</th>
                        <th scope="col">Tipo de consulta</th>
                        <th scope="col">Ciudad resuelta</th>
                        <th scope="col">Fecha / hora</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($history === []): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">Todavía no hay consultas registradas.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($history as $entry): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) ($entry['id'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($entry['city_query'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($viewLabels[$entry['view_type'] ?? ''] ?? ($entry['view_type'] ?? '—')), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($entry['resolved_city'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($entry['searched_at'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
