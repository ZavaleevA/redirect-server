<?php
// Check using the IPQualityScore API
function isLegitimateIp($ip) {
    $config = require 'config.php';
    $apiKey = $config['ipqualityscore']; // API key from config

    // Build the API URL
    $url = sprintf(
        'https://www.ipqualityscore.com/api/json/ip/%s/%s',
        $apiKey,
        $ip
    );

    // cURL to fetch the result
    $timeout = 5; // Timeout for the request
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);

    // Execute the request and get the response
    $json = curl_exec($curl);
    curl_close($curl);

    // Check if the response is valid
    if ($json === false) {
        return false; // cURL error
    }

    // Decode the response into an array
    $data = json_decode($json, true);

    // Check for success and fraud score, proxy, and VPN status
    if (isset($data['success']) && $data['success'] === true) {
        if ($data['fraud_score'] < 75 && !$data['proxy'] && !$data['vpn']) {
            return true; // Legitimate IP
        }
    }

    return false; // Suspicious or proxy IP
}
