<?php
// Check using the IPQualityScore API
function isLegitimateIp($ip) {
    $config = require 'config.php';
    $apiKey = $config['ipqualityscore']; // Specify your API key
    $url = "https://www.ipqualityscore.com/api/json/ip/$apiKey/$ip";
    
    $response = file_get_contents($url);
    if ($response === false) {
        return false; // API error
    }

    $data = json_decode($response, true);
    echo '1:' . $data['success'] . ' 2:' . $data['fraud_score'] . ' 3:' . $data['proxy'] . ' 4:' . $data['vpn'];
    exit();
    if ($data['success'] && $data['fraud_score'] < 75 && !$data['proxy'] && !$data['vpn']) {
        return true; // Legitimate IP
    }
    return false; // Suspicious or proxy IP
}
