<?php
$config = require 'config.php';

// Function to log requests
function logRequest($ip, $userAgent, $status) {
    $logFile = __DIR__ . '/requests.log';
    $logEntry = sprintf(
        "[%s] IP: %s, User-Agent: %s, Status: %s%s",
        date('Y-m-d H:i:s'),
        $ip,
        $userAgent,
        $status,
        PHP_EOL
    );
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Function to send alerts for suspicious activities
function sendAlert($ip, $userAgent, $cause) {
    // Loading the HTML template
    $htmlTemplate = file_get_contents('alert_email_template.html');

    // Replacing placeholders in the template with actual data
    $htmlContent = str_replace(
        ['{{cause}}', '{{ip}}', '{{userAgent}}', '{{time}}'],
        [$cause, $ip, $userAgent, date('Y-m-d H:i:s')],
        $htmlTemplate
    );

    $email = new Mail();
    $email->setFrom($config['sendgrid']['from_email'], $config['sendgrid']['from_name']);
    $email->setSubject("🚨 Suspicious Activity Detected");
    $email->addTo($config['sendgrid']['admin_email'], "Admin");
    $email->addContent("text/html", $htmlContent);

    // Key encoded for proper testing on the render.com server
    $encoded_key = $config['sendgrid']['encoded_key'];
    $encryption_key = $config['sendgrid']['encryption_key'];
    $cipher = "AES-256-CBC";

    // Decrypting the key
    list($iv, $encrypted_key) = explode('::', base64_decode($encoded_key), 2);
    $plaintext_key = openssl_decrypt($encrypted_key, $cipher, $encryption_key, 0, $iv);

    // Using SendGrid API to send the email
    $sendgrid = new \SendGrid($plaintext_key); // Specify your SendGrid API key
    try {
        $response = $sendgrid->send($email);
    } catch (Exception $e) {
        echo 'SendGrid error: ' . $e->getMessage();
    }
}
?>