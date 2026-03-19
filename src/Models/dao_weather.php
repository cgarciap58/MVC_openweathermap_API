<?php

include_once __DIR__ . '/../db/db.php';

class DAOWeather {
    private const CURRENT_TTL_MINUTES = 15;
    private const HOURLY_TTL_MINUTES = 60;
    private const WEEKLY_TTL_MINUTES = 360;
    private const GEO_API_URL = 'http://api.openweathermap.org/geo/1.0/direct';
    private const CURRENT_API_URL = 'https://api.openweathermap.org/data/2.5/weather';
    private const FORECAST_API_URL = 'https://api.openweathermap.org/data/2.5/forecast';
    private const DEFAULT_COUNTRY_CODE = null;
    private const DEFAULT_STATE = null;

    private $con;
    private $apiKey;

    public function __construct() {
        $this->con = Database::start_con();
        $this->apiKey = getenv('OPENWEATHER_API_KEY') ?: 'ac5d71bf15cc25c652db23b8bf627fd7';
    }

    // Recibe una ciudad y devuelve su ubicación
    public function getLocationByCity(string $city): ?array {
        $normalizedCity = $this->normalizeCity($city);
        if ($normalizedCity === '') {
            return null;
        }

        $location = $this->findLocationByNormalizedQuery($normalizedCity);
        if ($location !== null) {
            return $location;
        }

        $geocodedLocation = $this->fetchLocationFromApi($city);
        if ($geocodedLocation === null) {
            return null;
        }

        return $this->saveLocation($geocodedLocation, $normalizedCity);
    }

    public function getCurrentWeatherByCity(string $city): array {
        $location = $this->requireLocation($city);
        $cachedWeather = $this->findCurrentWeather($location['id']);

        if ($cachedWeather !== null) {
            return $cachedWeather;
        }

        $currentWeather = $this->fetchCurrentWeatherFromApi($location['lat'], $location['lon']);
        return $this->saveCurrentWeather((int) $location['id'], $currentWeather);
    }

    public function getNext24HoursByCity(string $city): array {
        $location = $this->requireLocation($city);
        $cachedForecast = $this->findHourlyForecast($location['id']);

        if (!empty($cachedForecast)) {
            return $cachedForecast;
        }

        $forecastData = $this->fetchForecastFromApi($location['lat'], $location['lon']);
        return $this->saveHourlyForecast((int) $location['id'], $forecastData['hourly']);
    }

    public function getWeeklyForecastByCity(string $city): array {
        $location = $this->requireLocation($city);
        $cachedForecast = $this->findWeeklyForecast($location['id']);

        if (!empty($cachedForecast)) {
            return $cachedForecast;
        }

        $forecastData = $this->fetchForecastFromApi($location['lat'], $location['lon']);
        return $this->saveWeeklyForecast((int) $location['id'], $forecastData['daily']);
    }

    private function requireLocation(string $city): array {
        $location = $this->getLocationByCity($city);

        if ($location === null) {
            throw new RuntimeException('No se pudo resolver la ubicación para la ciudad indicada.');
        }

        return $location;
    }

    private function normalizeCity(string $city): string {
        $trimmedCity = trim($city);
        $singleSpacedCity = preg_replace('/\s+/u', ' ', $trimmedCity);

        if ($singleSpacedCity === null) {
            return '';
        }
        

        return mb_strtolower($singleSpacedCity, 'UTF-8');
    }

    private function findLocationByNormalizedQuery(string $normalizedCity): ?array {
        $sql = 'SELECT id, city, country_code, state, lat, lon, normalized_query, created_at, updated_at
                FROM weather_locations
                WHERE normalized_query = :normalized_query
                LIMIT 1';

        $stmt = $this->con->prepare($sql);
        $stmt->execute(['normalized_query' => $normalizedCity]);
        $location = $stmt->fetch(PDO::FETCH_ASSOC);

        return $location ?: null;
    }

    private function findCurrentWeather(int $locationId): ?array {
        $sql = 'SELECT location_id, temperature, feels_like, humidity, pressure, description, icon, wind_speed, observed_at, fetched_at
                FROM weather_current
                WHERE location_id = :location_id
                  AND fetched_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL :ttl MINUTE)
                LIMIT 1';

        $stmt = $this->con->prepare($sql);
        $stmt->bindValue(':location_id', $locationId, PDO::PARAM_INT);
        $stmt->bindValue(':ttl', self::CURRENT_TTL_MINUTES, PDO::PARAM_INT);
        $stmt->execute();
        $weather = $stmt->fetch(PDO::FETCH_ASSOC);

        return $weather ?: null;
    }

    private function findHourlyForecast(int $locationId): array {
        $sql = 'SELECT location_id, forecast_at, temperature, description, icon, humidity, wind_speed, fetched_at
                FROM weather_hourly
                WHERE location_id = :location_id
                  AND fetched_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL :ttl MINUTE)
                ORDER BY forecast_at ASC';

        $stmt = $this->con->prepare($sql);
        $stmt->bindValue(':location_id', $locationId, PDO::PARAM_INT);
        $stmt->bindValue(':ttl', self::HOURLY_TTL_MINUTES, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function findWeeklyForecast(int $locationId): array {
        $sql = 'SELECT location_id, forecast_date, temp_min, temp_max, description, icon, fetched_at
                FROM weather_daily
                WHERE location_id = :location_id
                  AND fetched_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL :ttl MINUTE)
                ORDER BY forecast_date ASC';

        $stmt = $this->con->prepare($sql);
        $stmt->bindValue(':location_id', $locationId, PDO::PARAM_INT);
        $stmt->bindValue(':ttl', self::WEEKLY_TTL_MINUTES, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function saveLocation(array $location, string $normalizedCity): array {
        $sql = 'INSERT INTO weather_locations (city, country_code, state, lat, lon, normalized_query)
                VALUES (:city, :country_code, :state, :lat, :lon, :normalized_query)
                ON DUPLICATE KEY UPDATE
                    city = VALUES(city),
                    country_code = VALUES(country_code),
                    state = VALUES(state),
                    lat = VALUES(lat),
                    lon = VALUES(lon),
                    updated_at = CURRENT_TIMESTAMP';

        $stmt = $this->con->prepare($sql);
        $stmt->execute([
            'city' => $location['city'],
            'country_code' => $location['country_code'],
            'state' => $location['state'],
            'lat' => $location['lat'],
            'lon' => $location['lon'],
            'normalized_query' => $normalizedCity,
        ]);

        return $this->findLocationByNormalizedQuery($normalizedCity);
    }

    private function saveCurrentWeather(int $locationId, array $weather): array {
        $sql = 'INSERT INTO weather_current (
                    location_id, temperature, feels_like, humidity, pressure, description, icon, wind_speed, observed_at, fetched_at
                ) VALUES (
                    :location_id, :temperature, :feels_like, :humidity, :pressure, :description, :icon, :wind_speed, :observed_at, UTC_TIMESTAMP()
                )
                ON DUPLICATE KEY UPDATE
                    temperature = VALUES(temperature),
                    feels_like = VALUES(feels_like),
                    humidity = VALUES(humidity),
                    pressure = VALUES(pressure),
                    description = VALUES(description),
                    icon = VALUES(icon),
                    wind_speed = VALUES(wind_speed),
                    observed_at = VALUES(observed_at),
                    fetched_at = UTC_TIMESTAMP()';

        $stmt = $this->con->prepare($sql);
        $stmt->execute([
            'location_id' => $locationId,
            'temperature' => $weather['temperature'],
            'feels_like' => $weather['feels_like'],
            'humidity' => $weather['humidity'],
            'pressure' => $weather['pressure'],
            'description' => $weather['description'],
            'icon' => $weather['icon'],
            'wind_speed' => $weather['wind_speed'],
            'observed_at' => $weather['observed_at'],
        ]);

        return $this->findCurrentWeather($locationId) ?? [];
    }

    private function saveHourlyForecast(int $locationId, array $forecastEntries): array {
        $deleteSql = 'DELETE FROM weather_hourly WHERE location_id = :location_id';
        $deleteStmt = $this->con->prepare($deleteSql);
        $deleteStmt->execute(['location_id' => $locationId]);

        $insertSql = 'INSERT INTO weather_hourly (
                        location_id, forecast_at, temperature, description, icon, humidity, wind_speed, fetched_at
                      ) VALUES (
                        :location_id, :forecast_at, :temperature, :description, :icon, :humidity, :wind_speed, UTC_TIMESTAMP()
                      )';
        $insertStmt = $this->con->prepare($insertSql);

        foreach ($forecastEntries as $entry) {
            $insertStmt->execute([
                'location_id' => $locationId,
                'forecast_at' => $entry['forecast_at'],
                'temperature' => $entry['temperature'],
                'description' => $entry['description'],
                'icon' => $entry['icon'],
                'humidity' => $entry['humidity'],
                'wind_speed' => $entry['wind_speed'],
            ]);
        }

        return $this->findHourlyForecast($locationId);
    }

    private function saveWeeklyForecast(int $locationId, array $forecastEntries): array {
        $deleteSql = 'DELETE FROM weather_daily WHERE location_id = :location_id';
        $deleteStmt = $this->con->prepare($deleteSql);
        $deleteStmt->execute(['location_id' => $locationId]);

        $insertSql = 'INSERT INTO weather_daily (
                        location_id, forecast_date, temp_min, temp_max, description, icon, fetched_at
                      ) VALUES (
                        :location_id, :forecast_date, :temp_min, :temp_max, :description, :icon, UTC_TIMESTAMP()
                      )';
        $insertStmt = $this->con->prepare($insertSql);

        foreach ($forecastEntries as $entry) {
            $insertStmt->execute([
                'location_id' => $locationId,
                'forecast_date' => $entry['forecast_date'],
                'temp_min' => $entry['temp_min'],
                'temp_max' => $entry['temp_max'],
                'description' => $entry['description'],
                'icon' => $entry['icon'],
            ]);
        }

        return $this->findWeeklyForecast($locationId);
    }

    private function fetchLocationFromApi(string $city): ?array {
        $response = $this->requestJson(self::GEO_API_URL . '?' . http_build_query([
            'q' => trim($city),
            'limit' => 1,
            'appid' => $this->apiKey,
        ]));

        if (empty($response[0])) {
            return null;
        }

        return [
            'city' => $response[0]['name'] ?? trim($city),
            'country_code' => $response[0]['country'] ?? self::DEFAULT_COUNTRY_CODE,
            'state' => $response[0]['state'] ?? self::DEFAULT_STATE,
            'lat' => $response[0]['lat'],
            'lon' => $response[0]['lon'],
        ];
    }

    private function fetchCurrentWeatherFromApi($lat, $lon): array {
        $response = $this->requestJson(self::CURRENT_API_URL . '?' . http_build_query([
            'lat' => $lat,
            'lon' => $lon,
            'appid' => $this->apiKey,
            'units' => 'metric',
        ]));

        return [
            'temperature' => $response['main']['temp'],
            'feels_like' => $response['main']['feels_like'],
            'humidity' => $response['main']['humidity'],
            'pressure' => $response['main']['pressure'],
            'description' => $response['weather'][0]['description'],
            'icon' => $response['weather'][0]['icon'],
            'wind_speed' => $response['wind']['speed'] ?? 0,
            'observed_at' => gmdate('Y-m-d H:i:s', $response['dt']),
        ];
    }

    private function fetchForecastFromApi($lat, $lon): array {
        $response = $this->requestJson(self::FORECAST_API_URL . '?' . http_build_query([
            'lat' => $lat,
            'lon' => $lon,
            'appid' => $this->apiKey,
            'units' => 'metric',
        ]));

        $hourly = [];
        $dailyGrouped = [];

        foreach ($response['list'] as $index => $entry) {
            if ($index < 8) {
                $hourly[] = [
                    'forecast_at' => gmdate('Y-m-d H:i:s', $entry['dt']),
                    'temperature' => $entry['main']['temp'],
                    'description' => $entry['weather'][0]['description'],
                    'icon' => $entry['weather'][0]['icon'],
                    'humidity' => $entry['main']['humidity'],
                    'wind_speed' => $entry['wind']['speed'] ?? 0,
                ];
            }

            $forecastDate = gmdate('Y-m-d', $entry['dt']);
            if (!isset($dailyGrouped[$forecastDate])) {
                $dailyGrouped[$forecastDate] = [
                    'forecast_date' => $forecastDate,
                    'temp_min' => $entry['main']['temp_min'],
                    'temp_max' => $entry['main']['temp_max'],
                    'description' => $entry['weather'][0]['description'],
                    'icon' => $entry['weather'][0]['icon'],
                    'best_timestamp' => $entry['dt'],
                    'best_diff' => abs(($entry['dt'] % 86400) - 43200),
                ];
                continue;
            }

            $dailyGrouped[$forecastDate]['temp_min'] = min($dailyGrouped[$forecastDate]['temp_min'], $entry['main']['temp_min']);
            $dailyGrouped[$forecastDate]['temp_max'] = max($dailyGrouped[$forecastDate]['temp_max'], $entry['main']['temp_max']);

            $diffFromMidday = abs(($entry['dt'] % 86400) - 43200);
            if ($diffFromMidday < $dailyGrouped[$forecastDate]['best_diff']) {
                $dailyGrouped[$forecastDate]['description'] = $entry['weather'][0]['description'];
                $dailyGrouped[$forecastDate]['icon'] = $entry['weather'][0]['icon'];
                $dailyGrouped[$forecastDate]['best_timestamp'] = $entry['dt'];
                $dailyGrouped[$forecastDate]['best_diff'] = $diffFromMidday;
            }
        }

        $daily = [];
        foreach (array_slice(array_values($dailyGrouped), 0, 7) as $day) {
            $daily[] = [
                'forecast_date' => $day['forecast_date'],
                'temp_min' => $day['temp_min'],
                'temp_max' => $day['temp_max'],
                'description' => $day['description'],
                'icon' => $day['icon'],
            ];
        }

        return [
            'hourly' => $hourly,
            'daily' => $daily,
        ];
    }

    private function requestJson(string $url): array {
        $response = @file_get_contents($url);
        if ($response === false) {
            throw new RuntimeException('No se pudo obtener respuesta de la API meteorológica.');
        }

        $decodedResponse = json_decode($response, true);
        if (!is_array($decodedResponse)) {
            throw new RuntimeException('La API meteorológica devolvió una respuesta no válida.');
        }

        return $decodedResponse;
    }
}

?>
