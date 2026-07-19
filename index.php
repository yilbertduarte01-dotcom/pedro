<?php
session_start();

if (!isset($_SESSION['usuario_logueado']) || !$_SESSION['usuario_logueado']) {
    header('Location: login.php');
    exit;
}

$rol = $_SESSION['rol'] ?? '';
$nombre = $_SESSION['nombre'] ?? 'Usuario';

$modulos = [
    ['nombre' => 'Profesores', 'link' => 'modulos/profesor.php', 'icon' => 'M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z', 'roles' => ['Administrador', 'Profesor', 'Labor Social']],
    ['nombre' => 'Estudiantes', 'link' => 'modulos/gestion-estudiantes.php', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'roles' => ['Administrador', 'Secretaria', 'coordinador_academico']],
    ['nombre' => 'Representantes', 'link' => 'modulos/representante.php', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', 'roles' => ['Representante', 'Administrador']],
    ['nombre' => 'Coordinación', 'link' => 'modulos/coordinacion.php', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'roles' => ['coordinador_academico', 'coordinador_académico']],
    ['nombre' => 'Pasantías', 'link' => 'modulos/pasantias.php', 'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'roles' => ['Administrador', 'Secretaria', 'Prácticas Profesionales']],
    ['nombre' => 'Labor Social', 'link' => 'modulos/labor-social.php', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'roles' => ['Administrador', 'Labor Social']],
    ['nombre' => 'Mi Informacion.', 'link' => 'inicio-estudiante.php', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'roles' => ['Estudiante']],
    ['nombre' => 'Noticias', 'link' => 'modulos/noticias.php', 'icon' => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z', 'roles' => 'todos'],
    ['nombre' => 'Chatbot', 'link' => 'modulos/chatbot.php', 'icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z', 'roles' => 'todos'],
    ['nombre' => 'Planillas', 'link' => 'modulos/generar-planilla.php', 'icon' => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z', 'roles' => ['Administrador', 'Secretaria']],
    ['nombre' => 'Usuarios', 'link' => 'modulos/usuarios.php', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'roles' => ['Administrador', "coordinador_academico"]]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Premium - ET Pedro Garcia Leal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0f172a; /* Slate 900 */
            --primary-light: #1e293b; 
            --accent: #f59e0b; /* Amber 500 */
            --accent-hover: #d97706;
            --bg: #f8fafc;
            --surface: #ffffff;
            --text-main: #334155;
            --text-muted: #64748b;
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg); 
            margin: 0; 
            color: var(--text-main); 
            -webkit-font-smoothing: antialiased;
        }

        
        .navbar { 
            background: rgba(15, 23, 42, 0.95); 
            backdrop-filter: blur(10px);
            color: white; 
            padding: 1rem 2rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand { display: flex; flex-direction: column; }
        .navbar-brand h1 { margin: 0; font-size: 1.25rem; font-weight: 700; letter-spacing: 0.5px; }
        .navbar-brand span { font-size: 0.8rem; color: var(--accent); font-weight: 600; text-transform: uppercase; letter-spacing: 1px;}
        
        .user-profile { display: flex; align-items: center; gap: 15px; }
        .avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--accent); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem; color: white;}
        .user-info p { margin: 0; font-size: 0.9rem; }
        .user-info .role { font-size: 0.75rem; color: #94a3b8; }
        
        .btn-logout { 
            background: transparent; border: 1px solid rgba(255,255,255,0.2); color: white; 
            padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; 
            transition: all 0.2s; 
        }
        .btn-logout:hover { background: rgba(255,255,255,0.1); border-color: white; }

       
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        
        .page-header { margin-bottom: 40px; }
        .page-header h2 { font-size: 2rem; color: var(--primary); margin-bottom: 8px; }
        .page-header p { color: var(--text-muted); font-size: 1.1rem; margin: 0; }

       
        .modules-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); 
            gap: 24px; 
        }
        
        .module-card { 
            background: var(--surface); 
            border-radius: 16px; 
            padding: 24px; 
            text-decoration: none; 
            color: var(--text-main); 
            display: flex; 
            flex-direction: column; 
            align-items: flex-start;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

       
        .module-card::before {
            content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%;
            background: var(--accent); transform: scaleY(0); transition: transform 0.3s ease;
            transform-origin: bottom;
        }
        
        .module-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 15px 30px -5px rgba(0,0,0,0.1); 
            border-color: transparent;
        }
        
        .module-card:hover::before { transform: scaleY(1); transform-origin: top; }
        
        .module-card:hover .icon-wrapper { background: var(--primary); color: white; }

        .icon-wrapper { 
            width: 56px; height: 56px; border-radius: 12px; background: #f1f5f9; 
            color: var(--primary); display: flex; align-items: center; justify-content: center; 
            margin-bottom: 16px; transition: all 0.3s ease;
        }
        
        .icon-wrapper svg { width: 28px; height: 28px; }
        
        .module-card h3 { margin: 0 0 8px 0; font-size: 1.1rem; font-weight: 600; color: var(--primary); }
        .module-card p { margin: 0; font-size: 0.85rem; color: var(--text-muted); line-height: 1.4; }
        
        .arrow-icon { position: absolute; bottom: 24px; right: 24px; color: #cbd5e1; transition: all 0.3s; opacity: 0; transform: translateX(-10px);}
        .module-card:hover .arrow-icon { opacity: 1; transform: translateX(0); color: var(--accent); }

    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">
            <h1>Sistema de Gestión Escolar</h1>
            <span>E.T. Pedro Garcia Leal</span>
        </div>
        <div class="user-profile">
            <div class="user-info" style="text-align: right;">
                <p><strong><?php echo htmlspecialchars($nombre); ?></strong></p>
                <p class="role"><?php echo htmlspecialchars($rol); ?></p>
            </div>
            <div class="avatar"><?php echo strtoupper(substr($nombre, 0, 1)); ?></div>
            <a href="cerrar-sesion.php" class="btn-logout">Salir</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h2>Panel Principal</h2>
            <p>Seleccione el módulo al que desea acceder.</p>
        </div>

        <div class="modules-grid">
            <?php foreach ($modulos as $m): ?>
                <?php 
                    $puede_ver = ($m['roles'] === 'todos') || in_array($rol, (array)$m['roles']);
                    if ($puede_ver): 
                ?>
                    <a href="<?php echo $m['link']; ?>" class="module-card">
                        <div class="icon-wrapper">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $m['icon']; ?>"></path></svg>
                        </div>
                        <h3><?php echo $m['nombre']; ?></h3>
                        <p>Acceder a las herramientas y configuraciones del módulo.</p>
                        
                        <!-- Icono de flecha que aparece al hacer hover -->
                        <svg class="arrow-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>