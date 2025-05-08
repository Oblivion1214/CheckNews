<?php
include 'config.php';
session_start();

$mensaje = ""; // Para mostrar mensajes de error

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST['Correo'];
    $contrasena = $_POST['Contrasena'];

    // Validar campos vacíos
    if (empty($correo) || empty($contrasena)) {
        $mensaje = "Por favor, complete todos los campos.";
    } else {
        // Consulta para verificar si el correo existe en la base de datos
        $sql = "SELECT * FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            die("Error al preparar la consulta: " . $conn->error);
        }

        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $resultado = $stmt->get_result();

        // Verificar si se encontró un usuario con el correo proporcionado
        if ($resultado->num_rows > 0) {
            $usuario = $resultado->fetch_assoc();

            // Verificar si la contraseña es correcta
            if (password_verify($contrasena, $usuario['password'])) {
                // Iniciar sesión
                $_SESSION['usuarioID'] = $usuario['id'];
                $_SESSION['correo'] = $usuario['email'];
                
                // Redirigir a la página de productos
                header("Location: Principal.php");
                exit;
            } else {
                $mensaje = "Contraseña incorrecta.";
            }
        } else {
            $mensaje = "El usuario o correo no existe.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Check News</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #6296c4 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .login-container {
            position: relative;
            width: 100%;
            max-width: 400px;
            z-index: 1;
        }

        .login-box {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            transform: translateY(0);
            transition: transform 0.5s ease, box-shadow 0.5s ease;
            position: relative;
            overflow: hidden;
        }

        .login-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .login-box::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: rotate(45deg);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% { left: -50%; }
            100% { left: 150%; }
        }

        .logo {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo-circle {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .welcome {
            text-align: center;
            color: #333;
            font-size: 28px;
            margin-bottom: 5px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .input-group {
            position: relative;
            margin-bottom: 25px;
        }

        .input-group input {
            width: 100%;
            padding: 15px 40px 15px 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
            outline: none;
            transition: all 0.3s;
        }

        .input-group input:focus {
            border-color: #667eea;
        }

        .input-group label {
            position: absolute;
            top: 15px;
            left: 15px;
            color: #999;
            font-size: 16px;
            transition: all 0.3s;
            pointer-events: none;
            background: white;
            padding: 0 5px;
        }

        .input-group.focused label,
        .input-group input:valid + label {
            top: -10px;
            left: 10px;
            font-size: 12px;
            color: #667eea;
        }

        .icon {
            position: absolute;
            top: 15px;
            right: 15px;
            color: #999;
        }

        .toggle-password {
            position: absolute;
            top: 15px;
            right: 40px;
            color: #999;
            cursor: pointer;
        }

        .options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .remember {
            display: flex;
            align-items: center;
            color: #666;
            font-size: 14px;
            cursor: pointer;
        }

        .remember input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        .checkmark {
            height: 18px;
            width: 18px;
            border: 2px solid #ddd;
            border-radius: 4px;
            margin-right: 8px;
            position: relative;
        }

        .remember input:checked ~ .checkmark {
            background-color: #667eea;
            border-color: #667eea;
        }

        .checkmark::after {
            content: "";
            position: absolute;
            display: none;
            left: 5px;
            top: 2px;
            width: 4px;
            height: 8px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .remember input:checked ~ .checkmark::after {
            display: block;
        }

        .forgot {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }

        .forgot:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
        }

        .login-btn span {
            position: relative;
            z-index: 1;
            transition: transform 0.3s;
        }

        .login-btn i {
            position: absolute;
            right: 20px;
            opacity: 0;
            transform: translateX(-10px);
            transition: all 0.3s;
        }

        .login-btn:hover {
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }

        .login-btn:hover span {
            transform: translateX(-10px);
        }

        .login-btn:hover i {
            opacity: 1;
            transform: translateX(0);
        }

        .login-btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .login-btn:hover::after {
            opacity: 1;
        }

        .register {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-top: 20px;
        }

        .register a {
            color: #667eea;
            text-decoration: none;
            transition: color 0.3s;
        }

        .register a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .error-message {
            color: #ff3860;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }

        .input-group.error input {
            border-color: #ff3860;
        }

        .input-group.error label {
            color: #ff3860;
        }

        .input-group.error .icon {
            color: #ff3860;
        }

        .decoration {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -1;
            overflow: hidden;
        }

        .circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }

        .circle-1 {
            width: 300px;
            height: 300px;
            top: -100px;
            right: -100px;
            animation: float 6s infinite ease-in-out;
        }

        .circle-2 {
            width: 200px;
            height: 200px;
            bottom: -50px;
            left: -50px;
            animation: float 8s infinite ease-in-out 2s;
        }

        .circle-3 {
            width: 150px;
            height: 150px;
            bottom: 100px;
            right: 50px;
            animation: float 5s infinite ease-in-out 1s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        @media (max-width: 480px) {
            .login-box {
                padding: 30px 20px;
                margin: 0 15px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="form-header">
                <div class="logo">
                    <div class="logo-circle pulse">
                        <i class="fa-solid fa-user"></i>
                    </div>
                </div>
                <h1 class="welcome">Iniciar sesión</h1>
                <p class="subtitle">Ingresa con tu cuenta para acceder</p>
            </div>
            
            <div class="form-scroll-container">
                <?php if ($mensaje != ""): ?>
                    <div style="color: red; margin-bottom: 15px; text-align: center;">
                        <p><?php echo htmlspecialchars($mensaje); ?></p>
                    </div>
                <?php endif; ?>
                
                <form class="login-form" method="POST" action="">
                    <div class="input-group floating">
                        <input type="email" name="Correo" id="Correo" required>
                        <label for="Correo">Correo Electrónico</label>
                        <i class="fas fa-envelope icon"></i>
                        <div class="error-message" id="email-error"></div>
                    </div>

                    <div class="input-group floating">
                        <input type="password" name="Contrasena" id="Contrasena" required>
                        <label for="Contrasena">Contraseña</label>
                        <i class="fas fa-lock icon"></i>
                        <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                        <div class="error-message" id="password-error"></div>
                    </div>

                    <div class="options">
                        <label class="remember">
                            <input type="checkbox" name="remember">
                            <span class="checkmark"></span>
                            Recordarme
                        </label>
                        <a href="forgot-password.php" class="forgot">¿Olvidaste tu contraseña?</a>
                    </div>

                    <button type="submit" class="login-btn">
                        <span>Iniciar sesión</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>

                <div class="register">
                    ¿No tienes cuenta? <a href="register.php">Regístrate</a>
                </div>
            </div>
        </div>
    </div>

    <div class="decoration">
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
        <div class="circle circle-3"></div>
    </div>

    <script>
        // Función para mostrar/ocultar contraseña
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('Contrasena');
            const icon = this;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Efecto de etiquetas flotantes
        document.querySelectorAll('.input-group input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentNode.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentNode.classList.remove('focused');
                }
            });
            
            // Validación en tiempo real
            input.addEventListener('input', function() {
                validateField(this);
            });
        });
        
        // Validación de campos
        function validateField(field) {
            const parent = field.parentNode;
            const errorElement = parent.querySelector('.error-message');
            
            if (field.value.trim() === '') {
                parent.classList.add('error');
                errorElement.textContent = 'Este campo es obligatorio';
                errorElement.style.display = 'block';
            } else {
                parent.classList.remove('error');
                errorElement.style.display = 'none';
                
                // Validación específica para email
                if (field.id === 'Correo' && !isValidEmail(field.value)) {
                    parent.classList.add('error');
                    errorElement.textContent = 'Ingrese un correo electrónico válido';
                    errorElement.style.display = 'block';
                }
            }
        }
        
        // Validar formato de email
        function isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
        
        // Validar formulario antes de enviar
        document.querySelector('.login-form').addEventListener('submit', function(e) {
            let isValid = true;
            
            // Validar todos los campos
            document.querySelectorAll('.input-group input').forEach(input => {
                validateField(input);
                if (input.value.trim() === '' || 
                    (input.id === 'Correo' && !isValidEmail(input.value))) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>