<?php

declare(strict_types=1);

require_once __DIR__ . '/../Views/view.php';
require_once __DIR__ . '/../Models/dao_weather.php';
require_once __DIR__ . '/../Helpers/pChart.php';

class WeatherController
{
    private const DEFAULT_VIEW_TYPE = 'current';
    private const APP_NAME = 'App Meteo César';

    private const VIEW_MAP = [
        'current' => 'prevision_actual',
        '24h' => 'prevision_por_horas',
        'weekly' => 'prevision_semana_actual',
    ];

    public function handleRequest(array $request): void
    {
        if (($request['action'] ?? '') === 'history') {
            $this->showHistoryView();
            return;
        }

        $city = trim((string) ($request['city'] ?? ''));
        $type = trim((string) ($request['view'] ?? $request['type'] ?? self::DEFAULT_VIEW_TYPE));
        $hasSubmittedForm = array_key_exists('city', $request) || array_key_exists('view', $request) || array_key_exists('type', $request);

        // Validamos si la petición es inicial o si el formulario ya se ha intentado enviar.
        if (!$hasSubmittedForm) {
            $this->showSearchView(null, $type);
            return;
        }

        // Si el usuario no escribe nada, devolvemos el formulario con un aviso ligado al campo.
        if ($city === '') {
            $this->showSearchView(null, $type, $city, 'Escribe el nombre de la ciudad.');
            return;
        }

        // La ciudad solo puede contener letras y espacios para aceptar nombres compuestos.
        if (!$this->isValidCityInput($city)) {
            $this->showSearchView(null, $type, $city, 'La ciudad solo puede contener caracteres alfabéticos.');
            return;
        }

        if (!isset(self::VIEW_MAP[$type])) {
            $this->showSearchView('El tipo de consulta no es válido.', self::DEFAULT_VIEW_TYPE, $city);
            return;
        }

        try {
            $daoWeather = new DAOWeather();
            $payload = $this->buildViewData($daoWeather, $city, $type);
            $daoWeather->registerSearchHistory($city, $type, $payload['location']);
            View::show(self::VIEW_MAP[$type], $payload);
        } catch (Throwable $exception) {
            $this->showSearchView($exception->getMessage(), $type, $city);
        }
    }

    public function showHistoryView(): void
    {
        $payload = [
            'title' => 'Historial de consultas',
            'browser_title' => $this->buildBrowserTitle('Historial de consultas'),
            'is_history_view' => true,
            'history' => [],
        ];

        try {
            $daoWeather = new DAOWeather();
            $payload['history'] = $daoWeather->getRecentSearchHistory();
        } catch (Throwable $exception) {
            $payload['error'] = $exception->getMessage();
        }

        View::show('history', $payload);
    }

    private function showSearchView(?string $error = null, string $selectedType = self::DEFAULT_VIEW_TYPE, string $city = '', ?string $cityError = null): void
    {
        View::show('weather_search', [
            'title' => 'Buscar ciudad',
            'browser_title' => $this->buildBrowserTitle('Buscar ciudad'),            
            'error' => $error,
            'is_history_view' => false,
            'selected_type' => isset(self::VIEW_MAP[$selectedType]) ? $selectedType : self::DEFAULT_VIEW_TYPE,
            'city' => $city,
            'city_error' => $cityError,
            'show_welcome_background' => true,
        ]);
    }

    private function isValidCityInput(string $city): bool
    {
        // Usamos \p{L} para aceptar letras Unicode, incluidas vocales acentuadas y la ñ.
        return preg_match('/^[\p{L}\s]+$/u', $city) === 1;
    }

    private function buildViewData(DAOWeather $daoWeather, string $city, string $type): array
    {
        // Seleccionamos los datos y la vista del tipo de consulta solicitado
        $location = $daoWeather->getLocationByCity($city);

        if ($location === null) {
            throw new RuntimeException('No se ha encontrado la ciudad indicada.');
        }

        $chartPayload = null;
        $usingPlaceholderChart = false;

        switch ($type) {
            case '24h':
                $series = $daoWeather->getNext24HoursByCity($city);
                $summary = [
                    'label' => 'Próximas 24 horas',
                    'count' => count($series),
                ];
                $pageTitle = 'Previsión próximas 24 horas en ' . $location['city'];
                break;
            case 'weekly':
                $series = $daoWeather->getWeeklyForecastByCity($city);
                $chartPayload = $this->buildWeeklyChartPayload($series);
                $usingPlaceholderChart = $chartPayload['is_placeholder'] ?? false;
                $summary = [
                    'label' => 'Próximos 7 días',
                    'count' => count($series),
                ];
                $pageTitle = 'Resumen semanal en ' . $location['city'];
                break;
            case 'current':
            default:
                $current = $daoWeather->getCurrentWeatherByCity($city);
                $series = [$current];
                $summary = [
                    'temperature' => $current['temperature'] ?? null,
                    'description' => $current['description'] ?? null,
                    'humidity' => $current['humidity'] ?? null,
                    'pressure' => $current['pressure'] ?? null,
                    'wind_speed' => $current['wind_speed'] ?? null,
                ];
                $pageTitle = 'Tiempo actual en ' . $location['city'];
                break;
        }

        $lastUpdated = $this->resolveLastUpdated($series);

        return [
            'title' => $pageTitle,
            'browser_title' => $this->buildBrowserTitle($pageTitle),
            'is_history_view' => false,
            'type' => $type,
            'city' => $city,
            'location' => $location,
            'series' => $series,
            'summary' => $summary,
            'last_updated' => $lastUpdated,
            'chart_path' => $type === 'weekly' ? $this->renderWeeklyChart($location, $chartPayload) : null,
            'chart_is_placeholder' => $usingPlaceholderChart,
        ];
    }

    private function buildWeeklyChartPayload(array $series): array
    {
        $labels = [];
        $tempMin = [];
        $tempMax = [];

        foreach ($series as $entry) {
            if (!isset($entry['forecast_date'], $entry['temp_min'], $entry['temp_max'])) {
                continue;
            }

            $labels[] = (string) $entry['forecast_date'];
            $tempMin[] = (float) $entry['temp_min'];
            $tempMax[] = (float) $entry['temp_max'];
        }

        if ($labels === [] || $tempMin === [] || $tempMax === []) {
            return [
                'labels' => ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
                'temp_min' => [9.0, 10.0, 8.0, 11.0, 12.0, 10.0, 9.0],
                'temp_max' => [16.0, 17.0, 15.0, 18.0, 20.0, 19.0, 17.0],
                'is_placeholder' => true,
            ];
        }

        return [
            'labels' => $labels,
            'temp_min' => $tempMin,
            'temp_max' => $tempMax,
            'is_placeholder' => false,
        ];
    }

    private function renderWeeklyChart(array $location, ?array $chartPayload): ?string
    {
        if ($chartPayload === null || ($chartPayload['labels'] ?? []) === []) {
            return null;
        }

        $citySlug = $this->slugify((string) ($location['city'] ?? 'ciudad'));
        $countrySlug = $this->slugify((string) ($location['country_code'] ?? $location['country'] ?? ''));
        $isPlaceholder = (bool) ($chartPayload['is_placeholder'] ?? false);

        return ChartHelper::render([
            'slug' => trim('weekly-' . $citySlug . '-' . $countrySlug . ($isPlaceholder ? '-example' : ''), '-'),
            'title' => $isPlaceholder ? 'Ejemplo de temperaturas semanales (placeholder)' : 'Temperaturas semanales',
            'type' => 'line',
            'dataset' => [
                'labels' => $chartPayload['labels'],
                'series' => [
                    [
                        'label' => 'Temp. mínima',
                        'values' => $chartPayload['temp_min'],
                    ],
                    [
                        'label' => 'Temp. máxima',
                        'values' => $chartPayload['temp_max'],
                    ],
                ],
            ],
        ]);
    }

    private function buildBrowserTitle(string $pageTitle): string
    {
        return $pageTitle . ' | ' . self::APP_NAME;
    }

    private function slugify(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9]+/', '-', $normalized) ?? '';
        return trim($normalized, '-') ?: 'weather';
    }

    private function resolveLastUpdated(array $series): ?string
    {
        // Se resuelve la fecha más representativa priorizando datos recuperados
        foreach ($series as $entry) {
            if (!empty($entry['fetched_at'])) {
                return (string) $entry['fetched_at'];
            }

            if (!empty($entry['observed_at'])) {
                return (string) $entry['observed_at'];
            }
        }
        
        return null;
    }
}
?>
