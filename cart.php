<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/user_functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito - Copisteria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-50">
    
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-full px-6 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="flex items-center space-x-2">
                        <i class="fas fa-print text-blue-500 text-xl"></i>
                        <h1 class="text-xl font-bold text-gray-800">Copisteria</h1>
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- Botón Imprimir -->
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
                                <span class="bg-blue-500 text-white text-xs px-2 py-1 rounded" id="cart-count">1</span>
                                <span id="cart-total">2,01 €</span>
                            </div>
                            <div class="text-xs text-gray-500">(Envío incluido)</div>
                        </div>
                    </div>
                    
                    <!-- Menu Utilisateur -->
                    <div class="relative" id="user-menu">
                        <button class="flex items-center space-x-2 px-4 py-2 border border-gray-300 rounded-full hover:bg-gray-50 transition-colors">
                            <i class="fas fa-bars text-gray-600"></i>
                            <i class="fas fa-user text-gray-600"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="grid grid-cols-12 gap-6">
            
            <!-- Colonne gauche - Carpetas de impresión -->
            <div class="col-span-8">
                
                <!-- Header section -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="bg-blue-100 p-3 rounded-lg">
                                <i class="fas fa-folder text-blue-500 text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-800">Carpetas de impresión</h2>
                                <p class="text-sm text-gray-600">
                                    <i class="fas fa-folder mr-1"></i>
                                    <span id="folder-count">1</span> carpetas para imprimir
                                </p>
                            </div>
                        </div>
                      <button onclick="createNewFolder()" class="flex items-center space-x-2 px-4 py-2 border border-green-500 text-green-600 rounded-lg hover:bg-green-50 transition-colors">
    <i class="fas fa-plus"></i>
    <span>Crear nueva carpeta</span>
</button>
                    </div>
                </div>

                <!-- Carpeta sin título -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="bg-blue-500 text-white w-8 h-8 rounded flex items-center justify-center font-semibold">
                                1
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-800">Carpeta sin título</h3>
                                <i class="fas fa-edit text-gray-400 text-sm cursor-pointer ml-2"></i>
                            </div>
                            <!-- Badges configuration -->
                            <div class="flex flex-wrap gap-1">
                                <span class="badge badge-blue" id="config-color">BN</span>
                                <span class="badge badge-green" id="config-size">A4</span>
                                <span class="badge badge-orange" id="config-weight">80</span>
                                <span class="badge badge-purple" id="config-sides">DC</span>
                                <span class="badge badge-teal" id="config-finishing">IN</span>
                                <span class="badge badge-cyan" id="config-orientation">VE</span>
                                <span class="badge badge-pink" id="config-copies">LL</span>
                                <span class="badge badge-gray" id="config-special">SA</span>
                                <span class="badge badge-yellow" id="config-binding">SP</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <!-- Controls de quantité -->
                            <div class="flex items-center space-x-2 bg-gray-50 rounded-lg px-3 py-2">
                                <i class="fas fa-copy text-blue-500"></i>
                                <span class="text-sm text-gray-600">Copias</span>
                                <button onclick="changeQuantity(-1)" class="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center hover:bg-gray-100">
                                    <i class="fas fa-minus text-xs"></i>
                                </button>
                                <span class="font-semibold px-2" id="quantity-display">1</span>
                                <button onclick="changeQuantity(1)" class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center hover:bg-blue-600">
                                    <i class="fas fa-plus text-xs"></i>
                                </button>
                            </div>
                            <!-- Price -->
                            <div class="text-right">
                                <div class="text-lg font-bold text-gray-800" id="folder-price">0,02 €</div>
                                <div class="text-xs text-gray-500">(IVA incluido)</div>
                            </div>
                            <!-- Actions -->
                            <div class="flex space-x-2">
                                <button class="p-2 text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="p-2 text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-copy"></i>
                                </button>
                                <button class="p-2 text-gray-400 hover:text-red-500">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Table des fichiers -->
                    <div class="overflow-hidden">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pos.</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tamaño</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Páginas</th>
                                </tr>
                            </thead>
                            <tbody id="files-table">
                                <!-- Files will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Botón crear nueva carpeta -->
                <div class="text-center">
                   <button onclick="createNewFolder()" class="inline-flex items-center space-x-2 px-6 py-3 border-2 border-dashed border-blue-300 text-blue-600 rounded-lg hover:bg-blue-50 transition-colors">
    <i class="fas fa-plus"></i>
    <span>Crear nueva carpeta para imprimir</span>
</button>
                </div>

            </div>

            <!-- Colonne droite - Información de pedido -->
            <div class="col-span-4 space-y-6">
                
                <!-- Forma de entrega -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Forma de entrega</h3>
                    
                    <div class="grid grid-cols-3 gap-3 mb-4">
                        <button class="delivery-option active flex flex-col items-center p-3 border-2 border-blue-500 bg-blue-50 rounded-lg text-center">
                            <i class="fas fa-home text-blue-500 text-xl mb-2"></i>
                            <div class="text-sm font-medium">Envío a domicilio</div>
                        </button>
                        <button class="delivery-option flex flex-col items-center p-3 border-2 border-gray-300 rounded-lg text-center hover:border-gray-400">
                            <i class="fas fa-clock text-gray-400 text-xl mb-2"></i>
                            <div class="text-sm font-medium">Punto de recogida</div>
                        </button>
                        <button class="delivery-option flex flex-col items-center p-3 border-2 border-gray-300 rounded-lg text-center hover:border-gray-400">
                            <i class="fas fa-store text-gray-400 text-xl mb-2"></i>
                            <div class="text-sm font-medium">Recoger en tienda</div>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <!-- Dirección de envío -->
                        <div>
                            <h4 class="font-medium text-gray-700 mb-2">Dirección de envío</h4>
                            <div class="text-sm text-gray-500 mb-2">No proporcionada</div>
                            <button class="bg-blue-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-600 transition-colors">
                                Añadir
                            </button>
                        </div>

                        <!-- Envío Low-Cost -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-truck text-blue-500"></i>
                                    <span class="font-medium">Envío <strong>Low-Cost</strong></span>
                                    <span class="text-sm text-gray-500">(+ 1,99 €)</span>
                                </div>
                                <i class="fas fa-chevron-right text-gray-400"></i>
                            </div>
                            <div class="text-sm text-gray-600">¡Te faltan <strong>48,98 €</strong> para el envío gratis!</div>
                            <div class="text-sm text-green-600 mt-1">
                                <strong>Entrega prevista el jueves, 4 de septiembre</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Datos de facturación -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Datos de facturación</h3>
                    <div class="text-sm text-gray-500 mb-2">No proporcionado</div>
                    <button class="bg-blue-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-600 transition-colors">
                        Añadir
                    </button>
                </div>

                <!-- Método de pago -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Método de pago</h3>
                    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-credit-card text-gray-400 text-xl"></i>
                            <div>
                                <div class="font-medium">Pagar con tarjeta</div>
                                <div class="text-sm text-gray-500">Pago seguro cifrado con certif...</div>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <script>
        let cartConfig = {
            copies: 1,
            files: []
        };

        // Récupérer les données du panier depuis sessionStorage
       document.addEventListener('DOMContentLoaded', function() {
    const currentCart = JSON.parse(sessionStorage.getItem('currentCart') || '{"folders": []}');
    
    if (currentCart.folders && currentCart.folders.length > 0) {
        // Mettre à jour le compte de dossiers
        document.getElementById('folder-count').textContent = currentCart.folders.length;
        
        // Afficher tous les dossiers
        displayAllFolders(currentCart.folders);
        
        // Calculer le total général
        updateCartTotal(currentCart.folders);
    }
});



        function updateInterface() {
            // Mettre à jour le nombre de copies
            document.getElementById('quantity-display').textContent = cartConfig.copies;
            
            // Remplir la table des fichiers
            const filesTable = document.getElementById('files-table');
            filesTable.innerHTML = '';
            
            cartConfig.files.forEach((file, index) => {
                const row = document.createElement('tr');
                row.className = 'border-t border-gray-200';
                row.innerHTML = `
                    <td class="px-4 py-3 text-sm text-gray-600">${index + 1}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-file-pdf text-red-500"></i>
                            <div>
                                <div class="font-medium text-gray-800">${file.name}</div>
                                <div class="text-xs text-green-600 bg-green-100 px-2 py-1 rounded-full inline-block">Sin acabado</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">${formatFileSize(file.size)}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">${file.pages || 1}</td>
                `;
                filesTable.appendChild(row);
            });
            
            // Calculer et afficher le prix
            calculateCartPrice();
        }

        function updateConfigBadges(config) {
            if (!config) return;
            
            document.getElementById('config-color').textContent = config.colorMode === 'bw' ? 'BN' : 'CO';
            document.getElementById('config-size').textContent = config.paperSize;
            document.getElementById('config-weight').textContent = config.paperWeight.replace('g', '');
            document.getElementById('config-sides').textContent = config.sides === 'single' ? 'UC' : 'DC';
            document.getElementById('config-orientation').textContent = config.orientation === 'portrait' ? 'VE' : 'HO';
        }

        function changeQuantity(delta) {
            cartConfig.copies = Math.max(1, cartConfig.copies + delta);
            document.getElementById('quantity-display').textContent = cartConfig.copies;
            calculateCartPrice();
        }

        function calculateCartPrice() {
            // Simulation du calcul (utilisez votre logique de pricing)
            const basePrice = 0.02; // Prix de base
            const totalPrice = basePrice * cartConfig.copies;
            
            document.getElementById('folder-price').textContent = totalPrice.toFixed(2) + ' €';
            document.getElementById('cart-total').textContent = totalPrice.toFixed(2) + ' €';
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        function createNewFolder() {
    // Sauvegarder le panier actuel
    const currentCart = {
        folders: [
            {
                id: 1,
                name: 'Carpeta sin título',
                files: cartConfig.files,
                copies: cartConfig.copies,
                price: document.getElementById('folder-price').textContent
            }
        ]
    };
    
    sessionStorage.setItem('currentCart', JSON.stringify(currentCart));
    
    // Rediriger vers index.php pour ajouter plus de documents
    window.location.href = 'index.php?from=cart';
}
function displayAllFolders(folders) {
    const foldersContainer = document.querySelector('.col-span-8');
    
    // Garder le header, supprimer le reste
    const headerSection = foldersContainer.querySelector('.bg-white.rounded-lg.shadow-sm.p-6.mb-6');
    foldersContainer.innerHTML = '';
    foldersContainer.appendChild(headerSection);
    
    // Afficher chaque dossier
    folders.forEach((folder, index) => {
        const folderElement = createFolderElement(folder, index);
        foldersContainer.appendChild(folderElement);
    });
    
    // Ajouter le bouton "Crear nueva carpeta" à la fin
    const createButton = document.createElement('div');
    createButton.className = 'text-center mt-6';
    createButton.innerHTML = `
        <button onclick="createNewFolder()" class="inline-flex items-center space-x-2 px-6 py-3 border-2 border-dashed border-blue-300 text-blue-600 rounded-lg hover:bg-blue-50 transition-colors">
            <i class="fas fa-plus"></i>
            <span>Crear nueva carpeta para imprimir</span>
        </button>
    `;
    foldersContainer.appendChild(createButton);
}

function createFolderElement(folder, index) {
    const folderDiv = document.createElement('div');
    folderDiv.className = 'bg-white rounded-lg shadow-sm p-6 mb-6';
    folderDiv.setAttribute('data-folder-id', folder.id);
    
    folderDiv.innerHTML = `
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-3">
                <div class="bg-blue-500 text-white w-8 h-8 rounded flex items-center justify-center font-semibold">
                    ${folder.id}
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800">${folder.name}</h3>
                    <i class="fas fa-edit text-gray-400 text-sm cursor-pointer ml-2" onclick="editFolderName(${folder.id})"></i>
                </div>
                <!-- Badges configuration -->
                <div class="flex flex-wrap gap-1">
                    ${generateConfigBadges(folder.configuration)}
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <!-- Controls de quantité -->
                <div class="flex items-center space-x-2 bg-gray-50 rounded-lg px-3 py-2">
                    <i class="fas fa-copy text-blue-500"></i>
                    <span class="text-sm text-gray-600">Copias</span>
                    <button onclick="changeFolderQuantity(${folder.id}, -1)" class="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center hover:bg-gray-100">
                        <i class="fas fa-minus text-xs"></i>
                    </button>
                    <span class="font-semibold px-2" id="quantity-${folder.id}">${folder.copies}</span>
                    <button onclick="changeFolderQuantity(${folder.id}, 1)" class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center hover:bg-blue-600">
                        <i class="fas fa-plus text-xs"></i>
                    </button>
                </div>
                <!-- Price -->
                <div class="text-right">
                    <div class="text-lg font-bold text-gray-800" id="price-${folder.id}">${folder.total.toFixed(2)} €</div>
                    <div class="text-xs text-gray-500">(IVA incluido)</div>
                </div>
                <!-- Actions -->
                <div class="flex space-x-2">
                    <button onclick="editFolder(${folder.id})" class="p-2 text-gray-400 hover:text-gray-600" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="duplicateFolder(${folder.id})" class="p-2 text-gray-400 hover:text-gray-600" title="Duplicar">
                        <i class="fas fa-copy"></i>
                    </button>
                    <button onclick="deleteFolder(${folder.id})" class="p-2 text-gray-400 hover:text-red-500" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Table des fichiers -->
        <div class="overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pos.</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tamaño</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Páginas</th>
                    </tr>
                </thead>
                <tbody>
                    ${generateFilesRows(folder.files)}
                </tbody>
            </table>
        </div>
    `;
    
    return folderDiv;
}

function generateConfigBadges(config) {
    if (!config) return '';
    
    return `
        <span class="badge badge-blue">${config.colorMode === 'bw' ? 'BN' : 'CO'}</span>
        <span class="badge badge-green">${config.paperSize}</span>
        <span class="badge badge-orange">${config.paperWeight.replace('g', '')}</span>
        <span class="badge badge-purple">${config.sides === 'single' ? 'UC' : 'DC'}</span>
        <span class="badge badge-teal">${getFinishingCode(config.finishing)}</span>
        <span class="badge badge-cyan">${config.orientation === 'portrait' ? 'VE' : 'HO'}</span>
        <span class="badge badge-pink">${config.copies}</span>
    `;
}

function getFinishingCode(finishing) {
    const codes = {
        'individual': 'IN',
        'grouped': 'AG', 
        'none': 'SA',
        'spiral': 'EN',
        'staple': 'GR',
        'laminated': 'PL',
        'perforated2': 'P2',
        'perforated4': 'P4'
    };
    return codes[finishing] || 'SA';
}

function generateFilesRows(files) {
    return files.map((file, index) => `
        <tr class="border-t border-gray-200">
            <td class="px-4 py-3 text-sm text-gray-600">${index + 1}</td>
            <td class="px-4 py-3">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-file-pdf text-red-500"></i>
                    <div>
                        <div class="font-medium text-gray-800">${file.name}</div>
                        <div class="text-xs text-green-600 bg-green-100 px-2 py-1 rounded-full inline-block">Sin acabado</div>
                    </div>
                </div>
            </td>
            <td class="px-4 py-3 text-sm text-gray-600">${formatFileSize(file.size)}</td>
            <td class="px-4 py-3 text-sm text-gray-600">${file.pages || 1}</td>
        </tr>
    `).join('');
}

// Fonctions d'action sur les dossiers
function changeFolderQuantity(folderId, delta) {
    const currentCart = JSON.parse(sessionStorage.getItem('currentCart') || '{"folders": []}');
    const folder = currentCart.folders.find(f => f.id === folderId);
    
    if (folder) {
        folder.copies = Math.max(1, folder.copies + delta);
        
        // Mettre à jour l'affichage
        document.getElementById(`quantity-${folderId}`).textContent = folder.copies;
        
        // Recalculer le prix
        recalculateFolderPrice(folder);
        
        // Sauvegarder
        sessionStorage.setItem('currentCart', JSON.stringify(currentCart));
        
        // Mettre à jour le total général
        updateCartTotal(currentCart.folders);
    }
}

function editFolderName(folderId) {
    const nameElement = document.querySelector(`[data-folder-id="${folderId}"] h3`);
    const currentName = nameElement.textContent;
    
    const newName = prompt('Nuevo nombre para la carpeta:', currentName);
    if (newName && newName.trim()) {
        nameElement.textContent = newName.trim();
        
        // Sauvegarder dans sessionStorage
        const currentCart = JSON.parse(sessionStorage.getItem('currentCart') || '{"folders": []}');
        const folder = currentCart.folders.find(f => f.id === folderId);
        if (folder) {
            folder.name = newName.trim();
            sessionStorage.setItem('currentCart', JSON.stringify(currentCart));
        }
    }
}

function duplicateFolder(folderId) {
    const currentCart = JSON.parse(sessionStorage.getItem('currentCart') || '{"folders": []}');
    const folderToDuplicate = currentCart.folders.find(f => f.id === folderId);
    
    if (folderToDuplicate) {
        const newFolder = {
            ...folderToDuplicate,
            id: Math.max(...currentCart.folders.map(f => f.id)) + 1,
            name: folderToDuplicate.name + ' (copia)'
        };
        
        currentCart.folders.push(newFolder);
        sessionStorage.setItem('currentCart', JSON.stringify(currentCart));
        
        // Recharger l'affichage
        displayAllFolders(currentCart.folders);
        updateCartTotal(currentCart.folders);
        
        showNotification('Carpeta duplicada correctamente', 'success');
    }
}

function deleteFolder(folderId) {
    if (confirm('¿Estás seguro de que quieres eliminar esta carpeta?')) {
        const currentCart = JSON.parse(sessionStorage.getItem('currentCart') || '{"folders": []}');
        currentCart.folders = currentCart.folders.filter(f => f.id !== folderId);
        
        if (currentCart.folders.length === 0) {
            // Si no hay más carpetas, volver a index
            sessionStorage.removeItem('currentCart');
            window.location.href = 'index.php';
        } else {
            sessionStorage.setItem('currentCart', JSON.stringify(currentCart));
            
            // Recharger l'affichage
            displayAllFolders(currentCart.folders);
            updateCartTotal(currentCart.folders);
        }
        
        showNotification('Carpeta eliminada', 'success');
    }
}

function editFolder(folderId) {
    // Sauvegarder l'ID du dossier à éditer
    sessionStorage.setItem('editingFolderId', folderId);
    
    // Rediriger vers index.php pour modifier la configuration
    window.location.href = 'index.php?edit=' + folderId;
}

function recalculateFolderPrice(folder) {
    // Utiliser la même logique de pricing que dans main.js
    // (simulation pour l'exemple)
    const basePrice = 0.05; // Prix de base par page
    const totalPages = folder.files.reduce((sum, file) => sum + (file.pages || 1), 0);
    folder.total = basePrice * totalPages * folder.copies;
    
    // Mettre à jour l'affichage
    document.getElementById(`price-${folder.id}`).textContent = folder.total.toFixed(2) + ' €';
}

function updateCartTotal(folders) {
    const totalPrice = folders.reduce((sum, folder) => sum + folder.total, 0);
    const totalItems = folders.length;
    
    document.getElementById('cart-total').textContent = totalPrice.toFixed(2) + ' €';
    document.getElementById('cart-count').textContent = totalItems;
    
    // Mettre à jour le compte de dossiers
    document.getElementById('folder-count').textContent = totalItems;
}

function showNotification(message, type = 'info') {
    // Fonction de notification (utiliser la même que dans main.js)
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transform transition-all duration-300`;
    
    const colors = {
        'success': 'bg-green-500 text-white',
        'error': 'bg-red-500 text-white',
        'info': 'bg-blue-500 text-white'
    };
    
    notification.className += ` ${colors[type] || colors.info}`;
    notification.innerHTML = `
        <div class="flex items-center space-x-2">
            <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : 'info'}-circle"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

    </script>

</body>
</html>