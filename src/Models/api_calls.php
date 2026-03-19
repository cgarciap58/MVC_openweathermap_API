<?php

declare(strict_types=1);

class OpenWeatherApiClient
{
    private const GEO_API_URL = 'http://api.openweathermap.org/geo/1.0/direct';
    private const CURRENT_API_URL = 'https://api.openweathermap.org/data/2.5/weather';
    private const FORECAST_API_URL = 'https://api.openweathermap.org/data/2.5/forecast';
    private const DEFAULT_COUNTRY = 'ES';
    private const DEFAULT_LIMIT = 1;
    private const HOURLY_FORECAST_BLOCKS = 8;
    private const WEEKLY_FORECAST_DAYS = 7;
    private const RESPONSE_LANGUAGE = 'es';

    private string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $resolvedApiKey = $apiKey ?: getenv('OPENWEATHER_API_KEY');

        if (!is_string($resolvedApiKey) || trim($resolvedApiKey) === '') {
            throw new RuntimeException('Falta la API key de OpenWeather. Define la variable de entorno OPENWEATHER_API_KEY.');
        }

        $this->apiKey = trim($resolvedApiKey);
    }

    public function geocodeCity(string $city, string $country = self::DEFAULT_COUNTRY, int $limit = self::DEFAULT_LIMIT): array
    {
        $normalizedCity = trim($city);
        if ($normalizedCity === '') {
            throw new InvalidArgumentException('La ciudad no puede estar vacía.');
        }

        if ($limit < 1) {
            throw new InvalidArgumentException('El límite debe ser mayor o igual que 1.');
        }

        $query = $normalizedCity;
        $normalizedCountry = trim($country);
        if ($normalizedCountry !== '') {
            $query .= ',' . $normalizedCountry;
        }

        $response = $this->requestJson(self::GEO_API_URL . '?' . http_build_query([
            'q' => $query,
            'limit' => $limit,
            'appid' => $this->apiKey,
        ]));

        if (!isset($response[0]) || !is_array($response[0])) {
            throw new RuntimeException('No se encontró ninguna ubicación para la ciudad indicada.');
        }

        return $this->normalizeLocation($response[0]);
    }

    public function fetchCurrentWeather(float $lat, float $lon): array
    {
        $response = $this->requestJson(self::CURRENT_API_URL . '?' . http_build_query([
            'lat' => $lat,
            'lon' => $lon,
            'appid' => $this->apiKey,
            'units' => 'metric',
            'lang' => self::RESPONSE_LANGUAGE,
        ]));

        return $this->normalizeCurrentWeather($response);
    }

    public function fetchForecast(float $lat, float $lon): array
    {
        $response = $this->requestJson(self::FORECAST_API_URL . '?' . http_build_query([
            'lat' => $lat,
            'lon' => $lon,
            'appid' => $this->apiKey,
            'units' => 'metric',
            'lang' => self::RESPONSE_LANGUAGE,

        ]));

        if (!isset($response['list']) || !is_array($response['list']) || $response['list'] === []) {
            throw new RuntimeException('La respuesta de previsión no contiene datos de forecast válidos.');
        }

        $normalizedItems = array_map([$this, 'normalizeForecastItem'], $response['list']);

        return [
            'forecast' => $normalizedItems,
            'next_24_hours' => $this->extractNext24Hours($normalizedItems),
            'weekly' => $this->buildWeeklyForecast($response['list']),
        ];
    }

    private function requestJson(string $url): array
    {
        $context = stream_context_create([
            'http' => [
                'ignore_errors' => true,
                'timeout' => 10,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            $error = error_get_last();
            $detail = $error['message'] ?? 'Error de red desconocido.';
            throw new RuntimeException('No se pudo completar la petición HTTP a OpenWeather: ' . $detail);
        }

        $statusCode = $this->extractStatusCode($http_response_header ?? []);
        if ($statusCode !== null && $statusCode >= 400) {
            throw new RuntimeException('OpenWeather devolvió un error HTTP ' . $statusCode . '.');
        }

        if (trim($response) === '') {
            throw new RuntimeException('OpenWeather devolvió una respuesta vacía.');
        }

        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decodedResponse)) {
            throw new RuntimeException('No se pudo decodificar el JSON de OpenWeather: ' . json_last_error_msg());
        }

        if (isset($decodedResponse['cod']) && (string) $decodedResponse['cod'] !== '200') {
            $message = isset($decodedResponse['message']) ? (string) $decodedResponse['message'] : 'Respuesta de error sin detalle.';
            throw new RuntimeException('OpenWeather respondió con error: ' . $message);
        }

        return $decodedResponse;
    }

    private function normalizeLocation(array $location): array
    {
        foreach (['name', 'country', 'lat', 'lon'] as $requiredField) {
            if (!array_key_exists($requiredField, $location)) {
                throw new RuntimeException('La respuesta de geocodificación no contiene el campo obligatorio: ' . $requiredField);
            }
        }

        return [
            'city' => (string) $location['name'],
            'country' => (string) $location['country'],
            'state' => isset($location['state']) ? (string) $location['state'] : null,
            'lat' => (float) $location['lat'],
            'lon' => (float) $location['lon'],
        ];
    }

    private function normalizeCurrentWeather(array $response): array
    {
        if (!isset($response['main']) || !is_array($response['main'])) {
            throw new RuntimeException('La respuesta actual no contiene el bloque main.');
        }

        if (!isset($response['weather'][0]) || !is_array($response['weather'][0])) {
            throw new RuntimeException('La respuesta actual no contiene información descriptiva del tiempo.');
        }

        foreach (['temp', 'humidity', 'pressure'] as $requiredMainField) {
            if (!array_key_exists($requiredMainField, $response['main'])) {
                throw new RuntimeException('La respuesta actual no contiene el campo main.' . $requiredMainField);
            }
        }

        if (!isset($response['dt'])) {
            throw new RuntimeException('La respuesta actual no contiene la fecha de observación.');
        }

        return [
            'temperature' => (float) $response['main']['temp'],
            'feels_like' => isset($response['main']['feels_like']) ? (float) $response['main']['feels_like'] : (float) $response['main']['temp'],
            'description' => (string) ($response['weather'][0]['description'] ?? ''),
            'icon' => (string) ($response['weather'][0]['icon'] ?? ''),
            'humidity' => (int) $response['main']['humidity'],
            'pressure' => (int) $response['main']['pressure'],
            'wind_speed' => isset($response['wind']['speed']) ? (float) $response['wind']['speed'] : 0.0,
            'observed_at' => gmdate('Y-m-d H:i:s', (int) $response['dt']),
        ];
    }

    private function normalizeForecastItem(array $entry): array
    {
        if (!isset($entry['dt'])) {
            throw new RuntimeException('La previsión contiene un bloque sin timestamp.');
        }

        if (!isset($entry['main']) || !is_array($entry['main']) || !array_key_exists('temp', $entry['main'])) {
            throw new RuntimeException('La previsión contiene un bloque sin temperatura.');
        }

        if (!isset($entry['weather'][0]) || !is_array($entry['weather'][0])) {
            throw new RuntimeException('La previsión contiene un bloque sin descripción meteorológica.');
        }

        return [
            'forecast_at' => gmdate('Y-m-d H:i:s', (int) $entry['dt']),
            'temperature' => (float) $entry['main']['temp'],
            'description' => (string) ($entry['weather'][0]['description'] ?? ''),
            'icon' => (string) ($entry['weather'][0]['icon'] ?? ''),
            'humidity' => isset($entry['main']['humidity']) ? (int) $entry['main']['humidity'] : 0,
            'wind_speed' => isset($entry['wind']['speed']) ? (float) $entry['wind']['speed'] : 0.0,        ];
    }

    private function extractNext24Hours(array $forecastItems): array
    {
        return array_slice($forecastItems, 0, self::HOURLY_FORECAST_BLOCKS);
    }

    private function buildWeeklyForecast(array $forecastEntries): array
    {
        $groupedByDate = [];

        foreach ($forecastEntries as $entry) {
            if (!isset($entry['dt'])) {
                throw new RuntimeException('La previsión contiene un bloque sin fecha para el resumen semanal.');
            }

            $forecastDate = gmdate('Y-m-d', (int) $entry['dt']);
            $description = (string) ($entry['weather'][0]['description'] ?? '');
            $icon = (string) ($entry['weather'][0]['icon'] ?? '');
            $tempMin = isset($entry['main']['temp_min']) ? (float) $entry['main']['temp_min'] : (float) ($entry['main']['temp'] ?? 0);
            $tempMax = isset($entry['main']['temp_max']) ? (float) $entry['main']['temp_max'] : (float) ($entry['main']['temp'] ?? 0);
            $middayDiff = abs((((int) $entry['dt']) % 86400) - 43200);

            if (!isset($groupedByDate[$forecastDate])) {
                $groupedByDate[$forecastDate] = [
                    'forecast_date' => $forecastDate,
                    'temp_min' => $tempMin,
                    'temp_max' => $tempMax,
                    'description' => $description,
                    'icon' => $icon,
                    'best_diff' => $middayDiff,
                ];

                continue;
            }

            $groupedByDate[$forecastDate]['temp_min'] = min($groupedByDate[$forecastDate]['temp_min'], $tempMin);
            $groupedByDate[$forecastDate]['temp_max'] = max($groupedByDate[$forecastDate]['temp_max'], $tempMax);

            if ($middayDiff < $groupedByDate[$forecastDate]['best_diff']) {
                $groupedByDate[$forecastDate]['description'] = $description;
                $groupedByDate[$forecastDate]['icon'] = $icon;
                $groupedByDate[$forecastDate]['best_diff'] = $middayDiff;
            }
        }

        $weeklyForecast = [];
        foreach (array_slice(array_values($groupedByDate), 0, self::WEEKLY_FORECAST_DAYS) as $day) {
            $weeklyForecast[] = [
                'forecast_date' => $day['forecast_date'],
                'temp_min' => $day['temp_min'],
                'temp_max' => $day['temp_max'],
                'description' => $day['description'],
                'icon' => $day['icon'],
            ];
        }

        return $weeklyForecast;
    }

    private function extractStatusCode(array $headers): ?int
    {
        foreach ($headers as $header) {
            if (preg_match('/HTTP\/\S+\s+(\d{3})/', $header, $matches) === 1) {
                return (int) $matches[1];
            }
        }

        return null;
    }
}
?>