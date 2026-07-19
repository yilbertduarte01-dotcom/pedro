<?php
session_start();
if (!isset($_SESSION['usuario_logueado']) || !$_SESSION['usuario_logueado']) {
    header('Location: ../login.php');
    exit;
}

// -------------------------------------------------------------
// CONEXIÓN A BASE DE DATOS (Laragon / MySQL)
// -------------------------------------------------------------
$host = 'localhost';
$dbname = 'escuela_tecnica';
$username_db = 'root'; // Usuario por defecto en Laragon
$password_db = '';     // Contraseña por defecto vacía en Laragon

$estudiantes_db = [];
try {
    // Crear conexión PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Extraer estudiantes de la base de datos
    $stmt = $pdo->query("SELECT id, cedula, nombre, apellido, seccion FROM estudiantes WHERE estado = 'Activo' ORDER BY nombre ASC");
    $estudiantes_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Si falla la conexión, mostramos un error en el log pero dejamos que la página cargue
    error_log("Error de conexión a BD: " . $e->getMessage());
}
// -------------------------------------------------------------

// Variables globales para la UI
$rol_actual = $_SESSION['rol'] ?? 'Administrador';
$nombre_institucion = "Escuela Técnica Pedro Garcia Leal";
$modulo_actual = "Labor Social";
$año_actual = date("Y");
$vista = $_GET['vista'] ?? 'registros';

// Usaremos un archivo JSON local para que los datos NO se borren al cerrar el sistema
$archivo_datos = __DIR__ . '/labor_social_data.json';

// Datos por defecto si el archivo no existe
$datos_por_defecto = [
    'organizaciones' => [
        ['id' => 1, 'nombre' => 'Hospital Central', 'direccion' => 'Avenida Principal, Centro Médico', 'icono' => 'fa-solid fa-hospital', 'desc' => 'Centro de salud local. Apoyo en áreas administrativas.'],
        ['id' => 2, 'nombre' => 'Comunidad Plata 2', 'direccion' => 'Sector Plata 2, Cancha Múltiple', 'icono' => 'fa-solid fa-people-roof', 'desc' => 'Atención social y censo comunitario.'],
        ['id' => 3, 'nombre' => 'Refugio La Protectora', 'direccion' => 'Zona Rural, Finca Los Pinos', 'icono' => 'fa-solid fa-paw', 'desc' => 'Refugio de animales. Limpieza y cuidados.'],
        ['id' => 4, 'nombre' => 'Actividades E.T.P.G.L.', 'direccion' => 'Sede Principal del Liceo', 'icono' => 'fa-solid fa-school', 'desc' => 'Apoyo institucional, limpieza y mantenimiento.']
    ],
    'registros_labor' => []
];

// Crear archivo si no existe
if (!file_exists($archivo_datos)) {
    file_put_contents($archivo_datos, json_encode($datos_por_defecto, JSON_PRETTY_PRINT));
}

// Cargar los datos persistentes
$db_data = json_decode(file_get_contents($archivo_datos), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // GESTIÓN DE ORGANIZACIONES (Crear o Editar)
    if (isset($_POST['accion_org'])) {
        if ($_POST['accion_org'] === 'crear') {
            $db_data['organizaciones'][] = [
                'id' => time(),
                'nombre' => htmlspecialchars($_POST['nombre']),
                'direccion' => htmlspecialchars($_POST['direccion']),
                'desc' => htmlspecialchars($_POST['desc']),
                'icono' => $_POST['icono'] // Se guarda el icono seleccionado
            ];
        } elseif ($_POST['accion_org'] === 'editar') {
            foreach ($db_data['organizaciones'] as &$org) {
                if ($org['id'] == $_POST['org_id']) {
                    $org['nombre'] = htmlspecialchars($_POST['nombre']);
                    $org['direccion'] = htmlspecialchars($_POST['direccion']);
                    $org['desc'] = htmlspecialchars($_POST['desc']);
                    $org['icono'] = $_POST['icono'];
                    break;
                }
            }
        }
        file_put_contents($archivo_datos, json_encode($db_data, JSON_PRETTY_PRINT));
        header("Location: " . $_SERVER['PHP_SELF'] . "?vista=empresas");
        exit;
    }

    // ELIMINAR ORGANIZACIÓN
    if (isset($_POST['eliminar_org'])) {
        $id_eliminar = $_POST['id_eliminar'];
        $db_data['organizaciones'] = array_filter($db_data['organizaciones'], function($org) use ($id_eliminar) {
            return $org['id'] != $id_eliminar;
        });
        // Reindexar array tras eliminar
        $db_data['organizaciones'] = array_values($db_data['organizaciones']);
        file_put_contents($archivo_datos, json_encode($db_data, JSON_PRETTY_PRINT));
        header("Location: " . $_SERVER['PHP_SELF'] . "?vista=empresas");
        exit;
    }

    // NUEVO REGISTRO DE LABOR SOCIAL
    if (isset($_POST['nuevo_registro'])) {
        $db_data['registros_labor'][] = [
            'id' => time(),
            'estudiante' => htmlspecialchars($_POST['estudiante']),
            'org_id' => $_POST['org_id'],
            'horas' => $_POST['horas'],
            'fecha_inicio' => $_POST['fecha_inicio'],
            'fecha_fin' => $_POST['fecha_fin'],
            'estado' => $_POST['estado']
        ];
        file_put_contents($archivo_datos, json_encode($db_data, JSON_PRETTY_PRINT));
        header("Location: " . $_SERVER['PHP_SELF'] . "?vista=registros");
        exit;
    }
}

// Variables listas para usarse en el HTML
$organizaciones = $db_data['organizaciones'];
$registros = $db_data['registros_labor'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $modulo_actual; ?> - <?= $nombre_institucion; ?></title>
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
            
            <a href="labor-social.php" class="flex items-center gap-3 px-4 py-3 bg-amber-500 text-slate-900 font-bold rounded-xl shadow-md shadow-amber-500/20 transition-colors">
                <i class="fa-solid fa-handshake-angle w-5"></i> Labor Social
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

    <!-- Fondo oscuro para móvil cuando el sidebar está abierto -->
    <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/50 z-20 hidden lg:hidden backdrop-blur-sm"></div>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-20 bg-white/80 backdrop-blur-md shadow-sm flex items-center justify-between px-6 lg:px-8 z-10 border-b border-slate-200">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden text-slate-500 hover:text-amber-500 transition-colors text-xl p-2 -ml-2 rounded-lg hover:bg-slate-100">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h2 class="text-xl lg:text-2xl font-bold text-slate-800 leading-tight">Módulo de <?= $modulo_actual; ?></h2>
                    <p class="text-xs text-slate-500 hidden sm:block">Gestión de actividades de impacto comunitario</p>
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
                        <i class="fa-solid fa-arrow-left"></i> Panel Principal
                    </a>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-2 flex flex-col sm:flex-row gap-2">
                    <button onclick="cambiarVista('registros')" class="tab-btn flex-1 py-3 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 <?= ($vista === 'registros') ? 'bg-slate-900 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50' ?>" id="btn-registros">
                        <i class="fa-solid fa-folder-open"></i> Registros Estudiantiles
                    </button>
                    <button onclick="cambiarVista('empresas')" class="tab-btn flex-1 py-3 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 <?= ($vista === 'empresas') ? 'bg-slate-900 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50' ?>" id="btn-empresas">
                        <i class="fa-solid fa-building-ngo"></i> Instituciones Aliadas
                    </button>
                </div>

                <div id="registros" class="seccion-vista <?= ($vista === 'registros') ? 'activa' : ''; ?>">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden slide-up">
                        <div class="p-6 border-b border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div>
                                <h3 class="text-lg font-bold text-slate-800">Estudiantes Asignados</h3>
                                <p class="text-sm text-slate-500">Listado de alumnos cumpliendo horas de labor social</p>
                            </div>
                            <button onclick="abrirModal('modalRegistro')" class="w-full sm:w-auto bg-amber-500 hover:bg-amber-600 text-white font-medium py-2.5 px-5 rounded-xl transition-colors shadow-md shadow-amber-500/20 flex items-center justify-center gap-2 text-sm">
                                <i class="fa-solid fa-plus"></i> Nuevo Registro
                            </button>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <?php if (empty($registros)): ?>
                                <div class="flex flex-col items-center justify-center py-16 text-slate-400 px-4 text-center">
                                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4 border border-slate-100">
                                        <i class="fa-solid fa-folder-open text-3xl text-slate-300"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-slate-600">No hay registros activos</h3>
                                    <p class="text-sm mt-1 max-w-sm">Actualmente no tienes estudiantes en proceso cargados en el sistema.</p>
                                </div>
                            <?php else: ?>
                                <table class="w-full text-left text-sm whitespace-nowrap">
                                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider text-xs font-semibold">
                                        <tr>
                                            <th class="px-6 py-4">Estudiante</th>
                                            <th class="px-6 py-4">Institución Asignada</th>
                                            <th class="px-6 py-4">Horas</th>
                                            <th class="px-6 py-4">Período</th>
                                            <th class="px-6 py-4 text-center">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <?php foreach ($registros as $reg): ?>
                                        <tr class="hover:bg-slate-50/80 transition-colors group">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-xs border border-amber-100 group-hover:bg-amber-100 transition-colors">
                                                        <?= strtoupper(substr($reg['estudiante'], 0, 1)) ?>
                                                    </div>
                                                    <span class="font-medium text-slate-800"><?= htmlspecialchars($reg['estudiante']) ?></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-slate-600 font-medium">
                                                <?php 
                                                    $nombre_org = "Desconocida (Eliminada)";
                                                    foreach($organizaciones as $org) {
                                                        if($org['id'] == $reg['org_id']) $nombre_org = $org['nombre'];
                                                    }
                                                ?>
                                                <div class="flex items-center gap-2">
                                                    <i class="fa-solid fa-building text-slate-300"></i>
                                                    <?= htmlspecialchars($nombre_org); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-slate-600">
                                                <div class="flex items-center gap-1.5 font-medium bg-slate-100 px-2.5 py-1 rounded-lg w-fit">
                                                    <i class="fa-regular fa-clock text-slate-400"></i> <?= htmlspecialchars($reg['horas']) ?> h
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-slate-500 text-xs">
                                                <?= date('d/m/Y', strtotime($reg['fecha_inicio'])) ?> <br> <span class="text-slate-300">al</span> <?= date('d/m/Y', strtotime($reg['fecha_fin'])) ?>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <?php if($reg['estado'] == 'Finalizado'): ?>
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-sm"></div> Finalizado
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                        <div class="w-1.5 h-1.5 rounded-full bg-amber-500 shadow-sm animate-pulse"></div> En Progreso
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div id="empresas" class="seccion-vista <?= ($vista === 'empresas') ? 'activa' : ''; ?>">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4 mb-6 slide-up">
                        <div>
                            <h3 class="text-xl font-bold text-slate-800">Instituciones y Comunidades</h3>
                            <p class="text-sm text-slate-500 mt-1">Catálogo de entidades que reciben estudiantes</p>
                        </div>
                        <button onclick="abrirModalCrearOrg()" class="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white font-medium py-2.5 px-5 rounded-xl transition-colors shadow-md flex items-center justify-center gap-2 text-sm">
                            <i class="fa-solid fa-plus"></i> Nueva Organización
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 slide-up" style="animation-delay: 0.1s;">
                        <?php foreach ($organizaciones as $org): ?>
                            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:border-amber-400 hover:shadow-lg transition-all cursor-pointer group flex flex-col items-center text-center relative overflow-hidden" onclick='verDetallesOrg(<?= json_encode($org) ?>)'>
                                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-amber-400 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                
                                <div class="w-16 h-16 bg-slate-50 text-slate-600 group-hover:text-white group-hover:bg-amber-500 rounded-2xl flex items-center justify-center text-2xl mb-4 transition-all duration-300 border border-slate-100 shadow-sm group-hover:shadow-amber-500/30">
                                    <i class="<?= htmlspecialchars($org['icono']) ?>"></i>
                                </div>
                                <h3 class="text-lg font-bold text-slate-800 mb-2 leading-tight group-hover:text-amber-600 transition-colors"><?= htmlspecialchars($org['nombre']); ?></h3>
                                <p class="text-sm text-slate-500 line-clamp-2 flex-1"><?= htmlspecialchars($org['desc']); ?></p>
                                
                                <div class="mt-5 w-full pt-4 border-t border-slate-100 text-xs font-semibold text-slate-400 group-hover:text-amber-500 transition-colors flex items-center justify-center gap-2">
                                    <span>Ver detalles completos</span> <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if(empty($organizaciones)): ?>
                            <div class="col-span-full flex flex-col items-center justify-center py-16 bg-white rounded-2xl border border-dashed border-slate-300 text-slate-400">
                                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                    <i class="fa-solid fa-building-circle-xmark text-3xl text-slate-300"></i>
                                </div>
                                <h3 class="text-lg font-medium text-slate-600">Sin instituciones</h3>
                                <p class="text-sm mt-1">No hay instituciones aliadas registradas aún.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
            
            <footer class="mt-12 border-t border-slate-200 pt-6 text-center text-sm text-slate-400 pb-8">
                <p>&copy; <?= $año_actual; ?> <?= $nombre_institucion; ?>. Diseñado para la excelencia.</p>
            </footer>
        </div>
    </main>

    <!-- Modal Organización -->
    <div id="modalOrg" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 fade-in px-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md slide-up overflow-hidden glass-modal border border-white/20">
            <div class="flex justify-between items-center p-6 border-b border-slate-100 bg-white">
                <h2 id="modalOrg_title" class="text-xl font-bold text-slate-800">Registrar Organización</h2>
                <button onclick="cerrarModal('modalOrg')" class="text-slate-400 hover:text-red-500 hover:bg-red-50 w-8 h-8 rounded-lg flex items-center justify-center transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-5 bg-white">
                <input type="hidden" name="accion_org" id="form_accion_org" value="crear">
                <input type="hidden" name="org_id" id="form_org_id" value="">
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nombre de la Institución</label>
                    <input type="text" id="form_nombre" name="nombre" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 bg-slate-50 focus:bg-white transition-all shadow-sm" required placeholder="Ej: Ambulatorio Norte">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Categoría / Icono representativo</label>
                    <div class="relative">
                        <select name="icono" id="form_icono" class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 bg-slate-50 focus:bg-white transition-all shadow-sm appearance-none" required>
                            <option value="fa-solid fa-building-ngo">General / Edificio / ONG</option>
                            <option value="fa-solid fa-hospital">Salud / Hospital / Clínica</option>
                            <option value="fa-solid fa-school">Educación / Escuela</option>
                            <option value="fa-solid fa-people-roof">Comunidad / Consejo Comunal</option>
                            <option value="fa-solid fa-paw">Animales / Refugio</option>
                            <option value="fa-solid fa-tree">Medio Ambiente / Parque</option>
                            <option value="fa-solid fa-computer">Tecnología / Empresa</option>
                            <option value="fa-solid fa-scale-balanced">Institución Gubernamental</option>
                        </select>
                        <i class="fa-solid fa-icons absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Ubicación (Dirección)</label>
                    <input type="text" id="form_direccion" name="direccion" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 bg-slate-50 focus:bg-white transition-all shadow-sm" required placeholder="Ej: Avenida 5, calle 12...">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Breve Descripción</label>
                    <textarea id="form_desc" name="desc" rows="3" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 bg-slate-50 focus:bg-white transition-all shadow-sm resize-none" required placeholder="Actividades que se realizan allí..."></textarea>
                </div>
                <button type="submit" id="modalOrg_btn" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-semibold py-3.5 rounded-xl transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5 mt-2 flex justify-center items-center gap-2">
                    <i class="fa-solid fa-check"></i> <span>Guardar Organización</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Modal Registro Estudiante -->
    <div id="modalRegistro" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 fade-in px-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg slide-up overflow-hidden glass-modal border border-white/20">
            <div class="flex justify-between items-center p-6 border-b border-slate-100 bg-white">
                <h2 class="text-xl font-bold text-slate-800">Vincular Participante</h2>
                <button onclick="cerrarModal('modalRegistro')" class="text-slate-400 hover:text-red-500 hover:bg-red-50 w-8 h-8 rounded-lg flex items-center justify-center transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-5 bg-white">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Participante (Estudiante DB)</label>
                    <select name="estudiante" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 bg-slate-50 focus:bg-white transition-all shadow-sm" required>
                        <option value="">Seleccione un participante...</option>
                        <?php foreach($estudiantes_db as $est): ?>
                            <?php $nombre_completo = trim($est['nombre'] . ' ' . $est['apellido']); ?>
                            <option value="<?= htmlspecialchars($nombre_completo) ?>">
                                <?= htmlspecialchars($nombre_completo . ' (C.I: ' . $est['cedula'] . ' - Sec: ' . ($est['seccion'] ?? 'N/A') . ')') ?>
                            </option>
                        <?php endforeach; ?>
                        
                        <?php if(empty($estudiantes_db)): ?>
                            <option value="" disabled>No hay estudiantes activos en la Base de Datos</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Organización Aliada</label>
                    <select name="org_id" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 bg-slate-50 focus:bg-white transition-all shadow-sm" required>
                        <option value="">Seleccione una organización...</option>
                        <?php foreach($organizaciones as $org): ?>
                            <option value="<?= $org['id']; ?>"><?= htmlspecialchars($org['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Horas a Realizar</label>
                        <div class="relative">
                            <input type="number" name="horas" class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 bg-slate-50 focus:bg-white transition-all shadow-sm" required min="1" max="500" placeholder="Ej: 120">
                            <i class="fa-regular fa-clock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Estado Inicial</label>
                        <select name="estado" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 bg-slate-50 focus:bg-white transition-all shadow-sm" required>
                            <option value="En progreso">En progreso</option>
                            <option value="Finalizado">Finalizado</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 bg-slate-50 focus:bg-white transition-all shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Fecha Fin Estimada</label>
                        <input type="date" name="fecha_fin" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 bg-slate-50 focus:bg-white transition-all shadow-sm" required>
                    </div>
                </div>

                <button type="submit" name="nuevo_registro" class="w-full bg-amber-500 hover:bg-amber-600 text-white font-semibold py-3.5 rounded-xl transition-all shadow-lg hover:shadow-xl hover:shadow-amber-500/20 hover:-translate-y-0.5 mt-4 flex justify-center items-center gap-2">
                    <i class="fa-solid fa-link"></i> Vincular Participante
                </button>
            </form>
        </div>
    </div>

    <!-- Modal Detalles y Eliminación -->
    <div id="modalDetalles" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 fade-in px-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl slide-up overflow-hidden flex flex-col max-h-[90vh] glass-modal border border-white/20">
            <div class="flex justify-between items-center p-6 border-b border-slate-100 bg-white shrink-0">
                <div class="flex items-center gap-4">
                    <div id="det_icono_box" class="w-12 h-12 bg-amber-50 border border-amber-100 text-amber-500 rounded-xl flex items-center justify-center text-xl shadow-inner">
                        <i id="det_icono_icon" class="fa-solid fa-building"></i>
                    </div>
                    <div>
                        <h2 id="det_nombre" class="text-xl font-bold text-slate-800 leading-tight">Nombre Organización</h2>
                        <p class="text-xs text-slate-500 font-medium">Ficha de Institución Aliada</p>
                    </div>
                </div>
                <button onclick="cerrarModal('modalDetalles')" class="text-slate-400 hover:text-red-500 hover:bg-red-50 w-8 h-8 rounded-lg flex items-center justify-center transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto bg-white">
                <div class="bg-slate-50 p-5 rounded-xl border border-slate-200 mb-8 space-y-3 shadow-sm">
                    <div class="flex gap-3 items-start">
                        <i class="fa-solid fa-location-dot text-amber-500 mt-1"></i>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-0.5">Ubicación</p>
                            <p class="text-sm text-slate-700 font-medium" id="det_direccion">...</p>
                        </div>
                    </div>
                    <div class="flex gap-3 items-start pt-2 border-t border-slate-200/60">
                        <i class="fa-solid fa-align-left text-amber-500 mt-1"></i>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-0.5">Descripción</p>
                            <p class="text-sm text-slate-700" id="det_desc">...</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-md font-bold text-slate-800">Historial de Participantes</h3>
                    <span class="text-xs font-medium px-2.5 py-1 bg-slate-100 text-slate-600 rounded-lg border border-slate-200" id="det_contador">0 vinculados</span>
                </div>
                
                <div id="det_participantes_lista" class="mb-4">
                    <!-- Contenido inyectado por JS -->
                </div>
            </div>

            <div class="p-5 border-t border-slate-100 bg-slate-50 shrink-0 flex flex-col sm:flex-row justify-between items-center gap-3">
                <form method="POST" class="w-full sm:w-auto" onsubmit="return confirm('¿Estás seguro de eliminar esta institución? Esta acción no se puede deshacer.');">
                    <input type="hidden" name="id_eliminar" id="det_id_eliminar">
                    <button type="submit" name="eliminar_org" class="w-full sm:w-auto text-red-600 hover:text-red-700 hover:bg-red-50 bg-white border border-red-200 px-5 py-2.5 rounded-xl font-semibold transition-colors text-sm flex items-center justify-center gap-2 shadow-sm">
                        <i class="fa-regular fa-trash-can"></i> Eliminar
                    </button>
                </form>
                
                <button onclick="abrirModalEdicionDesdeDetalles()" class="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white font-semibold py-2.5 px-6 rounded-xl transition-all shadow-md hover:shadow-lg text-sm flex items-center justify-center gap-2">
                    <i class="fa-solid fa-pen-to-square"></i> Editar Información
                </button>
            </div>
        </div>
    </div>

    <script>
        const registrosLaborGlobales = <?= json_encode($registros); ?>;
        let organizacionActual = null;

        // Pestañas
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

        // Modales Básicos
        function abrirModal(id) {
            const modal = document.getElementById(id);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function cerrarModal(id) {
            const modal = document.getElementById(id);
            modal.classList.add('hidden');
            setTimeout(() => { modal.classList.remove('flex'); }, 50); // Pequeño delay visual
        }

        // Abrir modal para Crear Organización (Limpia el formulario)
        function abrirModalCrearOrg() {
            document.getElementById('form_accion_org').value = 'crear';
            document.getElementById('form_org_id').value = '';
            document.getElementById('form_nombre').value = '';
            document.getElementById('form_direccion').value = '';
            document.getElementById('form_desc').value = '';
            document.getElementById('form_icono').value = 'fa-solid fa-building-ngo';
            
            document.getElementById('modalOrg_title').innerText = 'Registrar Organización';
            document.getElementById('modalOrg_btn').innerHTML = '<i class="fa-solid fa-check"></i> <span>Guardar Organización</span>';
            
            abrirModal('modalOrg');
        }

        // Abrir modal para Editar
        function abrirModalEdicionDesdeDetalles() {
            if(!organizacionActual) return;
            
            cerrarModal('modalDetalles');
            
            document.getElementById('form_accion_org').value = 'editar';
            document.getElementById('form_org_id').value = organizacionActual.id;
            document.getElementById('form_nombre').value = organizacionActual.nombre;
            document.getElementById('form_direccion').value = organizacionActual.direccion;
            document.getElementById('form_desc').value = organizacionActual.desc;
            document.getElementById('form_icono').value = organizacionActual.icono;
            
            document.getElementById('modalOrg_title').innerText = 'Editar Organización';
            document.getElementById('modalOrg_btn').innerHTML = '<i class="fa-solid fa-arrows-rotate"></i> <span>Actualizar Datos</span>';
            
            abrirModal('modalOrg');
        }

        // Inyectar datos en el modal de detalles
        function verDetallesOrg(orgData) {
            organizacionActual = orgData; 
            
            document.getElementById('det_nombre').innerText = orgData.nombre;
            document.getElementById('det_direccion').innerText = orgData.direccion || 'No especificada';
            document.getElementById('det_desc').innerText = orgData.desc;
            document.getElementById('det_icono_icon').className = orgData.icono;
            document.getElementById('det_id_eliminar').value = orgData.id;
            
            const container = document.getElementById('det_participantes_lista');
            const asignados = registrosLaborGlobales.filter(r => r.org_id == orgData.id);
            
            document.getElementById('det_contador').innerText = `${asignados.length} vinculado(s)`;
            
            if (asignados.length === 0) {
                container.innerHTML = `
                    <div class="flex flex-col items-center justify-center py-10 border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50 text-slate-400">
                        <i class="fa-solid fa-users-slash text-4xl mb-3 opacity-30"></i>
                        <h4 class="font-medium text-slate-600">Sin historial</h4>
                        <p class="text-xs mt-1 text-center px-4 max-w-[200px]">No hay estudiantes vinculados a esta institución.</p>
                    </div>`;
            } else {
                let html = '<ul class="divide-y divide-slate-100 bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">';
                asignados.forEach(reg => {
                    let badgeClass = reg.estado === 'Finalizado' 
                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200' 
                        : 'bg-amber-50 text-amber-700 border-amber-200';
                    let estadoLabel = reg.estado === 'Finalizado' ? 'Finalizado' : 'En Progreso';
                    let dotColor = reg.estado === 'Finalizado' ? 'bg-emerald-500' : 'bg-amber-500 animate-pulse';
                    
                    html += `
                        <li class="p-4 flex flex-col sm:flex-row justify-between sm:items-center gap-3 hover:bg-slate-50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-xs border border-slate-200">
                                    ${reg.estudiante.charAt(0).toUpperCase()}
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800 text-sm leading-tight">${reg.estudiante}</p>
                                    <p class="text-xs text-slate-500 mt-0.5 font-medium"><i class="fa-regular fa-clock mr-1"></i> ${reg.horas} horas asignadas</p>
                                </div>
                            </div>
                            <div>
                                <span class="flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-bold rounded-lg border ${badgeClass}">
                                    <div class="w-1.5 h-1.5 rounded-full ${dotColor}"></div>
                                    ${estadoLabel}
                                </span>
                            </div>
                        </li>
                    `;
                });
                html += '</ul>';
                container.innerHTML = html;
            }

            abrirModal('modalDetalles');
        }

        // Sidebar Móvil
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