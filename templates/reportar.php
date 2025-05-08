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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportar Noticia - Check News</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background-color: #f4f4f9;
        }

        .sidebar {
            width: 250px;
            background-color: #ffffff;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            padding: 2rem 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: fixed;
            height: 100%;
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
            margin-bottom: 2rem;
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
            padding: 0.8rem;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .sidebar ul li a:hover {
            background-color: #e9f5ff;
            color: #3498db;
        }

        .sidebar ul li a.active {
            background-color: #3498db;
            color: white;
        }

        .content {
            margin-left: 250px;
            width: calc(100% - 250px);
            padding: 2rem;
        }

        .user-info {
            text-align: right;
            margin-bottom: 1.5rem;
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

        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .form-title {
            color: #333;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #3498db;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #444;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border 0.3s;
        }

        .form-control:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            text-align: center;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }
            
            .content {
                margin-left: 0;
                width: 100%;
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
            <li><a href="Principal.php">Inicio</a></li>
            <li><a href="verificados.php">Noticias verificadas</a></li>
            <?php if ($tipo_usuario === 'admin'): ?>
                <li><a href="admin/gestion_reportes.php">Panel Admin</a></li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Contenido principal -->
    <div class="content">
        <div class="user-info">
            Bienvenido, <?php echo $nombre_completo; ?> 
            (<a href="logout.php">Cerrar sesión</a>)
        </div>

        <div class="form-container">
            <h1 class="form-title">Reportar Noticia Dudosa</h1>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="reportar.php">
                <div class="form-group">
                    <label for="titulo" class="form-label">Título de la noticia:</label>
                    <input type="text" id="titulo" name="titulo" class="form-control"
                           value="<?php echo isset($_POST['titulo']) ? $_POST['titulo'] : ''; ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="url" class="form-label">URL de la noticia:</label>
                    <input type="url" id="url" name="url" class="form-control"
                           value="<?php echo isset($_POST['url']) ? $_POST['url'] : ''; ?>" 
                           placeholder="https://ejemplo.com/noticia" required>
                </div>

                <div class="form-group">
                    <label for="categoria" class="form-label">Categoría:</label>
                    <select id="categoria" name="categoria" class="form-control" required>
                        <option value="">Seleccione una categoría</option>
                        <option value="politica" <?php echo (isset($_POST['categoria']) && $_POST['categoria'] == 'politica') ? 'selected' : ''; ?>>Política</option>
                        <option value="economia" <?php echo (isset($_POST['categoria']) && $_POST['categoria'] == 'economia') ? 'selected' : ''; ?>>Economía</option>
                        <option value="salud" <?php echo (isset($_POST['categoria']) && $_POST['categoria'] == 'salud') ? 'selected' : ''; ?>>Salud</option>
                        <option value="tecnologia" <?php echo (isset($_POST['categoria']) && $_POST['categoria'] == 'tecnologia') ? 'selected' : ''; ?>>Tecnología</option>
                        <option value="otros" <?php echo (isset($_POST['categoria']) && $_POST['categoria'] == 'otros') ? 'selected' : ''; ?>>Otros</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="descripcion" class="form-label">¿Por qué crees que esta noticia es falsa o dudosa?</label>
                    <textarea id="descripcion" name="descripcion" class="form-control" required><?php 
                        echo isset($_POST['descripcion']) ? $_POST['descripcion'] : ''; 
                    ?></textarea>
                    <small class="text-muted">Mínimo 20 caracteres. Describe con detalle tus sospechas.</small>
                </div>

                <button type="submit" class="btn btn-primary">Reportar Noticia</button>
            </form>
        </div>
    </div>
</body>
</html>