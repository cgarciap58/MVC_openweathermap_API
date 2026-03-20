<?php

declare(strict_types=1);

require_once __DIR__ . '/../Controllers/weather_controller.php';

$controller = new WeatherController();
$hasHistoryRequest = isset($_GET['action']) && $_GET['action'] === 'history';
$hasQuery = isset($_GET['city']) || isset($_GET['view']) || isset($_GET['type']);

if ($hasHistoryRequest || $hasQuery) {
    $controller->handleRequest($_GET);
    return;
}

View::show('weather_search', [
    'title' => 'Buscar ciudad',
    'selected_type' => 'current',
    'city' => '',
]);