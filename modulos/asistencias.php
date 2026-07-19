<?php
session_start();

if (!isset($_SESSION['usuario_logueado']) || !$_SESSION['usuario_logueado']) {
    header('Location: ../login.php');
    exit;
}

$rol_actual = $_SESSION['rol'] ?? 'Usuario';
if ($rol_actual !== 'Administrador') {
    header('Location: ../no-autorizado.php');
    exit;
}


if (isset($_GET['getip'])) {
    header('Content-Type: text/plain');
    
    $ip = null;
    
    
    if (!empty($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] !== '127.0.0.1') {
        $ip = $_SERVER['SERVER_ADDR'];
    } else {
        
        if (php_uname('s') === 'Windows NT') {
            $output = @shell_exec('ipconfig /all');
            if (preg_match('/IPv4 Address.*:\s*(\d+\.\d+\.\d+\.\d+)/', $output, $matches)) {
                $ip = $matches[1];
            }
        } else {
           
            $output = @shell_exec("hostname -I 2>/dev/null || ifconfig 2>/dev/null | grep 'inet ' | grep -v '127.0.0.1' | head -n1 | awk '{print $2}'");
            if (!empty($output)) {
                $ips = explode(' ', trim($output));
                if (!empty($ips[0]) && preg_match('/\d+\.\d+\.\d+\.\d+/', $ips[0])) {
                    $ip = $ips[0];
                }
            }
        }
    }
    
    echo $ip ? htmlspecialchars($ip) : 'null';
    exit;
}



$nombre_institucion = "Escuela Técnica Pedro Garcia Leal";
$modulo_actual = "Asistencias";
$año_actual = date("Y");

$vista = $_GET['vista'] ?? 'principal';
$fecha = $_GET['fecha'] ?? date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $modulo_actual; ?> - <?php echo $nombre_institucion; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    
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

       
        #qrcode img {
            margin: 0 auto;
            border-radius: 8px;
        }

        .data-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
        .data-table th { background: #f8fafc; color: #64748b; padding: 12px 15px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0; }
        .data-table td { padding: 15px; border-bottom: 1px solid #e2e8f0; color: #334155; }
        .data-table tr:hover { background-color: #f8fafc; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 bg-slate-50">

    <aside id="sidebar" class="w-72 bg-slate-900 text-white hidden lg:flex flex-col shadow-2xl z-30 transition-all duration-300 absolute lg:relative h-full">
        <div class="p-6 flex items-center justify-between lg:justify-start gap-4 border-b border-slate-800">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-emerald-500 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/30 text-white shrink-0">
                    <i class="fa-solid fa-school text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-sm font-bold leading-tight uppercase tracking-wide text-slate-300">Escuela Técnica</h1>
                    <p class="text-xs text-emerald-400 font-medium">Pedro Garcia Leal</p>
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
            <a href="asistencias.php" class="flex items-center gap-3 px-4 py-3 bg-emerald-500 text-white font-bold rounded-xl shadow-md shadow-emerald-500/20 transition-colors">
                <i class="fa-solid fa-qrcode w-5"></i> Asistencias
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
            
            <a href="comunicacion.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-colors">
                <i class="fa-solid fa-handshake w-5 text-slate-400"></i> Reuniones
            </a>
            
            <a href="pasantias.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-all">
                <i class="fa-solid fa-briefcase w-5 text-slate-400"></i> Pasantías
            </a>
        </nav>

        <div class="p-4 border-t border-slate-800 bg-slate-950/50">
            <div class="flex items-center gap-3 px-2">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($rol_actual) ?>&background=10b981&color=fff" alt="Perfil" class="w-10 h-10 rounded-full border-2 border-slate-700">
                <div class="overflow-hidden">
                    <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($rol_actual); ?></p>
                    <p class="text-xs text-slate-400">Sesión Activa</p>
                </div>
            </div>
            <form method="POST" action="../cerrar-sesion.php" class="mt-3">
                <button type="submit" class="w-full bg-slate-800 hover:bg-red-500/20 text-slate-300 hover:text-red-400 border border-slate-700 hover:border-red-500/30 py-2 rounded-lg text-xs font-semibold transition-all">
                    Cerrar Sesión
                </button>
            </form>
        </div>
    </aside>

    <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/50 z-20 hidden lg:hidden backdrop-blur-sm"></div>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-20 bg-white/80 backdrop-blur-md shadow-sm flex items-center justify-between px-6 lg:px-8 z-10 border-b border-slate-200">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden text-slate-500 hover:text-emerald-500 transition-colors text-xl p-2 -ml-2 rounded-lg hover:bg-slate-100">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h2 class="text-xl lg:text-2xl font-bold text-slate-800 leading-tight">Módulo de Asistencias</h2>
                    <p class="text-xs text-slate-500 hidden sm:block">Control de ingreso y registro automatizado</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <span class="px-4 py-2 bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl border border-slate-200 flex items-center gap-2 shadow-sm">
                    <i class="fa-regular fa-calendar text-emerald-500"></i> <span class="hidden sm:inline">Fecha actual: </span><?= htmlspecialchars(date('d/m/Y', strtotime($fecha))); ?>
                </span>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 lg:p-8 w-full">
            <div class="max-w-6xl mx-auto space-y-6">
                
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <a href="../index.php" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-emerald-600 transition-colors bg-white px-4 py-2.5 rounded-xl border border-slate-200 shadow-sm hover:shadow">
                        <i class="fa-solid fa-arrow-left"></i> Volver al Inicio
                    </a>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-2 flex flex-col sm:flex-row gap-2">
                    <button onclick="cambiarVista('principal')" class="tab-btn flex-1 py-3 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 <?= ($vista === 'principal') ? 'bg-slate-900 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50' ?>" id="btn-principal">
                        <i class="fa-solid fa-qrcode"></i> Generar QR
                    </button>
                    <button onclick="cambiarVista('registros')" class="tab-btn flex-1 py-3 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 <?= ($vista === 'registros') ? 'bg-slate-900 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50' ?>" id="btn-registros">
                        <i class="fa-solid fa-clipboard-list"></i> Registros del Día
                    </button>
                    <button onclick="cambiarVista('estadisticas')" class="tab-btn flex-1 py-3 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 <?= ($vista === 'estadisticas') ? 'bg-slate-900 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50' ?>" id="btn-estadisticas">
                        <i class="fa-solid fa-chart-pie"></i> Estadísticas
                    </button>
                </div>

                <div id="principal" class="seccion-vista <?= ($vista === 'principal') ? 'activa' : ''; ?>">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden slide-up p-6 md:p-12 text-center flex flex-col items-center">
                        <div class="w-16 h-16 bg-emerald-50 rounded-full flex items-center justify-center mb-4 border border-emerald-100">
                            <i class="fa-solid fa-mobile-screen-button text-2xl text-emerald-500"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-slate-800 mb-2">Código QR de Asistencia</h2>
                        <p class="text-slate-500 mb-8 max-w-md">Proyecta este código para que los estudiantes lo escaneen con sus dispositivos móviles. Al hacerlo, registrarán automáticamente su asistencia del día de hoy.</p>
                        
                        <div class="p-4 bg-white border border-slate-200 rounded-2xl shadow-sm inline-block relative group transition-transform hover:scale-105 duration-300">
                            <!-- Marco decorativo -->
                            <div class="absolute -inset-0.5 bg-gradient-to-r from-emerald-400 to-teal-500 rounded-2xl opacity-20 group-hover:opacity-100 transition duration-300 blur"></div>
                            
                            <div class="relative bg-white p-6 rounded-xl border border-slate-100">
                                <div id="qrcode" class="flex justify-center items-center min-h-[256px] min-w-[256px]">
                                    <!-- El QR se generará aquí -->
                                    <div class="text-slate-400 flex flex-col items-center gap-3">
                                        <i class="fa-solid fa-spinner fa-spin text-3xl"></i>
                                        <span>Generando código...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 bg-emerald-50 border border-emerald-200 text-emerald-700 px-6 py-4 rounded-xl flex items-center gap-3 text-sm max-w-lg mx-auto text-left">
                            <i class="fa-solid fa-circle-info text-emerald-500 text-lg"></i>
                            <p>El código es válido únicamente para la fecha en curso (<strong><?= date('d/m/Y'); ?></strong>). Asegúrate de estar conectado a la red local del plantel para que la IP sea accesible.</p>
                        </div>
                        
                        <button onclick="generarQR()" class="mt-6 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2.5 px-6 rounded-xl transition-colors shadow-sm border border-slate-200 flex items-center gap-2">
                            <i class="fa-solid fa-arrows-rotate"></i> Recargar QR
                        </button>
                    </div>
                </div>

                <div id="registros" class="seccion-vista <?= ($vista === 'registros') ? 'activa' : ''; ?>">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden slide-up p-6 md:p-8">
                        <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-100 pb-5">
                            <div>
                                <h2 class="text-xl font-bold text-slate-800">Registros del Día</h2>
                                <p class="text-sm text-slate-500 mt-1">Listado de asistencias confirmadas para la fecha seleccionada.</p>
                            </div>
                            <form method="GET" class="flex items-center gap-2">
                                <input type="hidden" name="vista" value="registros">
                                <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>" class="px-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                                <button type="submit" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-colors">Filtrar</button>
                            </form>
                        </div>

                        
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Hora</th>
                                        <th>Estudiante</th>
                                        <th>Sección</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><i class="fa-regular fa-clock text-slate-400 mr-2"></i> 07:15 AM</td>
                                        <td><strong>Carlos Mendoza</strong></td>
                                        <td>4to Año A</td>
                                        <td><span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-lg text-xs font-bold border border-emerald-200"><i class="fa-solid fa-check mr-1"></i> Presente</span></td>
                                    </tr>
                                    <tr>
                                        <td><i class="fa-regular fa-clock text-slate-400 mr-2"></i> 07:22 AM</td>
                                        <td><strong>Ana Karina López</strong></td>
                                        <td>5to Año B</td>
                                        <td><span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-lg text-xs font-bold border border-emerald-200"><i class="fa-solid fa-check mr-1"></i> Presente</span></td>
                                    </tr>
                                    <tr>
                                        <td><i class="fa-regular fa-clock text-slate-400 mr-2"></i> --:--</td>
                                        <td><strong>Luis Alberto Rojas</strong></td>
                                        <td>3er Año A</td>
                                        <td><span class="bg-red-100 text-red-700 px-3 py-1 rounded-lg text-xs font-bold border border-red-200"><i class="fa-solid fa-xmark mr-1"></i> Ausente</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div id="estadisticas" class="seccion-vista <?= ($vista === 'estadisticas') ? 'activa' : ''; ?>">
                    <div class="slide-up">
                        <div class="mb-6">
                            <h2 class="text-xl font-bold text-slate-800">Resumen Estadístico</h2>
                            <p class="text-sm text-slate-500 mt-1">Métricas de asistencia correspondientes al día de hoy.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="bg-gradient-to-br from-emerald-500 to-emerald-700 rounded-2xl p-6 shadow-lg shadow-emerald-500/20 text-white flex flex-col items-center text-center">
                                <div class="bg-white/20 p-3 rounded-xl mb-3">
                                    <i class="fa-solid fa-users text-2xl"></i>
                                </div>
                                <h3 class="text-emerald-100 font-medium text-sm">Total Matriculados</h3>
                                <div class="text-4xl font-bold mt-1">450</div>
                            </div>
                            
                            <div class="bg-gradient-to-br from-sky-500 to-blue-600 rounded-2xl p-6 shadow-lg shadow-blue-500/20 text-white flex flex-col items-center text-center">
                                <div class="bg-white/20 p-3 rounded-xl mb-3">
                                    <i class="fa-solid fa-check-to-slot text-2xl"></i>
                                </div>
                                <h3 class="text-blue-100 font-medium text-sm">Presentes Hoy</h3>
                                <div class="text-4xl font-bold mt-1">412</div>
                            </div>
                            
                            <div class="bg-gradient-to-br from-rose-500 to-red-600 rounded-2xl p-6 shadow-lg shadow-red-500/20 text-white flex flex-col items-center text-center">
                                <div class="bg-white/20 p-3 rounded-xl mb-3">
                                    <i class="fa-solid fa-user-xmark text-2xl"></i>
                                </div>
                                <h3 class="text-rose-100 font-medium text-sm">Inasistencias</h3>
                                <div class="text-4xl font-bold mt-1">38</div>
                            </div>
                        </div>
                    </div>
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
            
            
            if (vistaId === 'principal') {
                generarQR();
            }
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

        
        let qrCodeObj = null;
        async function generarQR() {
            const qrContainer = document.getElementById('qrcode');
            
            try {
                
                qrContainer.innerHTML = '<div class="text-slate-400 flex flex-col items-center gap-3"><i class="fa-solid fa-spinner fa-spin text-3xl"></i><span>Generando código...</span></div>';
                
                const response = await fetch('?getip=1');
                const ip = await response.text();
                
                
                qrContainer.innerHTML = '';
                
                let qrText = '';
                if (ip !== 'null' && ip.trim() !== '') {
                    qrText = `http://${ip}/asistencia`;
                } else {
                   
                    qrText = window.location.origin + '/asistencia';
                }

                
                qrCodeObj = new QRCode(qrContainer, {
                    text: qrText,
                    width: 256,
                    height: 256,
                    colorDark : "#0f172a", 
                    colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.H
                });
            } catch (error) {
                console.error("Error al generar el QR:", error);
                qrContainer.innerHTML = '<div class="text-red-500 flex flex-col items-center gap-2"><i class="fa-solid fa-circle-exclamation text-3xl"></i><span>Error al generar código QR</span></div>';
            }
        }

        
        document.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            const vistaInicial = params.get('vista') || 'principal';
            
            if (vistaInicial === 'principal') {
                generarQR();
            }
        });
    </script>
</body>
</html>