<?php
session_start(); // Начало сессии для хранения состояния

require 'vendor/autoload.php'; // Подключаем библиотеку для работы с JWT

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

// Данные для подключения к базе данных
$host = 'autorack.proxy.rlwy.net';
$port = 24942;
$username = 'root';
$password = 'qoYVjZFmyggcSrdOBuIPXaxMZghjrdjA';
$dbname = 'railway';

// Создание подключения
$dsn = "mysql:host=$host;port=$port;dbname=$dbname";
try {
    // Создание объекта PDO для подключения
    $pdo = new PDO($dsn, $username, $password);
    // Устанавливаем режим ошибок
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Failed to connect to the database: " . $e->getMessage();
    exit();
}

// Проверка через IPQualityScore API
function isLegitimateIp($ip) {
    $apiKey = '4xf4Fuv7vE80ZAWeaITrCoUaBPIGQYRv'; // Укажите ваш API-ключ
    $url = "https://www.ipqualityscore.com/api/json/ip/$apiKey/$ip";
    
    $response = file_get_contents($url);
    if ($response === false) {
        return false; // Ошибка API
    }

    $data = json_decode($response, true);
    if ($data['success'] && $data['fraud_score'] < 75 && !$data['proxy']) {
        return true; // Легитимный IP
    }
    return false; // Подозрительный или прокси IP
}

// Получение реального IP-адреса пользователя
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $userIp = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]; // Первый IP из цепочки
} else {
    $userIp = $_SERVER['REMOTE_ADDR'];
}

// Проверка изменения IP-адреса
if (!isset($_SESSION['last_ip']) || $_SESSION['last_ip'] !== $userIp) {
    // IP изменился или не установлен
    $_SESSION['last_ip'] = $userIp; // Обновляем IP в сессии
    unset($_SESSION['recaptcha_verified']); // Сбрасываем статус капчи
}

// Проверка легитимности IP через API
if (!isset($_SESSION['recaptcha_verified'])) {
    if (!isLegitimateIp($userIp)) {

        // Логирование подозрительных соединений (по желанию, можно настроить в БД)
        // $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'; // Получение User-Agent
        // $logFile = __DIR__ . '/vpn_attempts.log';
        // file_put_contents($logFile, "IP: $userIp, User-Agent: $userAgent, Time: " . date('Y-m-d H:i:s') . PHP_EOL, FILE_APPEND);

        // Проверка reCAPTCHA
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $recaptchaSecret = '6LeQM8UqAAAAACYvWnAtLXloTJVia5Yf7XGI98kf'; // Секретный ключ reCAPTCHA
            $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';

            // Проверка ответа reCAPTCHA через Google API
            $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$recaptchaSecret&response=$recaptchaResponse");
            $result = json_decode($response, true);

            if ($result['success']) {
                $_SESSION['recaptcha_verified'] = true; // Устанавливаем флаг в сессии
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit();
            } else {
                echo "Verification failed. Please try again.";
                exit();
            }
        }

        // Отображение формы с reCAPTCHA
        echo '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Verify Your Identity</title>
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        </head>
        <body>
            <h1>Verify Your Identity</h1>
            <p>Your connection appears to come from a VPN or proxy. Please complete the reCAPTCHA below to proceed.</p>
            <form action="" method="POST">
                <div class="g-recaptcha" data-sitekey="6LeQM8UqAAAAAPbOcnZNrwV6DlskDPxZCt-NGObD" data-callback="enableButton"></div>
                <br>
                <input type="submit" value="Verify" id="verifyButton" disabled>
            </form>
            <script>
                function enableButton() {
                    document.getElementById("verifyButton").disabled = false;
                }
            </script>
        </body>
        </html>';
        exit();
    // echo "Your connection appears to be coming from a proxy or VPN. Please verify your identity to proceed.";
    // exit();
    }
}

// Секретный ключ для подписи и проверки JWT
$secretKey = 'Ldj0mr62ks6K8rb3D893na204qKAld810fnw49KE2sk4weHW21Mbe7wShebfh';

// Разрешенные IP-адреса (пример для демонстрации)
$allowedIps = ['87.244.131.22', '54.86.50.139', '135.148.55.133', '147.135.70.175', '51.159.180.169', '51.159.135.175', '456.456.456.456'];

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
        $uniqueId = $decoded->unique_id; // Извлекаем unique_id из декодированного JWT
        echo "1";
        exit();
        // Проверяем наличие записи в базе данных по уникальному ID
        $query = "SELECT * FROM temporary_links WHERE unique_id = :unique_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':unique_id', $uniqueId);
        $stmt->execute();
        $linkData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($linkData) {
            $url = $linkData['url'];
            $expiresAt = strtotime($linkData['expires_at']);
            $currentTime = time();

            // Проверяем, не истек ли срок действия ссылки
            if ($currentTime > $expiresAt) {
                echo "The link has expired.";
                exit();
            }
        echo 'uniqueId = ' . $uniqueId . ', url = ' . $url . ', expiresAt = ' . $expiresAt . 'Time = ' . $currentTime;
        exit();
        // Перенаправление пользователя
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                header("Location: $url");
                exit();
            } else {
                echo "Invalid URL provided.";
                exit();
            }
        }

    } catch (Exception $e) {
        echo "Invalid or expired token.";
        exit();
    }
} else {
    echo "No URL provided.";
}
?>
