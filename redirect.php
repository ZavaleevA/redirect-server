<?php
require 'vendor/autoload.php'; // Подключаем библиотеку для работы с JWT

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

// Секретный ключ для подписи и проверки JWT
$secretKey = 'Ldj0mr62ks6K8rb3D893na204qKAld810fnw49KE2sk4weHW21Mbe7wShebfh';

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
if (preg_match('/bot|crawl|slurp|spider|curl|wget|python|scrapy|httpclient|headless|java|fetch|urllib|perl|go-http|axios|http-request|libwww|httpclient|okhttp|mechanize|node-fetch|phantomjs|selenium|guzzle|aiohttp|http-kit|restsharp|ruby|cfnetwork|go-http-client/i', $userAgent)) {
    http_response_code(403);
    die("Bots are not allowed");
}

// Ограничение частоты обращений
$rateLimitFile = __DIR__ . '/rate_limit.log'; // Файл для хранения временных меток запросов
$rateLimitTime = 60; // Время (в секундах), за которое разрешено определенное количество запросов
$rateLimitCount = 10; // Максимальное количество запросов за $rateLimitTime

// Чтение лога запросов
$rateLimitData = file_exists($rateLimitFile) ? json_decode(file_get_contents($rateLimitFile), true) : [];

// Удаление старых записей для всех IP-адресов
foreach ($rateLimitData as $ip => &$timestamps) {
    $timestamps = array_filter($timestamps, function ($timestamp) use ($rateLimitTime) {
        return $timestamp > time() - $rateLimitTime;
    });

    // Удаляем IP, если массив временных меток пуст
    if (empty($timestamps)) {
        unset($rateLimitData[$ip]);
    }
}
unset($timestamps);

// Проверка частоты запросов для текущего IP
if (!isset($rateLimitData[$userIp])) {
    $rateLimitData[$userIp] = [];
}

if (count($rateLimitData[$userIp]) >= $rateLimitCount) {
    http_response_code(429); // Too Many Requests
    die("Rate limit exceeded. Please try again later.");
}

// Запись текущего запроса
$rateLimitData[$userIp][] = time();
file_put_contents($rateLimitFile, json_encode($rateLimitData));

// Проверка наличия параметра URL
if (isset($_GET['url'])) {
    // Декодируем JWT-ключ
    try {
        $jwt = urldecode($_GET['url']);
        $decoded = JWT::decode($jwt, new Key($secretKey, 'HS256'));
        $targetUrl = $decoded->url; // Извлекаем URL из декодированного JWT

        // Перенаправление пользователя
        if (filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            header("Location: $targetUrl");
            exit();
        } else {
            echo "Invalid URL provided.";
        }
    } catch (Exception $e) {
        echo "Invalid or expired token.";
    }
} else {
    echo "No URL provided.";
}
?>
