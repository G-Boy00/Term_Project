<?php
/**
 * Configuration File - Only included once per request
 */

// Check if configuration is already loaded
if (!defined('ONLINE_BANKING_CONFIG_LOADED')) {
    define('ONLINE_BANKING_CONFIG_LOADED', true);

    // Session handling
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 86400,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']), // Auto-enable for HTTPS
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }

    // Database configuration
    if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
    if (!defined('DB_USER')) define('DB_USER', 'root');
    if (!defined('DB_PASS')) define('DB_PASS', '');
    if (!defined('DB_NAME')) define('DB_NAME', 'online_banking');

    // Create database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    // Function declarations with existence checks
    if (!function_exists('sanitizeInput')) {
        function sanitizeInput($data) {
            if (!is_string($data)) return $data;
            $data = trim($data);
            $data = stripslashes($data);
            return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }

    if (!function_exists('requireAuth')) {
        function requireAuth() {
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (empty($_SESSION['user_id'])) {
                header("Location: /Online_banking/login.php");
                exit();
            }
        }
    }

    // Error reporting configuration
    if (!defined('PRODUCTION_ENV')) {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
    }
}
if (!function_exists('generateCsrfToken')) {
    function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
