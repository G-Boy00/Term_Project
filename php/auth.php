<?php
// includes/auth.php
require_once __DIR__ . '..includes/config.php';

if (!function_exists('requireAuth')) {
    function requireAuth() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header("Location: /Online_banking/src/auth/login.php");
            exit();
        }
    }
}