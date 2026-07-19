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

// Generar ID numérico basado en el usuario
$id_usuario = crc32($usuario_username) & 0x7fffffff; // Valor entre 0 y 2147483647

$vista = $_GET['vista'] ?? 'principal';
$mensaje = "";
$tipo_mensaje = "";

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        
        // CREAR REUNIÓN GENERAL (Profesor/Admin)
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
        
        // CONFIRMAR ASISTENCIA
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
        
        // ELIMINAR REUNIÓN
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

// Obtener reuniones
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
    <title>Reuniones - <?php echo $nombre_institucion; ?></title>
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
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .container {
            max-width: 1000px;
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
            color: #3b82f6;
            border-bottom-color: #3b82f6;
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
            background: #3b82f6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }

        button:hover {
            background: #2563eb;
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

        .reunion-card {
            background: #f9fafb;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 6px;
        }

        .reunion-titulo {
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 8px;
            font-size: 16px;
        }

        .reunion-meta {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .reunion-descripcion {
            color: #4b5563;
            margin: 10px 0;
            line-height: 1.5;
        }

        .reunion-acciones {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }

        .reunion-acciones button {
            padding: 6px 12px;
            font-size: 12px;
            flex: 1;
        }

        .reunion-acciones button.eliminar {
            background: #ef4444;
        }

        .reunion-acciones button.eliminar:hover {
            background: #dc2626;
        }

        .sin-reuniones {
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

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .reunion-acciones {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Centro de Reuniones</h1>
            <p>Gestión de reuniones académicas y encuentros institucionales</p>
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
            <?php if ($rol_actual === 'Profesor' || $rol_actual === 'Administrador'): ?>
                <button class="tab-btn <?php echo ($vista === 'principal' || $vista === 'agregar') ? 'active' : ''; ?>" onclick="irA('agregar')">
                    + Agregar Reunión
                </button>
            <?php endif; ?>
            <button class="tab-btn <?php echo ($vista === 'mis_reuniones') ? 'active' : ''; ?>" onclick="irA('mis_reuniones')">
                📅 Mis Reuniones
            </button>
        </div>

        <!-- SECCIÓN AGREGAR REUNIÓN (Profesor/Admin) -->
        <?php if ($rol_actual === 'Profesor' || $rol_actual === 'Administrador'): ?>
        <div class="content <?php echo ($vista === 'agregar' || $vista === 'principal') ? 'active' : ''; ?>" id="agregar">
            <h2>Crear Nueva Reunión</h2>
            <p style="color: #666; margin-bottom: 20px;">
                Aquí puedes programar reuniones con los representantes de una sección específica.
            </p>

            <div class="form-section">
                <h3>Formulario de Reunión</h3>
                <form method="POST">
                    <input type="hidden" name="accion" value="crear_reunion_general">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Sección *</label>
                            <select name="seccion" required>
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
                        <div class="form-group">
                            <label>Asunto de la Reunión *</label>
                            <input type="text" name="asunto" required placeholder="Ej: Evaluación del Trimestre I">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Fecha *</label>
                            <input type="date" name="fecha_reunion" required>
                        </div>
                        <div class="form-group">
                            <label>Hora</label>
                            <input type="time" name="hora_reunion">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Descripción / Temas a Tratar *</label>
                        <textarea name="descripcion" required placeholder="Describe los temas que se tratarán en la reunión, objetivos, etc..."></textarea>
                    </div>

                    <button type="submit">Crear Reunión</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- SECCIÓN MIS REUNIONES / REUNIONES DISPONIBLES -->
        <div class="content <?php echo ($vista === 'mis_reuniones' || $vista === 'principal') ? 'active' : ''; ?>" id="mis_reuniones">
            <h2><?php echo ($rol_actual === 'Profesor' || $rol_actual === 'Administrador') ? 'Reuniones Creadas' : 'Reuniones de mi Sección'; ?></h2>

            <?php if (empty($reuniones)): ?>
                <div class="sin-reuniones">
                    <p>No hay reuniones programadas en este momento.</p>
                </div>
            <?php else: ?>
                <?php foreach ($reuniones as $reunion): ?>
                <div class="reunion-card">
                    <div class="reunion-titulo"><?php echo htmlspecialchars($reunion['asunto']); ?></div>
                    
                    <div class="reunion-meta">
                        📅 <?php echo date('d/m/Y', strtotime($reunion['fecha_reunion'])); ?>
                        <?php if (!empty($reunion['hora_reunion'])): ?>
                            a las <?php echo htmlspecialchars($reunion['hora_reunion']); ?>
                        <?php endif; ?>
                        | Sección: <strong><?php echo htmlspecialchars($reunion['seccion']); ?></strong>
                    </div>

                    <div class="reunion-descripcion">
                        <?php echo nl2br(htmlspecialchars($reunion['descripcion'])); ?>
                    </div>

                    <div class="reunion-acciones">
                        <?php if ($rol_actual === 'Representante'): ?>
                            <form method="POST" style="flex: 1;">
                                <input type="hidden" name="accion" value="confirmar_asistencia">
                                <input type="hidden" name="id_reunion" value="<?php echo $reunion['id']; ?>">
                                <button type="submit" style="width: 100%;">✓ Confirmar Asistencia</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" style="flex: 1;">
                                <input type="hidden" name="accion" value="eliminar_reunion">
                                <input type="hidden" name="id_reunion" value="<?php echo $reunion['id']; ?>">
                                <button type="submit" class="eliminar" style="width: 100%;" onclick="return confirm('¿Estás seguro de que deseas eliminar esta reunión?');">
                                    🗑️ Eliminar
                                </button>
                            </form>
                        <?php endif; ?>
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
    </script>

    <footer style="text-align: center; padding: 20px; color: #999; margin-top: 40px;">
        <p>&copy; <?php echo date('Y'); ?> <?php echo $nombre_institucion; ?> - Todos los derechos reservados.</p>
    </footer>
</body>
</html>
