<?php
function logSecurityEvent($event, $details = []) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'user_id' => $_SESSION['user_id'] ?? null,
        'event' => $event,
        'details' => $details
    ];
    
    $log_line = json_encode($log_entry) . "\n";
    file_put_contents('logs/security.log', $log_line, FILE_APPEND | LOCK_EX);
}

// Exemples d'usage :
// logSecurityEvent('failed_login', ['email' => $email]);
// logSecurityEvent('admin_access', ['page' => $_SERVER['REQUEST_URI']]);
?>