<?php
session_start();

// Destroy session
session_unset();
session_destroy();

// Clear cookies
setcookie("user_id", "", time() - 3600, "/");
setcookie("user_token", "", time() - 3600, "/");

// Redirect to login page
header("Location: login.php");
exit();
?>
