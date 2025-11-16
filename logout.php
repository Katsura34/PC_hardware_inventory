<?php
require_once 'config/session.php';

// Clear session and redirect to login
clearSession();
header('Location: login.php');
exit();
?>
