<?php
include 'config.php';
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuarioID'])) {
    header("Location: login.php");
    exit();
}

// Obtener información del usuario
$user_id = $_SESSION['usuarioID'];
$sql = "SELECT nombre, apellido_paterno FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $nombre_completo = htmlspecialchars($user['nombre'] . ' ' . $user['apellido_paterno']);
} else {
    // Si no existe el usuario en BD pero tenía sesión, limpiar todo
    session_destroy();
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página Principal - Check News</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background-color: #f8f9fa;
        }

        /* Barra lateral */
        .sidebar {
            width: 20%;
            background-color: #2c3e50;
            color: white;
            padding: 2rem 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-container img {
            width: 90px;
            height: 90px;
            object-fit: cover;
            margin-bottom: 1rem;
            border-radius: 50%;
            border: 3px solid #3498db;
        }

        .sidebar h2 {
            font-size: 1.5rem;
            color: #ecf0f1;
            font-weight: 600;
        }

        .sidebar ul {
            list-style: none;
            width: 100%;
            margin-top: 2rem;
        }

        .sidebar ul li {
            margin: 1rem 0;
        }

        .sidebar ul li a {
            text-decoration: none;
            color: #bdc3c7;
            font-size: 1rem;
            display: block;
            padding: 0.8rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .sidebar ul li a:hover {
            background-color: #34495e;
            color: #ecf0f1;
            transform: translateX(5px);
        }

        /* Contenido principal */
        .menu-contenido {
            width: 80%;
            padding: 2.5rem;
            background-color: #f8f9fa;
        }

        .user-info {
            text-align: right;
            margin-bottom: 2rem;
            font-size: 1rem;
            color: #7f8c8d;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 1rem;
        }

        .user-info .welcome {
            font-weight: 500;
            color: #2c3e50;
        }

        .user-info a {
            color: #3498db;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            border: 1px solid #3498db;
            transition: all 0.3s ease;
        }

        .user-info a:hover {
            background-color: #3498db;
            color: white;
        }

        /* Barra de búsqueda */
        .search-container {
            background-color: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .search-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .search-bar input {
            flex: 1;
            padding: 1rem;
            font-size: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            transition: all 0.3s ease;
            outline: none;
        }

        .search-bar input:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .search-bar button {
            padding: 1rem 2rem;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .search-bar button:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .search-bar button:active {
            transform: translateY(0);
        }

        /* Resultados */
        .verification-result {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-top: 1.5rem;
            display: none;
        }

        .result-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .result-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .result-icon.real {
            background-color: #2ecc71;
        }

        .result-icon.fake {
            background-color: #e74c3c;
        }

        .result-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .result-meta {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .confidence-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            background-color: #f1f1f1;
            color: #7f8c8d;
        }

        .confidence-badge.high {
            background-color: #e8f8f5;
            color: #27ae60;
        }

        .confidence-badge.medium {
            background-color: #fef9e7;
            color: #f39c12;
        }

        .confidence-badge.low {
            background-color: #fdedec;
            color: #e74c3c;
        }

        .result-url {
            color: #3498db;
            text-decoration: none;
            word-break: break-all;
        }

        .result-url:hover {
            text-decoration: underline;
        }

        .extracted-text {
            margin-top: 1.5rem;
        }

        .extracted-text h3 {
            font-size: 1.1rem;
            color: #2c3e50;
            margin-bottom: 0.8rem;
        }

        .text-content {
            max-height: 150px;
            overflow: hidden;
            transition: max-height 0.3s ease;
            position: relative;
        }

        .text-content.expanded {
            max-height: none;
        }

        .text-content::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            background: linear-gradient(to bottom, rgba(255,255,255,0), rgba(255,255,255,1));
            opacity: 1;
            transition: opacity 0.3s ease;
        }

        .text-content.expanded::after {
            opacity: 0;
        }

        .toggle-text {
            color: #3498db;
            cursor: pointer;
            font-weight: 500;
            margin-top: 0.5rem;
            display: inline-block;
        }

        .toggle-text:hover {
            text-decoration: underline;
        }

        /* Animación de carga */
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(52, 152, 219, 0.2);
            border-top-color: #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Barra lateral -->
    <div class="sidebar">
        <div class="logo-container">
            <img src="static/CheckNews.png" alt="Logo">
            <h2>Check News</h2>
        </div>
        <ul>
            <li><a href="#"><i class="fas fa-compass"></i> Explorar</a></li>
            <li><a href="verificados.php"><i class="fas fa-check-circle"></i> Noticias verificadas</a></li>
            <li><a href="herramientas.php"><i class="fas fa-tools"></i> Herramientas de Ayuda</a></li>
            <li><a href="reportar.php"><i class="fas fa-flag"></i> Reportar</a></li>
        </ul>
    </div>
    
    <!-- Contenedor principal -->
    <div class="menu-contenido">
        <!-- Información del usuario -->
        <div class="user-info">
            <span class="welcome">Bienvenido, <?php echo $nombre_completo; ?></span>
            <a href="logout.php">Cerrar sesión</a>
        </div>

        <!-- Barra de búsqueda -->
        <div class="search-container">
            <h2 style="margin-bottom: 1.5rem; color: #2c3e50;">Verificador de Noticias</h2>
            <div class="search-bar">
                <input type="text" id="newsInput" placeholder="Ingrese URL o Texto de la Noticia">
                <button id="verifyButton">
                    <i class="fas fa-search"></i> Verificar
                </button>
            </div>
            <p style="color: #7f8c8d; font-size: 0.9rem;">Pega el enlace de una noticia o escribe directamente el texto que deseas verificar</p>
        </div>

        <!-- Resultados de verificación -->
        <div id="verificationResult" class="verification-result">
            <div class="result-header">
                <div id="resultIcon" class="result-icon">
                    <i class="fas fa-check"></i>
                </div>
                <h2 id="resultTitle" class="result-title">Resultado de la Verificación</h2>
            </div>
            
            <div class="result-meta">
                <div id="confidenceBadge" class="confidence-badge">
                    Confianza: <span id="confidenceValue">0</span>%
                </div>
            </div>
            
            <div id="resultUrlContainer" style="display: none;">
                <p><strong>URL analizada:</strong> <a id="resultUrl" class="result-url" target="_blank"></a></p>
            </div>
            
            <div id="resultTitleContainer" style="display: none;">
                <p><strong>Título:</strong> <span id="resultNewsTitle"></span></p>
            </div>
            
            <div class="extracted-text">
                <h3>Texto analizado</h3>
                <div id="textContent" class="text-content">
                    <p id="extractedText"></p>
                </div>
                <span id="toggleText" class="toggle-text">Mostrar más</span>
            </div>
        </div>

        <!-- Estado de carga -->
        <div id="loadingState" class="loading" style="display: none;">
            <div class="spinner"></div>
        </div>
    </div>

    <!-- Font Awesome para iconos -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

    <script>
        document.getElementById('verifyButton').addEventListener('click', function() {
            const input = document.getElementById('newsInput').value.trim();
            const resultContainer = document.getElementById('verificationResult');
            const loadingState = document.getElementById('loadingState');
            
            if (!input) {
                alert('Por favor ingrese una URL o texto de noticia');
                return;
            }

            // Mostrar carga y ocultar resultados anteriores
            loadingState.style.display = 'flex';
            resultContainer.style.display = 'none';

            // Determinar si es URL o texto
            const isUrl = input.startsWith('http://') || input.startsWith('https://');
            const requestData = {
                Noticias: [{
                    IdNoticia: 1,
                    [isUrl ? 'url' : 'Noticia']: input
                }]
            };

            // Enviar a la API Flask
            fetch('http://localhost:5000/predict', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            })
            .then(response => response.json())
            .then(data => {
                loadingState.style.display = 'none';
                
                if (data.error) {
                    alert(`Error: ${data.error}`);
                    return;
                }

                const result = data.Resultados[0];
                if (result.error) {
                    alert(`Error: ${result.error}`);
                    return;
                }

                // Procesar resultados
                const isFake = result.Prediccion === 0 || result.prediccion === 0;
                const confidence = result.Confianza || result.confianza;
                const extractedText = result.texto_extraido || result.Noticia || '';
                const title = result.Titulo || '';
                const url = result.url || '';
                
                // Actualizar UI
                const resultIcon = document.getElementById('resultIcon');
                const resultTitle = document.getElementById('resultTitle');
                const confidenceValue = document.getElementById('confidenceValue');
                const confidenceBadge = document.getElementById('confidenceBadge');
                const extractedTextElement = document.getElementById('extractedText');
                const resultUrl = document.getElementById('resultUrl');
                const resultNewsTitle = document.getElementById('resultNewsTitle');
                const resultUrlContainer = document.getElementById('resultUrlContainer');
                const resultTitleContainer = document.getElementById('resultTitleContainer');

                // Establecer icono y título según el resultado
                if (isFake) {
                    resultIcon.className = 'result-icon fake';
                    resultIcon.innerHTML = '<i class="fas fa-times"></i>';
                    resultTitle.textContent = 'Noticia Falsa';
                    resultTitle.style.color = '#e74c3c';
                } else {
                    resultIcon.className = 'result-icon real';
                    resultIcon.innerHTML = '<i class="fas fa-check"></i>';
                    resultTitle.textContent = 'Noticia Verdadera';
                    resultTitle.style.color = '#2ecc71';
                }

                // Establecer confianza
                confidenceValue.textContent = confidence;
                
                // Clasificar confianza
                if (confidence >= 70) {
                    confidenceBadge.className = 'confidence-badge high';
                } else if (confidence >= 40) {
                    confidenceBadge.className = 'confidence-badge medium';
                } else {
                    confidenceBadge.className = 'confidence-badge low';
                }

                // Mostrar URL si existe
                if (url) {
                    resultUrl.href = url;
                    resultUrl.textContent = url;
                    resultUrlContainer.style.display = 'block';
                } else {
                    resultUrlContainer.style.display = 'none';
                }

                // Mostrar título si existe
                if (title) {
                    resultNewsTitle.textContent = title;
                    resultTitleContainer.style.display = 'block';
                } else {
                    resultTitleContainer.style.display = 'none';
                }

                // Mostrar texto extraído
                extractedTextElement.textContent = extractedText;
                
                // Mostrar contenedor de resultados
                resultContainer.style.display = 'block';
            })
            .catch(error => {
                loadingState.style.display = 'none';
                alert(`Error al conectar con el servidor: ${error.message}`);
                console.error('Error:', error);
            });
        });

        // Toggle para expandir/contraer texto
        document.getElementById('toggleText').addEventListener('click', function() {
            const textContent = document.getElementById('textContent');
            const toggleText = document.getElementById('toggleText');
            
            textContent.classList.toggle('expanded');
            
            if (textContent.classList.contains('expanded')) {
                toggleText.textContent = 'Mostrar menos';
            } else {
                toggleText.textContent = 'Mostrar más';
            }
        });
    </script>
</body>
</html>