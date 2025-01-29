<?php
// Check using the IPQualityScore API
function isLegitimateIp($ip) {
    $config = require 'config.php';
    $apiKey = $config['ipqualityscore']; // Specify your API key
    $url = "https://www.ipqualityscore.com/api/json/ip/$apiKey/$ip";
    
    $response = file_get_contents($url);
    if ($response === false) {
        echo '4';
        exit();
        return false; // API error
    }
    echo '1';
    $data = json_decode($response, true);
    if ($data['success'] && $data['fraud_score'] < 75 && !$data['proxy']) {
        echo '2';
        exit();
        return true; // Legitimate IP
    }
    echo '3';
    exit();
    return false; // Suspicious or proxy IP
}
