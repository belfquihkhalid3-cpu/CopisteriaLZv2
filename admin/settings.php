<?php
session_start();
require_once 'auth.php';
require_once '../includes/security_headers.php';
requireAdmin();

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

$admin = getAdminUser();

// Récupérer tous les prix actuels
$current_pricing = fetchAll("
    SELECT * FROM pricing 
    WHERE is_active = 1 
    ORDER BY paper_size, paper_weight, color_mode
");

// Organiser par structure
$pricing_grid = [];
foreach ($current_pricing as $price) {
    $pricing_grid[$price['paper_size']][$price['paper_weight']][$price['color_mode']] = [
        'id' => $price['id'],
        'price' => $price['price_per_page'],
        'valid_from' => $price['valid_from']
    ];
}

// Récupérer coûts de finition
$finishing_costs = fetchAll("SELECT * FROM finishing_costs WHERE is_active = 1 ORDER BY service_type, service_name");

$message = '';
$message_type = '';

// Traitement des mises à jour
if ($_POST) {
    if (isset($_POST['update_pricing'])) {
        $message = updatePricing($_POST);
        $message_type = strpos($message, 'Error') === false ? 'success' : 'error';
    } elseif (isset($_POST['update_finishing'])) {
        $message = updateFinishingCosts($_POST);
        $message_type = strpos($message, 'Error') === false ? 'success' : 'error';
    }
    
    // Recharger les données
    header('Location: settings.php?msg=' . urlencode($message) . '&type=' . $message_type);
    exit();
}

// Afficher message si présent
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $message_type = $_GET['type'] ?? 'info';
}

function updatePricing($data) {
    try {
        $updated_count = 0;
        
        foreach ($data as $key => $value) {
            if (strpos($key, 'price_') === 0) {
                $parts = explode('_', $key);
                if (count($parts) === 4) {
                    $paper_size = $parts[1];
                    $paper_weight = $parts[2]; 
                    $color_mode = $parts[3];
                    $new_price = floatval($value);
                    
                    // UPDATE DIRECT sans transaction
                    $sql = "UPDATE pricing SET price_per_page = ? WHERE paper_size = ? AND paper_weight = ? AND color_mode = ?";
                    $stmt = executeQuery($sql, [$new_price, $paper_size, $paper_weight, $color_mode]);
                    
                    if ($stmt && $stmt->rowCount() > 0) {
                        $updated_count++;
                    }
                }
            }
        }
        
        return "Actualizados: $updated_count cambios";
        
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

function updateFinishingCosts($data) {
    try {
        beginTransaction();
        
        $updated_count = 0;
        
        foreach ($data as $key => $value) {
            if (strpos($key, 'finishing_') === 0) {
                $service_id = str_replace('finishing_', '', $key);
                $new_cost = floatval($value);
                
                if ($new_cost >= 0) {
                    $stmt = executeQuery("UPDATE finishing_costs SET cost = ? WHERE id = ?", [$new_cost, $service_id]);
                    if ($stmt && $stmt->rowCount() > 0) {
                        $updated_count++;
                    }
                }
            }
        }
        
        commit();
        return "Costos de acabado actualizados ($updated_count cambios)";
        
    } catch (Exception $e) {
        rollback();
        return "Error al actualizar acabados: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Admin Copisteria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
      <style>
        /* Estilo para el indicador de la barra de navegación activa */
    .nav-active { position: relative; }
.nav-active::before { 
    content: ''; 
    position: absolute; 
    left: 0; 
    top: 50%; 
    transform: translateY(-50%); 
    height: 60%; 
    width: 4px; 
    background-color: white; 
    border-radius: 0 4px 4px 0; 
}
    </style>
</head>
<body class="bg-gray-100">

    <!-- Include Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-64 min-h-screen">
        
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">Configuración del Sistema</h1>
                <div class="flex items-center space-x-4">
                    <button onclick="backupPricing()" class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600 transition-colors">
                        <i class="fas fa-save mr-2"></i>Backup Precios
                    </button>
                    <button onclick="resetToDefaults()" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors">
                        <i class="fas fa-undo mr-2"></i>Restaurar Defaults
                    </button>
                </div>
            </div>
        </header>

        <div class="p-6">
            
            <!-- Message d'alerte -->
            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?= $message_type === 'success' ? 'bg-green-100 border border-green-200 text-green-700' : 'bg-red-100 border border-red-200 text-red-700' ?>">
                <div class="flex items-center">
                    <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?> mr-3"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Precios de Impresión -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">
                        <i class="fas fa-euro-sign text-green-500 mr-3"></i>
                        Precios de Impresión
                    </h2>
                    <div class="text-sm text-gray-500">
                        Precios por página • Última actualización: <?= date('d/m/Y H:i') ?>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Formato</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Peso</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">B/N (€/página)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Color (€/página)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Diferencia</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php
                                $paper_sizes = ['A3', 'A4', 'A5'];
                                $paper_weights = ['80g', '160g', '280g'];
                                
                                foreach ($paper_sizes as $size):
                                    foreach ($paper_weights as $weight):
                                        $bw_price = $pricing_grid[$size][$weight]['BW']['price'] ?? 0;
                                        $color_price = $pricing_grid[$size][$weight]['COLOR']['price'] ?? 0;
                                        $difference = $color_price - $bw_price;
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                                                <?= $size ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-sm font-medium">
                                            <?= $weight ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">€</span>
                                            <input 
                                                type="number" 
                                                name="price_<?= $size ?>_<?= $weight ?>_BW"
                                                value="<?= number_format($bw_price, 4) ?>"
                                                step="0.0001"
                                                min="0"
                                                max="10"
                                                class="pl-8 w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-center"
                                                onchange="calculateDifference(this)"
                                            >
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">€</span>
                                            <input 
                                                type="number" 
                                                name="price_<?= $size ?>_<?= $weight ?>_COLOR"
                                                value="<?= number_format($color_price, 4) ?>"
                                                step="0.0001"
                                                min="0"
                                                max="10"
                                                class="pl-8 w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-center"
                                                onchange="calculateDifference(this)"
                                            >
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-center">
                                            <span class="difference-display font-medium <?= $difference > 0 ? 'text-red-600' : 'text-gray-500' ?>">
                                                +€<?= number_format($difference, 4) ?>
                                            </span>
                                            <div class="text-xs text-gray-500">
                                                <?= $bw_price > 0 ? round(($difference / $bw_price) * 100) : 0 ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    endforeach;
                                endforeach; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-6 flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-2"></i>
                            Los cambios se aplicarán inmediatamente para nuevos pedidos
                        </div>
                        <button type="submit" name="update_pricing" class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-colors font-medium">
                            <i class="fas fa-save mr-2"></i>Guardar Cambios de Precios
                        </button>
                    </div>
                </form>
            </div>

            <!-- Costos de Acabado -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">
                        <i class="fas fa-tools text-purple-500 mr-3"></i>
                        Costos de Acabado
                    </h2>
                    <button onclick="addNewFinishing()" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors text-sm">
                        <i class="fas fa-plus mr-2"></i>Nuevo Acabado
                    </button>
                </div>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <?php foreach ($finishing_costs as $cost): ?>
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h4 class="font-medium text-gray-800"><?= htmlspecialchars($cost['service_name']) ?></h4>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($cost['service_type']) ?></div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                        <?= htmlspecialchars($cost['cost_type']) ?>
                                    </span>
                                    <button type="button" onclick="deleteFinishing(<?= $cost['id'] ?>)" class="text-red-500 hover:text-red-700">
                                        <i class="fas fa-trash text-sm"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">€</span>
                                <input 
                                    type="number" 
                                    name="finishing_<?= $cost['id'] ?>"
                                    value="<?= number_format($cost['cost'], 2) ?>"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    class="pl-8 w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                                >
                            </div>
                            
                            <div class="mt-2 text-xs text-gray-500">
                                <?php
                                $service_descriptions = [
                                    'SPIRAL' => 'Encuadernación en espiral',
                                    'STAPLE' => 'Grapado en esquina superior',
                                    'THERMAL' => 'Encuadernación térmica',
                                    'LAMINATING' => 'Plastificado protector',
                                    'PERFORATION_2' => 'Perforación 2 agujeros',
                                    'PERFORATION_4' => 'Perforación 4 agujeros'
                                ];
                                echo $service_descriptions[$cost['service_name']] ?? 'Servicio de acabado';
                                ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                    </div>
                    
                    <div class="mt-6 flex justify-between">
                        <div class="text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-2"></i>
                            Costos aplicados por documento o trabajo según el tipo
                        </div>
                        <button type="submit" name="update_finishing" class="bg-purple-500 text-white px-6 py-3 rounded-lg hover:bg-purple-600 transition-colors font-medium">
                            <i class="fas fa-save mr-2"></i>Guardar Costos de Acabado
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <script>
        // Calculer différence en temps réel
        function calculateDifference(input) {
            const row = input.closest('tr');
            const bwInput = row.querySelector('input[name*="_BW"]');
            const colorInput = row.querySelector('input[name*="_COLOR"]');
            const differenceDisplay = row.querySelector('.difference-display');
            
            if (bwInput && colorInput && differenceDisplay) {
                const bwPrice = parseFloat(bwInput.value) || 0;
                const colorPrice = parseFloat(colorInput.value) || 0;
                const difference = colorPrice - bwPrice;
                const percentage = bwPrice > 0 ? Math.round((difference / bwPrice) * 100) : 0;
                
                differenceDisplay.innerHTML = `+€${difference.toFixed(4)}`;
                differenceDisplay.className = `difference-display font-medium ${difference > 0 ? 'text-red-600' : 'text-gray-500'}`;
                
                const percentageDisplay = differenceDisplay.nextElementSibling.querySelector('div');
                if (percentageDisplay) {
                    percentageDisplay.textContent = `${percentage}%`;
                }
            }
        }

        // Validations en temps réel
        document.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('input', function() {
                const value = parseFloat(this.value);
                
                // Validation prix
                if (this.name.includes('price_')) {
                    if (value < 0 || value > 10) {
                        this.classList.add('border-red-500');
                        this.classList.remove('border-gray-300');
                    } else {
                        this.classList.remove('border-red-500');
                        this.classList.add('border-gray-300');
                    }
                }
                
                // Marquer comme modifié
                this.classList.add('bg-yellow-50');
                setTimeout(() => {
                    this.classList.remove('bg-yellow-50');
                }, 2000);
            });
        });

        // Fonctions utilitaires
        function backupPricing() {
            if (confirm('¿Crear backup de los precios actuales?')) {
                window.location.href = 'backup-pricing.php';
            }
        }

        function resetToDefaults() {
            if (confirm('¿Restaurar precios por defecto? Esta acción no se puede deshacer.')) {
                window.location.href = 'reset-pricing.php';
            }
        }

        function deleteFinishing(id) {
            if (confirm('¿Eliminar este tipo de acabado?')) {
                window.location.href = `delete-finishing.php?id=${id}`;
            }
        }

        function addNewFinishing() {
            // Modal pour ajouter nouveau type de finition
            const modal = document.createElement('div');
            modal.innerHTML = `
                <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
                    <div class="bg-white rounded-lg p-6 w-96">
                        <h3 class="text-lg font-semibold mb-4">Nuevo Tipo de Acabado</h3>
                        <form id="newFinishingForm">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Servicio</label>
                                    <input type="text" name="service_name" required class="w-full px-3 py-2 border rounded-lg">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                                    <select name="service_type" class="w-full px-3 py-2 border rounded-lg">
                                        <option value="BINDING">Encuadernación</option>
                                        <option value="LAMINATING">Plastificado</option>
                                        <option value="PERFORATION">Perforación</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Costo (€)</label>
                                    <input type="number" name="cost" step="0.01" min="0" required class="w-full px-3 py-2 border rounded-lg">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Costo</label>
                                    <select name="cost_type" class="w-full px-3 py-2 border rounded-lg">
                                        <option value="FIXED">Fijo por trabajo</option>
                                        <option value="PER_PAGE">Por página</option>
                                        <option value="PER_DOCUMENT">Por documento</option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex space-x-3 mt-6">
                                <button type="submit" class="flex-1 bg-green-500 text-white py-2 rounded-lg hover:bg-green-600">
                                    Crear
                                </button>
                                <button type="button" onclick="this.closest('.fixed').remove()" class="flex-1 bg-gray-300 text-gray-700 py-2 rounded-lg hover:bg-gray-400">
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        // Highlight changes
        let originalValues = {};
        document.querySelectorAll('input[type="number"]').forEach(input => {
            originalValues[input.name] = input.value;
            
            input.addEventListener('change', function() {
                if (this.value !== originalValues[this.name]) {
                    this.style.borderColor = '#f59e0b';
                    this.style.backgroundColor = '#fef3c7';
                } else {
                    this.style.borderColor = '#d1d5db';
                    this.style.backgroundColor = 'white';
                }
            });
        });
    </script>

</body>
</html>