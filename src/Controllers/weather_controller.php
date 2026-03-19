<?php

declare(strict_types=1);

require_once __DIR__ . '/../Views/view.php';
require_once __DIR__ . '/../Models/dao_weather.php';

class WeatherController
{
    private const VIEW_MAP = [
        'current' => 'prevision_actual',
        '24h' => 'prevision_por_horas',
        'weekly' => 'prevision_semana_actual',
    ];

    public function handleRequest(array $request): void
    {
        $city = trim((string) ($request['city'] ?? ''));
        $type = trim((string) ($request['view'] ?? $request['type'] ?? 'current'));

                if ($city === '') {
            View::show('weather_search', [
                'title' => 'Buscar ciudad',
                'error' => 'Debes indicar una ciudad para consultar el tiempo.',
                'selected_type' => $type,
                'city' => '',
            ]);
            return;
        }

                if (!isset(self::VIEW_MAP[$type])) {
            View::show('weather_search', [
                'title' => 'Buscar ciudad',
                'error' => 'El tipo de consulta no es válido.',
                'selected_type' => 'current',
                'city' => $city,
            ]);
            return;
        }

                $daoWeather = new DAOWeather();

        try {
            $payload = $this->buildViewData($daoWeather, $city, $type);
            View::show(self::VIEW_MAP[$type], $payload);
        } catch (Throwable $exception) {
            View::show('weather_search', [
                'title' => 'Buscar ciudad',
                'error' => $exception->getMessage(),
                'selected_type' => $type,
                'city' => $city,
            ]);
        }
    }

        private function buildViewData(DAOWeather $daoWeather, string $city, string $type): array
    {
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
