<?php
session_start();

require_once '../includes/config.php';
require_once '../includes/db-functions.php';

$nombre_institucion = "Escuela Técnica Pedro Garcia Leal";
$modulo_actual = "Representante";
$año_actual = date("Y");
$rol_actual = $_SESSION['rol'] ?? 'Usuario';

if ($rol_actual !== 'Administrador' && $rol_actual !== 'Representante') {
    header('Location: ../no-autorizado.php');
    exit;
}

$vista = $_GET['vista'] ?? 'principal';
$id_estudiante = $_GET['id_estudiante'] ?? null;
$lapso = $_GET['lapso'] ?? 1;

// Para génerar PDF
if (isset($_GET['descargar_boleta']) && $id_estudiante) {
    require_once '../includes/fpdf/fpdf.php';
    
    $id_estudiante = (int)$id_estudiante;
    $lapso_descarga = (int)($_GET['lapso_descarga'] ?? 1);
    
    $estudiante = obtenerEstudiantePorId($conexion, $id_estudiante);
    $calificaciones = obtenerCalificacionesEstudiante($conexion, $id_estudiante, $lapso_descarga);
    
    if ($estudiante && $calificaciones) {
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, $nombre_institucion, 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 5, 'Boleta de Calificaciones - Lapso ' . $lapso_descarga, 0, 1, 'C');
        
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(30, 7, 'Estudiante:', 0, 0);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 7, htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']), 0, 1);
        
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(30, 7, 'Cédula:', 0, 0);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 7, htmlspecialchars($estudiante['cedula']), 0, 1);
        
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(30, 7, 'Nivel:', 0, 0);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 7, htmlspecialchars($estudiante['nivel_academico'] . ' - ' . $estudiante['seccion']), 0, 1);
        
        $pdf->Ln(3);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(200, 150, 100);
        $pdf->Cell(60, 8, 'Materia', 1, 0, 'C', true);
        $pdf->Cell(30, 8, '1era Eval', 1, 0, 'C', true);
        $pdf->Cell(30, 8, '2da Eval', 1, 0, 'C', true);
        $pdf->Cell(30, 8, '3era Eval', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Promedio', 1, 1, 'C', true);
        
        $pdf->SetFont('Arial', '', 9);
        $total_promedio = 0;
        $cantidad_materias = count($calificaciones);
        
        foreach ($calificaciones as $cal) {
            $pdf->Cell(60, 7, htmlspecialchars($cal['materia']), 1, 0);
            $pdf->Cell(30, 7, number_format($cal['nota_primera_evaluacion'], 2), 1, 0, 'C');
            $pdf->Cell(30, 7, number_format($cal['nota_segunda_evaluacion'], 2), 1, 0, 'C');
            $pdf->Cell(30, 7, number_format($cal['nota_tercera_evaluacion'], 2), 1, 0, 'C');
            $pdf->Cell(30, 7, number_format($cal['nota_promedio'], 2), 1, 1, 'C');
            $total_promedio += $cal['nota_promedio'];
        }
        
        $promedio_general = $cantidad_materias > 0 ? $total_promedio / $cantidad_materias : 0;
        
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(220, 180, 130);
        $pdf->Cell(150, 8, 'PROMEDIO GENERAL DEL LAPSO', 1, 0, 'R', true);
        $pdf->Cell(30, 8, number_format($promedio_general, 2), 1, 1, 'C', true);
        
        $pdf->Ln(5);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 5, 'Fecha de generación: ' . date('d/m/Y H:i'), 0, 1);
        
        $pdf->Output('D', 'Boleta_' . htmlspecialchars($estudiante['nombre']) . '_' . htmlspecialchars($estudiante['apellido']) . '_L' . $lapso_descarga . '.pdf');
        exit;
    }
}

$mensaje = "";
$tipo_mensaje = "";

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
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 bg-slate-50">

    <!-- Sidebar idéntico al nuevo diseño -->
    <aside id="sidebar" class="w-72 bg-slate-900 text-white hidden lg:flex flex-col shadow-2xl z-30 transition-all duration-300 absolute lg:relative h-full">
        <div class="p-6 flex items-center justify-between lg:justify-start gap-4 border-b border-slate-800">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-600/30 text-white shrink-0">
                    <i class="fa-solid fa-school text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-sm font-bold leading-tight uppercase tracking-wide text-slate-300">Escuela Técnica</h1>
                    <p class="text-xs text-blue-400 font-medium">Pedro Garcia Leal</p>
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
            
            <a href="representante.php" class="flex items-center gap-3 px-4 py-3 bg-blue-600 text-white font-bold rounded-xl shadow-md shadow-blue-600/20 transition-colors">
                <i class="fa-solid fa-user-tie w-5"></i> Representantes
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
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($rol_actual) ?>&background=2563eb&color=fff" alt="Perfil" class="w-10 h-10 rounded-full border-2 border-slate-700">
                <div class="overflow-hidden">
                    <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($rol_actual); ?></p>
                    <p class="text-xs text-slate-400">Sesión Activa</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Fondo oscuro para móvil -->
    <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/50 z-20 hidden lg:hidden backdrop-blur-sm"></div>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-20 bg-white/80 backdrop-blur-md shadow-sm flex items-center justify-between px-6 lg:px-8 z-10 border-b border-slate-200">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden text-slate-500 hover:text-blue-600 transition-colors text-xl p-2 -ml-2 rounded-lg hover:bg-slate-100">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h2 class="text-xl lg:text-2xl font-bold text-slate-800 leading-tight">Portal del <?= $modulo_actual; ?></h2>
                    <p class="text-xs text-slate-500 hidden sm:block">Consulta de notas, asistencia y convivencia</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <span class="px-4 py-2 bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl border border-slate-200 flex items-center gap-2 shadow-sm">
                    <i class="fa-regular fa-calendar text-blue-600"></i> <span class="hidden sm:inline">Hoy: </span><?= date('d/m/Y'); ?>
                </span>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 lg:p-8 w-full">
            <div class="max-w-6xl mx-auto space-y-6">
                
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-2">
                    <a href="../index.php" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-blue-600 transition-colors bg-white px-4 py-2.5 rounded-xl border border-slate-200 shadow-sm hover:shadow">
                        <i class="fa-solid fa-arrow-left"></i> Volver al Inicio
                    </a>
                </div>

                <!-- Tabs de Navegación -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-2 flex flex-wrap gap-2 slide-up">
                    <button onclick="location.href='?vista=principal'" class="flex-1 min-w-[150px] py-2.5 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 <?= ($vista === 'principal') ? 'bg-slate-900 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50' ?>">
                        <i class="fa-solid fa-building"></i> Institucional
                    </button>
                    <button onclick="location.href='?vista=notas'" class="flex-1 min-w-[150px] py-2.5 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 <?= ($vista === 'notas') ? 'bg-slate-900 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50' ?>">
                        <i class="fa-solid fa-chart-simple"></i> Notas
                    </button>
                    <button onclick="location.href='?vista=asistencia'" class="flex-1 min-w-[150px] py-2.5 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 <?= ($vista === 'asistencia') ? 'bg-slate-900 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50' ?>">
                        <i class="fa-solid fa-clipboard-check"></i> Asistencia
                    </button>
                    <button onclick="location.href='?vista=convivencia'" class="flex-1 min-w-[150px] py-2.5 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 <?= ($vista === 'convivencia') ? 'bg-slate-900 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50' ?>">
                        <i class="fa-solid fa-scale-balanced"></i> Convivencia
                    </button>
                    <button onclick="location.href='comunicacion.php'" class="flex-1 min-w-[150px] py-2.5 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 text-slate-500 hover:bg-slate-50">
                        <i class="fa-regular fa-comments"></i> Reuniones
                    </button>
                    <button onclick="location.href='reportes-conducta.php'" class="flex-1 min-w-[150px] py-2.5 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 text-slate-500 hover:bg-slate-50">
                        <i class="fa-solid fa-user-shield"></i> Conductas
                    </button>
                </div>

                <?php if ($vista === 'principal'): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8 slide-up">
                    <div class="mb-6 border-b border-slate-100 pb-4">
                        <h2 class="text-2xl font-bold text-slate-800">Información Institucional</h2>
                        <p class="text-sm text-slate-500 mt-1">Datos generales de la escuela técnica</p>
                    </div>

                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-5 rounded-xl mb-8 flex gap-4 items-start shadow-sm">
                        <div class="bg-emerald-100 p-3 rounded-lg text-emerald-600 mt-1">
                            <i class="fa-solid fa-circle-info text-xl"></i>
                        </div>
                        <div>
                            <strong class="block text-lg mb-1">Escuela Técnica Pedro Garcia Leal</strong>
                            <span class="text-emerald-700 text-sm leading-relaxed">Institución educativa especializada en formación técnica con programas de pasantías y labor social. Comprometidos con el desarrollo integral del estudiante.</span>
                        </div>
                    </div>

                    <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-address-book text-blue-600"></i> Contacto de Directivos
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-slate-50 border border-slate-200 p-5 rounded-xl hover:shadow-md transition-shadow">
                            <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mb-3">
                                <i class="fa-solid fa-user-tie"></i>
                            </div>
                            <p class="mb-1"><strong class="font-semibold text-slate-800">Director:</strong> <span class="text-slate-600">Lic. Juan Pérez</span></p>
                            <p class="mb-1"><strong class="font-semibold text-slate-800">Correo:</strong> <a href="mailto:director@escuela.edu.ve" class="text-blue-600 hover:underline">director@escuela.edu.ve</a></p>
                            <p><strong class="font-semibold text-slate-800">Teléfono:</strong> <span class="text-slate-600">(0293) 123-4567</span></p>
                        </div>
                    </div>
                </div>

                <?php elseif ($vista === 'notas'): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8 slide-up">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 border-b border-slate-100 pb-4">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-800">Notas en Tiempo Real</h2>
                            <p class="text-sm text-slate-500 mt-1">Visualiza las calificaciones actualizadas de tus representados</p>
                        </div>
                        
                        <div class="flex items-center gap-3 bg-slate-50 p-2 rounded-xl border border-slate-200">
                            <label class="text-sm font-semibold text-slate-600 pl-2">Lapso:</label>
                            <select onchange="location.href='?vista=notas&lapso=' + this.value" class="px-4 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 bg-white text-sm font-medium text-slate-700 shadow-sm cursor-pointer outline-none">
                                <option value="1" <?= ($lapso == 1) ? 'selected' : ''; ?>>1er Lapso</option>
                                <option value="2" <?= ($lapso == 2) ? 'selected' : ''; ?>>2do Lapso</option>
                                <option value="3" <?= ($lapso == 3) ? 'selected' : ''; ?>>3er Lapso</option>
                            </select>
                        </div>
                    </div>

                    <?php
                    // Simular algunos estudiantes con calificaciones
                    $estudiantes_ejemplo = obtenerEstudiantes($conexion);
                    
                    if (!empty($estudiantes_ejemplo)): 
                        foreach (array_slice($estudiantes_ejemplo, 0, 3) as $est):
                            $calificaciones = obtenerCalificacionesEstudiante($conexion, $est['id'], $lapso);
                    ?>
                        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 mb-6 overflow-hidden">
                            <div class="flex flex-col md:flex-row justify-between gap-4 mb-5 pb-5 border-b border-slate-200">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-lg border border-blue-200">
                                        <?= mb_substr($est['nombre'] ?? 'E', 0, 1, 'UTF-8') . mb_substr($est['apellido'] ?? 'S', 0, 1, 'UTF-8'); ?>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-800"><?= htmlspecialchars($est['nombre'] . ' ' . $est['apellido']); ?></h3>
                                        <p class="text-sm text-slate-500">C.I: <?= htmlspecialchars($est['cedula']); ?></p>
                                    </div>
                                </div>
                                <div class="flex gap-4">
                                    <div class="bg-white px-4 py-2 rounded-xl border border-slate-200 shadow-sm">
                                        <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Nivel</span>
                                        <span class="text-sm font-semibold text-slate-700"><?= htmlspecialchars($est['nivel_academico'] . ' - ' . $est['seccion']); ?></span>
                                    </div>
                                    <div class="bg-white px-4 py-2 rounded-xl border border-slate-200 shadow-sm">
                                        <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Lapso Escolar</span>
                                        <span class="text-sm font-semibold text-slate-700">Lapso <?= $lapso; ?> / <?= $año_actual; ?></span>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($calificaciones)): ?>
                                <div class="overflow-x-auto bg-white rounded-xl border border-slate-200 shadow-sm mb-4">
                                    <table class="w-full text-left text-sm whitespace-nowrap">
                                        <thead class="bg-slate-100 border-b border-slate-200 text-slate-600 uppercase tracking-wider text-xs font-semibold">
                                            <tr>
                                                <th class="px-5 py-3">Materia</th>
                                                <th class="px-5 py-3 text-center">1era Eval</th>
                                                <th class="px-5 py-3 text-center">2da Eval</th>
                                                <th class="px-5 py-3 text-center">3era Eval</th>
                                                <th class="px-5 py-3 text-center bg-blue-50/50 text-blue-800">Promedio</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <?php 
                                            $promedio_general = 0;
                                            foreach ($calificaciones as $cal):
                                                $promedio_general += $cal['nota_promedio'];
                                            ?>
                                                <tr class="hover:bg-slate-50 transition-colors">
                                                    <td class="px-5 py-3 font-medium text-slate-700"><?= htmlspecialchars($cal['materia']); ?></td>
                                                    <td class="px-5 py-3 text-center text-slate-600"><?= number_format($cal['nota_primera_evaluacion'], 2); ?></td>
                                                    <td class="px-5 py-3 text-center text-slate-600"><?= number_format($cal['nota_segunda_evaluacion'], 2); ?></td>
                                                    <td class="px-5 py-3 text-center text-slate-600"><?= number_format($cal['nota_tercera_evaluacion'], 2); ?></td>
                                                    <td class="px-5 py-3 text-center font-bold text-blue-600 bg-blue-50/30"><?= number_format($cal['nota_promedio'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; 
                                            
                                            if (count($calificaciones) > 0) {
                                                $promedio_general = $promedio_general / count($calificaciones);
                                            ?>
                                                <tr class="bg-slate-900 text-white">
                                                    <td class="px-5 py-3 font-bold uppercase tracking-wider text-xs">Promedio General</td>
                                                    <td colspan="4" class="px-5 py-3 text-center font-bold text-lg">
                                                        <?= number_format($promedio_general, 2); ?>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="flex justify-end">
                                    <a href="?vista=notas&lapso=<?= $lapso; ?>&descargar_boleta=1&id_estudiante=<?= $est['id']; ?>&lapso_descarga=<?= $lapso; ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-5 rounded-xl transition-all shadow-md shadow-blue-600/20 flex items-center gap-2 text-sm">
                                        <i class="fa-solid fa-file-pdf"></i> Descargar Boleta PDF
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="flex flex-col items-center justify-center py-10 bg-white rounded-xl border border-dashed border-slate-300 text-slate-400">
                                    <i class="fa-solid fa-folder-open text-4xl mb-3 text-slate-300"></i>
                                    <p class="font-medium">No hay calificaciones registradas para este lapso.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php 
                        endforeach;
                    else:
                    ?>
                        <div class="flex flex-col items-center justify-center py-16 bg-slate-50 rounded-2xl border border-dashed border-slate-300 text-slate-400">
                            <i class="fa-solid fa-user-graduate text-5xl mb-4 text-slate-300"></i>
                            <h3 class="text-lg font-medium text-slate-600">Sin Estudiantes Asignados</h3>
                            <p class="text-sm mt-1">No tienes representados vinculados a tu cuenta actualmente.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php elseif ($vista === 'asistencia'): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8 slide-up">
                    <div class="mb-6 border-b border-slate-100 pb-4">
                        <h2 class="text-2xl font-bold text-slate-800">Historial de Asistencia</h2>
                        <p class="text-sm text-slate-500 mt-1">Visualiza el registro de asistencia de tus representados</p>
                    </div>

                    <?php
                    $estudiantes_ejemplo = obtenerEstudiantes($conexion);
                    
                    if (!empty($estudiantes_ejemplo)): 
                        foreach (array_slice($estudiantes_ejemplo, 0, 3) as $est):
                            $asistencias = obtenerAsistenciaEstudiante($conexion, $est['id']);
                            $estadisticas = obtenerEstadisticasAsistenciaEstudiante($conexion, $est['id']);
                    ?>
                        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 mb-8">
                            <div class="flex flex-col md:flex-row justify-between gap-4 mb-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-lg border border-blue-200">
                                        <?= mb_substr($est['nombre'] ?? 'E', 0, 1, 'UTF-8') . mb_substr($est['apellido'] ?? 'S', 0, 1, 'UTF-8'); ?>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-800"><?= htmlspecialchars($est['nombre'] . ' ' . $est['apellido']); ?></h3>
                                        <p class="text-sm text-slate-500">C.I: <?= htmlspecialchars($est['cedula']); ?> | <?= htmlspecialchars($est['nivel_academico'] . ' - ' . $est['seccion']); ?></p>
                                    </div>
                                </div>
                            </div>

                            <?php if ($estadisticas): ?>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col items-center justify-center text-center">
                                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Días</span>
                                    <span class="text-2xl font-black text-slate-700"><?= $estadisticas['total_dias'] ?? 0; ?></span>
                                </div>
                                <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-200 shadow-sm flex flex-col items-center justify-center text-center">
                                    <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider mb-1">Presentes</span>
                                    <span class="text-2xl font-black text-emerald-700"><?= $estadisticas['presentes'] ?? 0; ?></span>
                                </div>
                                <div class="bg-rose-50 p-4 rounded-xl border border-rose-200 shadow-sm flex flex-col items-center justify-center text-center">
                                    <span class="text-xs font-bold text-rose-600 uppercase tracking-wider mb-1">Ausentes</span>
                                    <span class="text-2xl font-black text-rose-700"><?= $estadisticas['ausentes'] ?? 0; ?></span>
                                </div>
                                <div class="bg-amber-50 p-4 rounded-xl border border-amber-200 shadow-sm flex flex-col items-center justify-center text-center">
                                    <span class="text-xs font-bold text-amber-600 uppercase tracking-wider mb-1">% Asistencia</span>
                                    <span class="text-2xl font-black text-amber-700"><?= ($estadisticas['porcentaje_asistencia'] ?? 0) . '%'; ?></span>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($asistencias)): ?>
                                <div class="overflow-x-auto bg-white rounded-xl border border-slate-200 shadow-sm">
                                    <table class="w-full text-left text-sm whitespace-nowrap">
                                        <thead class="bg-slate-100 border-b border-slate-200 text-slate-600 uppercase tracking-wider text-xs font-semibold">
                                            <tr>
                                                <th class="px-5 py-3 text-center w-1/3">Fecha</th>
                                                <th class="px-5 py-3 text-center w-1/3">Hora</th>
                                                <th class="px-5 py-3 text-center w-1/3">Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <?php foreach (array_slice($asistencias, 0, 10) as $asis): 
                                                $estado_class = "";
                                                if(strtolower($asis['estado']) == 'presente') $estado_class = "bg-emerald-50 text-emerald-700 border-emerald-200";
                                                elseif(strtolower($asis['estado']) == 'ausente') $estado_class = "bg-rose-50 text-rose-700 border-rose-200";
                                                elseif(strtolower($asis['estado']) == 'tarde') $estado_class = "bg-amber-50 text-amber-700 border-amber-200";
                                                else $estado_class = "bg-slate-100 text-slate-700 border-slate-200";
                                            ?>
                                                <tr class="hover:bg-slate-50 transition-colors">
                                                    <td class="px-5 py-3 text-center font-medium text-slate-600"><?= date('d/m/Y', strtotime($asis['fecha'])); ?></td>
                                                    <td class="px-5 py-3 text-center text-slate-500"><?= htmlspecialchars($asis['hora']); ?></td>
                                                    <td class="px-5 py-3 text-center">
                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border <?= $estado_class; ?>">
                                                            <?= htmlspecialchars($asis['estado']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <p class="text-slate-400 text-xs mt-3 text-center"><i class="fa-solid fa-clock-rotate-left"></i> Mostrando últimas 10 asistencias</p>
                            <?php else: ?>
                                <div class="flex flex-col items-center justify-center py-10 bg-white rounded-xl border border-dashed border-slate-300 text-slate-400">
                                    <i class="fa-solid fa-calendar-xmark text-4xl mb-3 text-slate-300"></i>
                                    <p class="font-medium">No hay registros de asistencia en el sistema.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php 
                        endforeach;
                    else:
                    ?>
                        <div class="flex flex-col items-center justify-center py-16 bg-slate-50 rounded-2xl border border-dashed border-slate-300 text-slate-400">
                            <i class="fa-solid fa-users-slash text-5xl mb-4 text-slate-300"></i>
                            <h3 class="text-lg font-medium text-slate-600">Sin Estudiantes Asignados</h3>
                            <p class="text-sm mt-1">No tienes representados vinculados a tu cuenta actualmente.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php elseif ($vista === 'convivencia'): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8 slide-up">
                    <div class="mb-6 border-b border-slate-100 pb-4">
                        <h2 class="text-2xl font-bold text-slate-800">Monitoreo de Convivencia y Conducta</h2>
                        <p class="text-sm text-slate-500 mt-1">Bitácora de incidencias y reportes de comportamiento en tiempo real</p>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 text-blue-800 p-5 rounded-xl mb-8 flex gap-4 items-start shadow-sm">
                        <div class="bg-blue-100 p-3 rounded-lg text-blue-600 mt-1">
                            <i class="fa-solid fa-shield-halved text-xl"></i>
                        </div>
                        <div>
                            <strong class="block text-lg mb-1">Sistema de Monitoreo</strong>
                            <span class="text-blue-700 text-sm leading-relaxed">Aquí verás todos los reportes de conducta de tu representado(a) cargados por los profesores. Los reportes pueden ser: Felicitaciones, Llamados de Atención, Amonestaciones o Reportes Graves que requieren tu firma digital.</span>
                        </div>
                    </div>

                    <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-list-check text-slate-400"></i> Historial de Incidencias
                    </h3>

                    <?php
                    // Obtener todos los reportes
                    $todos_reportes = [];
                    $sql_reportes = "SELECT * FROM reportes_conducta ORDER BY fecha_reporte DESC, hora_reporte DESC LIMIT 20";
                    $resultado_reportes = @$conexion->query($sql_reportes);
                    
                    if ($resultado_reportes) {
                        while ($fila = $resultado_reportes->fetch_assoc()) {
                            $todos_reportes[] = $fila;
                        }
                    }

                    if (empty($todos_reportes)):
                    ?>
                        <div class="flex flex-col items-center justify-center py-16 bg-slate-50 rounded-2xl border border-dashed border-slate-300 text-slate-400">
                            <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mb-4 border border-slate-200 shadow-sm">
                                <i class="fa-solid fa-face-smile text-3xl text-emerald-400"></i>
                            </div>
                            <h3 class="text-lg font-medium text-slate-600">Sin reportes registrados</h3>
                            <p class="text-sm mt-1">El expediente de conducta está limpio.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php
                            $colores_tipo = [
                                'Felicitación' => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-500', 'badge_bg' => 'bg-emerald-500', 'text' => 'text-emerald-800'],
                                'Llamado de Atención' => ['bg' => 'bg-amber-50', 'border' => 'border-amber-500', 'badge_bg' => 'bg-amber-500', 'text' => 'text-amber-800'],
                                'Amonestación' => ['bg' => 'bg-rose-50', 'border' => 'border-rose-500', 'badge_bg' => 'bg-rose-500', 'text' => 'text-rose-800'],
                                'Reporte Grave' => ['bg' => 'bg-purple-50', 'border' => 'border-purple-600', 'badge_bg' => 'bg-purple-600', 'text' => 'text-purple-900']
                            ];
                            ?>
                            <?php foreach ($todos_reportes as $reporte): 
                                $estilo = $colores_tipo[$reporte['tipo_reporte']] ?? ['bg' => 'bg-slate-50', 'border' => 'border-slate-400', 'badge_bg' => 'bg-slate-400', 'text' => 'text-slate-700'];
                            ?>
                            <div class="<?= $estilo['bg'] ?> border-l-4 <?= $estilo['border'] ?> rounded-r-xl p-5 shadow-sm">
                                <div class="flex flex-col sm:flex-row justify-between items-start gap-2 mb-3">
                                    <div class="flex items-center flex-wrap gap-2">
                                        <span class="<?= $estilo['badge_bg'] ?> text-white px-3 py-1 rounded-lg text-xs font-bold tracking-wide">
                                            <?= htmlspecialchars($reporte['tipo_reporte']); ?>
                                        </span>
                                        <strong class="text-lg <?= $estilo['text'] ?> font-bold">
                                            <?= htmlspecialchars($reporte['titulo']); ?>
                                        </strong>
                                    </div>
                                    <span class="text-xs font-medium text-slate-500 bg-white px-2.5 py-1 rounded-md border border-slate-200/50 whitespace-nowrap">
                                        <i class="fa-regular fa-calendar mr-1"></i> <?= date('d/m/Y', strtotime($reporte['fecha_reporte'])); ?>
                                        <?php if (!empty($reporte['hora_reporte'])): ?>
                                            - <?= htmlspecialchars($reporte['hora_reporte']); ?>
                                        <?php endif; ?>
                                    </span>
                                </div>

                                <p class="text-slate-600 text-sm leading-relaxed mb-4">
                                    <?= nl2br(htmlspecialchars($reporte['descripcion'])); ?>
                                </p>

                                <?php 
                                // Verificar firma
                                $tiene_firma = false;
                                if (in_array($reporte['tipo_reporte'], ['Amonestación', 'Reporte Grave'])) {
                                    $usuario_actual = $_SESSION['usuario'] ?? '';
                                    $id_usuario_rep = crc32($usuario_actual) & 0x7fffffff;
                                    $sql_firma = "SELECT confirmado FROM firmas_amonestaciones 
                                                 WHERE id_reporte = {$reporte['id']} AND id_representante = $id_usuario_rep LIMIT 1";
                                    $resultado_firma = @$conexion->query($sql_firma);
                                    if ($resultado_firma && $resultado_firma->num_rows > 0) {
                                        $fila_firma = $resultado_firma->fetch_assoc();
                                        $tiene_firma = $fila_firma['confirmado'];
                                    }
                                }
                                
                                if (in_array($reporte['tipo_reporte'], ['Amonestación', 'Reporte Grave']) && !$tiene_firma):
                                ?>
                                <div class="bg-white p-4 rounded-xl border border-rose-200 shadow-sm mt-3">
                                    <p class="text-sm text-rose-600 font-medium mb-3 flex items-center gap-2">
                                        <i class="fa-solid fa-triangle-exclamation"></i> Esta amonestación requiere tu firma digital de notificado.
                                    </p>
                                    <form method="POST" class="flex flex-col sm:flex-row gap-3">
                                        <input type="hidden" name="accion" value="firmar_amonestacion">
                                        <input type="hidden" name="id_reporte" value="<?= $reporte['id']; ?>">
                                        <textarea name="observaciones" placeholder="Añadir observaciones (opcional)" class="flex-1 px-4 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 text-sm resize-none" rows="1"></textarea>
                                        <button type="submit" class="bg-emerald-500 hover:bg-emerald-600 text-white font-semibold py-2 px-6 rounded-lg transition-colors shadow-sm shadow-emerald-500/20 whitespace-nowrap flex items-center justify-center gap-2 text-sm">
                                            <i class="fa-solid fa-check-double"></i> Leído y Conforme
                                        </button>
                                    </form>
                                </div>
                                <?php elseif ($tiene_firma): ?>
                                <div class="bg-emerald-50 p-3 rounded-lg border border-emerald-200 mt-3 inline-flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs">
                                        <i class="fa-solid fa-check"></i>
                                    </div>
                                    <span class="text-sm font-semibold text-emerald-700">Firmado digitalmente como constancia de lectura</span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php
                // Procesar firma
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'firmar_amonestacion') {
                    $id_reporte = (int)$_POST['id_reporte'];
                    $observaciones = $conexion->real_escape_string($_POST['observaciones'] ?? '');
                    $usuario_actual = $_SESSION['usuario'] ?? '';
                    $id_usuario_rep = crc32($usuario_actual) & 0x7fffffff;
                    
                    $sql_reporte = "SELECT id_estudiante FROM reportes_conducta WHERE id = $id_reporte LIMIT 1";
                    $resultado_reporte = @$conexion->query($sql_reporte);
                    
                    if ($resultado_reporte && $resultado_reporte->num_rows > 0) {
                        $reporte_data = $resultado_reporte->fetch_assoc();
                        $datos_firma = [
                            'id_reporte' => $id_reporte,
                            'id_representante' => $id_usuario_rep,
                            'id_estudiante' => $reporte_data['id_estudiante'],
                            'observaciones' => $observaciones
                        ];
                        
                        if (registrarFirmaAmonestacion($conexion, $datos_firma)) {
                            echo "<script>alert('Amonestación firmada digitalmente. Constancia registrada.'); location.href='?vista=convivencia';</script>";
                        }
                    }
                }
                ?>
            </div>
            
            <footer class="mt-12 border-t border-slate-200 pt-6 text-center text-sm text-slate-400 pb-8">
                <p>&copy; <?= $año_actual; ?> <?= $nombre_institucion; ?> - Todos los derechos reservados.</p>
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