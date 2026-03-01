<?php
/**
 * Logout Handler
 * Destroys the user session and redirects to login page
 */

include 'auth_check.php';

// Logout the user
logoutUser();

// Redirect to login page
header('Location: login.html');
exit();
?>