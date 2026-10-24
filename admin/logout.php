
<?php
session_start();

// Remove admin-specific session variables
unset($_SESSION['admin_id']);
unset($_SESSION['admin_email']);

// Destroy the session
session_destroy();

// Redirect to the admin login page
header("Location: login.php");
exit();
?>
