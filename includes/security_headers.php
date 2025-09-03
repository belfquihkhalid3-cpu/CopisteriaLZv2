<?php
/**
 * Headers de sécurité pour Copisteria
 * À inclure dans toutes les pages
 */

// Empêcher le navigateur de deviner le type MIME
header('X-Content-Type-Options: nosniff');

// Empêcher l'affichage dans une iframe (protection clickjacking)
header('X-Frame-Options: DENY');

// Protection XSS intégrée navigateur
header('X-XSS-Protection: 1; mode=block');

// Forcer HTTPS (décommentez en production avec SSL)
// header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Politique de sécurité du contenu
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' cdn.tailwindcss.com cdnjs.cloudflare.com; style-src \'self\' \'unsafe-inline\' cdnjs.cloudflare.com; font-src \'self\' cdnjs.cloudflare.com; img-src \'self\' data:');

// Contrôler les informations de référence
header('Referrer-Policy: strict-origin-when-cross-origin');

// Empêcher la mise en cache des pages sensibles (optionnel)
// header('Cache-Control: no-cache, no-store, must-revalidate');
// header('Pragma: no-cache');
// header('Expires: 0');

// Supprimer header serveur pour cacher la version PHP
header_remove('X-Powered-By');
?>