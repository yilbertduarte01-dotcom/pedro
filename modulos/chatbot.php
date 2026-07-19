<?php
session_start();

if (!isset($_SESSION['usuario_logueado']) || !$_SESSION['usuario_logueado']) {
    header('Location: ../login.php');
    exit;
}

require_once '../includes/config.php';

$nombre_institucion = "Escuela Técnica Pedro Garcia Leal";
$rol_actual = $_SESSION['rol'] ?? 'Usuario';
$usuario_actual = $_SESSION['nombre'] ?? 'Usuario';

// Base de preguntas frecuentes organizadas por módulo
$preguntas_frecuentes = [
    // Preguntas Generales
    [
        'id' => 'general_1',
        'categoria' => 'General',
        'pregunta' => '¿Qué es este sistema?',
        'respuesta' => 'Este es el Sistema de Gestión Escolar de ' . $nombre_institucion . '. Te permite acceder a información académica, comunicarte con la institución, registrar asistencia, y recibir notificaciones sobre el desempeño estudiantil en tiempo real.'
    ],
    [
        'id' => 'general_2',
        'categoria' => 'General',
        'pregunta' => '¿Cómo cambio mi contraseña?',
        'respuesta' => 'Para cambiar tu contraseña, ve a tu perfil (esquina superior derecha) y busca la opción "Cambiar Contraseña". Ingresa tu contraseña actual y la nueva contraseña dos veces. Los cambios se guardarán inmediatamente.'
    ],
    [
        'id' => 'general_3',
        'categoria' => 'General',
        'pregunta' => '¿Olvidé mi contraseña, qué hago?',
        'respuesta' => 'Por ahora, contacta directamente con la administración de la escuela. En futuras versiones, habrá un sistema de recuperación automática. Los administradores pueden resetear tu contraseña.'
    ],
    [
        'id' => 'general_4',
        'categoria' => 'General',
        'pregunta' => '¿El sistema es seguro?',
        'respuesta' => 'Sí, utilizamos encriptación moderna para proteger tus datos. Tu información personal solo es accesible para el personal autorizado de la institución. Los pagos y datos sensibles están protegidos.'
    ],

    // Noticias
    [
        'id' => 'noticias_1',
        'categoria' => 'Noticias',
        'pregunta' => '¿Qué es el módulo de Noticias?',
        'respuesta' => 'En Noticias encontrarás comunicados oficiales de la institución, cambios de horario, fechas importantes, eventos escolares y anuncios generales. Se actualiza regularmente por la administración.'
    ],
    [
        'id' => 'noticias_2',
        'categoria' => 'Noticias',
        'pregunta' => '¿Cómo sé si hay noticias nuevas?',
        'respuesta' => 'Las noticias más recientes aparecen primero. Puedes revisar el módulo regularmente. Algunas noticias importantes pueden enviarse por notificación según la configuración de tu cuenta.'
    ],

    // Reuniones
    [
        'id' => 'reuniones_1',
        'categoria' => 'Reuniones',
        'pregunta' => '¿Cómo funciona el módulo de Reuniones?',
        'respuesta' => 'Los profesores y administradores pueden programar reuniones con representantes indicando fecha, hora y temas a tratar. Los representantes ven las reuniones de su sección y pueden confirmar asistencia.'
    ],
    [
        'id' => 'reuniones_2',
        'categoria' => 'Reuniones',
        'pregunta' => '¿Cómo confirmo mi asistencia a una reunión?',
        'respuesta' => 'En el módulo Reuniones, verás todas las reuniones programadas. Haz clic en el botón "✓ Confirmar Asistencia" en la reunión que deseas confirmar. Tu confirmación será registrada inmediatamente.'
    ],
    [
        'id' => 'reuniones_3',
        'categoria' => 'Reuniones',
        'pregunta' => '¿Qué hago si no puedo asistir a una reunión?',
        'respuesta' => 'Si no puedes asistir, comunícalo directamente con el profesor o administrador. Aunque no confirmes asistencia, es importante notificar tu inasistencia para que se planifiquen adecuadamente.'
    ],
    [
        'id' => 'reuniones_4',
        'categoria' => 'Reuniones',
        'pregunta' => '¿Puedo crear reuniones siendo representante?',
        'respuesta' => 'No, solo profesores y administradores pueden crear reuniones. Si necesitas una reunión, contacta al profesor o coordinador de tu sección.'
    ],

    // Reportes de Conducta
    [
        'id' => 'conducta_1',
        'categoria' => 'Reportes de Conducta',
        'pregunta' => '¿Qué es un reporte de conducta?',
        'respuesta' => 'Es un registro de incidencias de comportamiento estudiantil. Puede ser una felicitación por buen desempeño, un llamado de atención por faltas menores, una amonestación por faltas graves, o un reporte grave por conducta muy inapropiada.'
    ],
    [
        'id' => 'conducta_2',
        'categoria' => 'Reportes de Conducta',
        'pregunta' => '¿Quién puede crear reportes de conducta?',
        'respuesta' => 'Solo profesores y administradores pueden crear reportes de conducta. Los reportes se cargan en el módulo "Reportes de Conducta" y son visibles inmediatamente para los representantes.'
    ],
    [
        'id' => 'conducta_3',
        'categoria' => 'Reportes de Conducta',
        'pregunta' => '¿Debo firmar los reportes de conducta?',
        'respuesta' => 'Debes firmar digitalmente solo los reportes de tipo "Amonestación" y "Reporte Grave". Esta firma es una constancia legal de que fuiste notificado. Las felicitaciones y llamados de atención no requieren firma.'
    ],
    [
        'id' => 'conducta_4',
        'categoria' => 'Reportes de Conducta',
        'pregunta' => '¿Qué significa "Leído y Conforme"?',
        'respuesta' => '"Leído y Conforme" es tu firma digital que comprueba que leíste y entendiste la amonestación. Es una constancia legal de que fuiste notificado del comportamiento del estudiante. Se guarda la fecha, hora, IP y navegador.'
    ],
    [
        'id' => 'conducta_5',
        'categoria' => 'Reportes de Conducta',
        'pregunta' => '¿Puedo cambiar o eliminar mi firma?',
        'respuesta' => 'No, una vez que firmas digitalmente un reporte, la firma es permanente. Esto garantiza la integridad legal del proceso. Si hay error, debes contactar con administración.'
    ],
    [
        'id' => 'conducta_6',
        'categoria' => 'Reportes de Conducta',
        'pregunta' => '¿Qué tipos de reportes existen?',
        'respuesta' => 'Hay 4 tipos: (1) Felicitación - comportamiento excepcional, (2) Llamado de Atención - faltas menores, (3) Amonestación - faltas graves, (4) Reporte Grave - conducta muy inapropiada. Los dos últimos requieren firma digital.'
    ],

    // Convivencia y Conducta
    [
        'id' => 'convivencia_1',
        'categoria' => 'Convivencia y Conducta',
        'pregunta' => '¿Dónde veo los reportes de conducta de mi hijo?',
        'respuesta' => 'En el módulo Representante, hay una pestaña llamada "Convivencia y Conducta". Allí verás todos los reportes cargados por los profesores en tiempo real, incluyendo felicitaciones y amonestaciones.'
    ],
    [
        'id' => 'convivencia_2',
        'categoria' => 'Convivencia y Conducta',
        'pregunta' => '¿Por qué debo revisar frecuentemente la sección de Convivencia?',
        'respuesta' => 'Para enterarte rápidamente de problemas de conducta de tu hijo. Antes, los padres solo se enteraban al final del lapso. Ahora tienes información en tiempo real para tomar acciones correctivas.'
    ],
    [
        'id' => 'convivencia_3',
        'categoria' => 'Convivencia y Conducta',
        'pregunta' => '¿Qué hago si no estoy de acuerdo con un reporte?',
        'respuesta' => 'Puedes agregar observaciones al firmar. Si desacuerdas completamente, contacta directamente con el profesor o la coordinación para discutir el incidente.'
    ],

    // Gestión de Estudiantes
    [
        'id' => 'estudiantes_1',
        'categoria' => 'Gestión de Estudiantes',
        'pregunta' => '¿Qué información puedo ver en Gestión de Estudiantes?',
        'respuesta' => 'Es un módulo administrativo donde se gestiona la información de estudiantes, representantes y datos académicos. Solo administradores y coordinadores tienen acceso a este módulo.'
    ],

    // Asistencias
    [
        'id' => 'asistencias_1',
        'categoria' => 'Asistencias',
        'pregunta' => '¿Cómo funciona el sistema de códigos QR?',
        'respuesta' => 'Se generan códigos QR para cada clase. Los estudiantes escanean el código con su dispositivo para registrar asistencia. Es rápido, seguro y evita fraudes.'
    ],
    [
        'id' => 'asistencias_2',
        'categoria' => 'Asistencias',
        'pregunta' => '¿Qué debo hacer si falta la batería de mi dispositivo?',
        'respuesta' => 'Avisa al profesor inmediatamente. El profesor puede registrarte manualmente en el sistema. Pero intenta siempre tener batería para evitar problemas.'
    ],
    [
        'id' => 'asistencias_3',
        'categoria' => 'Asistencias',
        'pregunta' => '¿Puedo ver mi historial de asistencia?',
        'respuesta' => 'Sí, en el módulo Representante hay una pestaña "Historial de Asistencia" donde puedes ver todas las asistencias registradas con fechas y horas.'
    ],

    // Labor Social
    [
        'id' => 'labor_1',
        'categoria' => 'Labor Social',
        'pregunta' => '¿Qué es el módulo de Labor Social?',
        'respuesta' => 'Es donde se registran y monitorizan las horas de servicio comunitario que deben realizar los estudiantes. Es parte de su formación integral.'
    ],
    [
        'id' => 'labor_2',
        'categoria' => 'Labor Social',
        'pregunta' => '¿Cuántas horas de labor social debo cumplir?',
        'respuesta' => 'Eso depende de la institución y el nivel. Revisa las normas de la escuela o pregunta directamente a coordinación. El módulo mostrará tu progreso.'
    ],
    [
        'id' => 'labor_3',
        'categoria' => 'Labor Social',
        'pregunta' => '¿Cómo registro mis horas de labor social?',
        'respuesta' => 'El módulo de Labor Social tiene un formulario donde ingresas la fecha, lugar, actividad y horas realizadas. Un coordinador debe aprobar el registro.'
    ],

    // Profesor
    [
        'id' => 'profesor_1',
        'categoria' => 'Profesor',
        'pregunta' => '¿Cómo publico tareas en el módulo Profesor?',
        'respuesta' => 'En el módulo Profesor, encontrarás opciones para crear tareas, cargar material de clase, crear exámenes y compartir recursos. Especifica la fecha de entrega y los detalles de la tarea.'
    ],
    [
        'id' => 'profesor_2',
        'categoria' => 'Profesor',
        'pregunta' => '¿Dónde entrego las tareas?',
        'respuesta' => 'En el módulo Profesor, hay una sección de tareas donde puedes ver las tareas pendientes. Haz clic en la tarea y encontrarás la opción para entregarla antes de la fecha límite.'
    ],

    // Pasantías
    [
        'id' => 'pasantias_1',
        'categoria' => 'Pasantías',
        'pregunta' => '¿Qué es una pasantía?',
        'respuesta' => 'Una pasantía es una experiencia laboral donde los estudiantes aplican lo aprendido en una empresa real. Es parte importante de la formación técnica.'
    ],
    [
        'id' => 'pasantias_2',
        'categoria' => 'Pasantías',
        'pregunta' => '¿Dónde registro mis pasantías?',
        'respuesta' => 'En el módulo de Pasantías, hay un formulario donde registras la empresa, supervisor, fecha de inicio/fin, y actividades realizadas. Esto genera un récord de tu experiencia.'
    ],

    // Centro de Ayuda
    [
        'id' => 'ayuda_1',
        'categoria' => 'Centro de Ayuda',
        'pregunta' => '¿Cómo uso este chatbot?',
        'respuesta' => 'Escribe tu pregunta en el campo inferior. El sistema te mostrará respuestas de las preguntas frecuentes relacionadas. Si no encuentras la respuesta, busca más preguntas o contacta directamente con administración.'
    ],
    [
        'id' => 'ayuda_2',
        'categoria' => 'Centro de Ayuda',
        'pregunta' => '¿A quién contacto si tengo un problema técnico?',
        'respuesta' => 'Contacta con la administración de la escuela. Ellos pueden ayudarte con problemas técnicos, errores en el sistema o cualquier otra dificultad que tengas.'
    ],
    [
        'id' => 'ayuda_3',
        'categoria' => 'Centro de Ayuda',
        'pregunta' => '¿Cómo obtengo más ayuda?',
        'respuesta' => 'Puedes: (1) Explorar todas las preguntas frecuentes en este módulo, (2) Contactar con la administración por teléfono, (3) Enviar un correo electrónico, (4) Visitar personalmente la institución.'
    ]
];

// Procesar búsqueda
$termino_busqueda = '';
$resultados = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $termino_busqueda = $conexion->real_escape_string($_POST['pregunta'] ?? '');
    
    if (!empty($termino_busqueda)) {
        $termino_lower = strtolower($termino_busqueda);
        
        foreach ($preguntas_frecuentes as $item) {
            $pregunta_lower = strtolower($item['pregunta']);
            $respuesta_lower = strtolower($item['respuesta']);
            
            if (strpos($pregunta_lower, $termino_lower) !== false || 
                strpos($respuesta_lower, $termino_lower) !== false) {
                $resultados[] = $item;
            }
        }
    }
}

// Agrupar por categoría
$por_categoria = [];
foreach ($preguntas_frecuentes as $item) {
    $cat = $item['categoria'];
    if (!isset($por_categoria[$cat])) {
        $por_categoria[$cat] = [];
    }
    $por_categoria[$cat][] = $item;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centro de Ayuda - <?php echo $nombre_institucion; ?></title>
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
            background: linear-gradient(135deg, #f59e0b, #f97316);
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

        .buscador-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }

        .buscador-form {
            display: flex;
            gap: 10px;
        }

        .buscador-form input {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .buscador-form button {
            padding: 12px 30px;
            background: #f59e0b;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }

        .buscador-form button:hover {
            background: #f97316;
        }

        .resultados-info {
            background: #eff6ff;
            border: 1px solid #3b82f6;
            color: #1e40af;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .preguntas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .categoria-seccion {
            margin-bottom: 30px;
        }

        .categoria-titulo {
            font-size: 20px;
            font-weight: bold;
            color: #1f2937;
            padding-bottom: 12px;
            border-bottom: 3px solid #f59e0b;
            margin-bottom: 15px;
        }

        .pregunta-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .pregunta-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .pregunta-titulo {
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .pregunta-preview {
            font-size: 12px;
            color: #6b7280;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .respuesta-expandida {
            margin-top: 15px;
            padding: 15px;
            background: #fffbeb;
            border: 1px solid #f59e0b;
            border-radius: 6px;
            color: #92400e;
            line-height: 1.6;
            display: none;
        }

        .respuesta-expandida.activa {
            display: block;
        }

        .sin-resultados {
            background: white;
            padding: 40px;
            border-radius: 8px;
            text-align: center;
            color: #999;
        }

        .sin-resultados h3 {
            color: #333;
            margin-bottom: 10px;
        }

        .preguntas-populares {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .preguntas-populares h3 {
            color: #1f2937;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .pregunta-popular {
            padding: 10px;
            background: #f9fafb;
            border-radius: 4px;
            margin-bottom: 8px;
            cursor: pointer;
            color: #3b82f6;
            text-decoration: none;
            display: block;
            transition: all 0.3s;
        }

        .pregunta-popular:hover {
            background: #e0e7ff;
        }

        .ayuda-contacto {
            background: linear-gradient(135deg, #f59e0b, #f97316);
            color: white;
            padding: 25px;
            border-radius: 8px;
            text-align: center;
        }

        .ayuda-contacto h3 {
            margin-bottom: 10px;
        }

        .ayuda-contacto p {
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .preguntas-grid {
                grid-template-columns: 1fr;
            }

            .buscador-form {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Centro de Ayuda</h1>
            <p>Preguntas frecuentes y asistencia del sistema</p>
        </div>
    </header>

    <main class="container">
        <a href="../index.php" class="volver-btn">← Volver al Inicio</a>

        <!-- BUSCADOR -->
        <div class="buscador-section">
            <h2 style="margin-bottom: 15px; color: #1f2937;">¿Qué necesitas saber?</h2>
            <form method="POST" class="buscador-form">
                <input type="text" name="pregunta" placeholder="Escribe tu pregunta aquí..." 
                       value="<?php echo htmlspecialchars($termino_busqueda); ?>">
                <button type="submit">🔍 Buscar</button>
            </form>
        </div>

        <?php if (!empty($termino_busqueda)): ?>
            <!-- RESULTADOS DE BÚSQUEDA -->
            <?php if (!empty($resultados)): ?>
                <div class="resultados-info">
                    Se encontraron <?php echo count($resultados); ?> resultado(s) para: "<strong><?php echo htmlspecialchars($termino_busqueda); ?></strong>"
                </div>

                <div class="preguntas-grid">
                    <?php foreach ($resultados as $item): ?>
                    <div class="pregunta-card" onclick="toggleRespuesta(this)">
                        <div class="pregunta-titulo">❓ <?php echo htmlspecialchars($item['pregunta']); ?></div>
                        <div class="pregunta-preview"><?php echo htmlspecialchars(substr($item['respuesta'], 0, 60)) . '...'; ?></div>
                        <div class="respuesta-expandida">
                            <strong>Respuesta:</strong><br><br>
                            <?php echo nl2br(htmlspecialchars($item['respuesta'])); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="sin-resultados">
                    <h3>No se encontraron resultados</h3>
                    <p>No hay preguntas que coincidan con tu búsqueda. Intenta con otras palabras clave o explora todas las categorías abajo.</p>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- PREGUNTAS POPULARES -->
            <div class="preguntas-populares">
                <h3>🌟 Preguntas Más Frecuentes</h3>
                <?php 
                $populares = [
                    'conducta_4',
                    'conducta_3', 
                    'reuniones_2',
                    'convivencia_1',
                    'asistencias_1'
                ];
                foreach ($populares as $id) {
                    foreach ($preguntas_frecuentes as $item) {
                        if ($item['id'] === $id) {
                            echo '<a href="#" onclick="buscarPregunta(\'' . addslashes($item['pregunta']) . '\'); return false;" class="pregunta-popular">' . htmlspecialchars($item['pregunta']) . '</a>';
                            break;
                        }
                    }
                }
                ?>
            </div>
        <?php endif; ?>

        <!-- TODAS LAS CATEGORÍAS -->
        <?php if (empty($resultados) || empty($termino_busqueda)): ?>
        <div style="margin-top: 40px;">
            <h2 style="color: #1f2937; margin-bottom: 25px;">📚 Explora todas las categorías</h2>

            <?php foreach ($por_categoria as $categoria => $preguntas): ?>
            <div class="categoria-seccion">
                <div class="categoria-titulo"><?php echo htmlspecialchars($categoria); ?></div>
                <div class="preguntas-grid">
                    <?php foreach ($preguntas as $item): ?>
                    <div class="pregunta-card" onclick="toggleRespuesta(this)">
                        <div class="pregunta-titulo">❓ <?php echo htmlspecialchars($item['pregunta']); ?></div>
                        <div class="pregunta-preview"><?php echo htmlspecialchars(substr($item['respuesta'], 0, 60)) . '...'; ?></div>
                        <div class="respuesta-expandida">
                            <strong>Respuesta:</strong><br><br>
                            <?php echo nl2br(htmlspecialchars($item['respuesta'])); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- CONTACTO -->
        <div class="ayuda-contacto" style="margin-top: 40px;">
            <h3>¿Aún necesitas ayuda?</h3>
            <p>Si no encontraste la respuesta que buscas, contacta directamente con la administración de la escuela.</p>
            <p style="font-size: 12px; opacity: 0.9;">Disponible de lunes a viernes, 8:00 AM - 5:00 PM</p>
        </div>
    </main>

    <script>
        function toggleRespuesta(element) {
            const respuesta = element.querySelector('.respuesta-expandida');
            const estaAbierta = respuesta.classList.contains('activa');
            
            // Cerrar todas las respuestas
            document.querySelectorAll('.respuesta-expandida').forEach(r => {
                r.classList.remove('activa');
            });
            
            // Abrir la seleccionada si estaba cerrada
            if (!estaAbierta) {
                respuesta.classList.add('activa');
            }
        }

        function buscarPregunta(pregunta) {
            document.querySelector('input[name="pregunta"]').value = pregunta;
            document.querySelector('form').submit();
        }
    </script>

    <footer style="text-align: center; padding: 20px; color: #999; margin-top: 40px;">
        <p>&copy; <?php echo date('Y'); ?> <?php echo $nombre_institucion; ?> - Todos los derechos reservados.</p>
    </footer>
</body>
</html>
