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
    <title><?php echo $modulo_actual; ?> - <?php echo $nombre_institucion; ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .representante-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 10px 20px;
            background: #f59e0b;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }

        .tab-btn.active {
            background: #6b4423;
        }

        .tab-btn:hover {
            background: #6b4423;
        }

        .section {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .estudiante-card {
            background: #fffbf0;
            border: 1px solid #f3e8d8;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #f59e0b;
        }

        .estudiante-titulo {
            color: #6b4423;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .estudiante-datos {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .estudiante-dato {
            background: white;
            padding: 10px;
            border-radius: 4px;
            border-left: 3px solid #f59e0b;
        }

        .estudiante-dato-label {
            font-weight: bold;
            color: #6b4423;
            font-size: 12px;
        }

        .estudiante-dato-valor {
            color: #555;
            margin-top: 3px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
            font-size: 14px;
        }

        .btn-primary {
            background: #f59e0b;
            color: white;
        }

        .btn-primary:hover {
            background: #6b4423;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }

        .tabla-calificaciones {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .tabla-calificaciones th {
            background: #6b4423;
            color: white;
            padding: 12px;
            text-align: left;
        }

        .tabla-calificaciones td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }

        .tabla-calificaciones tr:hover {
            background: #fffbf0;
        }

        .tabla-asistencia {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 13px;
        }

        .tabla-asistencia th {
            background: #6b4423;
            color: white;
            padding: 10px;
            text-align: center;
        }

        .tabla-asistencia td {
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
            text-align: center;
        }

        .estado-presente {
            color: #065f46;
            font-weight: bold;
        }

        .estado-ausente {
            color: #991b1b;
            font-weight: bold;
        }

        .estado-tarde {
            color: #b45309;
            font-weight: bold;
        }

        .estadisticas {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }

        .estadistica-card {
            background: #f0f9ff;
            border: 2px solid #bfdbfe;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .estadistica-label {
            color: #1e40af;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .estadistica-valor {
            color: #1e40af;
            font-size: 32px;
            font-weight: bold;
            margin-top: 10px;
        }

        .volver-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #6b4423;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 20px;
            cursor: pointer;
        }

        .header {
            background: linear-gradient(135deg, #6b4423, #8b5a2b);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .header h2 {
            margin: 0 0 5px;
        }

        .header p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }

        .filtros {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filtros label {
            font-weight: bold;
            color: #6b4423;
        }

        .filtros select {
            padding: 8px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
        }

        @media (max-width: 768px) {
            .estudiante-datos {
                grid-template-columns: 1fr;
            }

            .estadisticas {
                grid-template-columns: 1fr 1fr;
            }

            .tabla-asistencia {
                font-size: 11px;
            }

            .tabla-asistencia td,
            .tabla-asistencia th {
                padding: 6px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1><?php echo $nombre_institucion; ?></h1>
            <p>Módulo: <?php echo $modulo_actual; ?></p>
        </div>
    </header>

    <main class="representante-container">
        <a href="../index.php" class="volver-btn">← Volver al Inicio</a>

        <div class="tabs">
            <button class="tab-btn <?php echo ($vista === 'principal') ? 'active' : ''; ?>" onclick="location.href='?vista=principal'">
                📋 Información Institucional
            </button>
            <button class="tab-btn <?php echo ($vista === 'notas') ? 'active' : ''; ?>" onclick="location.href='?vista=notas'">
                📊 Notas en Tiempo Real
            </button>
            <button class="tab-btn <?php echo ($vista === 'asistencia') ? 'active' : ''; ?>" onclick="location.href='?vista=asistencia'">
                ✓ Historial de Asistencia
            </button>
            <button class="tab-btn <?php echo ($vista === 'convivencia') ? 'active' : ''; ?>" onclick="location.href='?vista=convivencia'">
                ⚖️ Convivencia y Conducta
            </button>
             <a href=comunicacion.php><button class="tab-btn <?php echo ($vista === 'Reuniones') ? 'active' : ''; ?>" >
                 Centro De Reuniones
            </button></a>
            <a href=reportes-conducta.php><button class="tab-btn <?php echo ($vista === 'conducta') ? 'active' : ''; ?>" >
                 Conductas
                 </button></a>
        </div>

        <!-- SECCIÓN INFORMACIÓN INSTITUCIONAL -->
        <?php if ($vista === 'principal'): ?>
        <div class="section">
            <div class="header">
                <h2>Información Institucional</h2>
                <p>Datos generales de la escuela y horarios de clases</p>
            </div>

            <div style="background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                <strong>Escuela Técnica Pedro Garcia Leal</strong><br>
                Institución educativa especializada en formación técnica con programas de pasantías y labor social.
            </div>

            <!-- DESCARGA PLANTILLA DE INSCRIPCIÓN -->
            <div style="background: #e0f2fe; border: 1px solid #0284c7; color: #0c4a6e; padding: 20px; border-radius: 6px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <strong style="font-size: 14px;">📋 Plantilla de Inscripción de Representante</strong><br>
                    <span style="font-size: 12px; color: #075985;">Descarga este formulario en PDF para completarlo manualmente con tus datos y los de tu representado.</span>
                </div>
                <a href="../generar-plantilla-inscripcion.php" style="background: #0284c7; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block; transition: all 0.3s; white-space: nowrap; margin-left: 15px;" onmouseover="this.style.background='#0369a1'" onmouseout="this.style.background='#0284c7'">
                    ⬇️ Descargar PDF
                </a>
            </div>

            <h3 style="color: #6b4423; margin-top: 20px;">Horario de Clases 2026</h3>
            <table class="tabla-calificaciones">
                <thead>
                    <tr>
                        <th>Hora</th>
                        <th>Lunes a Viernes</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>7:00 AM - 7:45 AM</strong></td>
                        <td>1era Hora de Clase</td>
                    </tr>
                    <tr>
                        <td><strong>7:45 AM - 8:30 AM</strong></td>
                        <td>2da Hora de Clase</td>
                    </tr>
                    <tr>
                        <td><strong>8:30 AM - 8:45 AM</strong></td>
                        <td>Receso</td>
                    </tr>
                    <tr>
                        <td><strong>8:45 AM - 9:30 AM</strong></td>
                        <td>3era Hora de Clase</td>
                    </tr>
                    <tr>
                        <td><strong>9:30 AM - 10:15 AM</strong></td>
                        <td>4ta Hora de Clase</td>
                    </tr>
                    <tr>
                        <td><strong>10:15 AM - 11:00 AM</strong></td>
                        <td>5ta Hora de Clase</td>
                    </tr>
                    <tr>
                        <td><strong>11:00 AM - 11:45 AM</strong></td>
                        <td>6ta Hora de Clase</td>
                    </tr>
                </tbody>
            </table>

            <h3 style="color: #6b4423; margin-top: 30px;">Contacto de Directivos</h3>
            <div style="background: #fffbf0; border: 1px solid #f3e8d8; padding: 15px; border-radius: 6px;">
                <p><strong>Director:</strong> Lic. Juan Pérez</p>
                <p><strong>Correo:</strong> director@escuela.edu.ve</p>
                <p><strong>Teléfono:</strong> (0293) 123-4567</p>
            </div>
        </div>

        <!-- SECCIÓN NOTAS EN TIEMPO REAL -->
        <?php elseif ($vista === 'notas'): ?>
        <div class="section">
            <div class="header">
                <h2>Notas en Tiempo Real</h2>
                <p>Visualiza las calificaciones actualizadas de tus representados</p>
            </div>

            <div class="filtros">
                <label>Selecciona el Lapso:</label>
                <select onchange="location.href='?vista=notas&lapso=' + this.value">
                    <option value="1" <?php echo ($lapso == 1) ? 'selected' : ''; ?>>Lapso 1</option>
                    <option value="2" <?php echo ($lapso == 2) ? 'selected' : ''; ?>>Lapso 2</option>
                    <option value="3" <?php echo ($lapso == 3) ? 'selected' : ''; ?>>Lapso 3</option>
                </select>
            </div>

            <?php
            // Simular algunos estudiantes con calificaciones (en producción vendrían de la BD)
            $estudiantes_ejemplo = obtenerEstudiantes($conexion);
            
            if (!empty($estudiantes_ejemplo)): 
                foreach (array_slice($estudiantes_ejemplo, 0, 3) as $est):
                    $calificaciones = obtenerCalificacionesEstudiante($conexion, $est['id'], $lapso);
            ?>
                <div class="estudiante-card">
                    <div class="estudiante-titulo">
                        <?php echo htmlspecialchars($est['nombre'] . ' ' . $est['apellido']); ?>
                        <span style="color: #999; font-size: 12px; font-weight: normal;">- Cédula: <?php echo htmlspecialchars($est['cedula']); ?></span>
                    </div>

                    <div class="estudiante-datos">
                        <div class="estudiante-dato">
                            <div class="estudiante-dato-label">NIVEL</div>
                            <div class="estudiante-dato-valor"><?php echo htmlspecialchars($est['nivel_academico'] . ' - ' . $est['seccion']); ?></div>
                        </div>
                        <div class="estudiante-dato">
                            <div class="estudiante-dato-label">LAPSO</div>
                            <div class="estudiante-dato-valor">Lapso <?php echo $lapso; ?> / 2026</div>
                        </div>
                    </div>

                    <?php if (!empty($calificaciones)): ?>
                        <table class="tabla-calificaciones">
                            <thead>
                                <tr>
                                    <th>Materia</th>
                                    <th>1era Eval</th>
                                    <th>2da Eval</th>
                                    <th>3era Eval</th>
                                    <th>Promedio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $promedio_general = 0;
                                foreach ($calificaciones as $cal):
                                    $promedio_general += $cal['nota_promedio'];
                                ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($cal['materia']); ?></strong></td>
                                        <td><?php echo number_format($cal['nota_primera_evaluacion'], 2); ?></td>
                                        <td><?php echo number_format($cal['nota_segunda_evaluacion'], 2); ?></td>
                                        <td><?php echo number_format($cal['nota_tercera_evaluacion'], 2); ?></td>
                                        <td><strong><?php echo number_format($cal['nota_promedio'], 2); ?></strong></td>
                                    </tr>
                                <?php endforeach; 
                                
                                if (count($calificaciones) > 0) {
                                    $promedio_general = $promedio_general / count($calificaciones);
                                ?>
                                    <tr style="background: #f0f9ff;">
                                        <td><strong>PROMEDIO GENERAL</strong></td>
                                        <td colspan="4" style="text-align: center; font-weight: bold; color: #1e40af;">
                                            <?php echo number_format($promedio_general, 2); ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>

                        <div style="margin-top: 15px;">
                            <a href="?vista=notas&lapso=<?php echo $lapso; ?>&descargar_boleta=1&id_estudiante=<?php echo $est['id']; ?>&lapso_descarga=<?php echo $lapso; ?>" class="btn btn-primary btn-small">
                                ⬇ Descargar Boleta PDF
                            </a>
                        </div>
                    <?php else: ?>
                        <p style="color: #999; text-align: center; padding: 20px;">No hay calificaciones registradas para este lapso.</p>
                    <?php endif; ?>
                </div>
            <?php 
                endforeach;
            else:
            ?>
                <p style="color: #999; text-align: center; padding: 20px;">No hay estudiantes disponibles.</p>
            <?php endif; ?>
        </div>

        <!-- SECCIÓN HISTORIAL DE ASISTENCIA -->
        <?php elseif ($vista === 'asistencia'): ?>
        <div class="section">
            <div class="header">
                <h2>Historial de Asistencia</h2>
                <p>Visualiza el registro de asistencia de tus representados</p>
            </div>

            <?php
            $estudiantes_ejemplo = obtenerEstudiantes($conexion);
            
            if (!empty($estudiantes_ejemplo)): 
                foreach (array_slice($estudiantes_ejemplo, 0, 3) as $est):
                    $asistencias = obtenerAsistenciaEstudiante($conexion, $est['id']);
                    $estadisticas = obtenerEstadisticasAsistenciaEstudiante($conexion, $est['id']);
            ?>
                <div class="estudiante-card">
                    <div class="estudiante-titulo">
                        <?php echo htmlspecialchars($est['nombre'] . ' ' . $est['apellido']); ?>
                        <span style="color: #999; font-size: 12px; font-weight: normal;">- Cédula: <?php echo htmlspecialchars($est['cedula']); ?></span>
                    </div>

                    <div class="estudiante-datos">
                        <div class="estudiante-dato">
                            <div class="estudiante-dato-label">NIVEL</div>
                            <div class="estudiante-dato-valor"><?php echo htmlspecialchars($est['nivel_academico'] . ' - ' . $est['seccion']); ?></div>
                        </div>
                        <div class="estudiante-dato">
                            <div class="estudiante-dato-label">AÑO ESCOLAR</div>
                            <div class="estudiante-dato-valor">2026</div>
                        </div>
                    </div>

                    <?php if ($estadisticas): ?>
                    <div class="estadisticas">
                        <div class="estadistica-card">
                            <div class="estadistica-label">Total de Días</div>
                            <div class="estadistica-valor"><?php echo $estadisticas['total_dias'] ?? 0; ?></div>
                        </div>
                        <div class="estadistica-card" style="background: #d1fae5; border-color: #6ee7b7;">
                            <div class="estadistica-label" style="color: #065f46;">Presentes</div>
                            <div class="estadistica-valor" style="color: #065f46;"><?php echo $estadisticas['presentes'] ?? 0; ?></div>
                        </div>
                        <div class="estadistica-card" style="background: #fee2e2; border-color: #fca5a5;">
                            <div class="estadistica-label" style="color: #991b1b;">Ausentes</div>
                            <div class="estadistica-valor" style="color: #991b1b;"><?php echo $estadisticas['ausentes'] ?? 0; ?></div>
                        </div>
                        <div class="estadistica-card" style="background: #fef3c7; border-color: #fcd34d;">
                            <div class="estadistica-label" style="color: #92400e;">% Asistencia</div>
                            <div class="estadistica-valor" style="color: #92400e;"><?php echo ($estadisticas['porcentaje_asistencia'] ?? 0) . '%'; ?></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($asistencias)): ?>
                        <table class="tabla-asistencia">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($asistencias, 0, 10) as $asis): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($asis['fecha'])); ?></td>
                                        <td><?php echo htmlspecialchars($asis['hora']); ?></td>
                                        <td>
                                            <span class="estado-<?php echo strtolower($asis['estado']); ?>">
                                                <?php echo htmlspecialchars($asis['estado']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p style="color: #999; font-size: 12px; margin-top: 10px;">Mostrando últimas 10 asistencias</p>
                    <?php else: ?>
                        <p style="color: #999; text-align: center; padding: 20px;">No hay registros de asistencia.</p>
                    <?php endif; ?>
                </div>
            <?php 
                endforeach;
            else:
            ?>
                <p style="color: #999; text-align: center; padding: 20px;">No hay estudiantes disponibles.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- SECCIÓN CONVIVENCIA Y CONDUCTA -->
        <?php if ($vista === 'convivencia'): ?>
        <div class="section">
            <div class="header">
                <h2>Monitoreo de Convivencia y Conducta</h2>
                <p>Bitácora de incidencias y reportes de comportamiento en tiempo real</p>
            </div>

            <div style="background: #eff6ff; border: 1px solid #3b82f6; color: #1e40af; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                <strong>Sistema de Monitoreo:</strong> Aquí verás todos los reportes de conducta de tu representado(a) cargados por los profesores. 
                Los reportes pueden ser: Felicitaciones por buen desempeño, Llamados de Atención, Amonestaciones o Reportes Graves que requieren tu firma digital.
            </div>

            <h3 style="color: #1f2937; margin-bottom: 15px;">Historial de Incidencias</h3>

            <?php
            // Obtener todos los reportes (simulado - en producción serían del estudiante del representante)
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
                <div style="background: #f3f4f6; padding: 20px; border-radius: 6px; text-align: center; color: #999;">
                    <p>No hay reportes de conducta registrados.</p>
                </div>
            <?php else: ?>
                <div style="margin-top: 20px;">
                    <?php
                    $colores_tipo = [
                        'Felicitación' => ['bg' => '#d1fae5', 'border' => '#10b981', 'text' => '#065f46'],
                        'Llamado de Atención' => ['bg' => '#fef3c7', 'border' => '#f59e0b', 'text' => '#92400e'],
                        'Amonestación' => ['bg' => '#fee2e2', 'border' => '#ef4444', 'text' => '#991b1b'],
                        'Reporte Grave' => ['bg' => '#f3e8ff', 'border' => '#7c3aed', 'text' => '#4c1d95']
                    ];
                    ?>
                    <?php foreach ($todos_reportes as $reporte): ?>
                    <div style="border-left: 5px solid <?php echo $colores_tipo[$reporte['tipo_reporte']]['border'] ?? '#ccc'; ?>; 
                                background: <?php echo $colores_tipo[$reporte['tipo_reporte']]['bg'] ?? '#f9fafb'; ?>; 
                                padding: 15px; margin-bottom: 15px; border-radius: 6px;">
                        
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                            <div>
                                <span style="display: inline-block; background: <?php echo $colores_tipo[$reporte['tipo_reporte']]['border'] ?? '#ccc'; ?>; 
                                             color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; margin-right: 10px;">
                                    <?php echo htmlspecialchars($reporte['tipo_reporte']); ?>
                                </span>
                                <strong style="color: <?php echo $colores_tipo[$reporte['tipo_reporte']]['text'] ?? '#333'; ?>;">
                                    <?php echo htmlspecialchars($reporte['titulo']); ?>
                                </strong>
                            </div>
                            <span style="font-size: 12px; color: #6b7280;">
                                📅 <?php echo date('d/m/Y', strtotime($reporte['fecha_reporte'])); ?>
                                <?php if (!empty($reporte['hora_reporte'])): ?>
                                    a las <?php echo htmlspecialchars($reporte['hora_reporte']); ?>
                                <?php endif; ?>
                            </span>
                        </div>

                        <p style="color: #4b5563; margin: 10px 0; line-height: 1.5;">
                            <?php echo nl2br(htmlspecialchars($reporte['descripcion'])); ?>
                        </p>

                        <?php 
                        // Verificar si hay firma de amonestación
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
                        <div style="background: white; padding: 10px; border-radius: 6px; margin-top: 10px;">
                            <p style="font-size: 12px; color: #666; margin-bottom: 10px;">
                                ⚠️ Esta amonestación requiere tu firma digital como constancia de que has sido notificado.
                            </p>
                            <form method="POST" style="display: flex; gap: 10px;">
                                <input type="hidden" name="accion" value="firmar_amonestacion">
                                <input type="hidden" name="id_reporte" value="<?php echo $reporte['id']; ?>">
                                <textarea name="observaciones" placeholder="Observaciones (opcional)" 
                                          style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 12px;"></textarea>
                                <button type="submit" style="background: #10b981; padding: 8px 20px; white-space: nowrap;">
                                    ✓ Leído y Conforme
                                </button>
                            </form>
                        </div>
                        <?php elseif ($tiene_firma): ?>
                        <div style="background: white; padding: 10px; border-radius: 6px; margin-top: 10px; border: 1px solid #10b981;">
                            <p style="font-size: 12px; color: #065f46;">
                                ✓ Firmado digitalmente como constancia de lectura
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php
        // Procesar firma de amonestación
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'firmar_amonestacion') {
            $id_reporte = (int)$_POST['id_reporte'];
            $observaciones = $conexion->real_escape_string($_POST['observaciones'] ?? '');
            $usuario_actual = $_SESSION['usuario'] ?? '';
            $id_usuario_rep = crc32($usuario_actual) & 0x7fffffff;
            
            // Obtener datos del reporte
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
    </main>

    <footer style="text-align: center; padding: 20px; color: #999; margin-top: 40px;">
        <p>&copy; <?php echo $año_actual; ?> <?php echo $nombre_institucion; ?> - Todos los derechos reservados.</p>
    </footer>
</body>
</html>
