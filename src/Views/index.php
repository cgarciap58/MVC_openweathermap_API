<?php

declare(strict_types=1);

require_once __DIR__ . '/../Controllers/weather_controller.php';

$hasQuery = isset($_GET['city']) || isset($_GET['view']) || isset($_GET['type']);

if ($hasQuery) {
    $controller = new WeatherController();
    $controller->handleRequest($_GET);
    return;
}

View::show('weather_search', [
    'title' => 'Buscar ciudad',
    'selected_type' => 'current',
    'city' => '',
]);