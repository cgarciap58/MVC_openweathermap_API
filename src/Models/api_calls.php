<?php


$city = "Mérida";
$state = "";
$country = "ES";
$limit = 1;
$api_key = "ac5d71bf15cc25c652db23b8bf627fd7";

$url = "http://api.openweathermap.org/geo/1.0/direct?q=" . $city . "&limit=" . $limit . "&appid=" . $api_key;

echo "URL: " . $url . "\n<br>";

// Request to API
echo "Requesting data from API...\n<br>";
$response = file_get_contents($url);
echo "Response: " . $response . "\n<br>";

// Parse JSON
$data = json_decode($response, true);

$lat = $data[0]['lat'];
$lon = $data[0]['lon'];

echo "Latitude: " . $lat . "<br>";
echo "Longitude: " . $lon . "<br>";
// Display data
echo "Data received from API:\n<br>";
print_r($data);

// Example data received from API:
// Array ( [0] => Array ( [name] => Merida [local_names] => Array ( [ur] => \u0645\u06cc\u0631\u06cc\u062f\u0627 [ar] => \u0645\u0627\u0631\u062f\u0629 [es] => M�rida [la] => Emerita Augusta [fa] => \u0645\u0631\u06cc\u062f\u0627 [el] => \u039c\u03ad\u03c1\u03b9\u03b4\u03b1 [ru] => \u041c\u0435\u0440\u0438\u0434\u0430 [lt] => Merida [en] => Merida [be] => \u041c\u0435\u0440\u044b\u0434\u0430 [ca] => M�rida ) [lat] => 38.9174665 [lon] => -6.3443977 [country] => ES [state] => Extremadura ) ) 

?>

<h1>Datos del tiempo actuales</h1>

<?php
// Call weather data for this moment using lat and long:
$url_prevision_actual = "https://api.openweathermap.org/data/2.5/weather?lat=" . $lat . "&lon=" . $lon . "&appid=" . $api_key . "&units=metric"; // Doesn't work, can I get from the other API call?

echo "URL peticionada: " . $url_prevision_actual . "<br>";

$response = file_get_contents($url_prevision_actual);

$data = json_decode($response, true);

echo "Datos decodificados:<br>";
print_r($data);

$temp = $data['main']['temp'];
$description = $data['weather'][0]['description'];

echo "Temperatura: " . $temp . " °C<br>";
echo "Atmósfera: " . $description . "<br>";

echo "<br>";
echo "<br>";
echo "<br>";

?>

<h1>Datos del tiempo por horas en los próximos 4 días</h1>

<?php

$url_prevision_semanal = "https://api.openweathermap.org/data/2.5/forecast?lat=" . $lat . "&lon=" . $lon . "&appid=" . $api_key . "&units=metric";

echo "URL peticionada: " . $url_prevision_semanal . "<br>";

$response = file_get_contents($url_prevision_semanal);

$data = json_decode($response, true);

echo "<h2>Today's hourly forecast</h2>";

$count = 0;

foreach ($data['list'] as $entry) {
    if ($count >= 8) break;

    $time = date("H:i", strtotime($entry['dt_txt']));
    $temp = $entry['main']['temp'];
    $desc = $entry['weather'][0]['description'];

    echo "Hora: $time | Temp: $temp ºC | $desc<br>";

    $count++;
}

?>

<h1>Datos por días 7 días</h1>

<?php
// Call weather data for next 7 days using lat and long:
$url_prevision_semanal = "https://api.openweathermap.org/data/2.5/forecast?lat=" . $lat . "&lon=" . $lon . "&appid=" . $api_key . "&units=metric";
echo "Weather URL: " . $url_prevision_semanal . "<br>";

echo "Requesting weather data...<br>";

$weather_response = file_get_contents($url_prevision_semanal);

echo "Weather response: " . $weather_response . "<br>";

$weather_data = json_decode($weather_response, true);

echo "Decoded weather data:<br>";
print_r($weather_data);


?>