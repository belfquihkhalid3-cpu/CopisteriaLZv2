<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modal Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(2px);
        }
        
        .modal-content {
            animation: modalSlideIn 0.3s ease-out;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .input-group {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 18px;
        }
        
        .input-field {
            padding-left: 50px;
        }
        
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            cursor: pointer;
            font-size: 18px;
        }
        
        .password-toggle:hover {
            color: #6b7280;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

    <!-- Bouton pour ouvrir le modal -->
    <button onclick="openRegisterModal()" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-colors">
        Abrir Modal Registro
    </button>

    <!-- Modal Overlay -->
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
                        <a href="#" onclick="openLoginModal(); closeRegisterModal();" class="text-blue-600 hover:underline font-medium">Inicia sesión</a>
                    </div>
                    
                </form>
            </div>
        </div>
    </div>

    <script>
        // Funciones para abrir/cerrar modal
        function openRegisterModal() {
            document.getElementById('registerModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeRegisterModal() {
            document.getElementById('registerModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        // Cerrar modal si clic en overlay
        document.getElementById('registerModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRegisterModal();
            }
        });

        // Cerrar modal avec Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeRegisterModal();
            }
        });

        // Toggle password visibility
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        }

        // Gérer l'envoi du formulaire
        function handleRegister(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const data = {
                full_name: formData.get('full_name'),
                email: formData.get('email'),
                password: formData.get('password')
            };
            
            // Validation basique
            if (data.password.length < 6) {
                showNotification('La contraseña debe tener al menos 6 caracteres', 'error');
                return;
            }
            
            // Simulation d'envoi (remplacez par votre appel AJAX)
            console.log('Datos de registro:', data);
            
            // Exemple d'appel AJAX
            /*
            fetch('api/register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showNotification('Cuenta creada con éxito', 'success');
                    closeRegisterModal();
                } else {
                    showNotification(result.error || 'Error al crear la cuenta', 'error');
                }
            })
            .catch(error => {
                showNotification('Error de conexión', 'error');
            });
            */
            
            // Pour la démo
            showNotification('Cuenta creada con éxito (demo)', 'success');
            setTimeout(() => closeRegisterModal(), 2000);
        }

        // Fonction pour modal login (placeholder)
        function openLoginModal() {
            alert('Modal de login a implementar');
        }

        // Système de notifications
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-[60] p-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full`;
            
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
            
            // Animer l'entrée
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            // Supprimer après 4 secondes
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 4000);
        }
    </script>

</body>
</html>