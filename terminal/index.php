<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terminal - Copisteria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">

    <!-- Header Terminal -->
    <header class="bg-blue-600 text-white shadow-lg">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold">Terminal 1 - Entrada Principal</h1>
                <div class="text-sm">Terminal ID: T001</div>
            </div>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="container mx-auto px-6 py-12">
        
        <!-- Título Principal -->
        <div class="text-center mb-12">
            <div class="w-24 h-24 bg-white rounded-full shadow-xl flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-file-upload text-4xl text-blue-600"></i>
            </div>
            <h2 class="text-4xl font-bold text-gray-800 mb-4">Selecciona los documentos a imprimir</h2>
            <p class="text-xl text-gray-600">Sube tus documentos y empieza a imprimir con la mejor calidad al mejor precio</p>
        </div>

        <!-- Opciones de Usuario -->
        <div class="max-w-5xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                
                <!-- Opción Sin Cuenta -->
                <div class="bg-white rounded-3xl shadow-2xl p-10 text-center transform hover:scale-105 transition-all duration-300">
                    <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-8">
                        <i class="fas fa-user-slash text-3xl text-blue-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-6">Continuar sin cuenta</h3>
                    <p class="text-gray-600 mb-8 text-lg leading-relaxed">
                        Sube tus documentos directamente sin necesidad de registrarte
                    </p>
                    <button onclick="startGuestUpload()" class="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-bold py-4 px-8 rounded-xl text-lg transition-all duration-300 shadow-lg hover:shadow-xl">
                        <i class="fas fa-upload mr-3"></i>Subir como Invitado
                    </button>
                </div>

                <!-- Opción Con Cuenta -->
                <div class="bg-white rounded-3xl shadow-2xl p-10 text-center transform hover:scale-105 transition-all duration-300">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-8">
                        <i class="fas fa-user text-3xl text-green-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-6">Tengo una cuenta</h3>
                    <p class="text-gray-600 mb-8 text-lg leading-relaxed">
                        Accede para ver tu historial y configuraciones guardadas
                    </p>
                    <button onclick="openLoginModal()" class="w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-bold py-4 px-8 rounded-xl text-lg transition-all duration-300 shadow-lg hover:shadow-xl">
                        <i class="fas fa-sign-in-alt mr-3"></i>Iniciar Sesión
                    </button>
                </div>
            </div>
        </div>

        <!-- Información Adicional -->
        <div class="max-w-4xl mx-auto mt-16">
            <div class="bg-blue-50 rounded-2xl p-8 text-center">
                <h4 class="text-xl font-semibold text-blue-800 mb-4">
                    <i class="fas fa-info-circle mr-2"></i>Información del Terminal
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-blue-700">
                    <div>
                        <i class="fas fa-clock text-2xl mb-2"></i>
                        <p class="font-semibold">Horario</p>
                        <p class="text-sm">24/7 Disponible</p>
                    </div>
                    <div>
                        <i class="fas fa-file-pdf text-2xl mb-2"></i>
                        <p class="font-semibold">Formatos</p>
                        <p class="text-sm">PDF, DOC, JPG, PNG</p>
                    </div>
                    <div>
                        <i class="fas fa-euro-sign text-2xl mb-2"></i>
                        <p class="font-semibold">Pago</p>
                        <p class="text-sm">Efectivo o Tarjeta</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function startGuestUpload() {
            // Configurar modo invitado
            sessionStorage.setItem('terminal_mode', 'guest');
            sessionStorage.setItem('terminal_info', JSON.stringify({
                id: 'T001',
                name: 'Terminal 1',
                location: 'Entrada Principal'
            }));
            
            // Mostrar mensaje de confirmación
            alert('Modo invitado activado. Redirigiendo al área de subida...');
            
            // Redireccionar (cambiar por la URL real)
            window.location.href = 'cart.php';
        }

        function openLoginModal() {
            // Redireccionar a login
            window.location.href = '../index.php?terminal=T001';
        }
    </script>

</body>
</html>