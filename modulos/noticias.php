<?php
session_start();

// Simulación para previsualización
if (!isset($_SESSION['usuario_logueado'])) {
    $_SESSION['usuario_logueado'] = true;
    $_SESSION['rol'] = 'Administrador';
}

$rol_actual = $_SESSION['rol'] ?? 'Usuario';
$nombre_institucion = "Escuela Técnica Pedro Garcia Leal";
$año_actual = date("Y");

// Secretaria y Administrador pueden publicar noticias
$puede_publicar = ($rol_actual === 'Administrador' || $rol_actual === 'Secretaria');

// CORRECCIÓN: Administrador y Secretaria pueden editar y eliminar
$puede_editar_eliminar = ($rol_actual === 'Administrador' || $rol_actual === 'Secretaria');

if (!isset($_SESSION['noticias_data'])) {
    $_SESSION['noticias_data'] = [
        [
            'id' => 'noticia_1', // ID agregado
            'tipo' => 'Importante',
            'fecha' => '12 de Julio, 2026',
            'autor' => 'Dirección',
            'titulo' => 'Reunión General de Representantes',
            'cuerpo' => 'Se convoca a todos los representantes a la reunión de fin de lapso para la entrega de boletines y discusión de normas de convivencia. Asistencia obligatoria en las instalaciones centrales.'
        ],
        [
            'id' => 'noticia_2', // ID agregado
            'tipo' => 'Información',
            'fecha' => '10 de Julio, 2026',
            'autor' => 'Coordinación Académica',
            'titulo' => 'Cronograma de Evaluaciones Publicado',
            'cuerpo' => 'El cronograma detallado del 3er lapso ya se encuentra disponible en las carteleras de la institución y en los paneles digitales de los docentes.'
        ],
        [
            'id' => 'noticia_3', // ID agregado
            'tipo' => 'Evento',
            'fecha' => '05 de Julio, 2026',
            'autor' => 'Comité de Cultura',
            'titulo' => 'Festival Cultural Institucional',
            'cuerpo' => 'Invitamos a toda la comunidad estudiantil a participar en el próximo festival de danzas y teatro que se llevará a cabo el próximo viernes en la cancha techada.'
        ]
    ];
}

// Procesar Nueva Noticia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_noticia']) && $puede_publicar) {
    $nueva = [
        'id' => uniqid('noticia_'),
        'tipo' => htmlspecialchars($_POST['tipo'] ?? ''),
        'fecha' => date('d \d\e F, Y'),
        'autor' => $rol_actual,
        'titulo' => htmlspecialchars($_POST['titulo'] ?? ''),
        'cuerpo' => htmlspecialchars($_POST['cuerpo'] ?? '')
    ];
    array_unshift($_SESSION['noticias_data'], $nueva);
    header("Location: noticias.php");
    exit;
}

// Procesar Edición de Noticia (Solo Secretaria)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_noticia']) && $puede_editar_eliminar) {
    $id_editar = $_POST['id_noticia'] ?? '';
    foreach ($_SESSION['noticias_data'] as $key => $noticia) {
        if ($noticia['id'] === $id_editar) {
            $_SESSION['noticias_data'][$key]['tipo'] = htmlspecialchars($_POST['tipo'] ?? '');
            $_SESSION['noticias_data'][$key]['titulo'] = htmlspecialchars($_POST['titulo'] ?? '');
            $_SESSION['noticias_data'][$key]['cuerpo'] = htmlspecialchars($_POST['cuerpo'] ?? '');
            break;
        }
    }
    header("Location: noticias.php");
    exit;
}

// Procesar Eliminación de Noticia (Solo Secretaria)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_noticia']) && $puede_editar_eliminar) {
    $id_eliminar = $_POST['id_eliminar'] ?? '';
    foreach ($_SESSION['noticias_data'] as $key => $noticia) {
        if ($noticia['id'] === $id_eliminar) {
            unset($_SESSION['noticias_data'][$key]);
            $_SESSION['noticias_data'] = array_values($_SESSION['noticias_data']); // Reindexar
            break;
        }
    }
    header("Location: noticias.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Muro de Noticias - E.T. Pedro Garcia Leal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Animaciones para las tarjetas */
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .slide-up { animation: slideUp 0.5s ease-out forwards; opacity: 0; }
        
        /* Estilos base del panel */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 bg-slate-50">

    <aside id="sidebar" class="w-72 bg-slate-900 text-white hidden lg:flex flex-col shadow-2xl z-30 transition-all duration-300 absolute lg:relative h-full">
        <div class="p-6 flex items-center justify-between lg:justify-start gap-4 border-b border-slate-800">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30 text-white shrink-0">
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

            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 pt-4 mb-2">Secciones y Canales</div>
            <a href="noticias.php" class="flex items-center gap-3 px-4 py-3 bg-blue-500 text-white font-bold rounded-xl shadow-md shadow-blue-500/20 transition-all">
                <i class="fa-solid fa-bullhorn w-5"></i> Muro de Noticias
            </a>
        </nav>

        <div class="p-4 border-t border-slate-800 bg-slate-950/50">
            <div class="flex items-center gap-3 px-2">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($rol_actual) ?>&background=3b82f6&color=fff" alt="Perfil" class="w-10 h-10 rounded-full border-2 border-slate-700">
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
                <button onclick="toggleSidebar()" class="lg:hidden text-slate-500 hover:text-blue-500 transition-colors text-xl p-2 -ml-2 rounded-lg hover:bg-slate-100">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h2 class="text-xl lg:text-2xl font-bold text-slate-800 leading-tight">Muro de Noticias</h2>
                    <p class="text-xs text-slate-500 hidden sm:block">Anuncios y comunicados oficiales de la institución</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <span class="px-4 py-2 bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl border border-slate-200 flex items-center gap-2 shadow-sm">
                    <i class="fa-regular fa-calendar text-blue-500"></i> <span class="hidden sm:inline">Hoy: </span><?= date('d/m/Y'); ?>
                </span>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 lg:p-8">
            <div class="max-w-4xl mx-auto space-y-6">
                
                <!-- Acciones Top -->
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <a href="../index.php" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-800 transition-colors bg-white px-4 py-2 rounded-lg border border-slate-200 shadow-sm hover:shadow">
                        <i class="fa-solid fa-arrow-left"></i> Volver al Inicio
                    </a>
                    
                    <?php if($puede_publicar): ?>
                    <button onclick="abrirModalNueva()" class="w-full sm:w-auto bg-amber-500 hover:bg-amber-600 text-white font-medium py-2 px-6 rounded-xl transition-colors shadow-sm flex items-center justify-center gap-2">
                        <i class="fa-solid fa-pen-to-square"></i> Publicar Comunicado
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Feed de Comunicados -->
                <div class="space-y-6 mt-4">
                    <?php if (empty($_SESSION['noticias_data'])): ?>
                        <div class="text-center py-12 bg-white rounded-2xl border border-dashed border-slate-300">
                            <i class="fa-regular fa-newspaper text-5xl text-slate-300 mb-4"></i>
                            <h3 class="text-lg font-medium text-slate-700">No hay noticias publicadas</h3>
                            <p class="text-slate-500 mt-1">Los comunicados recientes aparecerán aquí.</p>
                        </div>
                    <?php else: ?>
                        <?php 
                        $delay = 0;
                        foreach($_SESSION['noticias_data'] as $noticia): 
                            
                            // Asignación dinámica de colores
                            $badgeColor = 'bg-slate-100 text-slate-700';
                            $iconType = 'fa-circle-info';
                            
                            if ($noticia['tipo'] === 'Importante') {
                                $badgeColor = 'bg-red-100 text-red-700 border border-red-200';
                                $iconType = 'fa-triangle-exclamation';
                            }
                            else if ($noticia['tipo'] === 'Información') {
                                $badgeColor = 'bg-blue-100 text-blue-700 border border-blue-200';
                                $iconType = 'fa-info';
                            }
                            else if ($noticia['tipo'] === 'Evento') {
                                $badgeColor = 'bg-amber-100 text-amber-700 border border-amber-200';
                                $iconType = 'fa-calendar-star';
                            }
                        ?>
                            <article class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8 slide-up relative" style="animation-delay: <?= $delay ?>s;">
                                
                                <div class="flex justify-between items-start mb-4">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider <?= $badgeColor ?>">
                                        <i class="fa-solid <?= $iconType ?>"></i> <?= htmlspecialchars($noticia['tipo'] ?? ''); ?>
                                    </span>
                                    <div class="text-right">
                                        <span class="block text-sm font-semibold text-slate-800"><?= htmlspecialchars($noticia['autor'] ?? ''); ?></span>
                                        <span class="text-xs text-slate-400"><i class="fa-regular fa-clock mr-1"></i> <?= htmlspecialchars($noticia['fecha'] ?? ''); ?></span>
                                    </div>
                                </div>
                                
                                <h2 class="text-2xl font-bold text-slate-900 mb-3 pr-20"><?= htmlspecialchars($noticia['titulo'] ?? ''); ?></h2>
                                <p class="text-slate-600 leading-relaxed text-[15px]">
                                    <?= nl2br(htmlspecialchars($noticia['cuerpo'] ?? '')); ?>
                                </p>
                                
                                <div class="mt-6 pt-4 border-t border-slate-100 flex justify-between items-center">
                                    <button class="text-slate-400 hover:text-amber-500 text-sm font-medium flex items-center gap-1.5 transition-colors">
                                        <i class="fa-solid fa-share-nodes"></i> Compartir
                                    </button>
                                    
                                    <?php if($puede_editar_eliminar): ?>
                                    <div class="flex gap-2">
                                        <!-- Botón Editar con data-attributes seguros -->
                                        <button 
                                            data-id="<?= htmlspecialchars($noticia['id'] ?? '') ?>"
                                            data-tipo="<?= htmlspecialchars($noticia['tipo'] ?? '') ?>"
                                            data-titulo="<?= htmlspecialchars($noticia['titulo'] ?? '') ?>"
                                            data-cuerpo="<?= htmlspecialchars($noticia['cuerpo'] ?? '') ?>"
                                            onclick="abrirModalEditar(this)" 
                                            class="text-blue-500 hover:bg-blue-50 px-3 py-1.5 rounded-lg text-sm font-medium flex items-center gap-1.5 transition-colors">
                                            <i class="fa-solid fa-pen"></i> Editar
                                        </button>
                                        
                                        <!-- Botón Eliminar -->
                                        <form method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este comunicado?');" class="inline">
                                            <input type="hidden" name="id_eliminar" value="<?= htmlspecialchars($noticia['id'] ?? '') ?>">
                                            <button type="submit" name="eliminar_noticia" class="text-red-500 hover:bg-red-50 px-3 py-1.5 rounded-lg text-sm font-medium flex items-center gap-1.5 transition-colors">
                                                <i class="fa-solid fa-trash-can"></i> Eliminar
                                            </button>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php 
                            $delay += 0.1;
                        endforeach; 
                        ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php if($puede_publicar): ?>
    <!-- Modal para Nueva Noticia -->
    <div id="modalNuevaNoticia" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 fade-in px-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg slide-up overflow-hidden">
            <div class="flex justify-between items-center p-6 border-b border-slate-100 bg-slate-50">
                <h2 class="text-xl font-bold text-slate-800">Publicar Nuevo Comunicado</h2>
                <button onclick="cerrarModalNueva()" class="text-slate-400 hover:text-red-500 transition-colors">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Categoría del Anuncio</label>
                    <select name="tipo" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 bg-slate-50 focus:bg-white transition-colors" required>
                        <option value="Información">Información General (Azul)</option>
                        <option value="Importante">Aviso Importante (Rojo)</option>
                        <option value="Evento">Evento Académico (Ámbar)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Título de la Noticia</label>
                    <input type="text" name="titulo" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 bg-slate-50 focus:bg-white transition-colors" required placeholder="Ej: Entrega de boletines de 3er lapso">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Cuerpo del Mensaje</label>
                    <textarea name="cuerpo" rows="6" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 bg-slate-50 focus:bg-white transition-colors" required placeholder="Redacte toda la información necesaria aquí..."></textarea>
                </div>
                <button type="submit" name="nueva_noticia" class="w-full bg-amber-500 hover:bg-amber-600 text-white font-medium py-3 rounded-xl transition-colors mt-2 shadow-md">
                    Publicar en el Muro
                </button>
            </form>
        </div>
    </div>

    <!-- Modal para Editar Noticia -->
    <div id="modalEditarNoticia" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 fade-in px-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg slide-up overflow-hidden">
            <div class="flex justify-between items-center p-6 border-b border-slate-100 bg-slate-50">
                <h2 class="text-xl font-bold text-slate-800">Editar Comunicado</h2>
                <button onclick="cerrarModalEditar()" class="text-slate-400 hover:text-red-500 transition-colors">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-5">
                <input type="hidden" name="id_noticia" id="edit_id_noticia">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Categoría del Anuncio</label>
                    <select name="tipo" id="edit_tipo" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 bg-slate-50 focus:bg-white transition-colors" required>
                        <option value="Información">Información General (Azul)</option>
                        <option value="Importante">Aviso Importante (Rojo)</option>
                        <option value="Evento">Evento Académico (Ámbar)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Título de la Noticia</label>
                    <input type="text" name="titulo" id="edit_titulo" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 bg-slate-50 focus:bg-white transition-colors" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Cuerpo del Mensaje</label>
                    <textarea name="cuerpo" id="edit_cuerpo" rows="6" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 bg-slate-50 focus:bg-white transition-colors" required></textarea>
                </div>
                <button type="submit" name="editar_noticia" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 rounded-xl transition-colors mt-2 shadow-md">
                    Guardar Cambios
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

        // Funciones para Modal Nueva Noticia
        function abrirModalNueva() {
            const modal = document.getElementById('modalNuevaNoticia');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function cerrarModalNueva() {
            const modal = document.getElementById('modalNuevaNoticia');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // Funciones para Modal Editar Noticia
        function abrirModalEditar(btn) {
            // Llenar los campos del formulario con los datos extraídos de los data-attributes
            document.getElementById('edit_id_noticia').value = btn.dataset.id;
            document.getElementById('edit_tipo').value = btn.dataset.tipo;
            document.getElementById('edit_titulo').value = btn.dataset.titulo;
            document.getElementById('edit_cuerpo').value = btn.dataset.cuerpo; 

            const modal = document.getElementById('modalEditarNoticia');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function cerrarModalEditar() {
            const modal = document.getElementById('modalEditarNoticia');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
    <?php endif; ?>
</body>
</html>