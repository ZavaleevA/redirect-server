<?php
// index.php
echo "Hello, this is your redirect service!";

// Путь к файлу логов
$logFile = __DIR__ . '/requests.log';

// Проверяем, существует ли файл
if (file_exists($logFile)) {
    // Читаем содержимое файла
    $logs = file_get_contents($logFile);

    // Выводим содержимое в виде предварительно форматированного текста
    echo "<h2>Suspicious Activity Logs:</h2>";
    echo "<pre style='background-color: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 5px; overflow: auto; max-height: 400px;'>" . htmlspecialchars($logs) . "</pre>";
} else {
    echo "<p>No suspicious activity logs found.</p>";
}
?>
