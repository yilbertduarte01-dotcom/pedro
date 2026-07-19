<?php
/**
 * Módulo de Profesor - Sistema de Gestión Académica
 * Integra la lógica robusta de consulta y gestión con el diseño moderno.
 */
session_start();

// Simulación de sesión si no se ha iniciado sesión (Para entornos de desarrollo)
if (!isset($_SESSION['usuario_logueado'])) {
    $_SESSION['usuario_logueado'] = true;
    $_SESSION['rol'] = 'Profesor';
    $_SESSION['nombre'] = 'Docente de Prueba';
    $_SESSION['username'] = 'jose_paez'; 
}

$rol_actual = $_SESSION['rol'] ?? 'Profesor';
$nombre_institucion = "Escuela Técnica Pedro Garcia Leal";

// Identificar dinámicamente el identificador del usuario desde múltiples posibles variables de sesión
$identificador_sesion = '';
$posibles_variables = ['username', 'usuario', 'nombre_usuario', 'user', 'id_usuario', 'id', 'cedula', 'email'];
foreach ($posibles_variables as $var) {
    if (!empty($_SESSION[$var])) {
        $identificador_sesion = $_SESSION[$var];
        break;
    }
}

$usuario_profesor = $identificador_sesion;

$error_db = "";
$status_db = "Desconectado";
$pdo = null;

try {
    $host = 'localhost';
    $dbname = 'escuela_tecnica';
    $user = 'root';
    $pass = ''; // Vacío por defecto en Laragon/XAMPP
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $status_db = "Conectado";
} catch(PDOException $e) {
    $error_db = "Fallo de conexión a la BD: " . $e->getMessage();
}

$mis_secciones = [];
$estudiantes_por_seccion = [];
$total_estudiantes = 0;

if ($pdo && !empty($usuario_profesor)) {
    try {
        // 1. Obtener el 'username' REAL de la BD sin importar qué dato guardó la sesión (ID, Cédula, Email, etc.)
        $stmt_u = $pdo->prepare("
            SELECT username 
            FROM usuarios 
            WHERE username = ? 
               OR id = ? 
               OR cedula = ? 
               OR email = ? 
            LIMIT 1
        ");
        
        $id_val = is_numeric($usuario_profesor) ? (int)$usuario_profesor : 0;
        $stmt_u->execute([$usuario_profesor, $id_val, $usuario_profesor, $usuario_profesor]);
        
        $real_username = $stmt_u->fetchColumn();
        
        if ($real_username) {
            $usuario_profesor = $real_username; // Actualizamos con el username exacto que usa asignaciones_docentes
        }

        // 2. Buscar las secciones asignadas por Coordinación a este profesor
        $stmt = $pdo->prepare("
            SELECT seccion 
            FROM asignaciones_docentes 
            WHERE LOWER(TRIM(usuario_profesor)) = LOWER(TRIM(?)) 
            ORDER BY seccion ASC
        ");
        $stmt->execute([$usuario_profesor]);
        $mis_secciones = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $mis_secciones = array_map('trim', $mis_secciones);

        // 3. Obtener los estudiantes reales asociados a esas secciones asignadas
        if (!empty($mis_secciones)) {
            foreach ($mis_secciones as $seccion) {
                // LÓGICA DE CRUCE INTELIGENTE
                $numero_grado = preg_replace('/[^0-9]/', '', $seccion); 
                $letra_seccion = preg_replace('/[^A-Za-z]/', '', $seccion);
                
                if (!empty($numero_grado) && !empty($letra_seccion)) {
                     $stmt_est = $pdo->prepare("
                        SELECT id, cedula, nombre, apellido, email, nivel_academico, seccion
                        FROM estudiantes 
                        WHERE estado = 'Activo' AND (
                            TRIM(seccion) = ? 
                            OR 
                            (TRIM(seccion) = ? AND nivel_academico LIKE ?)
                        )
                        ORDER BY apellido ASC, nombre ASC
                    ");
                    $param_nivel = "%" . $numero_grado . "%";
                    $stmt_est->execute([$seccion, $letra_seccion, $param_nivel]);
                } else {
                    $stmt_est = $pdo->prepare("
                        SELECT id, cedula, nombre, apellido, email, nivel_academico, seccion 
                        FROM estudiantes 
                        WHERE TRIM(seccion) = ? AND estado = 'Activo' 
                        ORDER BY apellido ASC, nombre ASC
                    ");
                    $stmt_est->execute([$seccion]);
                }

                $alumnos_seccion = $stmt_est->fetchAll(PDO::FETCH_ASSOC);
                
                $estudiantes_por_seccion[$seccion] = $alumnos_seccion;
                $total_estudiantes += count($alumnos_seccion);
            }
        }
    } catch (PDOException $e) {
        $error_db = "Error al consultar datos: " . $e->getMessage();
    }
}

if (!isset($_SESSION['evaluaciones_data'])) {
    $_SESSION['evaluaciones_data'] = [];
}

$mensaje_exito = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['guardar_evaluacion'])) {
        $_SESSION['evaluaciones_data'][] = [
            'id' => time(),
            'titulo' => htmlspecialchars($_POST['titulo']),
            'seccion' => htmlspecialchars($_POST['seccion']),
            'tipo' => htmlspecialchars($_POST['tipo']),
            'fecha' => $_POST['fecha'],
            'ponderacion' => htmlspecialchars($_POST['ponderacion']),
            'creado_por' => $usuario_profesor
        ];
        $mensaje_exito = "Evaluación guardada con éxito.";
    } elseif (isset($_POST['actualizar_evaluacion'])) {
        foreach ($_SESSION['evaluaciones_data'] as $k => $ev) {
            if ($ev['id'] == $_POST['id_evaluacion']) {
                $_SESSION['evaluaciones_data'][$k] = array_merge($ev, [
                    'titulo' => htmlspecialchars($_POST['titulo']),
                    'seccion' => htmlspecialchars($_POST['seccion']),
                    'tipo' => htmlspecialchars($_POST['tipo']),
                    'fecha' => $_POST['fecha'],
                    'ponderacion' => htmlspecialchars($_POST['ponderacion'])
                ]);
                $mensaje_exito = "Evaluación actualizada.";
            }
        }
    } elseif (isset($_POST['eliminar_evaluacion'])) {
        $_SESSION['evaluaciones_data'] = array_values(array_filter($_SESSION['evaluaciones_data'], fn($e) => $e['id'] != $_POST['id_evaluacion']));
        $mensaje_exito = "Evaluación eliminada.";
    }
}

$vista_activa = $_GET['seccion'] ?? ($mis_secciones[0] ?? 'evaluaciones');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Docente - <?= $nombre_institucion; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        .fade-in { animation: fadeIn 0.3s ease-in-out; }
        .slide-up { animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { 
            from { transform: translateY(20px); opacity: 0; } 
            to { transform: translateY(0); opacity: 1; } 
        }
        
        /* Estilos para glassmorphism */
        .glass-modal { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 bg-slate-50">

    <aside id="sidebar" class="w-72 bg-slate-900 text-white hidden lg:flex flex-col shadow-2xl z-30 transition-all duration-300 absolute lg:relative h-full">
        <div class="p-6 flex items-center justify-between lg:justify-start gap-4 border-b border-slate-800">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-amber-500 rounded-xl flex items-center justify-center shadow-lg shadow-amber-500/30 text-white shrink-0">
                    <i class="fa-solid fa-school text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-sm font-bold leading-tight uppercase tracking-wide text-slate-300">Escuela Técnica</h1>
                    <p class="text-xs text-amber-400 font-medium">Pedro Garcia Leal</p>
                </div>
            </div>
            <button onclick="toggleSidebar()" class="lg:hidden text-slate-400 hover:text-white">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 mb-2">Panel General</div>
            <a href="../index.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-colors">
                <i class="fa-solid fa-house w-5 text-slate-400"></i> Inicio
            </a>

            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 pt-4 mb-2">Secciones y Canales</div>
            <a href="noticias.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-all">
                <i class="fa-solid fa-bullhorn w-5 text-slate-400"></i> Muro de Noticias
            </a>
            <a href="profesor.php" class="flex items-center gap-3 px-4 py-3 bg-amber-500 text-slate-900 font-bold rounded-xl shadow-md shadow-amber-500/20 transition-all">
                <i class="fa-solid fa-chalkboard-user w-5"></i> Panel Docente
            </a>
        </nav>

        <div class="p-4 border-t border-slate-800 bg-slate-950/50">
            <div class="flex items-center gap-3 px-2">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($usuario_profesor) ?>&background=f59e0b&color=fff" alt="Perfil" class="w-10 h-10 rounded-full border-2 border-slate-700">
                <div class="overflow-hidden">
                    <p class="text-sm font-medium text-white truncate">@<?= htmlspecialchars($usuario_profesor); ?></p>
                    <p class="text-xs text-slate-400"><?= htmlspecialchars($rol_actual); ?></p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Fondo oscuro para móvil -->
    <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/50 z-20 hidden lg:hidden backdrop-blur-sm"></div>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-20 bg-white/80 backdrop-blur-md shadow-sm flex items-center justify-between px-6 lg:px-8 z-10 border-b border-slate-200">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden text-slate-500 hover:text-amber-500 transition-colors text-xl p-2 -ml-2 rounded-lg hover:bg-slate-100">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h2 class="text-xl lg:text-2xl font-bold text-slate-800 leading-tight">Módulo Docente</h2>
                    <p class="text-xs text-slate-500 hidden sm:block">Gestión de estudiantes y calificaciones</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-4 py-2 bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl border border-slate-200 flex items-center gap-2 shadow-sm">
                    <i class="fa-regular fa-calendar text-amber-500"></i> <span class="hidden sm:inline">Hoy: </span><?= date('d/m/Y'); ?>
                </span>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 lg:p-8 w-full">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <a href="../index.php" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-amber-600 transition-colors bg-white px-4 py-2.5 rounded-xl border border-slate-200 shadow-sm hover:shadow">
                        <i class="fa-solid fa-arrow-left"></i> Panel Principal
                    </a>
                </div>

                <?php if ($error_db): ?>
                    <div class="bg-red-50 border border-red-200 p-6 rounded-2xl flex items-start gap-4 slide-up">
                        <i class="fa-solid fa-database text-3xl text-red-500 mt-1"></i>
                        <div>
                            <h3 class="text-lg font-bold text-red-800">Error de Base de Datos</h3>
                            <p class="text-sm text-red-600 mt-1"><?= htmlspecialchars($error_db) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($mensaje_exito): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-xl flex items-center gap-3 shadow-sm slide-up" id="alerta-exito">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-2xl"></i>
                        <span class="text-sm font-medium"><?= htmlspecialchars($mensaje_exito); ?></span>
                        <button onclick="document.getElementById('alerta-exito').style.display='none'" class="ml-auto text-emerald-600 hover:text-emerald-800"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                <?php endif; ?>

                <?php if (empty($mis_secciones)): ?>
                    <div class="bg-white rounded-3xl p-10 shadow-sm border border-slate-200 text-center slide-up flex flex-col items-center justify-center">
                        <div class="w-20 h-20 bg-slate-50 text-slate-300 rounded-full flex items-center justify-center text-4xl mb-4 border border-slate-100 shadow-inner">
                            <i class="fa-solid fa-folder-open"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-700 mb-2">No tienes secciones asignadas</h3>
                        <p class="text-slate-500 max-w-md mx-auto">Actualmente no cuentas con carga horaria o secciones asignadas en el sistema. Por favor, comunícate con el departamento de <strong>Coordinación Académica</strong> para que se te asigne tu carga estudiantil.</p>
                    </div>
                <?php else: ?>
                    
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-2 overflow-x-auto slide-up">
                        <div class="flex gap-2 min-w-max">
                            <a href="?seccion=evaluaciones" class="px-5 py-2.5 rounded-xl font-medium text-sm transition-all flex items-center gap-2 <?= ($vista_activa === 'evaluaciones') ? 'bg-slate-900 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700' ?>">
                                <i class="fa-solid fa-clipboard-check"></i> Mis Evaluaciones
                            </a>
                            <?php foreach ($mis_secciones as $sec): ?>
                                <a href="?seccion=<?= urlencode($sec) ?>" class="px-5 py-2.5 rounded-xl font-medium text-sm transition-all flex items-center gap-2 <?= ($vista_activa === $sec) ? 'bg-slate-900 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700' ?>">
                                    <i class="fa-solid fa-users-rectangle"></i> Sección <?= htmlspecialchars($sec) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if ($vista_activa === 'evaluaciones'): ?>
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden slide-up">
                            <div class="p-6 border-b border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                        <i class="fa-solid fa-list-check text-amber-500"></i> Plan de Evaluaciones
                                    </h3>
                                    <p class="text-sm text-slate-500 mt-1">Actividades programadas para tus secciones</p>
                                </div>
                                <button onclick="document.getElementById('modalEvaluacion').classList.remove('hidden'); document.getElementById('modalEvaluacion').classList.add('flex');" class="bg-amber-500 hover:bg-amber-600 text-white font-medium py-2.5 px-5 rounded-xl transition-colors shadow-md shadow-amber-500/20 flex items-center gap-2 text-sm">
                                    <i class="fa-solid fa-plus"></i> Nueva Evaluación
                                </button>
                            </div>
                            
                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php if (empty($_SESSION['evaluaciones_data'])): ?>
                                    <div class="col-span-full p-10 text-center text-slate-500 border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                                        <i class="fa-solid fa-clipboard-list text-4xl text-slate-300 mb-3 block"></i>
                                        <h3 class="text-lg font-medium text-slate-600">Aún no has planificado ninguna evaluación.</h3>
                                        <p class="text-sm mt-1">Crea tu primera evaluación para comenzar.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($_SESSION['evaluaciones_data'] as $ev): ?>
                                        <div class="bg-white border border-slate-200 rounded-2xl p-6 hover:border-amber-400 hover:shadow-lg transition-all relative group flex flex-col h-full">
                                            <span class="absolute top-6 right-6 text-xs font-bold px-2 py-1 bg-amber-50 text-amber-600 rounded-md border border-amber-200 shadow-sm">
                                                <?= htmlspecialchars($ev['ponderacion']) ?>%
                                            </span>
                                            
                                            <div class="w-12 h-12 bg-slate-50 text-slate-400 group-hover:text-amber-500 rounded-xl flex items-center justify-center text-xl mb-4 transition-colors border border-slate-100">
                                                <i class="fa-solid fa-file-contract"></i>
                                            </div>
                                            
                                            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Sección <?= htmlspecialchars($ev['seccion']) ?></div>
                                            <h4 class="text-lg font-bold text-slate-800 mb-3 leading-tight group-hover:text-amber-600 transition-colors flex-1"><?= htmlspecialchars($ev['titulo']) ?></h4>
                                            
                                            <div class="flex items-center gap-2 text-sm text-slate-500 mb-4 bg-slate-50/50 p-2 rounded-lg border border-slate-100">
                                                <i class="fa-regular fa-calendar text-slate-400"></i> <?= date('d/m/Y', strtotime($ev['fecha'])) ?>
                                            </div>
                                            
                                            <div class="flex items-center justify-between mt-auto pt-4 border-t border-slate-100">
                                                <div class="text-xs font-medium text-slate-600 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">
                                                    <i class="fa-solid fa-tag text-slate-400 mr-1"></i> <?= htmlspecialchars($ev['tipo']) ?>
                                                </div>
                                                <form method="POST" class="flex gap-2" onsubmit="return confirm('¿Eliminar esta evaluación?');">
                                                    <input type="hidden" name="id_evaluacion" value="<?= $ev['id'] ?>">
                                                    <button type="submit" name="eliminar_evaluacion" class="text-red-400 hover:text-red-600 hover:bg-red-50 p-1.5 rounded-lg transition-colors" title="Eliminar">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden slide-up">
                            <div class="p-6 border-b border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                        <i class="fa-solid fa-chalkboard text-amber-500"></i> Listado de Estudiantes - Sección <?= htmlspecialchars($vista_activa) ?>
                                    </h3>
                                    <p class="text-sm text-slate-500 mt-1">Alumnos registrados activamente en esta sección</p>
                                </div>
                                <div class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg shadow-sm text-sm font-semibold text-slate-600 flex items-center gap-2">
                                    <i class="fa-solid fa-user-group text-amber-500"></i> 
                                    <?= count($estudiantes_por_seccion[$vista_activa] ?? []) ?> alumnos
                                </div>
                            </div>
                            
                            <div class="overflow-x-auto">
                                <?php if (empty($estudiantes_por_seccion[$vista_activa])): ?>
                                    <div class="flex flex-col items-center justify-center py-16 text-slate-400 px-4 text-center">
                                        <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4 border border-slate-100">
                                            <i class="fa-regular fa-folder-open text-3xl text-slate-300"></i>
                                        </div>
                                        <h3 class="text-lg font-medium text-slate-600">Sin estudiantes</h3>
                                        <p class="text-sm mt-1 max-w-sm">Esta sección no tiene estudiantes registrados actualmente.</p>
                                    </div>
                                <?php else: ?>
                                    <table class="w-full text-left text-sm whitespace-nowrap">
                                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider text-xs font-semibold">
                                            <tr>
                                                <th class="px-6 py-4">Estudiante</th>
                                                <th class="px-6 py-4">Cédula</th>
                                                <th class="px-6 py-4 hidden md:table-cell">Nivel / BD Info</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            <?php foreach ($estudiantes_por_seccion[$vista_activa] as $est): ?>
                                            <tr class="hover:bg-slate-50/80 transition-colors group">
                                                <td class="px-6 py-4">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-xs border border-amber-100 group-hover:bg-amber-100 transition-colors">
                                                            <?= strtoupper(substr($est['nombre'], 0, 1) . substr($est['apellido'], 0, 1)) ?>
                                                        </div>
                                                        <span class="font-medium text-slate-800"><?= htmlspecialchars($est['apellido'] . ', ' . $est['nombre']) ?></span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-slate-600 font-medium flex items-center gap-2">
                                                    <i class="fa-regular fa-id-card text-slate-300"></i>
                                                    <?= htmlspecialchars($est['cedula']) ?>
                                                </td>
                                                <td class="px-6 py-4 text-slate-500 text-xs hidden md:table-cell">
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                                        <?= htmlspecialchars($est['nivel_academico'] ?? 'N/A') ?>
                                                    </span>
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg font-medium bg-slate-100 text-slate-600 border border-slate-200 ml-1">
                                                        Sec: <?= htmlspecialchars($est['seccion']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

            </div>
            
            <footer class="mt-12 border-t border-slate-200 pt-6 text-center text-sm text-slate-400 pb-8">
                <p>&copy; <?= date('Y'); ?> <?= $nombre_institucion; ?>. Panel Docente.</p>
            </footer>
        </div>
    </main>

    <div id="modalEvaluacion" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 px-4 fade-in">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden glass-modal border border-white/20 slide-up">
            <div class="flex justify-between items-center p-6 border-b border-slate-100 bg-white">
                <h2 class="text-xl font-bold text-slate-800">Programar Evaluación</h2>
                <button onclick="document.getElementById('modalEvaluacion').classList.add('hidden'); document.getElementById('modalEvaluacion').classList.remove('flex');" class="text-slate-400 hover:text-red-500 hover:bg-red-50 w-8 h-8 rounded-lg flex items-center justify-center transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-5 bg-white">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Título de la Actividad</label>
                    <input type="text" name="titulo" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 bg-slate-50 focus:bg-white transition-all shadow-sm" required placeholder="Ej: Examen de Matemáticas">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Sección Destino</label>
                    <select name="seccion" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 bg-slate-50 focus:bg-white transition-all shadow-sm appearance-none" required>
                        <?php foreach($mis_secciones as $sec): ?>
                            <option value="<?= htmlspecialchars($sec) ?>">Sección <?= htmlspecialchars($sec) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Tipo</label>
                        <select name="tipo" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 bg-slate-50 focus:bg-white transition-all shadow-sm appearance-none" required>
                            <option value="Examen">Examen</option>
                            <option value="Taller">Taller</option>
                            <option value="Exposición">Exposición</option>
                            <option value="Proyecto">Proyecto</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Ponderación (%)</label>
                        <input type="number" name="ponderacion" min="1" max="100" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 bg-slate-50 focus:bg-white transition-all shadow-sm" required placeholder="Ej: 20">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Fecha de Ejecución</label>
                    <input type="date" name="fecha" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 bg-slate-50 focus:bg-white transition-all shadow-sm" required>
                </div>
                <button type="submit" name="guardar_evaluacion" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-semibold py-3.5 rounded-xl transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5 mt-4 flex justify-center items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Actividad
                </button>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (sidebar.classList.contains('hidden')) {
                sidebar.classList.remove('hidden');
                sidebar.classList.add('flex');
                overlay.classList.remove('hidden');
            } else {
                sidebar.classList.add('hidden');
                sidebar.classList.remove('flex');
                overlay.classList.add('hidden');
            }
        }
    </script>
</body>
</html>