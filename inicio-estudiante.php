<?php
session_start();

if (!isset($_SESSION['usuario_logueado']) || !$_SESSION['usuario_logueado']) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['rol'] !== 'Estudiante') {
    header('Location: index.php');
    exit;
}

require_once 'includes/config.php';
require_once 'includes/db-functions.php';

$nombre_institucion = "Escuela Técnica Pedro Garcia Leal";
$usuario_actual = $_SESSION['nombre'] ?? 'Estudiante';
$id_estudiante = $_SESSION['id'] ?? 0;


$sql = "SELECT * FROM estudiantes WHERE nombre LIKE '%$usuario_actual%' OR cedula LIKE '%$usuario_actual%' LIMIT 1";
$resultado = @$conexion->query($sql);
$estudiante = ($resultado && $resultado->num_rows > 0) ? $resultado->fetch_assoc() : ['seccion' => '', 'nombre' => $usuario_actual];
$seccion = $estudiante['seccion'] ?? '';


$tareas = $seccion ? obtenerTareasPorSeccion($conexion, $seccion) : [];
$material = $seccion ? obtenerMaterialPorSeccion($conexion, $seccion) : [];
$examenes = $seccion ? obtenerExamenesPorSeccion($conexion, $seccion) : [];
$calificaciones = obtenerCalificacionesEstudiante($conexion, $id_estudiante);
$estadisticas_asistencia = obtenerEstadisticasAsistenciaEstudiante($conexion, $id_estudiante);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Portal - <?php echo $nombre_institucion; ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .estudiante-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .welcome-banner {
            background: linear-gradient(135deg, #6b4423, #8b5a2b);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .welcome-banner h1 {
            margin: 0;
            font-size: 28px;
        }

        .welcome-banner p {
            margin: 8px 0 0 0;
            opacity: 0.9;
        }

        .info-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-top: 4px solid #f59e0b;
        }

        .card h3 {
            margin: 0 0 15px 0;
            color: #6b4423;
            font-size: 18px;
        }

        .card-content {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
        }

        .card-value {
            font-size: 24px;
            font-weight: bold;
            color: #f59e0b;
            margin: 10px 0;
        }

        .secciones {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .seccion-block {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .seccion-titulo {
            color: #6b4423;
            font-weight: bold;
            font-size: 20px;
            margin: 0 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #f59e0b;
        }

        .item {
            background: #fffbf0;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin-bottom: 12px;
            border-radius: 6px;
        }

        .item-titulo {
            color: #6b4423;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .item-fecha {
            font-size: 12px;
            color: #999;
            margin-bottom: 8px;
        }

        .item-desc {
            font-size: 13px;
            color: #666;
            line-height: 1.5;
        }

        .vacío {
            text-align: center;
            color: #999;
            padding: 20px;
            font-style: italic;
        }

        .header {
            background: linear-gradient(135deg, #6b4423, #8b5a2b);
            color: white;
            padding: 25px;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header h1 {
            margin: 0;
            font-size: 32px;
        }

        .header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }

        .btn-volver {
            display: inline-block;
            margin: 20px 0;
            padding: 10px 20px;
            background: #6b4423;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .btn-volver:hover {
            background: #8b5a2b;
        }

        @media (max-width: 768px) {
            .info-cards {
                grid-template-columns: 1fr;
            }

            .secciones {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1><?php echo $nombre_institucion; ?></h1>
            <p>Portal del Estudiante</p>
            <div style="position: absolute; top: 25px; right: 25px; text-align: right; color: white;">
                <p style="margin: 0; font-size: 14px;">Bienvenido, <strong><?php echo htmlspecialchars($usuario_actual); ?></strong></p>
                <form method="POST" action="cerrar-sesion.php" style="margin-top: 8px;">
                    <button type="submit" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.5); padding: 5px 12px; border-radius: 5px; cursor: pointer; font-size: 12px;">
                        Cerrar Sesión
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="estudiante-container">
        <a href="index.php" class="btn-volver">← Volver al Menú Principal</a>

        <div class="welcome-banner">
            <h1>Bienvenido a tu Portal de Aprendizaje</h1>
            <p>Aquí puedes ver tus tareas, material de clase, exámenes y tu desempeño académico.</p>
        </div>

        <!-- TARJETAS DE INFORMACIÓN -->
        <div class="info-cards">
            <div class="card">
                <h3>Mi Sección</h3>
                <div class="card-value"><?php echo htmlspecialchars($seccion ?: 'Sin asignar'); ?></div>
                <div class="card-content">
                    Sección académica actual
                </div>
            </div>

            <div class="card">
                <h3>Tareas Pendientes</h3>
                <div class="card-value"><?php echo count($tareas); ?></div>
                <div class="card-content">
                    Tareas a realizar
                </div>
            </div>

            <div class="card">
                <h3>Próximos Exámenes</h3>
                <div class="card-value"><?php echo count($examenes); ?></div>
                <div class="card-content">
                    Exámenes programados
                </div>
            </div>

            <?php if ($estadisticas_asistencia): ?>
            <div class="card">
                <h3>Asistencia</h3>
                <div class="card-value"><?php echo htmlspecialchars($estadisticas_asistencia['porcentaje_asistencia'] ?? '0'); ?>%</div>
                <div class="card-content">
                    <?php echo htmlspecialchars($estadisticas_asistencia['presentes'] ?? 0); ?> presentes | 
                    <?php echo htmlspecialchars($estadisticas_asistencia['ausentes'] ?? 0); ?> ausentes
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- SECCIONES DE CONTENIDO -->
        <div class="secciones">
            <!-- TAREAS -->
            <div class="seccion-block">
                <h2 class="seccion-titulo">📝 Mis Tareas</h2>
                <?php if (empty($tareas)): ?>
                    <div class="vacío">No hay tareas pendientes</div>
                <?php else: ?>
                    <?php foreach ($tareas as $tarea): ?>
                        <div class="item">
                            <div class="item-titulo"><?php echo htmlspecialchars($tarea['titulo']); ?></div>
                            <div class="item-fecha">Entrega: <?php echo date('d/m/Y', strtotime($tarea['fecha_entrega'])); ?></div>
                            <div class="item-desc"><?php echo htmlspecialchars($tarea['descripcion']); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- MATERIAL DE CLASE -->
            <div class="seccion-block">
                <h2 class="seccion-titulo">📚 Material de Clase</h2>
                <?php if (empty($material)): ?>
                    <div class="vacío">No hay material disponible</div>
                <?php else: ?>
                    <?php foreach (array_slice($material, 0, 5) as $mat): ?>
                        <div class="item">
                            <div class="item-titulo"><?php echo htmlspecialchars($mat['titulo']); ?></div>
                            <?php if ($mat['materia']): ?>
                                <div class="item-fecha">Materia: <?php echo htmlspecialchars($mat['materia']); ?></div>
                            <?php endif; ?>
                            <div class="item-desc"><?php echo htmlspecialchars(substr($mat['descripcion'], 0, 100)); ?>...</div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($material) > 5): ?>
                        <div style="text-align: center; color: #999; margin-top: 15px; font-size: 12px;">
                            +<?php echo count($material) - 5; ?> materiales más
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- EXÁMENES -->
            <div class="seccion-block">
                <h2 class="seccion-titulo">📋 Calendario de Exámenes</h2>
                <?php if (empty($examenes)): ?>
                    <div class="vacío">No hay exámenes programados</div>
                <?php else: ?>
                    <?php foreach ($examenes as $exam): ?>
                        <div class="item">
                            <div class="item-titulo"><?php echo htmlspecialchars($exam['titulo']); ?></div>
                            <div class="item-fecha">
                                📅 <?php echo date('d/m/Y', strtotime($exam['fecha_examen'])); ?> a las <?php echo htmlspecialchars($exam['hora_examen']); ?>
                                <?php if ($exam['lugar']): ?>
                                    | 📍 <?php echo htmlspecialchars($exam['lugar']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="item-desc"><?php echo htmlspecialchars($exam['descripcion']); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- DESEMPEÑO ACADÉMICO -->
            <div class="seccion-block">
                <h2 class="seccion-titulo">📊 Mi Desempeño</h2>
                <?php if (empty($calificaciones)): ?>
                    <div class="vacío">No hay calificaciones disponibles</div>
                <?php else: ?>
                    <?php 
                        $promedio_general = 0;
                        $total = 0;
                        foreach ($calificaciones as $cal): 
                            $promedio_general += (float)$cal['nota_promedio'];
                            $total++;
                        endforeach;
                        $promedio_general = $total > 0 ? $promedio_general / $total : 0;
                    ?>
                    <div style="background: #d1fae5; padding: 15px; border-radius: 6px; margin-bottom: 15px;">
                        <div style="color: #065f46; font-weight: bold; margin-bottom: 5px;">Promedio General</div>
                        <div style="font-size: 24px; color: #10b981; font-weight: bold;">
                            <?php echo number_format($promedio_general, 2); ?> / 20
                        </div>
                    </div>

                    <?php foreach (array_slice($calificaciones, 0, 4) as $cal): ?>
                        <div class="item">
                            <div class="item-titulo"><?php echo htmlspecialchars($cal['materia']); ?></div>
                            <div class="item-fecha">Lapso <?php echo htmlspecialchars($cal['lapso']); ?></div>
                            <div class="item-desc">
                                Promedio: <strong><?php echo number_format($cal['nota_promedio'], 2); ?>/20</strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer style="text-align: center; padding: 20px; color: #999; margin-top: 40px;">
        <p>&copy; <?php echo date('Y'); ?> <?php echo $nombre_institucion; ?> - Todos los derechos reservados.</p>
    </footer>
</body>
</html>
