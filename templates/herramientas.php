<?php
include 'config.php';
session_start();

if (!isset($_SESSION['usuarioID'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['usuarioID'];
$sql = "SELECT nombre, apellido_paterno, tipo_usuario FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $nombre_completo = htmlspecialchars($user['nombre'] . ' ' . $user['apellido_paterno']);
    $tipo_usuario = $user['tipo_usuario'];
} else {
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
    <title>Herramientas de Ayuda - Check News</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background-color: #f4f4f9;
        }

        .sidebar {
            width: 20%;
            background-color: #ffffff;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            margin-bottom: 0.5rem;
        }

        .sidebar h2 {
            font-size: 1.5rem;
            color: #333;
            font-weight: 600;
        }

        .sidebar ul {
            list-style: none;
            width: 100%;
            margin-top: 1rem;
        }

        .sidebar ul li {
            margin: 1rem 0;
        }

        .sidebar ul li a {
            text-decoration: none;
            color: #555;
            font-size: 1rem;
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sidebar ul li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
            color: #3498db;
        }

        .sidebar ul li a:hover {
            background-color: #f0f8ff;
            color: #2980b9;
            transform: translateX(5px);
        }

        .sidebar ul li a.active {
            background-color: #e1f0ff;
            color: #2980b9;
            font-weight: 500;
        }

        .content {
            width: 80%;
            padding: 2rem;
        }

        .user-info {
            text-align: right;
            margin-bottom: 2rem;
            font-size: 0.9rem;
            color: #666;
        }

        .user-info a {
            color: #3498db;
            text-decoration: none;
            margin-left: 10px;
            transition: all 0.3s;
        }

        .user-info a:hover {
            text-decoration: underline;
            color: #2980b9;
        }

        .guide-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .guide-container h1 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            font-size: 2rem;
            font-weight: 600;
        }

        .guide-section {
            margin-bottom: 3rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
        }

        .guide-section:last-child {
            border-bottom: none;
        }

        .guide-section h2 {
            color: #3498db;
            margin: 1.5rem 0 1.5rem;
            font-size: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
        }

        .guide-section h2 i {
            margin-right: 10px;
            font-size: 1.3rem;
        }

        .steps-container {
            display: grid;
            gap: 1.5rem;
        }

        .step {
            display: flex;
            background: #f9f9f9;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }

        .step:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .step-number {
            background-color: #3498db;
            color: white;
            min-width: 50px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.3rem;
            font-weight: bold;
        }

        .step-content {
            padding: 1.5rem;
            flex: 1;
        }

        .step-content h3 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .step-content p {
            color: #666;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .tip-box {
            background: linear-gradient(to right, #f8f9fa, #ffffff);
            border-left: 4px solid #3498db;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border-radius: 0 8px 8px 0;
            position: relative;
        }

        .tip-box::before {
            content: '💡';
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 2rem;
            opacity: 0.1;
        }

        .tip-box strong {
            color: #3498db;
            display: block;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .tip-box p {
            color: #555;
            line-height: 1.6;
        }

        .resources-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .resource-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s;
        }

        .resource-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            border-color: #3498db;
        }

        .resource-card h3 {
            color: #3498db;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .resource-card a {
            color: #555;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: color 0.3s;
            font-size: 0.95rem;
        }

        .resource-card a:hover {
            color: #2980b9;
        }

        .resource-card a i {
            margin-right: 8px;
            color: #3498db;
        }

        @media (max-width: 992px) {
            body {
                flex-direction: column;
            }
            
            .sidebar, .content {
                width: 100%;
            }
            
            .sidebar {
                padding: 1.5rem;
                align-items: flex-start;
            }
            
            .guide-container {
                padding: 1.5rem;
            }
        }

        @media (max-width: 576px) {
            .steps-container {
                grid-template-columns: 1fr;
            }
            
            .resources-list {
                grid-template-columns: 1fr;
            }
            
            .step {
                flex-direction: column;
            }
            
            .step-number {
                width: 100%;
                height: 50px;
            }
        }
    </style>
</head>
<body>
    <!-- Barra lateral -->
    <div class="sidebar">
        <div class="logo-container">
            <img src="imag/CheckNews.png" alt="Logo" class="logo">
            <h2>Check News</h2>
        </div>
        <ul>
            <li><a href="Principal.php"><i class="fas fa-home"></i> Inicio</a></li>
            <li><a href="verificados.php"><i class="fas fa-check-circle"></i> Noticias verificadas</a></li>
            <li><a href="herramientas.php" class="active"><i class="fas fa-tools"></i> Herramientas</a></li>
            <li><a href="reportar.php"><i class="fas fa-flag"></i> Reportar noticia</a></li>
            <?php if ($tipo_usuario === 'admin'): ?>
                <li><a href="admin.php"><i class="fas fa-user-shield"></i> Panel Admin</a></li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Contenido principal -->
    <div class="content">
        <div class="user-info">
            Bienvenido, <strong><?php echo $nombre_completo; ?></strong> | 
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a>
        </div>

        <div class="guide-container">
            <h1>Centro de Ayuda y Herramientas</h1>
            
            <div class="guide-section">
                <h2><i class="fas fa-search"></i> Cómo Verificar Noticias</h2>
                
                <div class="steps-container">
                    <div class="step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h3>Examina la fuente</h3>
                            <p>Verifica la URL del sitio web. Muchos sitios falsos imitan direcciones de medios reales con pequeños cambios.</p>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h3>Busca el titular</h3>
                            <p>Copia y pega el titular en un buscador. Si es falso, es probable que encuentres verificaciones de otros medios.</p>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h3>Revisa la fecha</h3>
                            <p>Algunas noticias falsas usan información antigua presentándola como actual.</p>
                        </div>
                    </div>
                </div>
                
                <div class="tip-box">
                    <strong>Consejo profesional</strong>
                    <p>Usa nuestra herramienta de búsqueda en la página principal para verificar si ya hemos analizado esa noticia. Nuestra base de datos se actualiza constantemente con verificaciones de expertos.</p>
                </div>
            </div>
            
            <div class="guide-section">
                <h2><i class="fas fa-flag"></i> Reportar Contenido Dudoso</h2>
                
                <div class="steps-container">
                    <div class="step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h3>Encuentra el contenido</h3>
                            <p>Localiza la noticia que deseas reportar y copia su URL exacta.</p>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h3>Completa el formulario</h3>
                            <p>Ve a la sección "Reportar noticia" y proporciona todos los detalles solicitados.</p>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h3>Describe tus sospechas</h3>
                            <p>Explica claramente por qué crees que la noticia es falsa o engañosa.</p>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h3>Envía el reporte</h3>
                            <p>Nuestro equipo de verificadores analizará tu reporte en menos de 24 horas.</p>
                        </div>
                    </div>
                </div>
                
                <div class="tip-box">
                    <strong>¿Qué ocurre después?</strong>
                    <p>Si confirmamos que es falsa, la agregaremos a nuestra base de datos verificada y podrás recibir actualizaciones sobre este caso si proporcionaste tu correo electrónico.</p>
                </div>
            </div>
            
            <div class="guide-section">
                <h2><i class="fas fa-external-link-alt"></i> Recursos Adicionales</h2>
                <p>Explora estas herramientas profesionales de verificación de hechos:</p>
                
                <div class="resources-list">
                    <div class="resource-card">
                        <h3>Maldita.es</h3>
                        <a href="https://es.maldita.es/" target="_blank">
                            <i class="fas fa-external-link-alt"></i> Visitar sitio web
                        </a>
                    </div>
                    
                    <div class="resource-card">
                        <h3>Chequeado</h3>
                        <a href="https://chequeado.com/" target="_blank">
                            <i class="fas fa-external-link-alt"></i> Visitar sitio web
                        </a>
                    </div>
                    
                    <div class="resource-card">
                        <h3>FactCheck.org</h3>
                        <a href="https://www.factcheck.org/" target="_blank">
                            <i class="fas fa-external-link-alt"></i> Visitar sitio web
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Efectos de hover y animaciones
        document.addEventListener('DOMContentLoaded', function() {
            const steps = document.querySelectorAll('.step');
            steps.forEach((step, index) => {
                setTimeout(() => {
                    step.style.opacity = '1';
                    step.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>