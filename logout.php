<?php
session_start();
session_destroy();
header("Location: login.php"); // Proper header syntax
exit(); // Optional but best practice to stop script execution
?>
