<?php

include_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/api_calls.php';

class DAOWeather {
    private const CURRENT_TTL_MINUTES = 15;
    private const HOURLY_TTL_MINUTES = 60;
    private const WEEKLY_TTL_MINUTES = 360;
    private const SEARCH_HISTORY_LIMIT = 20;

    private $con;
    private OpenWeatherApiClient $apiClient;

    public function __construct() {
        $this->con = Database::start_con();
        $apiKey = getenv('OPENWEATHER_API_KEY');

        if (!is_string($apiKey) || trim($apiKey) === '') {
            throw new RuntimeException('Falta configurar OPENWEATHER_API_KEY. Define la variable de entorno con una API key válida de OpenWeather.');
        }

        $this->apiClient = new OpenWeatherApiClient($apiKey);
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

        $currentWeather = $this->fetchCurrentWeatherFromApi((float) $location['lat'], (float) $location['lon']);
        return $this->saveCurrentWeather((int) $location['id'], $currentWeather);
    }

    public function getNext24HoursByCity(string $city): array {
        $location = $this->requireLocation($city);
        $cachedForecast = $this->findHourlyForecast($location['id']);

        if (!empty($cachedForecast)) {
            return $cachedForecast;
        }

        $forecastData = $this->fetchForecastFromApi((float) $location['lat'], (float) $location['lon']);
        return $this->saveHourlyForecast((int) $location['id'], $forecastData['hourly']);
    }

    public function getWeeklyForecastByCity(string $city): array {
        $location = $this->requireLocation($city);
        $cachedForecast = $this->findWeeklyForecast($location['id']);

        if (!empty($cachedForecast)) {
            return $cachedForecast;
        }

        $forecastData = $this->fetchForecastFromApi((float) $location['lat'], (float) $location['lon']);
        return $this->saveWeeklyForecast((int) $location['id'], $forecastData['daily']);
    }

    public function registerSearchHistory(string $cityQuery, string $viewType, array $location): void {
        $normalizedCityQuery = trim($cityQuery);
        $resolvedCity = $this->buildResolvedCityLabel($location);

        if ($normalizedCityQuery === '' || $resolvedCity === '') {
            return;
        }

        $sql = 'INSERT INTO weather_search_history (city_query, view_type, resolved_city, searched_at)
                VALUES (:city_query, :view_type, :resolved_city, UTC_TIMESTAMP())';

        $stmt = $this->con->prepare($sql);
        $stmt->execute([
            'city_query' => $normalizedCityQuery,
            'view_type' => $viewType,
            'resolved_city' => $resolvedCity,
        ]);
    }

    public function getRecentSearchHistory(int $limit = self::SEARCH_HISTORY_LIMIT): array {
        $safeLimit = $limit > 0 ? min($limit, self::SEARCH_HISTORY_LIMIT) : self::SEARCH_HISTORY_LIMIT;
        $sql = sprintf(
            'SELECT id, city_query, view_type, resolved_city, searched_at
             FROM weather_search_history
             ORDER BY searched_at DESC, id DESC
             LIMIT %d',
            $safeLimit
        );

        $stmt = $this->con->query($sql);
        if ($stmt === false) {
            $errorInfo = $this->con->errorInfo();
            throw new RuntimeException('No se pudo recuperar el historial de consultas: ' . ($errorInfo[2] ?? 'error desconocido.'));
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function requireLocation(string $city): array {
        $location = $this->getLocationByCity($city);

        if ($location === null) {
            throw new RuntimeException('No se pudo resolver la ubicación para la ciudad indicada.');
        }

        return $location;
    }

    private function normalizeCity(string $city): string {
        // Normaliza la búsqueda eliminando espacios sobrantes y unificando mayúsculas/minúsculas para reutilizar la misma clave de caché.
        $trimmedCity = trim($city);
        $singleSpacedCity = preg_replace('/\s+/u', ' ', $trimmedCity);

        if ($singleSpacedCity === null) {
            return '';
        }
        

        return $this->lowercaseUtf8($singleSpacedCity);
    }

    private function lowercaseUtf8(string $value): string {
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($value, 'UTF-8');
        }

        $normalizedValue = strtr($value, [
            'Á' => 'á',
            'À' => 'à',
            'Â' => 'â',
            'Ä' => 'ä',
            'Ã' => 'ã',
            'Å' => 'å',
            'Æ' => 'æ',
            'Ç' => 'ç',
            'É' => 'é',
            'È' => 'è',
            'Ê' => 'ê',
            'Ë' => 'ë',
            'Í' => 'í',
            'Ì' => 'ì',
            'Î' => 'î',
            'Ï' => 'ï',
            'Ñ' => 'ñ',
            'Ó' => 'ó',
            'Ò' => 'ò',
            'Ô' => 'ô',
            'Ö' => 'ö',
            'Õ' => 'õ',
            'Ø' => 'ø',
            'Ú' => 'ú',
            'Ù' => 'ù',
            'Û' => 'û',
            'Ü' => 'ü',
            'Ý' => 'ý',
            'Ÿ' => 'ÿ',
        ]);

        return strtolower($normalizedValue);
    }

    private function buildResolvedCityLabel(array $location): string {
        $parts = array_filter([
            $location['city'] ?? null,
            $location['state'] ?? null,
            $location['country_code'] ?? $location['country'] ?? null,
        ], static fn ($value): bool => is_string($value) && trim($value) !== '');

        return implode(', ', $parts);
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
        // Usa la caché en base de datos mientras el registro actual siga dentro del TTL para evitar llamadas repetidas a OpenWeather.
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
        // Reutiliza el forecast horario persistido si aún no caducó para reducir latencia y consumo de API.
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
        // Devuelve la previsión diaria almacenada en BD si sigue vigente según el TTL configurado.
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
        // Refresca la ubicación desde OpenWeather solo cuando no existe una coincidencia normalizada en la base local.
        try {
            $location = $this->apiClient->geocodeCity($city, '');
        } catch (RuntimeException $exception) {
            if (str_contains($exception->getMessage(), 'No se encontró ninguna ubicación')) {
                return null;
            }

            throw $exception;
        }

        return [
            'city' => $location['city'],
            'country_code' => $location['country'],
            'state' => $location['state'],
            'lat' => $location['lat'],
            'lon' => $location['lon'],
        ];
    }

    private function fetchCurrentWeatherFromApi(float $lat, float $lon): array {
        // Fuerza un refresco desde OpenWeather cuando la observación actual no está disponible o expiró en la caché local.
        return $this->apiClient->fetchCurrentWeather($lat, $lon);
    }

    private function fetchForecastFromApi(float $lat, float $lon): array {
        // Recupera un forecast nuevo desde OpenWeather al vencer la caché horaria/semanal almacenada en BD.
        $forecast = $this->apiClient->fetchForecast($lat, $lon);
        
        return [
            'hourly' => $forecast['next_24_hours'] ?? [],
            'daily' => $forecast['weekly'] ?? [],
        ];
    }
}

?>
