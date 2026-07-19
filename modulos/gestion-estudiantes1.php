<?php
session_start();
require_once '../includes/assignments_data.php';

if (!isset($_SESSION['usuario_logueado'])) {
    header('Location: ../login.php');
    exit;
}
var_dump($estudiantes);
$rol = $_SESSION['rol'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Estudiantes</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #f8fafc; --surface: #fff; --primary: #0f172a; --accent: #f59e0b; --text: #334155; --border: #e2e8f0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 20px; color: var(--text); }
        .container { max-width: 1300px; margin: 0 auto; }
        
        .header-box { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .header-box h1 { margin: 0; color: var(--primary); font-size: 1.8rem; }
        .btn-back { background: white; border: 1px solid var(--border); padding: 8px 16px; border-radius: 8px; text-decoration: none; color: var(--text); font-weight: 500; transition: all 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .btn-back:hover { border-color: var(--primary); color: var(--primary); }

        /* Controles de la tabla */
        .controls { background: var(--surface); padding: 15px 20px; border-radius: 12px 12px 0 0; border: 1px solid var(--border); border-bottom: none; display: flex; gap: 15px; align-items: center; }
        .search-box { flex: 1; position: relative; }
        .search-box input { width: 100%; padding: 10px 15px 10px 35px; border: 1px solid var(--border); border-radius: 8px; outline: none; font-family: inherit; font-size: 0.9rem; box-sizing: border-box; transition: border-color 0.2s; }
        .search-box input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1); }
        .search-box svg { position: absolute; left: 10px; top: 10px; color: #94a3b8; width: 18px; height: 18px; }
        
        .filter-select { padding: 10px; border: 1px solid var(--border); border-radius: 8px; outline: none; background: white; font-family: inherit; font-size: 0.9rem; cursor: pointer; }

        /* Estilos de la Tabla */
        .table-container { background: var(--surface); border-radius: 0 0 12px 12px; border: 1px solid var(--border); overflow-x: auto; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; text-align: left; white-space: nowrap; }
        th { background: #f1f5f9; padding: 14px 20px; font-weight: 600; font-size: 0.85rem; color: var(--primary); border-bottom: 2px solid var(--border); }
        td { padding: 14px 20px; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #f8fafc; }
        
        .badge { background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
        .badge.fem { background: #fce7f3; color: #be185d; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-box">
            <h1>🎓 Directorio de Estudiantes</h1>
            <a href="../index.php" class="btn-back">← Volver al Dashboard</a>
        </div>

        <div class="controls">
            <div class="search-box">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <input  value="yilbert es gay" type="text" id="searchInput" placeholder="Buscar por nombre, cédula o docente..." onkeyup="filterTable()">
            </div>
            <select id="gradoFilter" class="filter-select" onchange="filterTable()">
                <option value="all">Todos los Grados</option>
                <?php foreach(array_keys($estudiantes) as $grado) echo "<option value='$grado'>$grado</option>"; ?>
            </select>
        <?php 
        // ==========================================
        // 3. LÓGICA DE VISUALIZACIÓN (EL PROBLEMA PRINCIPAL)
        // ==========================================
        if (count($estudiantes) > 0): 
        ?>
            <!-- Si HAY datos, mostramos la tabla -->
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cédula</th>
                            <th class="px-6 py-3 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estudiante</th>
                            <th class="px-6 py-3 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nivel / Sección</th>
                            <th class="px-6 py-3 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contacto</th>
                            <th class="px-6 py-3 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 border-b text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($estudiantes as $est): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                    <?php echo htmlspecialchars($est['cedula']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($est['nombre'] . ' ' . $est['apellido']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?php echo htmlspecialchars($est['nivel_academico'] . ' - ' . $est['seccion']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <div><?php echo htmlspecialchars($est['email'] ? $est['email'] : 'Sin email'); ?></div>
                                    <div class="text-xs text-gray-400"><?php echo htmlspecialchars($est['telefono'] ? $est['telefono'] : 'Sin teléfono'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php if($est['estado'] == 'Activo'): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Activo</span>
                                    <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                    <a href="#" class="text-indigo-600 hover:text-indigo-900 mr-2">Editar</a>
                                    <a href="#" class="text-red-600 hover:text-red-900">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <!-- Si NO HAY datos, ocultamos la tabla y mostramos este mensaje -->
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mt-4 rounded-r">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <!-- Icono de alerta -->
                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Atención</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>No hay estudiantes registrados en el sistema actualmente. Haz clic en "Nuevo Estudiante" para comenzar a agregar registros.</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?></div>

        <div class="table-container">
        
        </div>
    </div>
    <script>
        function filterTable() {
            const input = document.getElementById("searchInput").value.toUpperCase();
            const filterGrado = document.getElementById("gradoFilter").value;
            const table = document.getElementById("studentsTable");
            const tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                const row = tr[i];
                const rowGrado = row.getAttribute("data-grado");
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
    </script>
</body>
</html>