<?php
require './phpCAS/source/CAS.php';
$cas_host = 'login.sabanciuniv.edu';
$cas_context = '/cas';
$cas_port = 443;
$app_base_url = 'http://pro2-dev.sabanciuniv.edu/odul';
$cas_service_url = getenv('CAS_SERVICE_URL') ?: $app_base_url;
phpCAS::client(CAS_VERSION_2_0, $cas_host, $cas_port, $cas_context, $app_base_url);
session_start();

// Redirect to login if the user is not authenticated
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

// Check if cookies are set
if (!isset($_COOKIE['username']) || !isset($_COOKIE['cookie_id'])) {
    header("Location: index.php");
    exit();
}

// Include the database connection
require_once 'database/dbConnection.php';

// Fetch the username and cookie_id from the browser cookies
$username = $_COOKIE['username'];
$cookie_id = $_COOKIE['cookie_id'];

try {
    // Check the cookie_id in the database
    $query = "SELECT cookie_id FROM user_cookies WHERE SUNET_Username = :username";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':username' => $username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || $row['cookie_id'] !== $cookie_id) {
        // If no record found or cookie_id does not match, log the user out
        setcookie("username", "", time() - 3600, "/"); // Expire the username cookie
        setcookie("cookie_id", "", time() - 3600, "/"); // Expire the cookie_id cookie
        session_unset();
        session_destroy(); // Destroy the session
        phpCAS::logoutWithRedirectService($app_base_url . "/ENS491-492");
        header("Location: index.php");
        exit();
    }
} catch (PDOException $e) {
    // Handle database errors
    die("Database error: " . $e->getMessage());
}
?>
