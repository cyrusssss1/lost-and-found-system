<?php

use Cloudinary\Configuration\Configuration;

$cloudName = getenv(" di5zie7e");
$apiKey = getenv("789654872826227");
$apiSecret = getenv("iKlrC1ZfIuussv5oLXPyBpiFhZ4");

if (!$cloudName || !$apiKey || !$apiSecret) {
    die("Cloudinary configuration is missing.");
}

Configuration::instance([
    "cloud" => [
        "cloud_name" => $cloudName,
        "api_key" => $apiKey,
        "api_secret" => $apiSecret
    ],
    "url" => [
        "secure" => true
    ]
]);
