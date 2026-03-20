<?php

declare(strict_types=1);

require_once __DIR__ . '/../Controllers/weather_controller.php';

$controller = new WeatherController();
$request = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$hasHistoryRequest = isset($_GET['action']) && $_GET['action'] === 'history';
$hasSearchRequest = isset($request['city']) || isset($request['view']) || isset($request['type']);

if ($hasHistoryRequest || $hasSearchRequest) {
    $controller->handleRequest($hasHistoryRequest ? $_GET : $request);
    return;
}

View::show('weather_search', [
    'title' => 'Buscar ciudad',
    'browser_title' => 'Buscar ciudad | Artemisa Meteo',
    'is_history_view' => false,
    'selected_type' => 'current',
    'city' => '',
    'show_welcome_background' => true,
]);