<?php
// Разрешенные IP-адреса (пример для демонстрации)
$allowedIps = ['87.244.131.22', '54.86.50.139', '456.456.456.456'];

// Получение реального IP-адреса пользователя
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $userIp = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]; // Первый IP из цепочки
} else {
    $userIp = $_SERVER['REMOTE_ADDR'];
}

// Проверка IP-адреса
if (!in_array($userIp, $allowedIps)) {
    http_response_code(403);
    die("Access denied");
}

// Проверка User-Agent на соответствие ботам
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
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
