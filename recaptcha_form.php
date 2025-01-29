<?php
$config = require 'config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Identity</title>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
        }

        .container {
            text-align: center;
            padding: 20px;
            background: #ffffff;
            border: 1px solid #ddd;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            max-width: 400px;
            width: 90%;
        }

        h1 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        p {
            font-size: 1rem;
            margin-bottom: 20px;
            color: #555;
        }

        .g-recaptcha {
            margin-bottom: 20px;
        }

        input[type="submit"] {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        input[type="submit"]:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Verify Your Identity</h1>
        <p>Your connection appears to come from a VPN or proxy. Please complete the reCAPTCHA below to proceed.</p>
        <form action="" method="POST">
            <div class="g-recaptcha" data-sitekey="<?php echo $config['recaptcha']['site_key']; ?>" data-callback="enableButton"></div>
            <br>
            <input type="submit" value="Verify" id="verifyButton" disabled>
        </form>
    </div>
    <script>
        function enableButton() {
            document.getElementById("verifyButton").disabled = false;
        }
    </script>
</body>
</html>