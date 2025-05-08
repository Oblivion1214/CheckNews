<?php
include 'config.php';
session_start();

// Verificar si el usuario está logueado (usando la variable de sesión que establece login.php)
if (!isset($_SESSION['usuarioID'])) {
    header("Location: login.php");
    exit();
}

// Obtener información del usuario desde la base de datos
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
    // Si no encuentra el usuario, cerrar sesión
    session_destroy();
    header("Location: login.php");
    exit();
}

// Consulta para obtener noticias verificadas
$sql_noticias = "SELECT * FROM noticias_verificadas ORDER BY fecha_publicacion DESC";
$result_noticias = $conn->query($sql_noticias);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Noticias Verificadas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
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
        }

        .sidebar ul {
            list-style: none;
            width: 100%;
        }

        .sidebar ul li {
            margin: 1rem 0;
        }

        .sidebar ul li a {
            text-decoration: none;
            color: #555;
            font-size: 1rem;
            display: block;
            padding: 0.5rem 0.8rem;
            border-radius: 8px;
            text-align: center;
        }

        .sidebar ul li a:hover {
            background-color: #f0f0f0;
        }

        .content {
            width: 80%;
            padding: 2rem;
        }

        .user-info {
            text-align: right;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #666;
        }

        .user-info a {
            color: #3498db;
            text-decoration: none;
            margin-left: 10px;
        }

        .user-info a:hover {
            text-decoration: underline;
        }

        .search-container {
            margin-bottom: 2rem;
        }

        .search-container input {
            width: 70%;
            padding: 0.8rem;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-right: 10px;
        }

        .search-container button {
            padding: 0.8rem 1.5rem;
            font-size: 1rem;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .search-container button:hover {
            background-color: #2980b9;
        }

        .filters {
            margin-bottom: 2rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            min-width: 200px;
        }

        .filters label {
            font-size: 0.9rem;
            margin-bottom: 5px;
            color: #555;
        }

        .filters input, .filters select {
            padding: 0.8rem;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 0.9rem;
        }

        .results {
            margin-top: 2rem;
        }

        .results h2 {
            margin-bottom: 1.5rem;
            color: #333;
        }

        .result-item {
            background-color: #fff;
            border: 1px solid #ddd;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .result-item h3 {
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .result-item p {
            font-size: 1rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .result-meta {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        .result-date {
            color: #888;
        }

        .result-category {
            color: #3498db;
            font-weight: bold;
        }

        .verification-true {
            color: #27ae60;
            font-weight: bold;
        }

        .verification-false {
            color: #e74c3c;
            font-weight: bold;
        }

        .no-results {
            text-align: center;
            padding: 2rem;
            color: #666;
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            
            .sidebar, .content {
                width: 100%;
            }
            
            .search-container input {
                width: 100%;
                margin-bottom: 10px;
                margin-right: 0;
            }
            
            .search-container button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Barra lateral de navegación -->
    <div class="sidebar">
        <div class="logo-container">
            <img src="imag/CheckNews.png" alt="Logo" class="logo">
            <h2>Noticias Verificadas</h2>
        </div>
        <ul>
            <li><a href="Principal.php">Inicio</a></li>
            <li><a href="reportar.php">Reportar</a></li>
            <?php if ($tipo_usuario === 'admin'): ?>
                <li><a href="admin.php">Panel Admin</a></li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Contenedor principal -->
    <div class="content">
        <!-- Información del usuario -->
        <div class="user-info">
            Bienvenido, <?php echo $nombre_completo; ?> 
            (<a href="logout.php">Cerrar sesión</a>)
        </div>

        <!-- Formulario de búsqueda -->
        <div class="search-container">
            <h1>Consulta de Noticias Verificadas</h1>
            <form id="search-form" method="GET" action="">
                <input type="text" id="search-input" name="q" placeholder="Ingrese una palabra clave, frase o enlace..." 
                       value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                <button type="submit">Buscar</button>
            </form>
        </div>

        <!-- Filtros -->
        <div class="filters">
            <div class="filter-group">
                <label for="date-filter">Fecha:</label>
                <input type="date" id="date-filter" name="fecha" 
                       value="<?php echo isset($_GET['fecha']) ? htmlspecialchars($_GET['fecha']) : ''; ?>">
            </div>
            
            <div class="filter-group">
                <label for="category-filter">Categoría:</label>
                <select id="category-filter" name="categoria">
                    <option value="all">Todas</option>
                    <option value="politica" <?php echo (isset($_GET['categoria']) && $_GET['categoria'] == 'politica') ? 'selected' : ''; ?>>Política</option>
                    <option value="economia" <?php echo (isset($_GET['categoria']) && $_GET['categoria'] == 'economia') ? 'selected' : ''; ?>>Economía</option>
                    <option value="salud" <?php echo (isset($_GET['categoria']) && $_GET['categoria'] == 'salud') ? 'selected' : ''; ?>>Salud</option>
                    <option value="tecnologia" <?php echo (isset($_GET['categoria']) && $_GET['categoria'] == 'tecnologia') ? 'selected' : ''; ?>>Tecnología</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="result-filter">Veracidad:</label>
                <select id="result-filter" name="veracidad">
                    <option value="all">Todos</option>
                    <option value="verdadero" <?php echo (isset($_GET['veracidad']) && $_GET['veracidad'] == 'verdadero') ? 'selected' : ''; ?>>Verdadero</option>
                    <option value="falso" <?php echo (isset($_GET['veracidad']) && $_GET['veracidad'] == 'falso') ? 'selected' : ''; ?>>Falso</option>
                </select>
            </div>
        </div>

        <!-- Resultados -->
        <div class="results">
            <h2>Resultados encontrados</h2>
            <div id="results-list">
                <?php
                // Construir consulta con filtros
                $sql = "SELECT * FROM noticias_verificadas WHERE 1=1";
                $params = [];
                $types = "";
                
                if (isset($_GET['q']) && !empty($_GET['q'])) {
                    $search = "%" . $_GET['q'] . "%";
                    $sql .= " AND (titulo LIKE ? OR contenido LIKE ? OR fuente LIKE ?)";
                    array_push($params, $search, $search, $search);
                    $types .= "sss";
                }
                
                if (isset($_GET['fecha']) && !empty($_GET['fecha'])) {
                    $sql .= " AND DATE(fecha_publicacion) = ?";
                    array_push($params, $_GET['fecha']);
                    $types .= "s";
                }
                
                if (isset($_GET['categoria']) && $_GET['categoria'] != 'all') {
                    $sql .= " AND categoria = ?";
                    array_push($params, $_GET['categoria']);
                    $types .= "s";
                }
                
                if (isset($_GET['veracidad']) && $_GET['veracidad'] != 'all') {
                    $sql .= " AND veracidad = ?";
                    array_push($params, $_GET['veracidad'] == 'verdadero' ? 1 : 0);
                    $types .= "i";
                }
                
                $sql .= " ORDER BY fecha_publicacion DESC";
                
                // Ejecutar consulta
                $stmt = $conn->prepare($sql);
                if ($params) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo '<div class="result-item">';
                        echo '<h3>' . htmlspecialchars($row['titulo']) . '</h3>';
                        echo '<p>' . htmlspecialchars($row['contenido']) . '</p>';
                        echo '<div class="result-meta">';
                        echo '<span class="result-date">' . date('d/m/Y', strtotime($row['fecha_publicacion'])) . '</span>';
                        echo '<span class="result-category">' . htmlspecialchars(ucfirst($row['categoria'])) . '</span>';
                        echo '<span class="verification-' . ($row['veracidad'] ? 'true">Verdadero' : 'false">Falso') . '</span>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="no-results">No se encontraron noticias con los criterios de búsqueda.</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <script>
        // Función para manejar la búsqueda con filtros
        document.getElementById('search-form').addEventListener('submit', function(e) {
            // Los filtros ya se manejan en el formulario con los campos name
        });
        
        // Mejorar la experiencia de usuario en móviles
        if (window.innerWidth <= 768) {
            document.querySelectorAll('.filter-group select, .filter-group input').forEach(el => {
                el.style.width = '100%';
            });
        }
    </script>
</body>
</html>