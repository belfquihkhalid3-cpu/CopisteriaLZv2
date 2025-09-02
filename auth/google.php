<?php
session_start();
require_once '../config/social-config.php';

$action = $_GET['action'] ?? 'login'; // login ou register
$_SESSION['social_action'] = $action;

// Rediriger vers Google OAuth
header('Location: ' . getGoogleAuthUrl());
exit();
?>