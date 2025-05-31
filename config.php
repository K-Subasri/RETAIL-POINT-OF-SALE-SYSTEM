<?php
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session management
session_start();

// Base URL
define('BASE_URL', 'http://localhost/pos_system/');

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Prevent direct access to include files
define('INCLUDED', true);
?>