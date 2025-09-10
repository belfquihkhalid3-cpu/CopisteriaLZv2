<?php
// terminal/config.php

$terminals = [
    // IPs localhost pour développement
    '127.0.0.1' => [
        'id' => 'T999', 
        'name' => 'Terminal Test', 
        'location' => 'Desarrollo Local',
        'status' => 'development'
    ],
    '::1' => [
        'id' => 'T998', 
        'name' => 'Terminal IPv6', 
        'location' => 'Desarrollo IPv6',
        'status' => 'development'
    ],
    // IPs production (à configurer plus tard)
    '192.168.1.10' => [
        'id' => 'T001', 
        'name' => 'Terminal 1', 
        'location' => 'Entrada Principal',
        'status' => 'active'
    ],
    '192.168.1.11' => [
        'id' => 'T002', 
        'name' => 'Terminal 2', 
        'location' => 'Centro del Local',
        'status' => 'active'
    ],
    // ... autres terminaux
];

function getTerminalInfo() {
    global $terminals;
    $ip = $_SERVER['REMOTE_ADDR'];
    
    return $terminals[$ip] ?? [
        'id' => 'UNKN', 
        'name' => 'Terminal Desconocido', 
        'location' => 'IP: ' . $ip,
        'status' => 'unknown'
    ];
}

function isTerminalAuthorized() {
    $terminal = getTerminalInfo();
    return in_array($terminal['status'], ['active', 'development']);
}
?>