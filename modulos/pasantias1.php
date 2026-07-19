<?php
session_start();
if (!isset($_SESSION['usuario_logueado'])) { header('Location: ../login.php'); exit; }

$rol = $_SESSION['rol'];
$vista = $_GET['vista'] ?? 'empresas';

// Inicializar Historial de Pasantías (Anteriores)
if (!isset($_SESSION['historial_pasantias'])) {
    $_SESSION['historial_pasantias'] = [
        ['estudiante' => 'Maria Perez', 'proyecto' => 'Mantenimiento de Servidores', 'empresa' => 'Fundacite', 'periodo' => '2023-II', 'estado' => 'Aprobado'],
        ['estudiante' => 'Jose Gonzalez', 'proyecto' => 'Soporte Técnico', 'empresa' => 'Alcaldía de Valera', 'periodo' => '2023-II', 'estado' => 'Aprobado'],
        ['estudiante' => 'Ana Rodriguez', 'proyecto' => 'Cableado Estructurado', 'empresa' => 'CANTV Sede Principal', 'periodo' => '2024-I', 'estado' => 'Aprobado']
    ];
}

// Procesar Exportación de Reporte (CSV) Dinámico
if (isset($_GET['exportar']) && $_GET['exportar'] == '1') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=reporte_pasantias_anteriores.csv');
    $salida = fopen('php://output', 'w');
    
    fputcsv($salida, ['Estudiante', 'Proyecto', 'Empresa', 'Periodo', 'Estado']);
    foreach ($_SESSION['historial_pasantias'] as $fila) { 
        fputcsv($salida, $fila); 
    }
    fclose($salida);
    exit; 
}

// Empresas con información extendida inventada
if (!isset($_SESSION['empresas_aliadas'])) {
    $_SESSION['empresas_aliadas'] = [
        [
            'id' => 1, 'nombre' => 'Alcaldía de Valera', 'icono' => '🏛️', 
            'desc' => 'Institución gubernamental local.', 'direccion' => 'Avenida Bolívar, Edificio Municipal, Valera.',
            'contacto' => 'rrhh@alcaldiavalera.gob.ve | 0271-2254321', 'areas' => 'Asistencia Administrativa, Soporte Técnico, Contabilidad.'
        ],
        [
            'id' => 2, 'nombre' => 'Fundacite', 'icono' => '🔬', 
            'desc' => 'Fundación para el Desarrollo Científico.', 'direccion' => 'Zona Industrial, Plata 3, Valera.',
            'contacto' => 'pasantias@fundacite.ve | 0414-7221199', 'areas' => 'Informática, Redes, Investigación Tecnológica.'
        ],
        [
            'id' => 3, 'nombre' => 'CANTV Sede Principal', 'icono' => '📞', 
            'desc' => 'Empresa estatal de telecomunicaciones.', 'direccion' => 'Calle 10, Casco Central, Valera.',
            'contacto' => 'talento.cantv@cantv.net.ve', 'areas' => 'Telecomunicaciones, Informática, Atención al Cliente.'
        ]
    ];
}

if (!isset($_SESSION['pasantias_activas'])) {
    $_SESSION['pasantias_activas'] = [];
}

// Procesar formulario de nueva empresa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_empresa'])) {
    $_SESSION['empresas_aliadas'][] = [
        'id' => time(), 
        'nombre' => htmlspecialchars($_POST['nombre_empresa']),
        'icono' => htmlspecialchars($_POST['icono_empresa'] ?: '🏢'),
        'desc' => htmlspecialchars($_POST['desc_empresa']),
        'direccion' => htmlspecialchars($_POST['dir_empresa']),
        'contacto' => htmlspecialchars($_POST['contacto_empresa']),
        'areas' => htmlspecialchars($_POST['areas_empresa'])
    ];
    header("Location: pasantias.php?vista=empresas");
    exit;
}

// Procesar formulario de nueva pasantía
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar_pasantia'])) {
    $_SESSION['pasantias_activas'][] = [
        'estudiante' => htmlspecialchars($_POST['estudiante_nombre']),
        'proyecto' => htmlspecialchars($_POST['proyecto_nombre']),
        'empresa_id' => $_POST['empresa_id'],
        'fecha' => $_POST['fecha_inicio'],
        'duracion' => htmlspecialchars($_POST['duracion']) // Nuevo campo
    ];
    header("Location: pasantias.php?vista=activas");
    exit;
}

// Procesar finalización de pasantía
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_pasantia'])) {
    $index = (int)$_POST['index_pasantia'];
    
    if (isset($_SESSION['pasantias_activas'][$index])) {
        $pas = $_SESSION['pasantias_activas'][$index];
        
        // Obtener el nombre real de la empresa
        $empresa_nombre = 'Empresa Desconocida';
        foreach($_SESSION['empresas_aliadas'] as $e) { 
            if($e['id'] == $pas['empresa_id']) {
                $empresa_nombre = $e['nombre'];
                break;
            }
        }
        
        // Mover al historial
        $_SESSION['historial_pasantias'][] = [
            'estudiante' => $pas['estudiante'],
            'proyecto' => $pas['proyecto'],
            'empresa' => $empresa_nombre,
            'periodo' => date('Y') . '-' . (date('n') > 6 ? 'II' : 'I'), // Autogenera Ej: 2026-II
            'estado' => 'Aprobado'
        ];
        
        // Eliminar de activas y reindexar array
        unset($_SESSION['pasantias_activas'][$index]);
        $_SESSION['pasantias_activas'] = array_values($_SESSION['pasantias_activas']);
    }
    header("Location: pasantias.php?vista=anteriores");
    exit;
}

$empresas = $_SESSION['empresas_aliadas'];
$activas = $_SESSION['pasantias_activas'];
$historial = $_SESSION['historial_pasantias'];

// Inyectar a cada empresa la lista de estudiantes asignados actualmente
foreach ($empresas as &$emp) {
    $emp['asignados'] = [];
    foreach ($activas as $activa) {
        if ($activa['empresa_id'] == $emp['id']) {
            $emp['asignados'][] = $activa['estudiante'] . ' (Proyecto: ' . $activa['proyecto'] . ')';
        }
    }
}
unset($emp);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prácticas Profesionales - E.T. Pedro Garcia Leal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        :root { --primary: #0f172a; --accent: #f59e0b; --bg: #f8fafc; --surface: #ffffff; --text: #334155; --border: #e2e8f0; }
        
        .pasantias-container { max-width: 1100px; margin: 0 auto; color: var(--text); width: 100%; }
        
        .header-box { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header-box h1 { margin: 0; color: var(--primary); font-size: 2rem; font-weight: bold;}
        
        .btn-action { background: var(--surface); border: 1px solid var(--border); padding: 10px 20px; border-radius: 8px; text-decoration: none; color: var(--primary); font-weight: 600; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.05); display: inline-flex; align-items: center; gap: 8px;}
        .btn-action:hover { border-color: var(--primary); }
        .btn-primary { background: var(--accent); color: white; border: none; }
        .btn-primary:hover { background: #d97706; border-color: transparent; }
        
        /* Botón especial para finalizar pasantía */
        .btn-success { background: #10b981; color: white; border: none; padding: 6px 12px; font-size: 0.85rem;}
        .btn-success:hover { background: #059669; }

        .nav-tabs { display: flex; gap: 10px; margin-bottom: 24px; border-bottom: 2px solid var(--border); padding-bottom: 10px; overflow-x: auto; }
        .tab-link { padding: 10px 20px; background: transparent; color: #64748b; text-decoration: none; border-radius: 8px; font-weight: 600; transition: all 0.2s; white-space: nowrap; }
        .tab-link:hover { background: #f1f5f9; color: var(--primary); }
        .tab-link.active { background: var(--accent); color: white; box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.3); }

        .content-card { background: var(--surface); padding: 30px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid var(--border); min-height: 400px;}

        .grid-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .card { border-radius: 12px; padding: 25px; border: 1px solid var(--border); text-align: left; transition: all 0.3s; cursor: pointer; position: relative; background: var(--surface);}
        .card:hover { transform: translateY(-3px); border-color: var(--accent); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .card-header { display: flex; align-items: center; gap: 15px; margin-bottom: 10px; }
        .card-icon { font-size: 2.5rem; background: #f8fafc; width: 60px; height: 60px; display: flex; justify-content: center; align-items: center; border-radius: 12px;}
        .card-title { font-size: 1.1rem; font-weight: 700; color: var(--primary); margin: 0; }
        .card-desc { font-size: 0.9rem; color: #64748b; margin: 0; line-height: 1.4;}
        .view-more { position: absolute; bottom: 15px; right: 15px; color: var(--accent); font-size: 0.85rem; font-weight: bold; opacity: 0; transition: opacity 0.3s;}
        .card:hover .view-more { opacity: 1; }

        .empty-state { text-align: center; padding: 60px 20px; color: #64748b; display: flex; flex-direction: column; align-items: center;}
        .empty-state svg { width: 64px; height: 64px; color: #cbd5e1; margin-bottom: 15px; }

        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.6); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 1000; opacity: 0; transition: opacity 0.3s; padding: 20px; box-sizing: border-box;}
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal-content { background: var(--surface); padding: 30px; border-radius: 16px; width: 100%; max-width: 500px; transform: translateY(20px); transition: transform 0.3s; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); max-height: 90vh; overflow-y: auto;}
        .modal-overlay.active .modal-content { transform: translateY(0); }
        
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 15px;}
        .modal-header h2 { margin: 0; font-size: 1.25rem; color: var(--primary); display: flex; align-items: center; gap: 10px; font-weight: 700;}
        .close-btn { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #94a3b8; }
        
        .info-row { margin-bottom: 15px; }
        .info-label { font-weight: 700; color: var(--primary); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; display: block;}
        .info-value { color: var(--text); font-size: 0.95rem; line-height: 1.5; background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px solid var(--border);}
        
        .asignados-list { padding-left: 20px; margin: 0; }
        .asignados-list li { padding: 4px 0; border-bottom: 1px dashed var(--border); }
        .asignados-list li:last-child { border-bottom: none; }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.9rem; }
        .form-input { width: 100%; padding: 10px 15px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; outline: none; box-sizing: border-box; background: #f8fafc;}
        .form-input:focus { border-color: var(--accent); background: white;}

        .data-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.95rem; }
        .data-table th { background: #f8fafc; color: #64748b; padding: 12px 15px; text-align: left; font-weight: 600; border-bottom: 2px solid var(--border); }
        .data-table td { padding: 15px; border-bottom: 1px solid var(--border); color: var(--text); }
        .badge { background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold; }
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
            
            <a href="labor-social.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-colors">
                <i class="fa-solid fa-handshake-angle w-5 text-slate-400"></i> Labor Social
            </a>
            
            <a href="pasantias.php" class="flex items-center gap-3 px-4 py-3 bg-amber-500 text-slate-900 font-bold rounded-xl shadow-md shadow-amber-500/20 transition-all">
                <i class="fa-solid fa-briefcase w-5"></i> Pasantías
            </a>
        </nav>

        <div class="p-4 border-t border-slate-800 bg-slate-950/50">
            <div class="flex items-center gap-3 px-2">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($rol) ?>&background=f59e0b&color=fff" alt="Perfil" class="w-10 h-10 rounded-full border-2 border-slate-700">
                <div class="overflow-hidden">
                    <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($rol); ?></p>
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
                    <h2 class="text-xl lg:text-2xl font-bold text-slate-800 leading-tight">Módulo de Pasantías</h2>
                    <p class="text-xs text-slate-500 hidden sm:block">Gestión de prácticas profesionales y empresas aliadas</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <span class="px-4 py-2 bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl border border-slate-200 flex items-center gap-2 shadow-sm">
                    <i class="fa-regular fa-calendar text-amber-500"></i> <span class="hidden sm:inline">Hoy: </span><?= date('d/m/Y'); ?>
                </span>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto w-full p-4 lg:p-8">
            <div class="pasantias-container">
                
                <div class="header-box">
                    <div>
                        <h1>💼 Prácticas Profesionales</h1>
                        <p style="margin: 5px 0 0 0; color: #64748b;">Gestión de pasantías y empresas aliadas.</p>
                    </div>
                    <a href="../index.php" class="btn-action"><i class="fa-solid fa-arrow-left"></i> Volver al Dashboard</a>
                </div>

                <nav class="nav-tabs">
                    <a href="?vista=empresas" class="tab-link <?php echo $vista === 'empresas' ? 'active' : ''; ?>">🏢 Empresas Aliadas</a>
                    <a href="?vista=activas" class="tab-link <?php echo $vista === 'activas' ? 'active' : ''; ?>">⏳ Pasantías Activas</a>
                    <a href="?vista=anteriores" class="tab-link <?php echo $vista === 'anteriores' ? 'active' : ''; ?>">✅ Pasantías Anteriores</a>
                </nav>

                <div class="content-card">
                    <?php if ($vista === 'empresas'): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap: wrap; gap: 10px;">
                            <h2 style="margin:0; color:var(--primary); font-size: 1.4rem; font-weight:700;">Directorio de Empresas</h2>
                            <?php if($rol === 'Administrador' || $rol === 'Prácticas Profesionales'): ?>
                                <button class="btn-action btn-primary" onclick="openFormModal()">
                                    <i class="fa-solid fa-plus"></i> Registrar Empresa
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="grid-cards">
                            <?php foreach($empresas as $index => $empresa): ?>
                                <!-- Pasamos toda la data de la empresa (incluyendo el array de "asignados") al JS -->
                                <div class="card" onclick='openInfoModal(<?php echo htmlspecialchars(json_encode($empresa), ENT_QUOTES, 'UTF-8'); ?>)'>
                                    <div class="card-header">
                                        <div class="card-icon"><?php echo $empresa['icono']; ?></div>
                                        <h3 class="card-title"><?php echo htmlspecialchars($empresa['nombre']); ?></h3>
                                    </div>
                                    <p class="card-desc"><?php echo htmlspecialchars($empresa['desc']); ?></p>
                                    <span class="view-more">Ver detalles →</span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <?php elseif ($vista === 'activas'): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap: wrap; gap: 10px;">
                            <h2 style="margin:0; color:var(--primary); font-size: 1.4rem; font-weight:700;">Pasantías en Curso</h2>
                            <?php if($rol === 'Administrador' || $rol === 'Prácticas Profesionales'): ?>
                                <button class="btn-action btn-primary" onclick="openAssignModal()">
                                    <i class="fa-solid fa-plus"></i> Asignar Pasantía
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <?php if(empty($activas)): ?>
                            <div class="empty-state">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <h3 style="font-weight:600; font-size:1.1rem; color:var(--primary); margin-bottom:5px;">Sin pasantías activas</h3>
                                <p style="margin:0;">Actualmente no hay estudiantes cursando su periodo de pasantías.</p>
                            </div>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Estudiante</th>
                                            <th>Proyecto</th>
                                            <th>Empresa</th>
                                            <th>Fecha Inicio</th>
                                            <th>Duración</th> <!-- Nuevo Encabezado -->
                                            <?php if($rol === 'Administrador' || $rol === 'Prácticas Profesionales'): ?>
                                                <th style="text-align:center;">Acciones</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($activas as $index => $pas): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($pas['estudiante']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($pas['proyecto']); ?></td>
                                                <td>
                                                    <?php 
                                                        foreach($empresas as $e) { 
                                                            if($e['id'] == $pas['empresa_id']) echo $e['icono'] . ' ' . htmlspecialchars($e['nombre']); 
                                                        } 
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($pas['fecha']); ?></td>
                                                <td><?php echo htmlspecialchars($pas['duracion'] ?? 'No especificada'); ?></td>
                                                
                                                <?php if($rol === 'Administrador' || $rol === 'Prácticas Profesionales'): ?>
                                                <td style="text-align:center;">
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que desea finalizar esta pasantía y moverla al historial?');">
                                                        <input type="hidden" name="index_pasantia" value="<?php echo $index; ?>">
                                                        <button type="submit" name="finalizar_pasantia" class="btn-action btn-success">
                                                            <i class="fa-solid fa-check-double"></i> Finalizar
                                                        </button>
                                                    </form>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                    <?php elseif ($vista === 'anteriores'): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap: wrap; gap: 10px;">
                            <h2 style="margin:0; color:var(--primary); font-size: 1.4rem; font-weight:700;">Historial de Pasantías</h2>
                            <a href="?vista=anteriores&exportar=1" class="btn-action btn-primary">
                                <i class="fa-solid fa-download"></i> Exportar Reporte CSV
                            </a>
                        </div>
                        
                        <?php if(empty($historial)): ?>
                            <div class="empty-state">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <h3 style="font-weight:600; font-size:1.1rem; color:var(--primary); margin-bottom:5px;">Historial vacío</h3>
                                <p style="margin:0;">Aún no se ha finalizado ninguna pasantía.</p>
                            </div>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table class="data-table">
                                    <thead>
                                        <tr><th>Estudiante</th><th>Proyecto</th><th>Empresa</th><th>Período</th><th>Estado</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($historial as $h): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($h['estudiante']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($h['proyecto']); ?></td>
                                                <td><?php echo htmlspecialchars($h['empresa']); ?></td>
                                                <td><?php echo htmlspecialchars($h['periodo']); ?></td>
                                                <td><span class="badge"><?php echo htmlspecialchars($h['estado']); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

            </div>
            
            <footer class="mt-12 border-t border-slate-200 pt-6 text-center text-sm text-slate-400 pb-8">
                <p>&copy; <?= date('Y'); ?> Escuela Técnica Pedro Garcia Leal. Diseñado para la excelencia.</p>
            </footer>
        </div>
    </main>

    <!-- Modal: Información de la Empresa -->
    <div class="modal-overlay" id="infoModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title"><span id="modal-icon"></span> <span id="modal-name">Nombre Empresa</span></h2>
                <button class="close-btn" onclick="closeInfoModal()">×</button>
            </div>
            
            <div class="info-row">
                <span class="info-label">Descripción</span>
                <div class="info-value" id="modal-desc">...</div>
            </div>
            <div class="info-row">
                <span class="info-label">Áreas de Práctica</span>
                <div class="info-value" id="modal-areas">...</div>
            </div>
            <div class="info-row">
                <span class="info-label">Dirección Física</span>
                <div class="info-value" id="modal-dir">...</div>
            </div>
            <div class="info-row">
                <span class="info-label">Contacto (Tutor Empresarial / RRHH)</span>
                <div class="info-value" id="modal-contacto">...</div>
            </div>
            
            <!-- Nueva fila para estudiantes asignados -->
            <div class="info-row" style="margin-top: 20px;">
                <span class="info-label"><i class="fa-solid fa-users"></i> Estudiantes Asignados Actualmente</span>
                <div class="info-value" style="background: #fffbeb; border-color: #fde68a;" id="modal-asignados">
                    <!-- Contenido insertado via JS -->
                </div>
            </div>

            <button class="btn-action btn-primary" style="width: 100%; justify-content:center; margin-top:10px;" onclick="closeInfoModal()">Cerrar Detalles</button>
        </div>
    </div>

    <!-- Modal: Agregar Empresa -->
    <div class="modal-overlay" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fa-solid fa-building"></i> Registrar Institución Aliada</h2>
                <button class="close-btn" onclick="closeFormModal()">×</button>
            </div>
            <form method="POST">
                <div style="display: flex; gap: 10px; flex-wrap:wrap;">
                    <div class="form-group" style="flex: 1; min-width:200px;">
                        <label>Nombre de la Empresa</label>
                        <input type="text" name="nombre_empresa" class="form-input" required placeholder="Ej: Corpoelec">
                    </div>
                    <div class="form-group" style="width: 80px;">
                        <label>Icono</label>
                        <input type="text" name="icono_empresa" class="form-input" placeholder="⚡" maxlength="2">
                    </div>
                </div>
                <div class="form-group">
                    <label>Breve Descripción</label>
                    <input type="text" name="desc_empresa" class="form-input" required placeholder="Ej: Sector eléctrico...">
                </div>
                <div class="form-group">
                    <label>Dirección</label>
                    <input type="text" name="dir_empresa" class="form-input" required placeholder="Ubicación de la sede...">
                </div>
                <div class="form-group">
                    <label>Información de Contacto</label>
                    <input type="text" name="contacto_empresa" class="form-input" required placeholder="Email o teléfono...">
                </div>
                <div class="form-group">
                    <label>Áreas Disponibles para Pasantías</label>
                    <textarea name="areas_empresa" class="form-input" required placeholder="Ej: Redes, Soporte, Base de Datos..." rows="2"></textarea>
                </div>
                <button type="submit" name="nueva_empresa" class="btn-action btn-primary" style="width: 100%; justify-content: center; margin-top: 10px;">Guardar Registro</button>
            </form>
        </div>
    </div>

    <!-- Modal: Asignar Pasantía -->
    <div class="modal-overlay" id="assignModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fa-solid fa-user-plus"></i> Asignar Nuevo Proyecto</h2>
                <button type="button" class="close-btn" onclick="closeAssignModal()">×</button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Nombre del Estudiante</label>
                    <input type="text" name="estudiante_nombre" class="form-input" required placeholder="Ej: Juan Pérez">
                </div>
                <div class="form-group">
                    <label>Título del Proyecto / Tesis</label>
                    <input type="text" name="proyecto_nombre" class="form-input" required placeholder="Ej: Implementación de Red LAN">
                </div>
                <div class="form-group">
                    <label>Empresa Asignada</label>
                    <select name="empresa_id" class="form-input" required>
                        <option value="">Seleccione una empresa...</option>
                        <?php foreach($empresas as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="display: flex; gap: 15px; flex-wrap:wrap;">
                    <div class="form-group" style="flex:1; min-width: 150px;">
                        <label>Fecha de Inicio</label>
                        <input type="date" name="fecha_inicio" class="form-input" required>
                    </div>
                    <div class="form-group" style="flex:1; min-width: 150px;">
                        <label>Duración</label>
                        <!-- Nuevo input para duración -->
                        <input type="text" name="duracion" class="form-input" required placeholder="Ej: 12 Semanas">
                    </div>
                </div>

                <button type="submit" name="asignar_pasantia" class="btn-action btn-primary" style="width: 100%; justify-content: center; margin-top: 10px;">Guardar Asignación</button>
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

        const infoModal = document.getElementById('infoModal');
        const addModal = document.getElementById('addModal');
        const assignModal = document.getElementById('assignModal');

        function openInfoModal(data) {
            document.getElementById('modal-icon').innerText = data.icono;
            document.getElementById('modal-name').innerText = data.nombre;
            document.getElementById('modal-desc').innerText = data.desc;
            document.getElementById('modal-dir').innerText = data.direccion || 'No especificada';
            document.getElementById('modal-contacto').innerText = data.contacto || 'No especificado';
            document.getElementById('modal-areas').innerText = data.areas || 'No especificadas';
            
            // Renderizar los estudiantes asignados a esta empresa específica
            const contenedorAsignados = document.getElementById('modal-asignados');
            if (data.asignados && data.asignados.length > 0) {
                let listHtml = '<ul class="asignados-list">';
                data.asignados.forEach(estudiante => {
                    listHtml += `<li><i class="fa-solid fa-user-graduate text-amber-500 mr-2"></i> ${estudiante}</li>`;
                });
                listHtml += '</ul>';
                contenedorAsignados.innerHTML = listHtml;
            } else {
                contenedorAsignados.innerHTML = '<span style="color: #92400e; font-size: 0.9rem;">Ningún estudiante realizando pasantías aquí en este momento.</span>';
            }

            infoModal.classList.add('active');
        }

        function closeInfoModal() { infoModal.classList.remove('active'); }
        function openFormModal() { addModal.classList.add('active'); }
        function closeFormModal() { addModal.classList.remove('active'); }
        function openAssignModal() { assignModal.classList.add('active'); }
        function closeAssignModal() { assignModal.classList.remove('active'); }

        window.onclick = function(event) {
            if (event.target == infoModal) closeInfoModal();
            if (event.target == addModal) closeFormModal();
            if (event.target == assignModal) closeAssignModal();
        }
    </script>
</body>
</html>