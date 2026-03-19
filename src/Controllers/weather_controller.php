<?php

declare(strict_types=1);

require_once __DIR__ . '/../Views/view.php';
require_once __DIR__ . '/../Models/dao_weather.php';

class WeatherController
{
    private const DEFAULT_VIEW_TYPE = 'current';

    private const VIEW_MAP = [
        'current' => 'prevision_actual',
        '24h' => 'prevision_por_horas',
        'weekly' => 'prevision_semana_actual',
    ];

    public function handleRequest(array $request): void
    {
        $city = trim((string) ($request['city'] ?? ''));
        $type = trim((string) ($request['view'] ?? $request['type'] ?? self::DEFAULT_VIEW_TYPE));
        $hasSubmittedForm = array_key_exists('city', $request) || array_key_exists('view', $request) || array_key_exists('type', $request);

        // Validamos si la petición es inicial o si el formulario ya se ha intentado enviar.
        if (!$hasSubmittedForm) {
            $this->showSearchView(null, $type);
            return;
        }

        if ($city === '') {
            $this->showSearchView('Debes indicar una ciudad para consultar el tiempo.', $type);
            return;
        }

        if (!isset(self::VIEW_MAP[$type])) {
            $this->showSearchView('El tipo de consulta no es válido.', self::DEFAULT_VIEW_TYPE, $city);
            return;
        }

        $daoWeather = new DAOWeather();

        try {
            $payload = $this->buildViewData($daoWeather, $city, $type);
            View::show(self::VIEW_MAP[$type], $payload);
        } catch (Throwable $exception) {
            $this->showSearchView($exception->getMessage(), $type, $city);
        }
    }

    private function showSearchView(?string $error = null, string $selectedType = self::DEFAULT_VIEW_TYPE, string $city = ''): void
    {
        View::show('weather_search', [
            'title' => 'Buscar ciudad',
            'error' => $error,
            'selected_type' => isset(self::VIEW_MAP[$selectedType]) ? $selectedType : self::DEFAULT_VIEW_TYPE,
            'city' => $city,
        ]);
    }

    private function buildViewData(DAOWeather $daoWeather, string $city, string $type): array
    {
        // Seleccionamos los datos y la vista del tipo de consulta solicitado
        $location = $daoWeather->getLocationByCity($city);

        if ($location === null) {
            throw new RuntimeException('No se ha encontrado la ciudad indicada.');
        }

        switch ($type) {
            case '24h':
                $series = $daoWeather->getNext24HoursByCity($city);
                $summary = [
                    'label' => 'Próximas 24 horas',
                    'count' => count($series),
                ];
                break;
            case 'weekly':
                $series = $daoWeather->getWeeklyForecastByCity($city);
                $summary = [
                    'label' => 'Próximos 7 días',
                    'count' => count($series),
                ];
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
                break;
        }

        $lastUpdated = $this->resolveLastUpdated($series);

        return [
            'title' => 'Tiempo en ' . $location['city'],
            'type' => $type,
            'city' => $city,
            'location' => $location,
            'series' => $series,
            'summary' => $summary,
            'last_updated' => $lastUpdated,
        ];
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
