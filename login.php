<?php
session_start();

// Usuarios del sistema (Respaldo) - Contraseñas simples
$usuarios_legacy = [
    'admin' => [
        'password' => 'admin123',
        'rol' => 'Administrador',
        'nombre' => 'Administrador'
    ],
    'secretaria' => [
        'password' => 'secretaria123',
        'rol' => 'Secretaria',
        'nombre' => 'Secretaria de Dirección'
    ],
    'profesor' => [
        'password' => 'profesor123',
        'rol' => 'Profesor',
        'nombre' => 'Prof. Juan García'
    ],
    'estudiante' => [
        'password' => 'estudiante123',
        'rol' => 'Estudiante',
        'nombre' => 'Estudiante'
    ],
    'representante' => [
        'password' => 'representante123',
        'rol' => 'Representante',
        'nombre' => 'Representante'
    ],
    'usuario' => [
        'password' => 'usuario123',
        'rol' => 'Usuario',
        'nombre' => 'Usuario'
    ],
    'practicas' => [
        'password' => 'practicas123',
        'rol' => 'Prácticas Profesionales',
        'nombre' => 'Coord. de Pasantías'
    ],
    'neriz' => [
        'password' => 'neriz123',
        'rol' => 'Labor Social',
        'nombre' => 'Neriz Teran'
    ],
    'coord' => [
        'password' => 'coord123',
        'rol' => 'coordinador_academico',
        'nombre' => 'Coord. Académico'
    ],
    'ana_martinez' => ['password' => 'ana11612798', 'rol' => 'Profesor', 'nombre' => 'ana_martinez'],
    'bettsymar' => ['password' => 'bettsymar12542399', 'rol' => 'Profesor', 'nombre' => 'Bettsymar Santos'],
    'marielis' => ['password' => 'marielis10765310', 'rol' => 'Profesor', 'nombre' => 'Marielis Duran'],
    'jose_paez' => ['password' => 'jose15176936', 'rol' => 'Profesor', 'nombre' => 'Jose_Paez']
];

// Si ya está logueado, redirigir al inicio
if (isset($_SESSION['usuario_logueado']) && $_SESSION['usuario_logueado']) {
    $rol = $_SESSION['rol'] ?? '';
    if ($rol === 'estudiante') {
        header('Location: inicio-estudiante.php');
    } elseif ($rol === 'Secretaria') {
        header('Location: inicio-secretaria.php');
    } elseif ($rol === 'Prácticas Profesionales') {
        header('Location: modulos/pasantias.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

$error = "";

// Procesar el formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $login_success = false;
    $user_data = [];

    // 1. Intentar conexión a la Base de Datos
    $host = 'localhost';
    $dbname = 'escuela_tecnica';
    $db_user = 'root';
    $db_pass = '';
    $db_connected = false;

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db_connected = true;
    } catch (PDOException $e) {
        // Conexión fallida. Se usará el respaldo automáticamente.
    }

    if ($db_connected) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = :username AND estado = 'Activo'");
            $stmt->execute(['username' => $usuario]);
            $db_row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verificar contraseña usando password_verify para los hashes de bcrypt en la BD
            if ($db_row && password_verify($password, $db_row['password'])) {
                $login_success = true;
                
                // Mapear los roles de la base de datos a los roles usados en las redirecciones del sistema antiguo
                $rol_mapped = $db_row['rol'];
                $role_map = [
                    'secretaria_direccion' => 'Secretaria',
                    'coordinador_academico' => 'Coordinador Académico',
                    'profesor' => 'Profesor',
                    'practicas_profesionales' => 'Prácticas Profesionales',
                    'labor_social' => 'Labor Social',
                    'estudiante' => 'Estudiante',
                    'representante' => 'Representante'
                ];
                
                if (array_key_exists($rol_mapped, $role_map)) {
                    $rol_mapped = $role_map[$rol_mapped];
                }

                $user_data = [
                    'rol' => $rol_mapped,
                    'nombre' => $db_row['nombre'] . ' ' . $db_row['apellido'],
                    'id' => $db_row['id'],
                    'username' => $db_row['username']
                ];
            }
        } catch (PDOException $e) {
            // Error en consulta, continúa para intentar con el arreglo legacy
        }
    }

    // 2. Si no se pudo loguear con la BD, intentar con el arreglo local (Respaldo)
    if (!$login_success && isset($usuarios_legacy[$usuario]) && $password === $usuarios_legacy[$usuario]['password']) {
        $login_success = true;
        $user_data = [
            'rol' => $usuarios_legacy[$usuario]['rol'],
            'nombre' => $usuarios_legacy[$usuario]['nombre'],
            'id' => 9999 + array_search($usuario, array_keys($usuarios_legacy)), // ID simulado numérico para compatibilidad
            'username' => $usuario
        ];
    }

    // 3. Crear variables de sesión y redirigir
    if ($login_success) {
        // Variables requeridas por el login antiguo
        $_SESSION['usuario_logueado'] = true;
        $_SESSION['usuario'] = $user_data['username'];
        $_SESSION['rol'] = $user_data['rol'];
        $_SESSION['nombre'] = $user_data['nombre'];
        
        // Variables requeridas por el dashboard y módulos modernos (index.php, estudiantes.php)
        $_SESSION['user_id'] = $user_data['id'];
        $_SESSION['username'] = $user_data['username'];
        
        // Redirigir según rol
        if ($user_data['rol'] === 'estudiante') {
            header('Location: inicio-estudiante.php');
        } elseif ($user_data['rol'] === 'Secretaria') {
            header('Location: index.php');
        } elseif ($user_data['rol'] === 'Prácticas Profesionales') {
            header('Location: modulos/pasantias.php');
        } else {
            header('Location: index.php');
        }
        exit;
    } else {
        $error = "Usuario o contraseña incorrectos, o cuenta inactiva.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Escuela Técnica Pedro Garcia Leal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        :root {
            --primary: #0f172a; 
            --primary-light: #1e293b; 
            --accent: #f59e0b; 
            --accent-hover: #d97706;
            --bg: #f8fafc;
            --surface: #ffffff;
            --text-main: #334155;
            --text-muted: #64748b;
        }

        body {
            font-family: 'Inter', sans-serif;
            /* Usamos el color de la navbar del dashboard como fondo para un look premium */
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            -webkit-font-smoothing: antialiased;
        }

        .login-container {
            background: var(--surface);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 400px;
            padding: 40px;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: var(--primary);
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 5px 0;
            letter-spacing: 0.5px;
        }

        .login-header p {
            color: var(--accent);
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: var(--text-main);
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            color: var(--text-main);
            background: var(--bg);
            transition: all 0.3s;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
            background: var(--surface);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 10px;
            font-family: 'Inter', sans-serif;
        }

        .btn-login:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(245, 158, 11, 0.2);
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc2626;
            font-size: 14px;
        }

        .credenciales {
            background: var(--bg);
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin-top: 30px;
            font-size: 13px;
            color: var(--text-muted);
        }

        .credenciales h3 {
            margin: 0 0 10px 0;
            color: var(--primary);
            font-size: 14px;
        }

        .credenciales p {
            margin: 5px 0;
            font-family: monospace;
        }

        .credenciales strong {
            display: inline-block;
            min-width: 100px;
            color: var(--text-main);
        }

        .help-link {
            text-align: center;
            margin-top: 30px;
        }

        .help-link a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .help-link a:hover {
            border-color: var(--accent);
            color: var(--accent);
            background: rgba(245, 158, 11, 0.05);
        }
        
        .db-status {
            text-align: center;
            margin-top: 15px;
            font-size: 11px;
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Escuela Técnica</h1>
            <p>Pedro Garcia Leal</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="usuario">Usuario:</label>
                <input type="text" id="usuario" name="usuario" required>
            </div>

            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn-login">Iniciar Sesión</button>
        </form>

        <div class="credenciales">
            <h3>Credenciales de Respaldo:</h3>
            <p><strong>Administrador:</strong> admin / admin123</p>
            <p><strong>Secretaria:</strong> secretaria / secretaria123</p>
            <p><strong>Pasantías:</strong> practicas / practicas123</p>
            <p><strong>Usuario:</strong> usuario / usuario123</p>
        </div>

        
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M12 16v-4M12 8h.01"></path>
                </svg>
            </a>
        </div>
    </div>
</body>
</html>