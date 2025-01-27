<?php
// Разрешенные IP-адреса (пример для демонстрации)
$allowedIps = ['192.168.0.107', '456.456.456.456'];

// Проверка IP-адреса
$userIp = $_SERVER['REMOTE_ADDR'];
if (!in_array($userIp, $allowedIps)) {
    http_response_code(403);
    die("Access denied");
}

// Проверка User-Agent на соответствие ботам
$userAgent = $_SERVER['HTTP_USER_AGENT'];
if (preg_match('/bot|crawl|slurp|spider/i', $userAgent)) {
    http_response_code(403);
    die("Bots are not allowed");
}

// Проверка наличия параметра URL
if (isset($_GET['url'])) {
    $targetUrl = urldecode($_GET['url']);
    if (filter_var($targetUrl, FILTER_VALIDATE_URL)) {
        // Перенаправление пользователя
        header("Location: $targetUrl");
        exit();
    } else {
        echo "Invalid URL provided.";
    }
} else {
    echo "No URL provided.";
}
?>
