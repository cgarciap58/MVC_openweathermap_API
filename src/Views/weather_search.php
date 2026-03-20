<?php
$title = isset($data['title']) ? (string) $data['title'] : 'Buscar ciudad';
$error = isset($data['error']) ? (string) $data['error'] : null;
$selectedType = isset($data['selected_type']) ? (string) $data['selected_type'] : 'current';
$city = isset($data['city']) ? (string) $data['city'] : '';
$showWelcomeBackground = (bool) ($data['show_welcome_background'] ?? false);

$sectionStyle = $showWelcomeBackground
    ? "background-image: linear-gradient(rgba(248, 249, 250, 0.35), rgba(248, 249, 250, 0.35)), url('static/merida.jpg');"
        . " background-size: cover; background-position: center; border-radius: 1rem; padding: 3rem 1rem;"
    : '';
?>

<section class="row justify-content-center"<?= $sectionStyle !== '' ? ' style="' . htmlspecialchars($sectionStyle, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
<div class="col-lg-8">
        <?php
        $cardStyle = $showWelcomeBackground
        ? "background-color: rgba(255, 255, 255, 0.88); backdrop-filter: blur(2px); min-height: 28rem;"
        : '';
        ?>
        <div class="card shadow-sm border-0 overflow-hidden"<?= $cardStyle !== '' ? ' style="' . htmlspecialchars($cardStyle, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>        
            <div class="card-body p-4">
                <h1 class="h3 mb-3"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="text-muted">Consulta el tiempo actual, la previsión de las próximas 24 horas o el resumen semanal.</p>

                <?php if ($error !== null && $error !== ''): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form action="index.php" method="get" class="row g-3">
                    <div class="col-12">
                        <label for="city" class="form-label">Ciudad</label>
                        <input
                            type="text"
                            class="form-control"
                            id="city"
                            name="city"
                            value="<?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Ej. Madrid"
                            required
                        >
                    </div>
                    <div class="col-12">
                        <label for="view" class="form-label">Tipo de consulta</label>
                        <select class="form-select" id="view" name="view">
                            <?php
                            $options = [
                                'current' => 'Tiempo actual',
                                '24h' => 'Próximas 24 horas',
                                'weekly' => 'Resumen semanal',
                            ];
                            foreach ($options as $value => $label):
                            ?>
                                <option value="<?= $value ?>" <?= $selectedType === $value ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Consultar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
