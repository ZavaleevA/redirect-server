<?php
// Check using the IPQualityScore API
function isLegitimateIp($ip) {
    $config = require 'config.php';
    $apiKey = $config['ipqualityscore']; // Specify your API key
    $url = "https://www.ipqualityscore.com/api/json/ip/$apiKey/$ip";
    
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects if any
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification (if necessary)

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        return false; // API error
    }

    $data = json_decode($response, true);

    if ($data['success'] && $data['fraud_score'] < 75 && !$data['proxy'] && !$data['vpn']) {
        return true; // Legitimate IP
    }
    return false; // Suspicious or proxy IP
}
