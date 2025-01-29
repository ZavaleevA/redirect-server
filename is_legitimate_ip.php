<?php
$config = require 'config.php';

// Check using the IPQualityScore API
function isLegitimateIp($ip) {
    $apiKey = $config['ipqualityscore']; // Specify your API key
    $url = "https://www.ipqualityscore.com/api/json/ip/$apiKey/$ip";
    
    $response = file_get_contents($url);
    if ($response === false) {
        return false; // API error
    }

    $data = json_decode($response, true);
    if ($data['success'] && $data['fraud_score'] < 75 && !$data['proxy']) {
        return true; // Legitimate IP
    }
    return false; // Suspicious or proxy IP
}
?>