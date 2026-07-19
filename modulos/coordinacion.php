<?php
session_start();

// Configuración de la base de datos local (Fallback directo para garantizar conexión)
$host = 'localhost';
$dbname = 'escuela_tecnica';
$user = 'root';
$pass = ''; // Vacío para XAMPP/Laragon

$mensaje_exito = "";
$error_db = "";
$status_db = "Desconectado";
$profesores = [];
$secciones_disponibles = [];
$asignaciones = [];

try {
    // Intentamos conectar directamente para evitar problemas de rutas o archivos rotos
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $status_db = "Conectado de manera exitosa";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_asignacion'])) {
        $prof_username = $_POST['prof_username'];
        $secciones_seleccionadas = $_POST['secciones'] ?? [];
        
        // 1. Limpiar las asignaciones anteriores de este profesor
        $stmt = $pdo->prepare("DELETE FROM asignaciones_docentes WHERE usuario_profesor = ?");
        $stmt->execute([$prof_username]);
        
        // 2. Insertar las nuevas selecciones
        if (!empty($secciones_seleccionadas)) {
            $stmt = $pdo->prepare("INSERT INTO asignaciones_docentes (usuario_profesor, seccion) VALUES (?, ?)");
            foreach ($secciones_seleccionadas as $sec) {
                $stmt->execute([$prof_username, $sec]);
            }
        }
        $mensaje_exito = "Las secciones han sido asignadas y guardadas en la base de datos correctamente.";
    }
    
    // Traemos a todos los usuarios con rol 'profesor'
    $stmt = $pdo->query("SELECT username, nombre, apellido, email FROM usuarios WHERE LOWER(rol) = 'profesor' OR LOWER(rol) LIKE '%profesor%'");
    $profesores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Traemos las secciones registradas en tu tabla 'secciones'
    $stmt_sec = $pdo->query("SELECT nombre FROM secciones WHERE estado = 'Activa' ORDER BY nombre ASC");
    $secciones_disponibles = $stmt_sec->fetchAll(PDO::FETCH_COLUMN);
    
    // Si la tabla secciones está vacía, usamos fallback automático con tus secciones para que no quede vacío
    if (empty($secciones_disponibles)) {
        $secciones_disponibles = ['1A', '1B', '2A', '2B', '3A'];
    }
    
    $stmt = $pdo->query("SELECT usuario_profesor, seccion FROM asignaciones_docentes");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $asignaciones[$row['usuario_profesor']][] = $row['seccion'];
    }
    
} catch(PDOException $e) {
    $error_db = "Error de conexión a la BD: " . $e->getMessage();
    $status_db = "Fallo de conexión";
}

// Variables de UI
$nombre_institucion = "Escuela Técnica Pedro Garcia Leal";
$rol_actual = $_SESSION['nombre'] ?? 'Coordinador Académico';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinación Académica - <?= $nombre_institucion; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .slide-up { animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
        @keyframes slideUp { 
            from { transform: translateY(20px); opacity: 0; } 
            to { transform: translateY(0); opacity: 1; } 
        }
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
            
            <a href="coordinacion.php" class="flex items-center gap-3 px-4 py-3 bg-amber-500 text-slate-900 font-bold rounded-xl shadow-md shadow-amber-500/20 transition-colors mt-2">
                <i class="fa-solid fa-sitemap w-5"></i> Coordinación
            </a>

            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 pt-4 mb-2">Otros Módulos</div>
            <a href="profesor.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-all">
                <i class="fa-solid fa-chalkboard-user w-5 text-slate-400"></i> Panel Docente
            </a>
            <a href="labor-social.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-all">
                <i class="fa-solid fa-handshake-angle w-5 text-slate-400"></i> Labor Social
            </a>
        </nav>

        <div class="p-4 border-t border-slate-800 bg-slate-950/50">
            <div class="flex items-center gap-3 px-2">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($rol_actual) ?>&background=f59e0b&color=fff" alt="Perfil" class="w-10 h-10 rounded-full border-2 border-slate-700">
                <div class="overflow-hidden">
                    <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($rol_actual); ?></p>
                    <p class="text-xs text-slate-400">Coordinación</p>
                </div>
            </div>
        </div>
    </aside>

    <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/50 z-20 hidden lg:hidden backdrop-blur-sm"></div>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-20 bg-white/80 backdrop-blur-md shadow-sm flex items-center justify-between px-6 lg:px-8 z-10 border-b border-slate-200">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden text-slate-500 hover:text-amber-500 transition-colors text-xl p-2 -ml-2 rounded-lg hover:bg-slate-100">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h2 class="text-xl lg:text-2xl font-bold text-slate-800 leading-tight">Coordinación Académica</h2>
                    <p class="text-xs text-slate-500 hidden sm:block">Asignación de carga horaria y secciones a docentes</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <!-- Indicador de Salud de Conexión de Base de Datos -->
                <?php if ($status_db === "Conectado de manera exitosa"): ?>
                    <span class="px-3 py-1.5 bg-emerald-100 text-emerald-800 text-xs font-bold rounded-xl border border-emerald-200 flex items-center gap-1.5 shadow-sm">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block animate-pulse"></span> DB Conectada
                    </span>
                <?php else: ?>
                    <span class="px-3 py-1.5 bg-rose-100 text-rose-800 text-xs font-bold rounded-xl border border-rose-200 flex items-center gap-1.5 shadow-sm animate-bounce">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-600 inline-block"></span> DB Desconectada
                    </span>
                <?php endif; ?>
                <span class="px-4 py-2 bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl border border-slate-200 flex items-center gap-2 shadow-sm">
                    <i class="fa-regular fa-calendar text-amber-500"></i> <span class="hidden sm:inline">Hoy: </span><?= date('d/m/Y'); ?>
                </span>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 lg:p-8 w-full">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <div class="flex justify-between items-center">
                    <a href="../index.php" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-amber-600 transition-colors bg-white px-4 py-2.5 rounded-xl border border-slate-200 shadow-sm hover:shadow">
                        <i class="fa-solid fa-arrow-left"></i> Volver al Dashboard
                    </a>
                </div>

                <?php if ($mensaje_exito): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-xl flex items-center gap-3 shadow-sm slide-up" id="alerta-exito">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-2xl"></i>
                        <span class="text-sm font-medium"><?= htmlspecialchars($mensaje_exito); ?></span>
                        <button onclick="document.getElementById('alerta-exito').style.display='none'" class="ml-auto text-emerald-600 hover:text-emerald-800"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                <?php endif; ?>

                <?php if ($error_db): ?>
                    <div class="bg-red-50 border border-red-200 p-6 rounded-2xl flex items-start gap-4 slide-up">
                        <i class="fa-solid fa-database text-3xl text-red-500 mt-1"></i>
                        <div>
                            <h3 class="text-lg font-bold text-red-800">No se pudo conectar a la Base de Datos</h3>
                            <p class="text-sm text-red-600 mt-1"><?= htmlspecialchars($error_db) ?></p>
                            <p class="text-xs text-red-500 mt-3 font-mono bg-red-100/50 p-2 rounded block">Verifica que tu servidor local (MySQL) esté encendido con el nombre de BD 'escuela_tecnica'.</p>
                        </div>
                    </div>
                <?php else: ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 slide-up" style="animation-delay: 0.1s;">
                        <?php foreach ($profesores as $prof): 
                            $username = $prof['username'];
                            $nombre_completo = $prof['nombre'] . ' ' . $prof['apellido'];
                            $asig_actuales = $asignaciones[$username] ?? [];
                        ?>
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:border-amber-400 hover:shadow-md transition-all flex flex-col relative overflow-hidden group">
                            <!-- Decoración estética superior -->
                            <div class="absolute top-0 left-0 w-full h-1 bg-slate-100 group-hover:bg-amber-400 transition-colors"></div>
                            
                            <!-- Información básica del Profesor -->
                            <div class="flex items-start gap-4 mb-5">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($nombre_completo) ?>&background=f1f5f9&color=64748b" alt="Avatar" class="w-14 h-14 rounded-xl border border-slate-200">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-800 leading-tight"><?= htmlspecialchars($nombre_completo) ?></h3>
                                    <p class="text-xs text-slate-500 font-medium mb-1">@<?= htmlspecialchars($username) ?></p>
                                    
                                    <!-- Badges informativos de secciones cargadas en tiempo real -->
                                    <div class="flex flex-wrap gap-1 mt-1.5">
                                        <?php if (empty($asig_actuales)): ?>
                                            <span class="px-2 py-0.5 bg-red-50 text-red-600 text-[10px] font-bold uppercase rounded-md border border-red-100">Sin Secciones</span>
                                        <?php else: ?>
                                            <?php foreach($asig_actuales as $sa): ?>
                                                <span class="px-2 py-0.5 bg-amber-50 text-amber-700 text-[10px] font-bold uppercase rounded-md border border-amber-200 shadow-sm"><?= htmlspecialchars($sa) ?></span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <hr class="border-slate-100 mb-5">

                            <form method="POST" class="flex-1 flex flex-col">
                                <input type="hidden" name="prof_username" value="<?= htmlspecialchars($username) ?>">
                                
                                <div class="flex justify-between items-center mb-3">
                                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Asignar Secciones</h4>
                                    <i class="fa-solid fa-list-check text-slate-300"></i>
                                </div>
                                
                                <!-- Lista interactiva de Secciones -->
                                <div class="grid grid-cols-2 gap-2.5 mb-6 max-h-40 overflow-y-auto pr-2 custom-scrollbar">
                                    <?php foreach ($secciones_disponibles as $sec): ?>
                                        <label class="flex items-center gap-3 p-2.5 rounded-xl border <?= in_array($sec, $asig_actuales) ? 'border-amber-400 bg-amber-50/50' : 'border-slate-200 bg-slate-50 hover:bg-slate-100' ?> cursor-pointer transition-colors group/label">
                                            <input type="checkbox" name="secciones[]" value="<?= htmlspecialchars($sec) ?>" 
                                                   class="w-4 h-4 text-amber-500 rounded border-slate-300 focus:ring-amber-500 focus:ring-offset-0"
                                                   <?= in_array($sec, $asig_actuales) ? 'checked' : '' ?>>
                                            <span class="text-sm font-medium text-slate-700 group-hover/label:text-slate-900"><?= htmlspecialchars($sec) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                
                                <button type="submit" name="guardar_asignacion" class="mt-auto w-full bg-slate-900 hover:bg-slate-800 text-white font-medium py-3 rounded-xl transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5 flex justify-center items-center gap-2 text-sm">
                                    <i class="fa-solid fa-floppy-disk"></i> Guardar Asignación
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
            
            <footer class="mt-12 border-t border-slate-200 pt-6 text-center text-sm text-slate-400 pb-8">
                <p>&copy; <?= date('Y'); ?> <?= $nombre_institucion; ?>. Panel de Administración.</p>
            </footer>
        </div>
    </main>

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