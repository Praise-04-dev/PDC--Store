<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Redirect back to home page
header("Location: home.html");
exit();
?>
