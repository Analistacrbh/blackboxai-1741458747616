<?php
/**
 * Logout Script
 * Destroys user session and redirects to login page
 */

require_once 'init.php';

// Log the logout activity if user was logged in
if (isset($_SESSION['user_id'])) {
    $logger->info("User logged out", [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username']
    ]);
}

// Destroy session
$session->destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Clear any other application cookies
$cookies = array_keys($_COOKIE);
foreach ($cookies as $cookie) {
    setcookie($cookie, '', time() - 3600, '/');
}

// Set flash message for login page
setFlash('info', 'Você foi desconectado com sucesso.');

// Redirect to login page
header('Location: login.php');
exit();
?>
