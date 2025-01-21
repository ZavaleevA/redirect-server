<?php
if (isset($_GET['url'])) {
    $targetUrl = urldecode($_GET['url']);
    if (filter_var($targetUrl, FILTER_VALIDATE_URL)) {
        header("Location: $targetUrl");
        exit();
    } else {
        echo "Invalid URL provided.";
    }
} else {
    echo "No URL provided.";
}