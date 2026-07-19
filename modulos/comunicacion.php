<?php
session_start();

if (!isset($_SESSION['usuario_logueado']) || !$_SESSION['usuario_logueado']) {
    header('Location: ../login.php');
    exit;
}

require_once '../includes/config.php';
require_once '../includes/db-functions.php';

$nombre_institucion = "Escuela Técnica Pedro Garcia Leal";
$rol_actual = $_SESSION['rol'] ?? 'Usuario';
$usuario_actual = $_SESSION['nombre'] ?? 'Usuario';
$usuario_username = $_SESSION['usuario'] ?? 'usuario';


$id_usuario = crc32($usuario_username) & 0x7fffffff; 

$vista = $_GET['vista'] ?? (($rol_actual === 'Profesor' || $rol_actual === 'Administrador') ? 'agregar' : 'mis_reuniones');
$mensaje = "";
$tipo_mensaje = "";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        
        
        if ($_POST['accion'] === 'crear_reunion_general') {
            $seccion = $conexion->real_escape_string($_POST['seccion'] ?? '');
            $asunto = $conexion->real_escape_string($_POST['asunto'] ?? '');
            $descripcion = $conexion->real_escape_string($_POST['descripcion'] ?? '');
            $fecha_reunion = $conexion->real_escape_string($_POST['fecha_reunion'] ?? '');
            $hora_reunion = $conexion->real_escape_string($_POST['hora_reunion'] ?? '');
            
            if (!empty($seccion) && !empty($asunto) && !empty($fecha_reunion)) {
                $id_usuario_int = (int)$id_usuario;
                $sql = "INSERT INTO reuniones_generales (seccion, asunto, descripcion, fecha_reunion, hora_reunion, creado_por, rol_creador)
                        VALUES ('$seccion', '$asunto', '$descripcion', '$fecha_reunion', '$hora_reunion', $id_usuario_int, '$rol_actual')";
                
                if (@$conexion->query($sql)) {
                    $mensaje = "Reunión creada exitosamente.";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al crear la reunión.";
                    $tipo_mensaje = "error";
                }
            } else {
                $mensaje = "Por favor completa todos los campos requeridos.";
                $tipo_mensaje = "error";
            }
        }
        
        
        elseif ($_POST['accion'] === 'confirmar_asistencia') {
            $id_reunion = (int)$_POST['id_reunion'];
            $id_usuario_int = (int)$id_usuario;
            
            $sql = "INSERT INTO asistencia_reuniones (id_reunion, id_usuario, rol_usuario, confirmado)
                    VALUES ($id_reunion, $id_usuario_int, '$rol_actual', TRUE)
                    ON DUPLICATE KEY UPDATE confirmado = TRUE";
            
            if (@$conexion->query($sql)) {
                $mensaje = "Asistencia confirmada.";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al confirmar asistencia.";
                $tipo_mensaje = "error";
            }
        }
        
        
        elseif ($_POST['accion'] === 'eliminar_reunion') {
            $id_reunion = (int)$_POST['id_reunion'];
            
            @$conexion->query("DELETE FROM asistencia_reuniones WHERE id_reunion = $id_reunion");
            $sql = "DELETE FROM reuniones_generales WHERE id = $id_reunion";
            
            if (@$conexion->query($sql)) {
                $mensaje = "Reunión eliminada.";
                $tipo_mensaje = "success";
            }
        }
    }
}


$mi_seccion = $_SESSION['seccion'] ?? '';
$sql_reuniones = "SELECT * FROM reuniones_generales ";

if ($rol_actual === 'Representante' && !empty($mi_seccion)) {
    $mi_seccion = $conexion->real_escape_string($mi_seccion);
    $sql_reuniones .= "WHERE seccion = '$mi_seccion' ";
}

$sql_reuniones .= "ORDER BY fecha_reunion DESC, hora_reunion DESC";
$resultado_reuniones = @$conexion->query($sql_reuniones);
$reuniones = [];

if ($resultado_reuniones) {
    while ($fila = $resultado_reuniones->fetch_assoc()) {
        $reuniones[] = $fila;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centro de Reuniones - <?= $nombre_institucion; ?></title>
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
        
        .seccion-vista { display: none; }
        .seccion-vista.activa { display: block; animation: fadeIn 0.4s ease; }
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
            <a href="asistencias.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-colors">
                <i class="fa-solid fa-qrcode w-5 text-slate-400"></i> Asistencias
            </a>

            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 pt-4 mb-2">Secciones y Canales</div>
            <a href="noticias.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-all">
                <i class="fa-solid fa-bullhorn w-5 text-slate-400"></i> Muro de Noticias
            </a>
            <a href="profesor.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-all">
                <i class="fa-solid fa-chalkboard-user w-5 text-slate-400"></i> Panel Docente
            </a>
            <a href="representante.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-all">
                <i class="fa-solid fa-user-tie w-5 text-slate-400"></i> Representantes
            </a>
            
            <a href="comunicacion.php" class="flex items-center gap-3 px-4 py-3 bg-amber-500 text-slate-900 font-bold rounded-xl shadow-md shadow-amber-500/20 transition-colors">
                <i class="fa-solid fa-handshake w-5"></i> Reuniones
            </a>
            
            <a href="labor-social.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-all">
                <i class="fa-solid fa-handshake-angle w-5 text-slate-400"></i> Labor Social
            </a>
            
            <a href="pasantias.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-all">
                <i class="fa-solid fa-briefcase w-5 text-slate-400"></i> Pasantías
            </a>
        </nav>

        <div class="p-4 border-t border-slate-800 bg-slate-950/50">
            <div class="flex items-center gap-3 px-2">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($rol_actual) ?>&background=f59e0b&color=fff" alt="Perfil" class="w-10 h-10 rounded-full border-2 border-slate-700">
                <div class="overflow-hidden">
                    <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($rol_actual); ?></p>
                    <p class="text-xs text-slate-400">Sesión Activa</p>
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
                    <h2 class="text-xl lg:text-2xl font-bold text-slate-800 leading-tight">Centro de Reuniones</h2>
                    <p class="text-xs text-slate-500 hidden sm:block">Gestión de reuniones académicas y encuentros institucionales</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <span class="px-4 py-2 bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl border border-slate-200 flex items-center gap-2 shadow-sm">
                    <i class="fa-regular fa-calendar text-amber-500"></i> <span class="hidden sm:inline">Hoy: </span><?= date('d/m/Y'); ?>
                </span>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 lg:p-8 w-full">
            <div class="max-w-6xl mx-auto space-y-6">
                
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <a href="../index.php" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-amber-600 transition-colors bg-white px-4 py-2.5 rounded-xl border border-slate-200 shadow-sm hover:shadow">
                        <i class="fa-solid fa-arrow-left"></i> Volver al Inicio
                    </a>
                </div>

                <?php if ($mensaje): ?>
                    <div class="px-4 py-3 rounded-xl border flex items-center gap-3 fade-in shadow-sm <?php echo ($tipo_mensaje === 'success') ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-red-50 border-red-200 text-red-700'; ?>">
                        <i class="fa-solid <?php echo ($tipo_mensaje === 'success') ? 'fa-circle-check text-emerald-500' : 'fa-circle-exclamation text-red-500'; ?> text-lg"></i>
                        <p class="font-medium text-sm"><?php echo htmlspecialchars($mensaje); ?></p>
                    </div>
                <?php endif; ?>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-2 flex flex-col sm:flex-row gap-2">
                    <?php if ($rol_actual === 'Profesor' || $rol_actual === 'Administrador'): ?>
                        <button onclick="cambiarVista('agregar')" class="tab-btn flex-1 py-3 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 <?= ($vista === 'agregar' || $vista === 'principal') ? 'bg-slate-900 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50' ?>" id="btn-agregar">
                            <i class="fa-solid fa-plus"></i> Agregar Reunión
                        </button>
                    <?php endif; ?>
                    <button onclick="cambiarVista('mis_reuniones')" class="tab-btn flex-1 py-3 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 <?= ($vista === 'mis_reuniones' || ($rol_actual === 'Representante' && $vista !== 'agregar')) ? 'bg-slate-900 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50' ?>" id="btn-mis_reuniones">
                        <i class="fa-solid fa-calendar-days"></i> Mis Reuniones
                    </button>
                </div>

                
                <?php if ($rol_actual === 'Profesor' || $rol_actual === 'Administrador'): ?>
                <div id="agregar" class="seccion-vista <?= ($vista === 'agregar' || $vista === 'principal') ? 'activa' : ''; ?>">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden slide-up p-6 md:p-8">
                        <div class="mb-6">
                            <h2 class="text-xl font-bold text-slate-800">Crear Nueva Reunión</h2>
                            <p class="text-sm text-slate-500 mt-1">Programa reuniones con los representantes de una sección específica.</p>
                        </div>

                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="accion" value="crear_reunion_general">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Sección *</label>
                                    <select name="seccion" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 bg-slate-50 focus:bg-white transition-all shadow-sm" required>
                                        <option value="">-- Selecciona una sección --</option>
                                        <option value="1ro A">1ro A</option>
                                        <option value="1ro B">1ro B</option>
                                        <option value="1ro C">1ro C</option>
                                        <option value="2do A">2do A</option>
                                        <option value="2do B">2do B</option>
                                        <option value="2do C">2do C</option>
                                        <option value="3ro A">3ro A</option>
                                        <option value="3ro B">3ro B</option>
                                        <option value="3ro C">3ro C</option>
                                        <option value="4to A">4to A</option>
                                        <option value="4to B">4to B</option>
                                        <option value="5to A">5to A</option>
                                        <option value="5to B">5to B</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Asunto de la Reunión *</label>
                                    <input type="text" name="asunto" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 bg-slate-50 focus:bg-white transition-all shadow-sm" required placeholder="Ej: Evaluación del Trimestre I">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Fecha *</label>
                                    <input type="date" name="fecha_reunion" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 bg-slate-50 focus:bg-white transition-all shadow-sm" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Hora</label>
                                    <input type="time" name="hora_reunion" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 bg-slate-50 focus:bg-white transition-all shadow-sm">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Descripción / Temas a Tratar *</label>
                                <textarea name="descripcion" rows="4" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 bg-slate-50 focus:bg-white transition-all shadow-sm resize-none" required placeholder="Describe los temas que se tratarán en la reunión, objetivos, etc..."></textarea>
                            </div>

                            <div class="pt-2">
                                <button type="submit" class="w-full md:w-auto bg-amber-500 hover:bg-amber-600 text-white font-semibold py-3 px-8 rounded-xl transition-all shadow-lg hover:shadow-xl hover:shadow-amber-500/20 hover:-translate-y-0.5 flex justify-center items-center gap-2">
                                    <i class="fa-solid fa-paper-plane"></i> Crear Reunión
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                
                <div id="mis_reuniones" class="seccion-vista <?= ($vista === 'mis_reuniones' || ($rol_actual === 'Representante' && $vista !== 'agregar')) ? 'activa' : ''; ?>">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4 mb-6 slide-up">
                        <div>
                            <h3 class="text-xl font-bold text-slate-800"><?php echo ($rol_actual === 'Profesor' || $rol_actual === 'Administrador') ? 'Reuniones Creadas' : 'Reuniones de mi Sección'; ?></h3>
                            <p class="text-sm text-slate-500 mt-1">Listado de encuentros pautados y su estatus</p>
                        </div>
                    </div>

                    <?php if (empty($reuniones)): ?>
                        <div class="flex flex-col items-center justify-center py-16 bg-white rounded-2xl shadow-sm border border-dashed border-slate-300 text-slate-400 slide-up">
                            <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4 border border-slate-100">
                                <i class="fa-regular fa-calendar-xmark text-3xl text-slate-300"></i>
                            </div>
                            <h3 class="text-lg font-medium text-slate-600">No hay reuniones</h3>
                            <p class="text-sm mt-1 max-w-sm text-center">No hay reuniones programadas en este momento para mostrar.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 slide-up" style="animation-delay: 0.1s;">
                            <?php foreach ($reuniones as $reunion): ?>
                                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 border-l-4 border-l-amber-500 hover:shadow-md transition-all flex flex-col relative overflow-hidden group">
                                    
                                    <div class="flex justify-between items-start gap-4 mb-4">
                                        <h3 class="text-lg font-bold text-slate-800 leading-tight group-hover:text-amber-600 transition-colors"><?php echo htmlspecialchars($reunion['asunto']); ?></h3>
                                        <span class="inline-flex items-center justify-center bg-amber-50 text-amber-600 font-bold text-xs px-2.5 py-1 rounded-lg border border-amber-100 shrink-0">
                                            Sección: <?php echo htmlspecialchars($reunion['seccion']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="flex flex-wrap gap-3 text-xs font-semibold text-slate-600 mb-4 bg-slate-50 p-3 rounded-xl border border-slate-100">
                                        <div class="flex items-center gap-1.5">
                                            <i class="fa-regular fa-calendar text-amber-500"></i>
                                            <?php echo date('d/m/Y', strtotime($reunion['fecha_reunion'])); ?>
                                        </div>
                                        <?php if (!empty($reunion['hora_reunion'])): ?>
                                        <div class="flex items-center gap-1.5 border-l border-slate-300 pl-3">
                                            <i class="fa-regular fa-clock text-amber-500"></i>
                                            <?php echo htmlspecialchars($reunion['hora_reunion']); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <p class="text-sm text-slate-600 mb-6 flex-1"><?php echo nl2br(htmlspecialchars($reunion['descripcion'])); ?></p>
                                    
                                    <div class="mt-auto pt-4 border-t border-slate-100 flex gap-3">
                                        <?php if ($rol_actual === 'Representante'): ?>
                                            <form method="POST" class="w-full">
                                                <input type="hidden" name="accion" value="confirmar_asistencia">
                                                <input type="hidden" name="id_reunion" value="<?php echo $reunion['id']; ?>">
                                                <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-semibold py-2.5 rounded-xl transition-all shadow-md hover:shadow-lg text-sm flex items-center justify-center gap-2">
                                                    <i class="fa-solid fa-check"></i> Confirmar Asistencia
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" class="w-full">
                                                <input type="hidden" name="accion" value="eliminar_reunion">
                                                <input type="hidden" name="id_reunion" value="<?php echo $reunion['id']; ?>">
                                                <button type="submit" onclick="return confirm('¿Estás seguro de que deseas eliminar esta reunión?');" class="w-full bg-white hover:bg-red-50 text-red-600 hover:text-red-700 border border-red-200 font-semibold py-2.5 rounded-xl transition-all shadow-sm text-sm flex items-center justify-center gap-2">
                                                    <i class="fa-regular fa-trash-can"></i> Eliminar Reunión
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
            
            <footer class="mt-12 border-t border-slate-200 pt-6 text-center text-sm text-slate-400 pb-8">
                <p>&copy; <?= date('Y'); ?> <?= $nombre_institucion; ?>. Diseñado para la excelencia.</p>
            </footer>
        </div>
    </main>

    <script>
        
        function cambiarVista(vistaId) {
            
            const url = new URL(window.location);
            url.searchParams.set('vista', vistaId);
            window.history.pushState({}, '', url);

            
            document.querySelectorAll('.seccion-vista').forEach(el => el.classList.remove('activa'));
            
            
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.className = 'tab-btn flex-1 py-3 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 text-slate-500 hover:bg-slate-50';
            });

           
            const seccionActiva = document.getElementById(vistaId);
            if(seccionActiva) seccionActiva.classList.add('activa');
            
            
            const botonActivo = document.getElementById('btn-' + vistaId);
            if(botonActivo) botonActivo.className = 'tab-btn flex-1 py-3 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 bg-slate-900 text-white shadow-md';
        }

        
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