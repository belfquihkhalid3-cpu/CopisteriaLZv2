<?php
$terminals = [
    'a7f8e9d6c2b4a1f7e8d5c3b6a9f2e7d4c1b8a5f9e6d3c7b4a2f8e5d9c6b3a7f4e1d8c5b2a9f6e3d7c4b1a8f5e2d9c6b3a7f4' => [
<<<<<<< HEAD
        'id' => 'Client 01',
=======
        'id' => 'T001',
>>>>>>> e2b0e54c53dd611fdc7485e7c43341cd79e07aaf
        'name' => 'PC Accueil',
        'location' => 'Entrada Principal',
        'status' => 'active'
    ],
    'x9k7m3n5p2q8r6t4y1w9e8u7i6o5p4a3s2d1f7g6h5j4k3l2m1n9b8v7c6x5z4a3s2d1f6g5h4j3k2l1m9n8b7v6c5x4z3a2s1' => [
<<<<<<< HEAD
        'id' => 'Client 02',
=======
        'id' => 'T002',
>>>>>>> e2b0e54c53dd611fdc7485e7c43341cd79e07aaf
        'name' => 'PC Centre',
        'location' => 'Centro del Local',
        'status' => 'active'
    ],
    'z8x7c6v5b4n3m2q1w9e8r7t6y5u4i3o2p1a8s7d6f5g4h3j2k1l9m8n7b6v5c4x3z2a1s9d8f7g6h5j4k3l2m1n9b8v7c6x5z4' => [
<<<<<<< HEAD
        'id' => 'Client 03',
=======
        'id' => 'T003',
>>>>>>> e2b0e54c53dd611fdc7485e7c43341cd79e07aaf
        'name' => 'PC Fond',
        'location' => 'Fondo del Local',
        'status' => 'active'
    ],
    'p9o8i7u6y5t4r3e2w1q8a7s6d5f4g3h2j1k9l8m7n6b5v4c3x2z1a8s7d6f5g4h3j2k1l9m8n7b6v5c4x3z2a1s9d8f7g6h5j4' => [
<<<<<<< HEAD
        'id' => 'Client 04',
=======
        'id' => 'T004',
>>>>>>> e2b0e54c53dd611fdc7485e7c43341cd79e07aaf
        'name' => 'PC Bureau',
        'location' => 'Oficina',
        'status' => 'active'
    ],
    'k3j4h5g6f7d8s9a1z2x3c4v5b6n7m8q9w1e2r3t4y5u6i7o8p9l1k2j3h4g5f6d7s8a9z1x2c3v4b5n6m7q8w9e1r2t3y4u5i6' => [
<<<<<<< HEAD
        'id' => 'Client 05',
=======
        'id' => 'T005',
>>>>>>> e2b0e54c53dd611fdc7485e7c43341cd79e07aaf
        'name' => 'PC Annexe',
        'location' => 'Sala Anexa',
        'status' => 'active'
    ],
    'a7f8e9d6c2b4a1f7e8d5c3b6a9f2e7d4' => [
<<<<<<< HEAD
        'id' => 'Client 06',
=======
        'id' => 'T006',
>>>>>>> e2b0e54c53dd611fdc7485e7c43341cd79e07aaf
        'name' => 'PC Test',
        'location' => 'Test',
        'status' => 'active'
    ]
];

// Configuration spécifique terminaux
define('TERMINAL_ORDER_PREFIX', 'T');
define('TERMINAL_PAYMENT_METHOD', 'STORE_PAYMENT');

function getTerminalInfo() {
    global $terminals;
    
<<<<<<< HEAD
    // Chercher token dans plusieurs sources
    $token = $_GET['token'] ?? $_POST['token'] ?? $_SESSION['terminal_token'] ?? '';
    
=======
$token = $_GET['token'] ?? $_POST['token'] ?? $_SESSION['terminal_token'] ?? '';
>>>>>>> e2b0e54c53dd611fdc7485e7c43341cd79e07aaf
    if (!empty($token) && isset($terminals[$token])) {
        $terminal = $terminals[$token];
        $terminal['ip'] = $_SERVER['REMOTE_ADDR'];
        return $terminal;
    }
    
    return [
        'id' => 'UNKN',
<<<<<<< HEAD
        'name' => 'Terminal Desconocido',
=======
        'name' => 'Terminal Desconocido', 
        'location' => 'Token: ' . $token,
>>>>>>> e2b0e54c53dd611fdc7485e7c43341cd79e07aaf
        'status' => 'unknown'
    ];
}

function isTerminalAuthorized() {
    $terminal = getTerminalInfo();
    return in_array($terminal['status'], ['active', 'development']);
}
?>