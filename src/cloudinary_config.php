<?php

use Cloudinary\Configuration\Configuration;

$cloudName =  "di5zie7e";
$apiKey = "789654872826227";
$apiSecret = "iKlrC1ZfIuussv5oLXPyBpiFhZ4";

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