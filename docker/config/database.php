<?php
// Database configuration using environment variables for Docker
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'fourjs_db';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';

define('DB_HOST', $db_host);
define('DB_NAME', $db_name);
define('DB_USER', $db_user);
define('DB_PASS', $db_pass);

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>