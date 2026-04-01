<?php
// /Gymora/auth/logout.php
require_once '../config/session.php';
require_once '../config/constants.php';

// Unset all session variables and destroy the session
session_unset();
session_destroy();

// Redirect to login page
header("Location: " . BASE_URL . "auth/login.php");
exit();
?>