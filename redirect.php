<?php
session_start(); // Start a session to store state

$config = require 'config.php';
require 'db_connection.php'; // Include the new database connection file
require 'is_legitimate_ip.php';
require 'log_and_alert_functions.php';
require 'vendor/autoload.php'; // Load the library for working with JWT

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;
use SendGrid\Mail\Mail;

// Retrieving the user's real IP address
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $userIp = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]; // First IP in the chain
} else {
    $userIp = $_SERVER['REMOTE_ADDR'];
}

// Checking User-Agent for bot signatures
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
if (preg_match('/bot|crawl|slurp|spider|curl|wget|python|scrapy|httpclient|headless|java|fetch|urllib|perl|go-http|axios|http-request|libwww|httpclient|okhttp|mechanize|node-fetch|phantomjs|selenium|guzzle|aiohttp|http-kit|restsharp|ruby|cfnetwork|go-http-client/i', $userAgent)) {
    logRequest($userIp, $userAgent, 'Bot');
    http_response_code(403);
    die("Bots are not allowed");
}

// Checking if the IP address has changed
if (!isset($_SESSION['last_ip']) || $_SESSION['last_ip'] !== $userIp) {
    // IP has changed or is not set
    $_SESSION['last_ip'] = $userIp; // Update IP in the session
    unset($_SESSION['recaptcha_verified']); // Reset CAPTCHA status
}

// Checking IP legitimacy via an API
if (!isset($_SESSION['recaptcha_verified'])) {
    if (!isLegitimateIp($userIp)) {
        // CAPTCHA verification
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            logRequest($userIp, $userAgent, 'Suspicious IP');
            sendAlert($userIp, $userAgent, 'Suspicious IP'); // Send an alert
            $recaptchaSecret = $config['recaptcha']['secret_key']; // reCAPTCHA secret key
            $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';

            // Verify reCAPTCHA response via Google API
            $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$recaptchaSecret&response=$recaptchaResponse");
            $result = json_decode($response, true);

            if ($result['success']) {
                logRequest($userIp, $userAgent, 'reCAPTCHA passed');
                $_SESSION['recaptcha_verified'] = true; // Set the flag in the session
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit();
            } else {
                logRequest($userIp, $userAgent, 'reCAPTCHA failed');
                echo "Verification failed. Please try again.";
                exit();
            }
        }

        // Include the separate reCAPTCHA form HTML
        include('recaptcha_form.php');
        exit();
    }
}

// Secret key for signing and verifying JWT
$secretKey = $config['secret_key'];

// Allowed IP addresses (example for demonstration purposes)
$allowedIps = $config['allowed_ips'];

// Checking the IP address
if (!in_array($userIp, $allowedIps)) {
    logRequest($userIp, $userAgent, 'Access denied');
    http_response_code(403);
    die("Access denied");
} else {
    logRequest($userIp, $userAgent, 'Access granted');
}

// Rate limiting implementation
$rateLimitFile = __DIR__ . '/rate_limit.log'; // File to store request timestamps
$rateLimitTime = 60; // Time period (in seconds) for a certain number of requests
$rateLimitCount = 10; // Maximum number of requests in $rateLimitTime

// Reading the request log
$rateLimitData = file_exists($rateLimitFile) ? json_decode(file_get_contents($rateLimitFile), true) : [];

// Removing old entries for all IP addresses
foreach ($rateLimitData as $ip => &$timestamps) {
    $timestamps = array_filter($timestamps, function ($timestamp) use ($rateLimitTime) {
        return $timestamp > time() - $rateLimitTime;
    });

    // Remove the IP if the array of timestamps is empty
    if (empty($timestamps)) {
        unset($rateLimitData[$ip]);
    }
}
unset($timestamps);

// Checking request frequency for the current IP
if (!isset($rateLimitData[$userIp])) {
    $rateLimitData[$userIp] = [];
}

if (count($rateLimitData[$userIp]) >= $rateLimitCount) {
    logRequest($userIp, $userAgent, 'Rate limit exceeded');
    sendAlert($userIp, $userAgent, 'Rate limit exceeded'); // Send an alert
    http_response_code(429); // Too Many Requests
    die("Rate limit exceeded. Please try again later.");
}

// Logging the current request
$rateLimitData[$userIp][] = time();
file_put_contents($rateLimitFile, json_encode($rateLimitData));

// Checking if the URL parameter is present
if (isset($_GET['url'])) {
    // Decoding the JWT token
    try {
        $jwt = urldecode($_GET['url']);
        $decoded = JWT::decode($jwt, new Key($secretKey, 'HS256'));
        $uniqueId = $decoded->unique_id; // Extracting unique_id from the decoded JWT

        // Checking for a record in the database by unique ID
        $query = "SELECT * FROM temporary_links WHERE unique_id = :unique_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':unique_id', $uniqueId);
        $stmt->execute();
        $linkData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($linkData) {
            $url = $linkData['url'];
            $expiresAt = strtotime($linkData['expires_at']);
            $currentTime = time();

            // Checking if the link has expired
            if ($currentTime > $expiresAt) {
                logRequest($userIp, $userAgent, 'The link has expired');
                echo "The link has expired.";
                exit();
            }

            // Redirecting the user
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                header("Location: $url");
                exit();
            } else {
                logRequest($userIp, $userAgent, 'Invalid URL provided');
                echo "Invalid URL provided.";
                exit();
            }
        }
    } catch (Exception $e) {
        logRequest($userIp, $userAgent, 'Invalid or expired token');
        echo "Invalid or expired token.";
        exit();
    }
} else {
    echo "No URL provided.";
}
?>