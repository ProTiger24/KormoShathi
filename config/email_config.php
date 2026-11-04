<?php
// ============================================
// EMAIL CONFIGURATION - BREVO API
// ============================================

// ENVIRONMENT VARIABLE READER
if (!function_exists('getEnvValue')) {
    function getEnvValue($key, $default = null) {
        $env_path = __DIR__ . '/../.env';
        if (!file_exists($env_path)) {
            return $default;
        }

        $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;

            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                if (trim($name) === $key) {
                    return trim($value, " \t\n\r\0\x0B\"'");
                }
            }
        }
        return $default;
    }
}

// BREVO EMAIL SENDER
if (!function_exists('sendEmail')) {
    function sendEmail($to, $subject, $body) {
        $api_key = getEnvValue('BREVO_API_KEY');

        if (empty($api_key)) {
            error_log("Brevo Error: BREVO_API_KEY not found in .env");
            return false;
        }

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log("Brevo Error: Invalid recipient email.");
            return false;
        }

        $data = [
            'sender' => [
                'name' => 'KormoShathi HR',
                'email' => 'abdulalim528260@gmail.com'
            ],
            'to' => [
                ['email' => $to]
            ],
            'subject' => $subject,
            'htmlContent' => $body
        ];

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'api-key: ' . $api_key
            ],
            CURLOPT_TIMEOUT => 15
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        // curl_close($ch); // Removed for PHP 8.5 compatibility

        // SUCCESS
        if ($http_code === 201 || $http_code === 200) {
            error_log("Email sent successfully to: $to");
            return true;
        }

        // ERROR
        error_log("Brevo Email Error: HTTP $http_code | Response: $response | cURL: $curl_error");
        return false;
    }
}
