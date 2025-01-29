<?php
// Check using the IPQualityScore API
function isLegitimateIp($ip) {
    $config = require 'config.php';
    $apiKey = $config['ipqualityscore']; // API key from config
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'; // User-Agent
    $userLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en-US'; // Language

    // Optional parameters for enhanced fraud score detection
    $parameters = array(
        'user_agent' => $userAgent,
        'user_language' => $userLanguage,
        'strictness' => 1,  // Set the strictness level
        'allow_public_access_points' => 'true',
        'lighter_penalties' => 'false'
    );

    // Format parameters for URL
    $formattedParameters = http_build_query($parameters);

    // Build the API URL with parameters
    $url = sprintf(
        'https://www.ipqualityscore.com/api/json/ip/%s/%s?%s',
        $apiKey,
        $ip,
        $formattedParameters
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
