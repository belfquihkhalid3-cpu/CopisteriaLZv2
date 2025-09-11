<?php
/**
 * Page de déconnexion - Copisteria
 */

session_start();
require_once '../includes/user_functions.php';

// Déconnecter l'utilisateur
logoutUser();

// Redirection vers la page d'accueil
header('Location: index.php?logged_out=1');
exit();
?>