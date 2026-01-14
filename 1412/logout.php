<?php
require_once 'db.php';

// Destroy session and redirect
session_destroy();
header("Location: index.php");
exit;
?>