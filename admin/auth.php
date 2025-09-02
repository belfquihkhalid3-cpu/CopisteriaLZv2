<?php
// Middleware pour vérifier authentification admin
function requireAdmin() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit();
    }
}

function getAdminUser() {
    if (!isset($_SESSION['admin_id'])) {
        return null;
    }
    
    require_once '../config/database.php';
    return fetchOne("SELECT * FROM users WHERE id = ? AND is_admin = 1", [$_SESSION['admin_id']]);
}

function logoutAdmin() {
    session_destroy();
    header('Location: login.php');
    exit();
}
?>