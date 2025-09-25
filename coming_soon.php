<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Próxima Apertura - Tinta Expres LZ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="assets/img/imprimerie.ico" type="image/x-icon">
    
    <style>
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes textGlow {
            0% { filter: drop-shadow(0 0 5px rgba(255, 107, 53, 0.4)); }
            100% { filter: drop-shadow(0 0 15px rgba(255, 107, 53, 0.8)); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes countdown {
            0% { opacity: 0; transform: scale(0.8); }
            50% { opacity: 1; transform: scale(1.1); }
            100% { opacity: 1; transform: scale(1); }
        }

        .animated-title {
            background: linear-gradient(45deg, #ff6b35, #f7931e, #ff8c42, #ff6b35);
            background-size: 300% 300%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: gradientShift 3s ease-in-out infinite, textGlow 2s ease-in-out infinite alternate;
            text-shadow: 0 0 20px rgba(255, 107, 53, 0.3);
            font-family: 'Arial Black', sans-serif;
            letter-spacing: 1px;
        }

        .floating-icon {
            animation: float 6s ease-in-out infinite;
        }

        .pulse-animation {
            animation: pulse 2s ease-in-out infinite;
        }

        .countdown-animation {
            animation: countdown 1s ease-out;
        }

        .gradient-bg {
            background: linear-gradient(135deg, 
                rgba(255, 107, 53, 0.1) 0%, 
                rgba(247, 147, 30, 0.1) 25%,
                rgba(255, 140, 66, 0.1) 50%,
                rgba(255, 107, 53, 0.1) 75%,
                rgba(247, 147, 30, 0.1) 100%);
            animation: gradientShift 8s ease-in-out infinite;
        }

        .glass-effect {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .countdown-digit {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(255, 107, 53, 0.3);
        }

        .progress-bar {
            background: linear-gradient(90deg, #ff6b35, #f7931e, #ff8c42);
            background-size: 200% 100%;
            animation: gradientShift 2s ease-in-out infinite;
        }
    </style>
</head>
<body class="min-h-screen gradient-bg flex flex-col">
    
    <!-- Header avec logo -->
    <header class="p-6">
        <div class="flex items-center justify-center">
            <div class="flex items-center space-x-4 glass-effect rounded-2xl p-4 shadow-xl">
                <img src="assets/img/1.jpeg" alt="Tinta Expres LZ Logo" class="h-16 w-16 object-contain floating-icon">
                <h1 class="text-3xl font-bold animated-title">
                    Tinta Expres LZ
                </h1>
            </div>
        </div>
    </header>

    <!-- Contenido principal -->
    <main class="flex-1 flex items-center justify-center px-4">
        <div class="max-w-4xl mx-auto text-center">
            
            <!-- Icono principal -->
            <div class="mb-8">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-orange-500 to-red-500 rounded-full shadow-2xl pulse-animation">
                    <i class="fas fa-store text-3xl text-white"></i>
                </div>
            </div>

            <!-- Título principal -->
            <h2 class="text-4xl md:text-6xl font-bold text-gray-800 mb-6">
                ¡Próxima Apertura!
            </h2>

            <!-- Mensaje principal -->
            <div class="glass-effect rounded-3xl p-8 mb-8 shadow-2xl">
                <p class="text-xl md:text-2xl text-gray-700 mb-6 leading-relaxed">
                    Estamos preparando algo <span class="text-orange-500 font-bold">increíble</span> para ti
                </p>
                
                <div class="flex items-center justify-center space-x-2 mb-6">
                    <i class="fas fa-calendar-alt text-orange-500 text-2xl"></i>
                    <span class="text-2xl md:text-3xl font-bold text-gray-800">
                        29 de Septiembre de 2025
                    </span>
                </div>

                <p class="text-lg text-gray-600 mb-8">
                    Nuestra nueva tienda de servicios de impresión profesional abrirá muy pronto.<br>
                    Gracias por su paciencia y comprensión.
                </p>

                <!-- Cuenta regresiva -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Cuenta regresiva:</h3>
                    <div class="grid grid-cols-4 gap-4 max-w-md mx-auto">
                        <div class="countdown-digit p-4 text-center countdown-animation">
                            <div id="days" class="text-2xl font-bold">00</div>
                            <div class="text-xs uppercase tracking-wide">Días</div>
                        </div>
                        <div class="countdown-digit p-4 text-center countdown-animation">
                            <div id="hours" class="text-2xl font-bold">00</div>
                            <div class="text-xs uppercase tracking-wide">Horas</div>
                        </div>
                        <div class="countdown-digit p-4 text-center countdown-animation">
                            <div id="minutes" class="text-2xl font-bold">00</div>
                            <div class="text-xs uppercase tracking-wide">Min</div>
                        </div>
                        <div class="countdown-digit p-4 text-center countdown-animation">
                            <div id="seconds" class="text-2xl font-bold">00</div>
                            <div class="text-xs uppercase tracking-wide">Seg</div>
                        </div>
                    </div>
                </div>

                <!-- Barra de progreso -->
                <div class="mb-6">
                    <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                        <div id="progress" class="progress-bar h-full rounded-full transition-all duration-1000" style="width: 85%"></div>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">Preparación: 85% completada</p>
                </div>
            </div>

            <!-- Servicios que ofreceremos -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="glass-effect rounded-2xl p-6 hover:scale-105 transition-transform duration-300">
                    <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-print text-white text-xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-2">Impresión Digital</h3>
                    <p class="text-gray-600 text-sm">Documentos, fotografías y materiales promocionales</p>
                </div>
                
                <div class="glass-effect rounded-2xl p-6 hover:scale-105 transition-transform duration-300">
                    <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-copy text-white text-xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-2">Fotocopias</h3>
                    <p class="text-gray-600 text-sm">Copias rápidas y económicas en blanco y negro o color</p>
                </div>
                
                <div class="glass-effect rounded-2xl p-6 hover:scale-105 transition-transform duration-300">
                    <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-file-alt text-white text-xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-2">Encuadernación</h3>
                    <p class="text-gray-600 text-sm">Acabados profesionales para sus documentos</p>
                </div>
            </div>

            <!-- Información de contacto -->
            <div class="glass-effect rounded-2xl p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-info-circle text-orange-500 mr-2"></i>
                    Información de Contacto
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-map-marker-alt text-orange-500"></i>
                        <span>Carrer de les Tres Creus, 142<br>08202 Sabadell, Barcelona</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-phone text-orange-500"></i>
                        <span>+34 932 52 05 70</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-envelope text-orange-500"></i>
                        <span>info@tintaexpreslz.com</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-clock text-orange-500"></i>
                        <span>Lun-Vie: 9:00-19:00</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="p-6 text-center">
        <div class="glass-effect rounded-xl p-4 inline-block">
            <p class="text-gray-600">
                © 2025 Tinta Expres LZ. Todos los derechos reservados.
            </p>
        </div>
    </footer>

    <script>
        // Cuenta regresiva
        function updateCountdown() {
            const targetDate = new Date('2025-09-29T09:00:00').getTime();
            const now = new Date().getTime();
            const difference = targetDate - now;

            if (difference > 0) {
                const days = Math.floor(difference / (1000 * 60 * 60 * 24));
                const hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((difference % (1000 * 60)) / 1000);

                document.getElementById('days').textContent = days.toString().padStart(2, '0');
                document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
                document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
                document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
            } else {
                // La fecha ya pasó
                document.getElementById('days').textContent = '00';
                document.getElementById('hours').textContent = '00';
                document.getElementById('minutes').textContent = '00';
                document.getElementById('seconds').textContent = '00';
                
                // Cambiar mensaje
                document.querySelector('h2').textContent = '¡Ya Estamos Abiertos!';
            }
        }

        // Actualizar cada segundo
        updateCountdown();
        setInterval(updateCountdown, 1000);

        // Animación de la barra de progreso
        setTimeout(() => {
            const progressBar = document.getElementById('progress');
            progressBar.style.width = '90%';
        }, 2000);

        // Efectos visuales adicionales
        document.addEventListener('DOMContentLoaded', function() {
            // Añadir efectos de entrada
            const elements = document.querySelectorAll('.glass-effect');
            elements.forEach((el, index) => {
                setTimeout(() => {
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(20px)';
                    el.style.transition = 'all 0.6s ease-out';
                    
                    setTimeout(() => {
                        el.style.opacity = '1';
                        el.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 200);
            });
        });
    </script>
</body>
</html>