<?php

include "Views/header.php";

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


// Display data
echo "Data received from API:\n<br>";
print_r($data);

?>