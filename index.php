<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Copisteria - Impresión Online</title>
    
    <!-- External CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <!-- Header -->
<header class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-full px-6 py-3">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-print text-blue-500 text-xl"></i>
                    <h1 class="text-xl font-bold text-gray-800">Copisteria</h1>
                </div>
            </div>
            
            <div class="flex items-center space-x-4">
                <!-- Bouton Imprimir -->
                <button class="flex items-center space-x-2 px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors">
                    <i class="fas fa-print"></i>
                    <span>Imprimir</span>
                </button>
                
                <!-- Total carrito -->
                <div class="flex items-center space-x-2">
                    <i class="fas fa-shopping-cart text-blue-500"></i>
                    <div class="text-center">
                        <div class="text-sm text-gray-600">Total carrito</div>
                        <div class="font-bold text-blue-600">
                            <span class="bg-blue-500 text-white text-xs px-1 rounded">0</span>
                            <span id="total-price">0,00 €</span>
                        </div>
                        <div class="text-xs text-gray-500">(Envío incluido)</div>
                    </div>
                </div>
                
                <!-- Menu Utilisateur -->
             <!-- Menu Utilisateur -->
<div class="relative" id="user-menu">
    <button class="flex items-center space-x-2 px-4 py-2 border border-gray-300 rounded-full hover:bg-gray-50 transition-colors" onclick="toggleUserMenu()">
        <i class="fas fa-bars text-gray-600"></i>
        <i class="fas fa-user text-gray-600"></i>
    </button>
    
    <!-- Dropdown Menu -->
    <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-200 hidden" id="user-dropdown">
        <?php if ($user_id): ?>
            <a href="account.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Mi cuenta</a>
            <a href="orders.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Mis pedidos</a>
            <hr class="my-1">
            <a href="logout.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Cerrar sesión</a>
        <?php else: ?>
            <a href="#" onclick="openLoginModal()" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Iniciar sesión</a>
            <a href="#" onclick="openRegisterModal()" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Registrarte</a>
            <hr class="my-1">
            <a href="#" class="block px-4 py-2 text-blue-600 hover:bg-blue-50 font-medium">Consulta tu pedido</a>
        <?php endif; ?>
    </div>
</div>
            </div>
        </div>
    </div>
</header>

    <div class="flex h-screen">
        <!-- Sidebar de Configuration -->
        <aside class="w-96 bg-gray-50 border-r border-gray-200">
            <div class="sidebar-scroll p-6">
                <!-- Header Sidebar -->
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-800">Configuración</h2>
                    <p class="text-sm text-gray-600">Selecciona cómo lo imprimimos</p>
                </div>

                <!-- Copias -->
                <div class="config-section">
                    <h3 class="section-title">Copias</h3>
                    <div class="flex items-center justify-center space-x-6">
                        <button class="quantity-btn border-blue-200 text-blue-500 hover:bg-blue-50" onclick="changeQuantity(-1)">
                            <i class="fas fa-minus"></i>
                        </button>
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-copy text-blue-500 text-2xl"></i>
                            <span class="text-3xl font-bold text-gray-800" id="copies-count">5</span>
                            <i class="fas fa-plus text-blue-500 text-xl"></i>
                        </div>
                        <button class="quantity-btn bg-blue-500 text-white hover:bg-blue-600" onclick="changeQuantity(1)">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>

                <!-- Color de la impresión -->
                <div class="config-section">
                    <h3 class="section-title">
                        Color de la impresión
                        <div class="tooltip">
                            <i class="fas fa-info-circle text-gray-400 text-sm cursor-help"></i>
                            <span class="tooltiptext">Selecciona el tipo de impresión</span>
                        </div>
                    </h3>
                    <p class="section-subtitle">Selecciona el tipo de impresión</p>
                    <div class="option-grid-2">
                        <button class="option-btn active" onclick="selectColorMode('bw')" data-color="bw">
                            <div class="font-semibold mb-1">B/N</div>
                            <div class="text-xs opacity-75">Escala de grises</div>
                        </button>
                        <button class="option-btn" onclick="selectColorMode('color')" data-color="color">
                            <div class="font-semibold mb-1">Color</div>
                            <div class="text-xs opacity-75">Formato CMYK</div>
                        </button>
                    </div>
                </div>

                <!-- Tamaño del papel -->
                <div class="config-section">
                    <h3 class="section-title">
                        Tamaño del papel
                        <div class="tooltip">
                            <i class="fas fa-info-circle text-gray-400 text-sm cursor-help"></i>
                            <span class="tooltiptext">Formato del papel</span>
                        </div>
                    </h3>
                    <div class="option-grid-3">
                        <button class="option-btn" onclick="selectPaperSize('A3')" data-size="A3">
                            <div class="font-semibold mb-1">A3</div>
                            <div class="text-xs opacity-75">420 x 297 mm</div>
                        </button>
                        <button class="option-btn active" onclick="selectPaperSize('A4')" data-size="A4">
                            <div class="font-semibold mb-1">A4</div>
                            <div class="text-xs opacity-75">297 x 210 mm</div>
                        </button>
                        <button class="option-btn" onclick="selectPaperSize('A5')" data-size="A5">
                            <div class="font-semibold mb-1">A5</div>
                            <div class="text-xs opacity-75">210 x 148 mm</div>
                        </button>
                    </div>
                </div>

                <!-- Grosor del papel -->
                <div class="config-section">
                    <h3 class="section-title">
                        Grosor del papel
                        <div class="tooltip">
                            <i class="fas fa-info-circle text-gray-400 text-sm cursor-help"></i>
                            <span class="tooltiptext">Peso del papel en gramos</span>
                        </div>
                    </h3>
                    <p class="section-subtitle">Peso del papel en gramos</p>
                    <div class="option-grid-3">
                        <button class="option-btn active" onclick="selectPaperWeight('80g')" data-weight="80g">
                            <div class="font-semibold mb-1">80 gr</div>
                            <div class="text-xs opacity-75">Estándar</div>
                        </button>
                        <button class="option-btn" onclick="selectPaperWeight('160g')" data-weight="160g">
                            <div class="font-semibold mb-1">160 gr</div>
                            <div class="text-xs opacity-75">Grueso alto</div>
                        </button>
                        <button class="option-btn" onclick="selectPaperWeight('280g')" data-weight="280g">
                            <div class="font-semibold mb-1">280 gr</div>
                            <div class="text-xs opacity-75">Tipo cartulina</div>
                        </button>
                    </div>
                </div>

                <!-- Forma de impresión -->
                <div class="config-section">
                    <h3 class="section-title">
                        Forma de impresión
                        <div class="tooltip">
                            <i class="fas fa-info-circle text-gray-400 text-sm cursor-help"></i>
                            <span class="tooltiptext">Una cara o ambas caras</span>
                        </div>
                    </h3>
                    <div class="option-grid-2">
                        <button class="option-btn" onclick="selectSides('single')" data-sides="single">
                            <div class="font-semibold mb-1">Una cara</div>
                            <div class="text-xs opacity-75">por una cara del papel</div>
                        </button>
                        <button class="option-btn active" onclick="selectSides('double')" data-sides="double">
                            <div class="font-semibold mb-1">Doble cara</div>
                            <div class="text-xs opacity-75">por ambas caras del papel</div>
                        </button>
                    </div>
                </div>

                <!-- Orientación -->
                <div class="config-section">
                    <h3 class="section-title">
                        Orientación
                        <div class="tooltip">
                            <i class="fas fa-info-circle text-gray-400 text-sm cursor-help"></i>
                            <span class="tooltiptext">Orientación del documento</span>
                        </div>
                    </h3>
                    <div class="option-grid-2">
                        <button class="option-btn active" onclick="selectOrientation('portrait')" data-orientation="portrait">
                            <div class="font-semibold">Vertical</div>
                        </button>
                        <button class="option-btn" onclick="selectOrientation('landscape')" data-orientation="landscape">
                            <div class="font-semibold">Horizontal</div>
                        </button>
                    </div>
                </div>

                <!-- Acabado -->
                <div class="config-section">
                    <h3 class="section-title">
                        Acabado
                        <div class="tooltip">
                            <i class="fas fa-info-circle text-gray-400 text-sm cursor-help"></i>
                            <span class="tooltiptext">Tipo de acabado</span>
                        </div>
                    </h3>
                    <p class="section-subtitle">Selecciona el tipo de acabado</p>
                    
                    <div class="finishing-grid">
                        <button class="option-btn active" onclick="selectFinishing('individual')" data-finishing="individual">
                            <i class="fas fa-file text-green-500 mb-2 text-lg"></i>
                            <div class="font-semibold mb-1">Individual</div>
                            <div class="text-xs opacity-75">Cada documento</div>
                        </button>
                        <button class="option-btn" onclick="selectFinishing('grouped')" data-finishing="grouped">
                            <i class="fas fa-copy text-blue-500 mb-2 text-lg"></i>
                            <div class="font-semibold mb-1">Agrupado</div>
                            <div class="text-xs opacity-75">Todos en uno</div>
                        </button>
                    </div>
                    
                    <div class="finishing-grid">
                        <button class="option-btn" onclick="selectFinishing('none')" data-finishing="none">
                            <i class="fas fa-file-alt text-gray-500 mb-2 text-lg"></i>
                            <div class="font-semibold mb-1">Sin acabado</div>
                            <div class="text-xs opacity-75">Solo imprimir</div>
                        </button>
                        <button class="option-btn" onclick="selectFinishing('spiral')" data-finishing="spiral">
                            <i class="fas fa-book text-blue-500 mb-2 text-lg"></i>
                            <div class="font-semibold mb-1">Encuadernado</div>
                            <div class="text-xs opacity-75">En espiral</div>
                        </button>
                    </div>
                    
                    <div class="finishing-grid">
                        <button class="option-btn" onclick="selectFinishing('staple')" data-finishing="staple">
                            <i class="fas fa-paperclip text-gray-600 mb-2 text-lg"></i>
                            <div class="font-semibold mb-1">Grapado</div>
                            <div class="text-xs opacity-75">En esquina</div>
                        </button>
                        <button class="option-btn" onclick="selectFinishing('laminated')" data-finishing="laminated">
                            <i class="fas fa-shield-alt text-yellow-500 mb-2 text-lg"></i>
                            <div class="font-semibold mb-1">Plastificado</div>
                            <div class="text-xs opacity-75">Ultra resistente</div>
                        </button>
                    </div>
                    
                    <div class="finishing-grid">
                        <button class="option-btn" onclick="selectFinishing('perforated2')" data-finishing="perforated2">
                            <i class="fas fa-circle text-gray-400 mb-2 text-lg"></i>
                            <div class="font-semibold mb-1">Perforado</div>
                            <div class="text-xs opacity-75">2 agujeros</div>
                        </button>
                        <button class="option-btn" onclick="selectFinishing('perforated4')" data-finishing="perforated4">
                            <i class="fas fa-circle text-gray-400 mb-2 text-lg"></i>
                            <div class="font-semibold mb-1">Perforado</div>
                            <div class="text-xs opacity-75">4 agujeros</div>
                        </button>
                    </div>
                </div>

                <!-- Comentario -->
                <div class="config-section">
                    <h3 class="section-title">
                        Comentario
                        <i class="fas fa-comment text-gray-400"></i>
                    </h3>
                    <p class="section-subtitle">Comentario de la impresión</p>
                    <textarea 
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none text-sm"
                        placeholder="Comentario de impresión"
                        rows="3"
                        id="print-comments"
                    ></textarea>
                </div>
            </div>
        </aside>

        <!-- Zone principale -->
        <main class="flex-1 bg-white flex flex-col">
            <!-- Document Title Bar -->
            <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-folder text-blue-500"></i>
                        <span class="font-medium text-gray-800">Carpeta sin título</span>
                        <i class="fas fa-edit text-gray-400 text-sm cursor-pointer"></i>
                        <div class="flex flex-wrap gap-1">
    <span class="badge badge-blue" id="color-badge">BN</span>
    <span class="badge badge-green" id="size-badge">A4</span>
    <span class="badge badge-orange" id="weight-badge">80</span>
    <span class="badge badge-purple" id="sides-badge">DC</span>
    <span class="badge badge-teal" id="finishing-badge">IN</span>
    <span class="badge badge-cyan" id="orientation-badge">VE</span>
    <span class="badge badge-pink" id="copies-badge">5</span>
</div>

                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-gray-800" id="price-display">0,00</div>
                        <div class="text-sm text-gray-600">EUR</div>
                        <button class="bg-green-500 hover:bg-green-600 text-white text-sm px-4 py-1 rounded-full mt-1 transition-colors">
                            Añadir al carro
                        </button>
                    </div>
                </div>
            </div>

            <!-- Upload Zone -->
            <div class="flex-1 flex items-center justify-center p-8">
                <div class="upload-zone w-full max-w-2xl h-96 border-2 border-dashed border-gray-300 rounded-xl flex flex-col items-center justify-center text-center bg-gradient-to-br from-gray-50 to-gray-100 hover:from-blue-50 hover:to-blue-100 hover:border-blue-300 transition-all duration-300 cursor-pointer" id="upload-zone">
                    <!-- Illustration -->
                    <div class="mb-6">
                        <svg width="120" height="120" viewBox="0 0 200 200" class="text-gray-400">
                            <!-- Laptop -->
                            <rect x="40" y="80" width="120" height="80" rx="8" fill="currentColor" opacity="0.3"/>
                            <rect x="50" y="90" width="100" height="60" rx="4" fill="white"/>
                            <!-- Documents floating -->
                            <rect x="70" y="40" width="30" height="40" rx="2" fill="currentColor" opacity="0.6" transform="rotate(-10 85 60)"/>
                            <rect x="90" y="30" width="30" height="40" rx="2" fill="currentColor" opacity="0.7" transform="rotate(5 105 50)"/>
                            <rect x="110" y="45" width="30" height="40" rx="2" fill="currentColor" opacity="0.8" transform="rotate(-5 125 65)"/>
                            <!-- Chart lines in documents -->
                            <path d="M75 55 L85 50 L95 58" stroke="white" stroke-width="1.5" fill="none"/>
                            <path d="M95 40 L105 35 L115 42" stroke="white" stroke-width="1.5" fill="none"/>
                            <!-- Floating elements -->
                            <circle cx="160" cy="50" r="3" fill="currentColor" opacity="0.4"/>
                            <circle cx="170" cy="70" r="2" fill="currentColor" opacity="0.3"/>
                            <circle cx="155" cy="80" r="2" fill="currentColor" opacity="0.5"/>
                        </svg>
                    </div>
                    
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Selecciona los documentos a imprimir</h3>
                    <p class="text-gray-600 mb-6">Sube tus documentos y empieza a imprimir con la mejor calidad al mejor precio</p>
                    
                    <button class="bg-blue-500 hover:bg-blue-600 text-white font-medium px-8 py-3 rounded-lg flex items-center space-x-2 transition-colors shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Subir documentos</span>
                    </button>
                    
                    <!-- Cloud service icons -->
                    <div class="flex items-center space-x-4 mt-6 opacity-70">
                        <i class="fab fa-google-drive text-2xl text-blue-500"></i>
                        <i class="fab fa-dropbox text-2xl text-blue-600"></i>
                        <i class="fab fa-microsoft text-2xl text-blue-700"></i>
                    </div>
                    
                    <input type="file" multiple accept=".pdf,.doc,.docx,.txt" class="hidden" id="file-input">
                </div>
            </div>

            <!-- File List (initially hidden) -->
            <div class="hidden p-6 border-t border-gray-200" id="file-list">
                <h4 class="font-medium text-gray-800 mb-4">Documentos subidos:</h4>
                <div id="files-container" class="space-y-2">
                    <!-- Files will be dynamically added here -->
                </div>
            </div>
        </main>
    </div>
<!-- Modal d'inscription -->
<div id="registerModal" class="fixed inset-0 modal-overlay z-50 flex items-center justify-center hidden">
    <div class="modal-content bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        
        <!-- Modal Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-100">
            <h2 class="text-xl font-semibold text-gray-800">Crea tu cuenta</h2>
            <button onclick="closeRegisterModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <!-- Modal Body -->
        <div class="p-6 space-y-4">
            <form id="registerForm" onsubmit="handleRegister(event)">
                
                <!-- Nombre y apellidos -->
                <div class="input-group">
                    <i class="fas fa-user input-icon"></i>
                    <input 
                        type="text" 
                        name="full_name"
                        class="input-field w-full py-4 pr-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all placeholder-gray-500"
                        placeholder="Nombre y apellidos"
                        required
                    >
                </div>
                
                <!-- Correo electrónico -->
                <div class="input-group">
                    <i class="fas fa-envelope input-icon"></i>
                    <input 
                        type="email" 
                        name="email"
                        class="input-field w-full py-4 pr-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all placeholder-gray-500"
                        placeholder="Correo electrónico"
                        required
                    >
                </div>
                
                <!-- Contraseña -->
                <div class="input-group">
                    <i class="fas fa-key input-icon"></i>
                    <input 
                        type="password" 
                        name="password"
                        id="registerPassword"
                        class="input-field w-full py-4 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all placeholder-gray-500"
                        placeholder="Contraseña"
                        required
                        minlength="6"
                    >
                    <i class="fas fa-eye-slash password-toggle" onclick="togglePassword('registerPassword', this)"></i>
                </div>
                
                <!-- Términos y condiciones -->
                <div class="text-sm text-gray-600 leading-relaxed">
                    Al registrarte aceptas nuestros 
                    <a href="#" class="text-blue-600 hover:underline">Términos y Condiciones</a> 
                    y la 
                    <a href="#" class="text-blue-600 hover:underline">Política de Privacidad</a>.
                </div>
                
                <!-- Botón Crear cuenta -->
                <button 
                    type="submit" 
                    class="btn-primary w-full py-4 text-white font-semibold rounded-lg text-lg"
                >
                    Crear mi cuenta ahora
                </button>
                
                <!-- Separador -->
                <div class="flex items-center my-6">
                    <div class="flex-1 border-t border-gray-300"></div>
                    <span class="mx-4 text-gray-500 text-sm">O accede con:</span>
                    <div class="flex-1 border-t border-gray-300"></div>
                </div>
                
                <!-- Botones de redes sociales -->
                <div class="flex space-x-3">
                    <button type="button" class="flex-1 flex items-center justify-center py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fab fa-google text-red-500 text-xl"></i>
                    </button>
                    <button type="button" class="flex-1 flex items-center justify-center py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fab fa-facebook text-blue-600 text-xl"></i>
                    </button>
                    <button type="button" class="flex-1 flex items-center justify-center py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fab fa-apple text-gray-800 text-xl"></i>
                    </button>
                </div>
                
                <!-- Link login -->
                <div class="text-center mt-6">
                    <span class="text-gray-600">¿Ya tienes cuenta? </span>
                    <a href="login.php" class="text-blue-600 hover:underline font-medium">Inicia sesión</a>
                </div>
                
            </form>
        </div>
    </div>
</div>
<div id="loginModal" class="fixed inset-0 modal-overlay z-50 flex items-center justify-center hidden">
    <div class="modal-content bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        
        <!-- Modal Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-100">
            <h2 class="text-xl font-semibold text-gray-800">Iniciar sesión</h2>
            <button onclick="closeLoginModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <!-- Modal Body -->
        <div class="p-6 space-y-4">
            <!-- Message d'erreur -->
            <div id="loginError" class="error hidden">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <span id="loginErrorMessage"></span>
            </div>
            
            <form id="loginForm" onsubmit="handleLogin(event)">
                
                <!-- Correo electrónico -->
                <div class="input-group">
                    <i class="fas fa-envelope input-icon"></i>
                    <input 
                        type="email" 
                        name="email"
                        id="loginEmail"
                        class="input-field w-full py-4 pr-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all placeholder-gray-500"
                        placeholder="Correo electrónico"
                        required
                    >
                </div>
                
                <!-- Contraseña -->
                <div class="input-group">
                    <i class="fas fa-key input-icon"></i>
                    <input 
                        type="password" 
                        name="password"
                        id="loginPassword"
                        class="input-field w-full py-4 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all placeholder-gray-500"
                        placeholder="Contraseña"
                        required
                    >
                    <i class="fas fa-eye-slash password-toggle" onclick="togglePassword('loginPassword', this)"></i>
                </div>
                
                <!-- Remember me y forgot password -->
                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center">
                        <input type="checkbox" name="remember_me" class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-gray-600">Recordarme</span>
                    </label>
                    <a href="#" class="text-blue-600 hover:underline">¿Olvidaste tu contraseña?</a>
                </div>
                
                <!-- Botón Iniciar sesión -->
                <button 
                    type="submit" 
                    class="btn-primary w-full py-4 text-white font-semibold rounded-lg text-lg"
                    id="loginButton"
                >
                    Iniciar sesión
                </button>
                
                <!-- Separador -->
                <div class="flex items-center my-6">
                    <div class="flex-1 border-t border-gray-300"></div>
                    <span class="mx-4 text-gray-500 text-sm">O inicia con:</span>
                    <div class="flex-1 border-t border-gray-300"></div>
                </div>
                
                <!-- Botones de redes sociales -->
                <div class="flex space-x-3">
                    <button type="button" class="flex-1 flex items-center justify-center py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fab fa-google text-red-500 text-xl"></i>
                    </button>
                    <button type="button" class="flex-1 flex items-center justify-center py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fab fa-facebook text-blue-600 text-xl"></i>
                    </button>
                    <button type="button" class="flex-1 flex items-center justify-center py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fab fa-apple text-gray-800 text-xl"></i>
                    </button>
                </div>
                
                <!-- Link register -->
                <div class="text-center mt-6">
                    <span class="text-gray-600">¿No tienes cuenta? </span>
                    <a href="#" onclick="openRegisterModal(); closeLoginModal();" class="text-blue-600 hover:underline font-medium">Regístrate</a>
                </div>
                
            </form>
        </div>
    </div>
</div>
    <!-- JavaScript -->
    <script src="assets/js/main.js"></script>
</body>
</html>