<?php
session_start();

// Validar que exista una sesión activa
if (!isset($_SESSION['user_id']) && !isset($_SESSION['usuario_logueado'])) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'] ?? $_SESSION['usuario_logueado'] ?? 'Usuario';
$rol = $_SESSION['rol'] ?? 'Administrador';

// Parámetros de conexión a la base de datos local
$host = 'localhost';
$dbname = 'escuela_tecnica';
$db_user = 'root';
$db_pass = '';
$db_connected = false;
$error_con = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_connected = true;
} catch (PDOException $e) {
    $error_con = $e->getMessage();
}

// Variables para manejo de mensajes (flash session)
$success_msg = $_SESSION['success_msg'] ?? '';
$error_msg = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

$action = $_GET['action'] ?? 'list';
$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// PROCESAR FORMULARIO DE CREAR / EDITAR
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_student'])) {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $cedula = trim($_POST['cedula'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $genero = $_POST['genero'] ?? '';
        $fecha_nacimiento = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null;
        $nivel_academico = $_POST['nivel_academico'] ?? '';
        $seccion = $_POST['seccion'] ?? '';
        $estado_civil = $_POST['estado_civil'] ?? '';
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $ciudad = trim($_POST['ciudad'] ?? '');
        $estado = $_POST['estado'] ?? 'Activo';

        // Validaciones básicas
        if (empty($cedula) || empty($nombre) || empty($apellido)) {
            $_SESSION['error_msg'] = "La cédula, el nombre y el apellido son campos obligatorios.";
            header("Location: estudiantes.php?action=" . ($id > 0 ? "edit&id=$id" : "new"));
            exit();
        }

        if ($db_connected) {
            try {
                // Verificar si la cédula ya existe para otro estudiante
                $check_stmt = $pdo->prepare("SELECT id FROM estudiantes WHERE cedula = ? AND id != ?");
                $check_stmt->execute([$cedula, $id]);
                if ($check_stmt->fetch()) {
                    $_SESSION['error_msg'] = "Error: Ya existe un estudiante registrado con la cédula $cedula.";
                    header("Location: estudiantes.php?action=" . ($id > 0 ? "edit&id=$id" : "new"));
                    exit();
                }

                if ($id > 0) {
                    // Actualizar estudiante
                    $sql = "UPDATE estudiantes SET 
                                cedula = ?, nombre = ?, apellido = ?, genero = ?, fecha_nacimiento = ?, 
                                nivel_academico = ?, seccion = ?, estado_civil = ?, email = ?, 
                                telefono = ?, direccion = ?, ciudad = ?, estado = ?
                            WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $cedula, $nombre, $apellido, $genero, $fecha_nacimiento,
                        $nivel_academico, $seccion, $estado_civil, $email,
                        $telefono, $direccion, $ciudad, $estado, $id
                    ]);
                    $_SESSION['success_msg'] = "Estudiante actualizado correctamente.";
                } else {
                    // Insertar nuevo estudiante
                    $sql = "INSERT INTO estudiantes (
                                cedula, nombre, apellido, genero, fecha_nacimiento, 
                                nivel_academico, seccion, estado_civil, email, 
                                telefono, direccion, ciudad, estado
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $cedula, $nombre, $apellido, $genero, $fecha_nacimiento,
                        $nivel_academico, $seccion, $estado_civil, $email,
                        $telefono, $direccion, $ciudad, $estado
                    ]);
                    $_SESSION['success_msg'] = "Nuevo estudiante registrado con éxito.";
                }
                header('Location: estudiantes.php');
                exit();
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = "Error al procesar los datos: " . $e->getMessage();
                header("Location: estudiantes.php?action=" . ($id > 0 ? "edit&id=$id" : "new"));
                exit();
            }
        } else {
            $_SESSION['error_msg'] = "No hay conexión activa con la base de datos para guardar la información.";
            header('Location: estudiantes.php');
            exit();
        }
    }

    // PROCESAR ELIMINACIÓN
    if (isset($_POST['delete_student_confirmed'])) {
        $id = intval($_POST['delete_id'] ?? 0);
        if ($db_connected && $id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM estudiantes WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success_msg'] = "Registro de estudiante eliminado exitosamente.";
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = "No se pudo eliminar el estudiante. Posibles datos vinculados.";
            }
        } else {
            $_SESSION['error_msg'] = "Error al conectar con la base de datos o identificador de estudiante no válido.";
        }
        header('Location: estudiantes.php');
        exit();
    }
}

// OBTENER DATOS DE ESTUDIANTE PARA EDICIÓN
$current_student = null;
if ($action === 'edit' && $student_id > 0) {
    if ($db_connected) {
        $stmt = $pdo->prepare("SELECT * FROM estudiantes WHERE id = ?");
        $stmt->execute([$student_id]);
        $current_student = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$current_student) {
            $_SESSION['error_msg'] = "Estudiante no encontrado en el sistema.";
            header('Location: estudiantes.php');
            exit();
        }
    } else {
        // Mock de datos para testing o contingencia sin BD
        $current_student = [
            'id' => 1, 'cedula' => 'V-28.555.111', 'nombre' => 'Yilbert', 'apellido' => 'Ramírez',
            'genero' => 'Masculino', 'fecha_nacimiento' => '2005-04-12', 'nivel_academico' => '6to Año',
            'seccion' => 'A', 'estado_civil' => 'Soltero/a', 'email' => 'yramirez@liceo.edu.ve',
            'telefono' => '0414-5551234', 'direccion' => 'Av. Bolívar Casa #23', 'ciudad' => 'Valera', 'estado' => 'Activo'
        ];
    }
}

// OBTENER TODOS LOS ESTUDIANTES PARA LA LISTA
$estudiantes = [];
$all_grados = [];

if ($db_connected) {
    try {
        $query = "SELECT * FROM estudiantes ORDER BY nombre ASC, apellido ASC";
        $stmt = $pdo->query($query);
        $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_con = "Fallo de consulta: " . $e->getMessage();
    }
}

// Si no hay datos en BD o falló la conexión, cargamos datos simulados detallados
if (empty($estudiantes)) {
    $estudiantes = [
        [
            'id' => 1, 'cedula' => 'V-28.555.111', 'nombre' => 'Yilbert', 'apellido' => 'Ramírez',
            'genero' => 'Masculino', 'fecha_nacimiento' => '2005-08-14', 'nivel_academico' => '6to Año',
            'seccion' => 'A', 'estado_civil' => 'Soltero/a', 'email' => 'yramirez@liceo.edu.ve',
            'telefono' => '0414-5551234', 'direccion' => 'San Luis, Calle Principal', 'ciudad' => 'Valera', 'estado' => 'Activo'
        ],
        [
            'id' => 2, 'cedula' => 'V-29.444.222', 'nombre' => 'María', 'apellido' => 'Gómez',
            'genero' => 'Femenino', 'fecha_nacimiento' => '2006-02-23', 'nivel_academico' => '5to Año',
            'seccion' => 'B', 'estado_civil' => 'Soltero/a', 'email' => 'maria.gomez@liceo.edu.ve',
            'telefono' => '0424-4445678', 'direccion' => 'La Puerta, Urb. El Prado', 'ciudad' => 'La Puerta', 'estado' => 'Activo'
        ],
        [
            'id' => 3, 'cedula' => 'V-30.333.333', 'nombre' => 'Juan', 'apellido' => 'Pérez',
            'genero' => 'Masculino', 'fecha_nacimiento' => '2007-11-05', 'nivel_academico' => '6to Año',
            'seccion' => 'B', 'estado_civil' => 'Soltero/a', 'email' => '',
            'telefono' => '', 'direccion' => 'Sector Plata II', 'ciudad' => 'Valera', 'estado' => 'Inactivo'
        ]
    ];
}

// Obtener grados únicos para los filtros de búsqueda
foreach ($estudiantes as $est) {
    if (!empty($est['nivel_academico'])) {
        $all_grados[] = $est['nivel_academico'];
    }
}
$all_grados = array_unique($all_grados);
sort($all_grados);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Estudiantes - Liceo Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    
    <style>
        :root {
            --primary-color: #0d6efd;
            --primary-hover: #0b5ed7;
            --dark-blue: #0f172a;
            --border-color: #e2e8f0;
            --bg-light: #f8fafc;
            --text-dark: #334155;
            --text-muted: #64748b;
        }

        /* Directorio General y Contenedores */
        .directory-container {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid var(--border-color);
            overflow: hidden;
            margin-top: 1.5rem;
        }
        
        .directory-controls {
            background: #f8fafc;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .directory-search {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .directory-search input {
            width: 100%;
            padding: 0.625rem 1rem 0.625rem 2.5rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.9rem;
            outline: none;
            font-family: inherit;
            transition: all 0.2s;
            box-sizing: border-box;
        }

        .directory-search input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
        }

        .directory-search i {
            position: absolute;
            left: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .directory-filter {
            padding: 0.625rem 1.25rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background-color: #ffffff;
            font-size: 0.9rem;
            color: var(--text-dark);
            cursor: pointer;
            outline: none;
            transition: all 0.2s;
            font-family: inherit;
        }

        .directory-table-wrapper {
            overflow-x: auto;
        }

        .directory-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .directory-table th {
            background: #f1f5f9;
            padding: 1rem 1.5rem;
            font-weight: 600;
            font-size: 0.8rem;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid var(--border-color);
        }

        .directory-table td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.9rem;
            color: var(--text-dark);
            vertical-align: middle;
        }

        .directory-table tr:hover {
            background-color: #f8fafc;
        }

        /* Badges de Estado */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-badge.active {
            background-color: #dcfce7;
            color: #15803d;
        }

        .status-badge.inactive {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        /* Botones de acción */
        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            color: #475569;
            background: #f1f5f9;
            border: 1px solid var(--border-color);
            transition: all 0.2s;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-action.edit:hover {
            background-color: #eff6ff;
            border-color: #bfdbfe;
            color: #2563eb;
        }

        .btn-action.delete:hover {
            background-color: #fef2f2;
            border-color: #fca5a5;
            color: #dc2626;
        }

        .student-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #e2f0fd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: var(--primary-color);
        }

        /* Alertas de Notificación Personalizadas */
        .alert-toast {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
            font-weight: 500;
            border-left: 4px solid;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .alert-toast.success {
            background: #ecfdf5;
            color: #065f46;
            border-color: #10b981;
        }

        .alert-toast.error {
            background: #fef2f2;
            color: #991b1b;
            border-color: #ef4444;
        }

        /* Formularios y cuadrícula de campos */
        .form-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            padding: 2rem;
            margin-top: 1.5rem;
        }

        .form-section-title {
            font-size: 1.1rem;
            color: var(--dark-blue);
            font-weight: 600;
            margin-bottom: 1.25rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-group input, .form-group select, .form-group textarea {
            padding: 0.625rem 0.875rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.9rem;
            font-family: inherit;
            outline: none;
            transition: all 0.2s;
            color: #1e293b;
            background: #ffffff;
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            border-top: 1px solid var(--border-color);
            padding-top: 1.5rem;
            margin-top: 1rem;
        }

        .btn-cancel {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .btn-cancel:hover {
            background-color: #e2e8f0;
            color: #1e293b;
        }

        .btn-save {
            background-color: var(--primary-color);
            color: #ffffff;
            border: none;
            padding: 0.625rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .btn-save:hover {
            background-color: var(--primary-hover);
        }

        /* Modal personalizado de eliminación (Reemplaza confirm nativo) */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.25s ease-in-out;
        }

        .modal-overlay.show {
            display: flex;
            opacity: 1;
        }

        .custom-modal {
            background: #ffffff;
            border-radius: 12px;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            padding: 2rem;
            border: 1px solid var(--border-color);
            transform: scale(0.95);
            transition: transform 0.25s ease-in-out;
        }

        .modal-overlay.show .custom-modal {
            transform: scale(1);
        }

        .modal-header-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #fef2f2;
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1.25rem;
        }

        .modal-title {
            font-size: 1.2rem;
            color: var(--dark-blue);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .modal-description {
            font-size: 0.9rem;
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 1.5rem;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        .btn-modal-cancel {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            color: #475569;
        }

        .btn-modal-cancel:hover {
            background: #e2e8f0;
        }

        .btn-modal-delete {
            background: #ef4444;
            color: #ffffff;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-modal-delete:hover {
            background: #dc2626;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar unificado idéntico al resto de módulos -->
            <div class="content-wrapper">
                
                <!-- Toast de notificaciones de éxito o error -->
                <?php if (!empty($success_msg)): ?>
                    <div class="alert-toast success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($success_msg); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_msg)): ?>
                    <div class="alert-toast error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error_msg); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($action === 'list'): ?>
                    <!-- VISTA: DIRECTORIO / LISTADO DE ESTUDIANTES -->
                    <div class="top-action-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <div>
                            <h1 style="font-size: 1.8rem; color: #0f172a; margin: 0; font-weight: 700;">🎓 Directorio de Estudiantes</h1>
                        </div>
                        <a href="gestion-estudiantes.php?action=new" class="btn-add" style="background-color: var(--primary-color); color: #fff; padding: 0.625rem 1.25rem; border-radius: 8px; font-weight: 600; font-size: 0.9rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; transition: background 0.2s;">
                            <i class="fas fa-plus"></i> Registrar Estudiante
                        </a>
                    </div>

                    <div class="directory-container">
                        <div class="directory-controls">
                            <div class="directory-search">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchInput" placeholder="Buscar por nombre, apellido, cédula..." onkeyup="filterTable()">
                            </div>
                            
                            <select id="gradoFilter" class="directory-filter" onchange="filterTable()">
                                <option value="all">Todos los Grados / Niveles</option>
                                <?php foreach($all_grados as $grado): ?>
                                    <option value="<?php echo htmlspecialchars($grado); ?>"><?php echo htmlspecialchars($grado); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="directory-table-wrapper">
                            <table class="directory-table" id="studentsTable">
                                <thead>
                                    <tr>
                                        <th>Estudiante</th>
                                        <th>Cédula</th>
                                        <th>Nivel / Sección</th>
                                        <th>Género</th>
                                        <th>Contacto</th>
                                        <th>Estado</th>
                                        <th style="text-align: center;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($estudiantes as $est): ?>
                                        <tr data-grado="<?php echo htmlspecialchars($est['nivel_academico'] ?? ''); ?>">
                                            <td>
                                                <div class="student-info" style="display: flex; align-items: center; gap: 0.75rem;">
                                                    <div class="student-avatar">
                                                        <?php 
                                                            $inicialNombre = mb_substr($est['nombre'] ?? 'E', 0, 1, 'UTF-8');
                                                            $inicialApellido = mb_substr($est['apellido'] ?? 'S', 0, 1, 'UTF-8');
                                                            echo htmlspecialchars($inicialNombre . $inicialApellido); 
                                                        ?>
                                                    </div>
                                                    <div>
                                                        <strong style="color: #0f172a; display: block;"><?php echo htmlspecialchars(($est['nombre'] ?? '') . ' ' . ($est['apellido'] ?? '')); ?></strong>
                                                        <span style="font-size: 0.75rem; color: #64748b;"><?php echo htmlspecialchars($est['ciudad'] ?? 'Sin ciudad'); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td style="font-weight: 500; color: #475569;">
                                                <?php echo htmlspecialchars($est['cedula'] ?? 'S/C'); ?>
                                            </td>
                                            <td>
                                                <span class="badge" style="background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 6px; font-weight: 600;">
                                                    <?php echo htmlspecialchars(($est['nivel_academico'] ?? 'N/A') . ' - ' . ($est['seccion'] ?? 'A')); ?>
                                                </span>
                                            </td>
                                            <td style="color: #475569;">
                                                <?php echo htmlspecialchars($est['genero'] ?? 'No especifica'); ?>
                                            </td>
                                            <td>
                                                <div class="contact-details" style="display: flex; flex-direction: column;">
                                                    <span style="color: #334155; font-weight: 500; font-size: 0.85rem;"><?php echo htmlspecialchars(!empty($est['email']) ? $est['email'] : 'Sin correo'); ?></span>
                                                    <span style="font-size: 0.75rem; color: #64748b;"><?php echo htmlspecialchars(!empty($est['telefono']) ? $est['telefono'] : 'Sin teléfono'); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if(strcasecmp(($est['estado'] ?? ''), 'Activo') === 0): ?>
                                                    <span class="status-badge active"><i class="fas fa-circle" style="font-size: 6px; margin-right: 5px;"></i> Activo</span>
                                                <?php else: ?>
                                                    <span class="status-badge inactive"><i class="fas fa-circle" style="font-size: 6px; margin-right: 5px;"></i> Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <div style="display: inline-flex; gap: 6px;">
                                                    <a href="gestion-estudiantes.php?action=edit&id=<?php echo urlencode($est['id'] ?? ''); ?>" class="btn-action edit" title="Editar Estudiante">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn-action delete" title="Eliminar Estudiante" onclick="showDeleteModal(<?php echo $est['id']; ?>, '<?php echo htmlspecialchars($est['nombre'] . ' ' . $est['apellido'], ENT_QUOTES); ?>')">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php elseif ($action === 'new' || $action === 'edit'): ?>
                    <!-- VISTA: CREAR / EDITAR REGISTRO -->
                    <div class="top-action-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <div>
                            <h1 style="font-size: 1.8rem; color: #0f172a; margin: 0; font-weight: 700;">
                                <?php echo $action === 'edit' ? '📝 Editar Estudiante' : '👤 Registrar Nuevo Estudiante'; ?>
                            </h1>
                            <p style="color: #64748b; font-size: 0.9rem; margin-top: 0.25rem;">Completa los campos del expediente académico del estudiante.</p>
                        </div>
                        <a href="gestion-estudiantes.php" class="btn-cancel">
                            <i class="fas fa-arrow-left" style="margin-right: 0.5rem;"></i> Volver al listado
                        </a>
                    </div>

                    <form method="POST" action="gestion-estudiantes.php" class="form-card">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($current_student['id'] ?? '0'); ?>">
                        
                        <!-- SECCIÓN 1: DATOS PERSONALES -->
                        <div class="form-section-title">
                            <i class="fas fa-id-card"></i> Datos Personales Básicos
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="cedula">Cédula de Identidad *</label>
                                <input type="text" id="cedula" name="cedula" placeholder="Ej: V-28123456" value="<?php echo htmlspecialchars($current_student['cedula'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="nombre">Nombres *</label>
                                <input type="text" id="nombre" name="nombre" placeholder="Ingresa nombres" value="<?php echo htmlspecialchars($current_student['nombre'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="apellido">Apellidos *</label>
                                <input type="text" id="apellido" name="apellido" placeholder="Ingresa apellidos" value="<?php echo htmlspecialchars($current_student['apellido'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="genero">Género</label>
                                <select id="genero" name="genero">
                                    <option value="" disabled <?php echo !isset($current_student['genero']) ? 'selected' : ''; ?>>Selecciona...</option>
                                    <option value="Masculino" <?php echo (isset($current_student['genero']) && $current_student['genero'] === 'Masculino') ? 'selected' : ''; ?>>Masculino</option>
                                    <option value="Femenino" <?php echo (isset($current_student['genero']) && $current_student['genero'] === 'Femenino') ? 'selected' : ''; ?>>Femenino</option>
                                    <option value="Otro" <?php echo (isset($current_student['genero']) && $current_student['genero'] === 'Otro') ? 'selected' : ''; ?>>Otro</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?php echo htmlspecialchars($current_student['fecha_nacimiento'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="estado_civil">Estado Civil</label>
                                <select id="estado_civil" name="estado_civil">
                                    <option value="" disabled <?php echo !isset($current_student['estado_civil']) ? 'selected' : ''; ?>>Selecciona...</option>
                                    <option value="Soltero/a" <?php echo (isset($current_student['estado_civil']) && $current_student['estado_civil'] === 'Soltero/a') ? 'selected' : ''; ?>>Soltero/a</option>
                                    <option value="Casado/a" <?php echo (isset($current_student['estado_civil']) && $current_student['estado_civil'] === 'Casado/a') ? 'selected' : ''; ?>>Casado/a</option>
                                    <option value="Divorciado/a" <?php echo (isset($current_student['estado_civil']) && $current_student['estado_civil'] === 'Divorciado/a') ? 'selected' : ''; ?>>Divorciado/a</option>
                                </select>
                            </div>
                        </div>

                        <!-- SECCIÓN 2: INFORMACIÓN ACADÉMICA -->
                        <div class="form-section-title" style="margin-top: 2rem;">
                            <i class="fas fa-book-reader"></i> Información Académica
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="nivel_academico">Nivel Académico (Grado)</label>
                                <select id="nivel_academico" name="nivel_academico">
                                    <option value="" disabled <?php echo !isset($current_student['nivel_academico']) ? 'selected' : ''; ?>>Selecciona...</option>
                                    <option value="1er Año" <?php echo (isset($current_student['nivel_academico']) && $current_student['nivel_academico'] === '1er Año') ? 'selected' : ''; ?>>1er Año</option>
                                    <option value="2do Año" <?php echo (isset($current_student['nivel_academico']) && $current_student['nivel_academico'] === '2do Año') ? 'selected' : ''; ?>>2do Año</option>
                                    <option value="3er Año" <?php echo (isset($current_student['nivel_academico']) && $current_student['nivel_academico'] === '3er Año') ? 'selected' : ''; ?>>3er Año</option>
                                    <option value="4to Año" <?php echo (isset($current_student['nivel_academico']) && $current_student['nivel_academico'] === '4to Año') ? 'selected' : ''; ?>>4to Año</option>
                                    <option value="5to Año" <?php echo (isset($current_student['nivel_academico']) && $current_student['nivel_academico'] === '5to Año') ? 'selected' : ''; ?>>5to Año</option>
                                    <option value="6to Año" <?php echo (isset($current_student['nivel_academico']) && $current_student['nivel_academico'] === '6to Año') ? 'selected' : ''; ?>>6to Año</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="seccion">Sección</label>
                                <select id="seccion" name="seccion">
                                    <option value="" disabled <?php echo !isset($current_student['seccion']) ? 'selected' : ''; ?>>Selecciona...</option>
                                    <option value="A" <?php echo (isset($current_student['seccion']) && $current_student['seccion'] === 'A') ? 'selected' : ''; ?>>Sección A</option>
                                    <option value="B" <?php echo (isset($current_student['seccion']) && $current_student['seccion'] === 'B') ? 'selected' : ''; ?>>Sección B</option>
                                    <option value="C" <?php echo (isset($current_student['seccion']) && $current_student['seccion'] === 'C') ? 'selected' : ''; ?>>Sección C</option>
                                    <option value="D" <?php echo (isset($current_student['seccion']) && $current_student['seccion'] === 'D') ? 'selected' : ''; ?>>Sección D</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="estado">Estado de Matrícula</label>
                                <select id="estado" name="estado">
                                    <option value="Activo" <?php echo (isset($current_student['estado']) && $current_student['estado'] === 'Activo') ? 'selected' : ''; ?>>Activo</option>
                                    <option value="Inactivo" <?php echo (isset($current_student['estado']) && $current_student['estado'] === 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                                </select>
                            </div>
                        </div>

                        <!-- SECCIÓN 3: DATOS DE CONTACTO Y DIRECCIÓN -->
                        <div class="form-section-title" style="margin-top: 2rem;">
                            <i class="fas fa-map-marked-alt"></i> Datos de Ubicación y Contacto
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="email">Correo Electrónico</label>
                                <input type="email" id="email" name="email" placeholder="ejemplo@liceo.edu" value="<?php echo htmlspecialchars($current_student['email'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="telefono">Número de Teléfono</label>
                                <input type="text" id="telefono" name="telefono" placeholder="Ej: 0412-3456789" value="<?php echo htmlspecialchars($current_student['telefono'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="ciudad">Ciudad</label>
                                <input type="text" id="ciudad" name="ciudad" placeholder="Ej: Valera" value="<?php echo htmlspecialchars($current_student['ciudad'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 2rem;">
                            <label for="direccion">Dirección Domiciliaria Completa</label>
                            <textarea id="direccion" name="direccion" placeholder="Sector, Avenida, número de casa, punto de referencia..."><?php echo htmlspecialchars($current_student['direccion'] ?? ''); ?></textarea>
                        </div>

                        <!-- ACCIONES DEL FORMULARIO -->
                        <div class="form-actions">
                            <a href="gestion-estudiantes.php" class="btn-cancel">Cancelar</a>
                            <button type="submit" name="save_student" class="btn-save">
                                <i class="fas fa-save"></i> Guardar Expediente
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- MODAL DE CONFIRMACIÓN DE ELIMINACIÓN PERSONALIZADO (CUMPLE REGLAS SENCILLEZ SINFONÍA JS) -->
    <div class="modal-overlay" id="deleteConfirmModal">
        <div class="custom-modal">
            <div class="modal-header-icon">
                <i class="fas fa-trash-alt"></i>
            </div>
            <div class="modal-title">¿Eliminar Expediente?</div>
            <div class="modal-description">
                ¿Está seguro de que desea eliminar permanentemente al estudiante <strong id="deleteStudentName"></strong> del sistema? Esta acción es irreversible y podría remover calificaciones asociadas.
            </div>
            <form method="POST" action="gestion-estudiantes.php">
                <input type="hidden" name="delete_id" id="deleteStudentId" value="0">
                <div class="modal-actions">
                    <button type="button" class="btn-modal-cancel" onclick="closeDeleteModal()">Cancelar</button>
                    <button type="submit" name="delete_student_confirmed" class="btn-modal-delete">Eliminar Registro</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Filtrar tabla dinámicamente según la entrada y el combo de selección
        function filterTable() {
            const input = document.getElementById("searchInput").value.toUpperCase();
            const filterGrado = document.getElementById("gradoFilter").value;
            const table = document.getElementById("studentsTable");
            if (!table) return;
            const tr = table.getElementsByTagName("tr");

            // Empezar en la fila index 1 para omitir cabeceras
            for (let i = 1; i < tr.length; i++) {
                const row = tr[i];
                const rowGrado = row.getAttribute("data-grado") || "";
                const textContent = row.textContent || row.innerText;
                
                const matchesSearch = textContent.toUpperCase().indexOf(input) > -1;
                const matchesGrado = (filterGrado === "all" || rowGrado === filterGrado);

                if (matchesSearch && matchesGrado) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            }
        }

        // Mostrar Modal de confirmación de eliminación
        function showDeleteModal(id, studentName) {
            document.getElementById("deleteStudentId").value = id;
            document.getElementById("deleteStudentName").innerText = studentName;
            
            const modal = document.getElementById("deleteConfirmModal");
            modal.classList.add("show");
        }

        // Ocultar Modal de eliminación
        function closeDeleteModal() {
            const modal = document.getElementById("deleteConfirmModal");
            modal.classList.remove("show");
        }
    </script>
</body>
</html>