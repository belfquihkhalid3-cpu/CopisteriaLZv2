<?php
$terminals = [
    'a7f8e9d6c2b4a1f7e8d5c3b6a9f2e7d4c1b8a5f9e6d3c7b4a2f8e5d9c6b3a7f4e1d8c5b2a9f6e3d7c4b1a8f5e2d9c6b3a7f4' => [
        'id' => 'Clt-01',
        'name' => 'Client 01',
        'location' => 'Entrada Principal',
        'status' => 'active'
    ],
    'x9k7m3n5p2q8r6t4y1w9e8u7i6o5p4a3s2d1f7g6h5j4k3l2m1n9b8v7c6x5z4a3s2d1f6g5h4j3k2l1m9n8b7v6c5x4z3a2s1' => [
        'id' => 'Clt-02',
        'name' => 'Client 02',
        'location' => 'Centro del Local',
        'status' => 'active'
    ],
    'z8x7c6v5b4n3m2q1w9e8r7t6y5u4i3o2p1a8s7d6f5g4h3j2k1l9m8n7b6v5c4x3z2a1s9d8f7g6h5j4k3l2m1n9b8v7c6x5z4' => [
        'id' => 'Clt-03',
        'name' => 'Client 03',
        'location' => 'Fondo del Local',
        'status' => 'active'
    ],
    'p9o8i7u6y5t4r3e2w1q8a7s6d5f4g3h2j1k9l8m7n6b5v4c3x2z1a8s7d6f5g4h3j2k1l9m8n7b6v5c4x3z2a1s9d8f7g6h5j4' => [
        'id' => 'Clt-04',
        'name' => 'Client 04',
        'location' => 'Oficina',
        'status' => 'active'
    ],
    'k3j4h5g6f7d8s9a1z2x3c4v5b6n7m8q9w1e2r3t4y5u6i7o8p9l1k2j3h4g5f6d7s8a9z1x2c3v4b5n6m7q8w9e1r2t3y4u5i6' => [
        'id' => 'Clt-05',
        'name' => 'Client 05',
        'location' => 'Sala Anexa',
        'status' => 'active'
    ],
    'a7f8e9d6c2b4a1f7e8d5c3b6a9f2e7d4' => [
        'id' => 'Client 06',
        'name' => 'Client 06',
        'location' => 'Client 06',
        'status' => 'active'
    ]
     ,
    'a7f8e9d6c2b4a1f7e8d5c3b6a9f2e7d41z2x3c4v5b6n7m8q9w1e2r3t4y5u6i7o8p9l1k' => [
        'id' => 'Client Telephone',
        'name' => 'Client Telephone',
        'location' => 'Tinta',
        'status' => 'active'
    ]
];

// Configuration spécifique terminaux
define('TERMINAL_ORDER_PREFIX', 'T');
define('TERMINAL_PAYMENT_METHOD', 'STORE_PAYMENT');

function getTerminalInfo() {
    global $terminals;
    
    // Chercher token dans plusieurs sources
    $token = $_GET['token'] ?? $_POST['token'] ?? $_SESSION['terminal_token'] ?? '';
    
    if (!empty($token) && isset($terminals[$token])) {
        $terminal = $terminals[$token];
        $terminal['ip'] = $_SERVER['REMOTE_ADDR'];
        return $terminal;
    }
    
    return [
        'id' => 'UNKN',
        'name' => 'Terminal Desconocido',
        'status' => 'unknown'
    ];
}

function isTerminalAuthorized() {
    $terminal = getTerminalInfo();
    return in_array($terminal['status'], ['active', 'development']);
}
?>