<?php
session_start();
require_once '../config/database.php'; // Ajusta la ruta según la ubicación de tu conexión

// Verificación de seguridad básica
if (!isset($_SESSION['usuario_logueado']) || !$_SESSION['usuario_logueado'] || $_SESSION['rol'] !== 'Administrador') {
    // header('Location: ../login.php');
    // exit;
}

$mensaje = '';

// --- LÓGICA CRUD ---

// 1. CREAR USUARIO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    $hash = password_hash($_POST['password'], PASSWORD_BCRYPT); // Encriptación compatible con $2y$10$
    
    $stmt = $pdo->prepare("INSERT INTO usuarios (cedula, username, password, nombre, apellido, email, rol, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    try {
        $stmt->execute([
            $_POST['cedula'], $_POST['username'], $hash, $_POST['nombre'], 
            $_POST['apellido'], $_POST['email'], $_POST['rol'], $_POST['estado']
        ]);
        $mensaje = "Usuario creado exitosamente.";
    } catch (PDOException $e) {
        $mensaje = "Error al crear: " . $e->getMessage();
    }
}

// 2. EDITAR USUARIO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'editar') {
    if (!empty($_POST['password'])) {
        // Si se ingresó una nueva contraseña, se actualiza el hash
        $hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE usuarios SET cedula=?, username=?, password=?, nombre=?, apellido=?, email=?, rol=?, estado=? WHERE id=?");
        $parametros = [
            $_POST['cedula'], $_POST['username'], $hash, $_POST['nombre'], 
            $_POST['apellido'], $_POST['email'], $_POST['rol'], $_POST['estado'], $_POST['id']
        ];
    } else {
        // Si la contraseña está vacía, se mantienen los demás datos sin tocar la contraseña
        $stmt = $pdo->prepare("UPDATE usuarios SET cedula=?, username=?, nombre=?, apellido=?, email=?, rol=?, estado=? WHERE id=?");
        $parametros = [
            $_POST['cedula'], $_POST['username'], $_POST['nombre'], 
            $_POST['apellido'], $_POST['email'], $_POST['rol'], $_POST['estado'], $_POST['id']
        ];
    }
    
    try {
        $stmt->execute($parametros);
        $mensaje = "Usuario actualizado exitosamente.";
    } catch (PDOException $e) {
        $mensaje = "Error al actualizar: " . $e->getMessage();
    }
}

// 3. ELIMINAR USUARIO
if (isset($_GET['eliminar'])) {
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    try {
        $stmt->execute([$_GET['eliminar']]);
        $mensaje = "Usuario eliminado.";
    } catch (PDOException $e) {
        $mensaje = "Error al eliminar: " . $e->getMessage();
    }
}

// 4. LEER USUARIOS
$stmt = $pdo->query("SELECT * FROM usuarios ORDER BY id DESC");
$usuarios = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Hereda tus variables de diseño original */
        :root {
            --primary: #0f172a; 
            --accent: #f59e0b; 
            --bg: #f8fafc;
            --surface: #ffffff;
            --text-main: #334155;
            --border: #e2e8f0;
        }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg); margin: 0; color: var(--text-main); }
        .navbar { background: rgba(15, 23, 42, 0.95); color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        
        /* Estilos de la tabla y botones */
        .card { background: var(--surface); border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid var(--border); margin-bottom: 20px;}
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        th { color: var(--primary); font-weight: 600; }
        .btn { padding: 8px 16px; border-radius: 6px; cursor: pointer; border: none; font-size: 0.85rem; text-decoration: none; display: inline-block; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-warning { background: var(--accent); color: white; }
        
        /* Estilos del Modal */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: var(--surface); padding: 24px; border-radius: 12px; width: 100%; max-width: 500px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.85rem; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 4px; box-sizing: border-box; font-family: inherit;}
        .close-btn { float: right; cursor: pointer; font-size: 1.5rem; font-weight: bold; line-height: 1; }
        
        .alert { padding: 12px; background: #dcfce3; color: #166534; border-radius: 6px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <nav class="navbar">
        <h2>Gestión de Usuarios</h2>
        <a href="../index.php" style="color: white; text-decoration: none;">&larr; Volver al Panel</a>
    </nav>

    <div class="container">
        <?php if ($mensaje): ?>
            <div class="alert"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3>Lista de Usuarios</h3>
                <button class="btn btn-primary" onclick="abrirModal('crear')">+ Nuevo Usuario</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Cédula</th>
                        <th>Usuario</th>
                        <th>Nombre y Apellido</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($u['cedula']); ?></td>
                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                        <td><?php echo htmlspecialchars($u['nombre'] . ' ' . $u['apellido']); ?></td>
                        <td><?php echo htmlspecialchars($u['rol']); ?></td>
                        <td>
                            <span style="background: <?php echo $u['estado'] === 'Activo' ? '#dcfce3' : '#fee2e2'; ?>; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;">
                                <?php echo htmlspecialchars($u['estado']); ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-warning" onclick='abrirModal("editar", <?php echo json_encode($u); ?>)'>Editar</button>
                            <a href="?eliminar=<?php echo $u['id']; ?>" class="btn btn-danger" onclick="return confirm('¿Seguro que deseas eliminar este usuario?');">Borrar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Formulario -->
    <div id="usuarioModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="cerrarModal()">&times;</span>
            <h3 id="modalTitle">Nuevo Usuario</h3>
            <form method="POST" id="usuarioForm">
                <input type="hidden" name="accion" id="accion" value="crear">
                <input type="hidden" name="id" id="userId">
                
                <div class="form-group">
                    <label>Cédula</label>
                    <input type="text" name="cedula" id="cedula" required>
                </div>
                <div class="form-group">
                    <label>Usuario (Username)</label>
                    <input type="text" name="username" id="username" required>
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" id="password">
                    <small id="passHelp" style="color: #64748b; font-size: 0.75rem; display:none;">Déjalo en blanco si no deseas cambiarla.</small>
                </div>
                <div style="display: flex; gap: 10px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Nombre</label>
                        <input type="text" name="nombre" id="nombre" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Apellido</label>
                        <input type="text" name="apellido" id="apellido" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="email">
                </div>
                <div class="form-group">
                    <label>Rol</label>
                    <select name="rol" id="rol" required>
                        <option value="Administrador">Administrador</option>
                        <option value="secretaria_direccion">Secretaria Dirección</option>
                        <option value="coordinador_academico">Coordinador Académico</option>
                        <option value="profesor">Profesor</option>
                        <option value="practicas_profesionales">Prácticas Profesionales</option>
                        <option value="labor_social">Labor Social</option>
                        <option value="estudiante">Estudiante</option>
                        <option value="representante">Representante</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado" id="estado" required>
                        <option value="Activo">Activo</option>
                        <option value="Inactivo">Inactivo</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Guardar Cambios</button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('usuarioModal');
        const form = document.getElementById('usuarioForm');

        function abrirModal(tipo, datos = null) {
            modal.style.display = 'flex';
            form.reset();
            
            if (tipo === 'crear') {
                document.getElementById('modalTitle').innerText = 'Nuevo Usuario';
                document.getElementById('accion').value = 'crear';
                document.getElementById('password').required = true;
                document.getElementById('passHelp').style.display = 'none';
            } else if (tipo === 'editar') {
                document.getElementById('modalTitle').innerText = 'Editar Usuario';
                document.getElementById('accion').value = 'editar';
                document.getElementById('password').required = false;
                document.getElementById('passHelp').style.display = 'block';
                
                // Cargar datos al formulario
                document.getElementById('userId').value = datos.id;
                document.getElementById('cedula').value = datos.cedula;
                document.getElementById('username').value = datos.username;
                document.getElementById('nombre').value = datos.nombre;
                document.getElementById('apellido').value = datos.apellido;
                document.getElementById('email').value = datos.email;
                document.getElementById('rol').value = datos.rol;
                document.getElementById('estado').value = datos.estado;
            }
        }

        function cerrarModal() {
            modal.style.display = 'none';
        }

        // Cerrar modal si se hace clic fuera del contenido
        window.onclick = function(event) {
            if (event.target == modal) {
                cerrarModal();
            }
        }
    </script>
</body>
</html>