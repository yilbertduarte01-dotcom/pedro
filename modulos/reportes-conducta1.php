<?php
session_start();

if (!isset($_SESSION['usuario_logueado']) || !$_SESSION['usuario_logueado']) {
    header('Location: ../login.php');
    exit;
}

if ($_SESSION['rol'] !== 'Profesor' && $_SESSION['rol'] !== 'Administrador') {
    header('Location: ../index.php');
    exit;
}

require_once '../includes/config.php';
require_once '../includes/db-functions.php';

$nombre_institucion = "Escuela Técnica Pedro Garcia Leal";
$rol_actual = $_SESSION['rol'];
$usuario_actual = $_SESSION['nombre'] ?? 'Usuario';
$usuario_username = $_SESSION['usuario'] ?? 'usuario';
$id_profesor = crc32($usuario_username) & 0x7fffffff;

$vista = $_GET['vista'] ?? 'principal';
$seccion_filtro = $_GET['seccion'] ?? '';
$mensaje = "";
$tipo_mensaje = "";

// Tipos de reportes disponibles
$tipos_reportes = [
    'Felicitación' => '#10b981',
    'Llamado de Atención' => '#f59e0b',
    'Amonestación' => '#ef4444',
    'Reporte Grave' => '#7c3aed'
];

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        
        // CREAR REPORTE DE CONDUCTA
        if ($_POST['accion'] === 'crear_reporte') {
            $id_estudiante = (int)$_POST['id_estudiante'];
            $seccion = $conexion->real_escape_string($_POST['seccion']);
            $tipo_reporte = $conexion->real_escape_string($_POST['tipo_reporte']);
            $titulo = $conexion->real_escape_string($_POST['titulo']);
            $descripcion = $conexion->real_escape_string($_POST['descripcion']);
            $fecha_reporte = $conexion->real_escape_string($_POST['fecha_reporte'] ?? date('Y-m-d'));
            
            if (!empty($id_estudiante) && !empty($seccion) && !empty($tipo_reporte) && !empty($titulo)) {
                $datos = [
                    'id_estudiante' => $id_estudiante,
                    'seccion' => $seccion,
                    'id_profesor' => $id_profesor,
                    'tipo_reporte' => $tipo_reporte,
                    'titulo' => $titulo,
                    'descripcion' => $descripcion,
                    'fecha_reporte' => $fecha_reporte
                ];
                
                if (crearReporteConducta($conexion, $datos)) {
                    $mensaje = "Reporte de conducta registrado exitosamente.";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al registrar el reporte.";
                    $tipo_mensaje = "error";
                }
            }
        }
        
        // ELIMINAR REPORTE
        elseif ($_POST['accion'] === 'eliminar_reporte') {
            $id_reporte = (int)$_POST['id_reporte'];
            
            if (eliminarReporteConducta($conexion, $id_reporte)) {
                $mensaje = "Reporte eliminado.";
                $tipo_mensaje = "success";
            }
        }
    }
}

// Obtener reportes del profesor
$reportes = obtenerReportesPorProfesor($conexion, $id_profesor);

// Filtrar por sección si se selecciona
if (!empty($seccion_filtro)) {
    $reportes = array_filter($reportes, function($r) use ($seccion_filtro) {
        return $r['seccion'] === $seccion_filtro;
    });
}

// Obtener secciones únicas para el filtro
$secciones = [];
foreach ($reportes as $r) {
    if (!in_array($r['seccion'], $secciones)) {
        $secciones[] = $r['seccion'];
    }
}

// Estudiantes de ejemplo (en producción, obtendrías estos de BD)
$estudiantes_ejemplo = [
    ['id' => 1, 'nombre' => 'Juan García', 'seccion' => '1ro A'],
    ['id' => 2, 'nombre' => 'María López', 'seccion' => '1ro A'],
    ['id' => 3, 'nombre' => 'Carlos Rodríguez', 'seccion' => '1ro B'],
    ['id' => 4, 'nombre' => 'Ana Martínez', 'seccion' => '2do A'],
    ['id' => 5, 'nombre' => 'Luis Pérez', 'seccion' => '2do B'],
];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Conducta - <?php echo $nombre_institucion; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }

        .header {
            background: linear-gradient(135deg, #7c3aed, #5b21b6);
            color: white;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .volver-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #6b7280;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .volver-btn:hover {
            background: #4b5563;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            border-bottom: 2px solid #e0e0e0;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 12px 20px;
            background: transparent;
            border: none;
            cursor: pointer;
            font-weight: bold;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab-btn.active {
            color: #7c3aed;
            border-bottom-color: #7c3aed;
        }

        .content {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: none;
        }

        .content.active {
            display: block;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
            box-sizing: border-box;
        }

        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        button {
            background: #7c3aed;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }

        button:hover {
            background: #5b21b6;
        }

        .mensaje {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .mensaje.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        .mensaje.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .reporte-card {
            border-left: 5px solid #ccc;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 6px;
            background: #f9fafb;
        }

        .reporte-titulo {
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 8px;
            font-size: 16px;
        }

        .reporte-meta {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .reporte-descripcion {
            color: #4b5563;
            margin: 10px 0;
            line-height: 1.5;
        }

        .reporte-tipo {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            color: white;
            margin-bottom: 10px;
        }

        .reporte-acciones {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }

        .reporte-acciones button {
            padding: 6px 12px;
            font-size: 12px;
            flex: 1;
        }

        .reporte-acciones button.eliminar {
            background: #ef4444;
        }

        .reporte-acciones button.eliminar:hover {
            background: #dc2626;
        }

        .sin-reportes {
            text-align: center;
            color: #999;
            padding: 30px;
            background: #f9fafb;
            border-radius: 6px;
        }

        .form-section {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .form-section h3 {
            color: #1f2937;
            margin-bottom: 15px;
        }

        .tipo-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: bold;
            color: white;
            margin-right: 10px;
        }

        .filtro-section {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .reporte-acciones {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Reportes de Conducta</h1>
            <p>Bitácora de Incidencias y Reportes de Comportamiento Estudiantil</p>
        </div>
    </header>

    <main class="container">
        <a href="../index.php" class="volver-btn">← Volver al Inicio</a>

        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <!-- TABS -->
        <div class="tabs">
            <button class="tab-btn <?php echo ($vista === 'principal' || $vista === 'crear') ? 'active' : ''; ?>" onclick="irA('crear')">
                + Crear Reporte
            </button>
            <button class="tab-btn <?php echo ($vista === 'historial') ? 'active' : ''; ?>" onclick="irA('historial')">
                📋 Historial de Reportes
            </button>
        </div>

        <!-- SECCIÓN CREAR REPORTE -->
        <div class="content <?php echo ($vista === 'crear' || $vista === 'principal') ? 'active' : ''; ?>" id="crear">
            <h2>Registrar Nuevo Reporte de Conducta</h2>
            <p style="color: #666; margin-bottom: 20px;">
                Documenta las incidencias de conducta de los estudiantes. Los reportes serán visibles para los representantes en tiempo real.
            </p>

            <div class="form-section">
                <h3>Formulario de Reporte</h3>
                <form method="POST">
                    <input type="hidden" name="accion" value="crear_reporte">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Estudiante *</label>
                            <select name="id_estudiante" required>
                                <option value="">-- Selecciona un estudiante --</option>
                                <?php foreach ($estudiantes_ejemplo as $est): ?>
                                    <option value="<?php echo $est['id']; ?>">
                                        <?php echo htmlspecialchars($est['nombre']); ?> (<?php echo $est['seccion']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Sección *</label>
                            <select name="seccion" required>
                                <option value="">-- Selecciona una sección --</option>
                                <option value="1ro A">1ro A</option>
                                <option value="1ro B">1ro B</option>
                                <option value="2do A">2do A</option>
                                <option value="2do B">2do B</option>
                                <option value="3ro A">3ro A</option>
                                <option value="3ro B">3ro B</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Tipo de Reporte *</label>
                            <select name="tipo_reporte" required>
                                <option value="">-- Selecciona un tipo --</option>
                                <option value="Felicitación">Felicitación</option>
                                <option value="Llamado de Atención">Llamado de Atención</option>
                                <option value="Amonestación">Amonestación</option>
                                <option value="Reporte Grave">Reporte Grave</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Fecha del Incidente *</label>
                            <input type="date" name="fecha_reporte" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Título/Asunto *</label>
                        <input type="text" name="titulo" required placeholder="Ej: Inasistencia sin justificación, Excelente participación en clase">
                    </div>

                    <div class="form-group">
                        <label>Descripción Detallada *</label>
                        <textarea name="descripcion" required placeholder="Describe con detalle lo que ocurrió, contexto, testigos, acciones tomadas..."></textarea>
                    </div>

                    <button type="submit">Registrar Reporte</button>
                </form>
            </div>
        </div>

        <!-- SECCIÓN HISTORIAL -->
        <div class="content <?php echo ($vista === 'historial') ? 'active' : ''; ?>" id="historial">
            <h2>Historial de Reportes Registrados</h2>

            <?php if (!empty($secciones)): ?>
            <div class="filtro-section">
                <strong>Filtrar por Sección:</strong>
                <select onchange="filtrarSeccion(this.value)" style="margin-left: 10px; padding: 8px; border-radius: 4px;">
                    <option value="">Todas las secciones</option>
                    <?php foreach ($secciones as $sec): ?>
                        <option value="<?php echo $sec; ?>" <?php echo ($seccion_filtro === $sec) ? 'selected' : ''; ?>>
                            <?php echo $sec; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if (empty($reportes)): ?>
                <div class="sin-reportes">
                    <p>No hay reportes registrados.</p>
                </div>
            <?php else: ?>
                <?php foreach ($reportes as $reporte): ?>
                <div class="reporte-card" style="border-left-color: <?php echo $tipos_reportes[$reporte['tipo_reporte']] ?? '#ccc'; ?>;">
                    <div class="tipo-badge" style="background-color: <?php echo $tipos_reportes[$reporte['tipo_reporte']] ?? '#ccc'; ?>;">
                        <?php echo htmlspecialchars($reporte['tipo_reporte']); ?>
                    </div>

                    <div class="reporte-titulo"><?php echo htmlspecialchars($reporte['titulo']); ?></div>
                    
                    <div class="reporte-meta">
                        Estudiante ID: <?php echo $reporte['id_estudiante']; ?> | 
                        Sección: <?php echo htmlspecialchars($reporte['seccion']); ?> |
                        📅 <?php echo date('d/m/Y', strtotime($reporte['fecha_reporte'])); ?>
                        <?php if (!empty($reporte['hora_reporte'])): ?>
                            a las <?php echo htmlspecialchars($reporte['hora_reporte']); ?>
                        <?php endif; ?>
                    </div>

                    <div class="reporte-descripcion">
                        <?php echo nl2br(htmlspecialchars($reporte['descripcion'])); ?>
                    </div>

                    <div class="reporte-acciones">
                        <form method="POST" style="flex: 1;">
                            <input type="hidden" name="accion" value="eliminar_reporte">
                            <input type="hidden" name="id_reporte" value="<?php echo $reporte['id']; ?>">
                            <button type="submit" class="eliminar" style="width: 100%;" onclick="return confirm('¿Estás seguro de eliminar este reporte?');">
                                🗑️ Eliminar
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function irA(vista) {
            window.location.href = '?vista=' + vista;
        }

        function filtrarSeccion(seccion) {
            const url = new URL(window.location);
            if (seccion) {
                url.searchParams.set('seccion', seccion);
            } else {
                url.searchParams.delete('seccion');
            }
            window.location.href = url.toString();
        }
    </script>

    <footer style="text-align: center; padding: 20px; color: #999; margin-top: 40px;">
        <p>&copy; <?php echo date('Y'); ?> <?php echo $nombre_institucion; ?> - Todos los derechos reservados.</p>
    </footer>
</body>
</html>
