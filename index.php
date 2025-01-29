<?php
// index.php
echo "Hello, this is your redirect service!";

// Path to the log file
$logFile = __DIR__ . '/requests.log';

// Check if the file exists
if (file_exists($logFile)) {
    // Read the file contents
    $logs = file_get_contents($logFile);

    // Display the content as preformatted text
    echo "<h2>Suspicious Activity Logs:</h2>";
    echo "<pre style='background-color: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 5px; overflow: auto; max-height: 400px;'>" . htmlspecialchars($logs) . "</pre>";
} else {
    echo "<p>No suspicious activity logs found.</p>";
}
?>