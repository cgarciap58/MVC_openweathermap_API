<?php
$browserTitle = isset($data['browser_title']) && is_string($data['browser_title'])
    ? $data['browser_title']
    : ((isset($data['title']) && is_string($data['title']) ? $data['title'] : 'Artemisa Meteo') . ' | Artemisa Meteo');
$isHistoryView = (bool) ($data['is_history_view'] ?? false);
$showWelcomeBackground = (bool) ($data['show_welcome_background'] ?? false);
$mainClasses = $showWelcomeBackground
    ? 'py-4 flex-grow-1 d-flex align-items-center'
    : 'container py-4 flex-grow-1';
$mainStyle = $showWelcomeBackground
    ? "background-image: linear-gradient(rgba(248, 249, 250, 0.35), rgba(248, 249, 250, 0.35)), url('static/merida.jpg');"
        . " background-size: cover; background-position: center; background-repeat: no-repeat;"
    : '';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($browserTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-lg bg-white border-bottom shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="index.php">Artemisa Meteo</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Mostrar navegación">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link<?= $isHistoryView ? '' : ' active' ?>" <?= $isHistoryView ? '' : 'aria-current="page"' ?> href="index.php">Buscador</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= $isHistoryView ? ' active' : '' ?>" <?= $isHistoryView ? 'aria-current="page"' : '' ?> href="index.php?action=history">Historial</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<main class="<?= htmlspecialchars($mainClasses, ENT_QUOTES, 'UTF-8') ?>"<?= $mainStyle !== '' ? ' style="' . htmlspecialchars($mainStyle, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
    <div class="container h-100">