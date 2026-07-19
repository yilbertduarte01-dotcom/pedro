<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planilla de Inscripción - Escuela Técnica Pedro Garcia Leal</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #fffbf0;
            padding: 10px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .button-group button {
            padding: 10px 20px;
            background: #6b4423;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }

        .button-group button:hover {
            background: #4a2f1a;
        }

        #contenidoPDF {
            background: white;
            padding: 25px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .encabezado {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #6b4423;
            padding-bottom: 10px;
        }

        .encabezado h1 {
            color: #6b4423;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .encabezado p {
            font-size: 11px;
            color: #666;
            margin: 1px 0;
        }

        .seccion-titulo {
            background: #f59e0b;
            color: white;
            padding: 7px 10px;
            margin-top: 10px;
            margin-bottom: 8px;
            font-weight: bold;
            font-size: 11px;
            border-radius: 2px;
        }

        .fila {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 8px;
        }

        .fila.completa {
            grid-template-columns: 1fr;
        }

        .fila.tres {
            grid-template-columns: 1fr 1fr 1fr;
        }

        .grupo-campo {
            display: flex;
            flex-direction: column;
        }

        label {
            font-weight: bold;
            font-size: 10px;
            color: #6b4423;
            margin-bottom: 2px;
        }

        input, select, textarea {
            padding: 4px 5px;
            border: 1px solid #ccc;
            border-radius: 2px;
            font-size: 10px;
            font-family: Arial, sans-serif;
        }

        textarea {
            resize: vertical;
            min-height: 35px;
        }

        .firmas {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin-top: 15px;
            text-align: center;
        }

        .firma-linea {
            border-top: 1px solid #999;
            padding-top: 8px;
            font-size: 9px;
            min-height: 50px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }

        .pie-pagina {
            text-align: center;
            margin-top: 15px;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .button-group {
                display: none;
            }

            #contenidoPDF {
                box-shadow: none;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="button-group">
            <button onclick="descargarPDF()">Descargar PDF</button>
            <button onclick="window.print()">Imprimir</button>
        </div>

        <div id="contenidoPDF">
            <!-- ENCABEZADO -->
            <div class="encabezado">
                <h1>ESCUELA TÉCNICA PEDRO GARCIA LEAL</h1>
                <p>Planilla de Inscripción - Año Escolar 2024-2025</p>
                <p>Documento editable y descargable en PDF</p>
            </div>

            <!-- DATOS DEL ESTUDIANTE -->
            <div class="seccion-titulo">DATOS DEL ESTUDIANTE</div>
            <div class="fila">
                <div class="grupo-campo">
                    <label>Nombres:</label>
                    <input type="text" placeholder="Nombre completo">
                </div>
                <div class="grupo-campo">
                    <label>Apellidos:</label>
                    <input type="text" placeholder="Apellidos">
                </div>
            </div>

            <div class="fila tres">
                <div class="grupo-campo">
                    <label>Cédula:</label>
                    <input type="text" placeholder="V-000.000.000">
                </div>
                <div class="grupo-campo">
                    <label>Fecha de Nacimiento:</label>
                    <input type="date">
                </div>
                <div class="grupo-campo">
                    <label>Género:</label>
                    <select>
                        <option value="">-- Selecciona --</option>
                        <option value="M">Masculino</option>
                        <option value="F">Femenino</option>
                    </select>
                </div>
            </div>

            <div class="fila">
                <div class="grupo-campo">
                    <label>Lugar de Nacimiento:</label>
                    <input type="text" placeholder="Ciudad, Estado">
                </div>
                <div class="grupo-campo">
                    <label>Nacionalidad:</label>
                    <input type="text" placeholder="Ej: Venezolana">
                </div>
            </div>

            <!-- DATOS DE CONTACTO -->
            <div class="seccion-titulo">DATOS DE CONTACTO</div>
            <div class="fila">
                <div class="grupo-campo">
                    <label>Teléfono Móvil:</label>
                    <input type="text" placeholder="+58 414 000-0000">
                </div>
                <div class="grupo-campo">
                    <label>Teléfono Fijo:</label>
                    <input type="text" placeholder="+58 212 000-0000">
                </div>
            </div>

            <div class="fila completa">
                <div class="grupo-campo">
                    <label>Correo Electrónico:</label>
                    <input type="email" placeholder="correo@ejemplo.com">
                </div>
            </div>

            <div class="fila completa">
                <div class="grupo-campo">
                    <label>Dirección:</label>
                    <input type="text" placeholder="Calle, número, sector, ciudad">
                </div>
            </div>

            <!-- INFORMACIÓN ACADÉMICA -->
            <div class="seccion-titulo">INFORMACIÓN ACADÉMICA</div>
            <div class="fila">
                <div class="grupo-campo">
                    <label>Año a Cursar:</label>
                    <select>
                        <option value="">-- Selecciona --</option>
                        <option value="1">1er Año</option>
                        <option value="2">2do Año</option>
                        <option value="3">3er Año</option>
                        <option value="4">4to Año</option>
                        <option value="5">5to Año</option>
                    </select>
                </div>
                <div class="grupo-campo">
                    <label>Especialidad:</label>
                    <input type="text" placeholder="Ej: Informática">
                </div>
            </div>

            <div class="fila">
                <div class="grupo-campo">
                    <label>Institución Anterior:</label>
                    <input type="text" placeholder="Nombre de la institución">
                </div>
                <div class="grupo-campo">
                    <label>Promedio Anterior:</label>
                    <input type="number" placeholder="Ej: 18.5" step="0.1" min="0" max="20">
                </div>
            </div>

            <!-- DATOS DEL REPRESENTANTE -->
            <div class="seccion-titulo">DATOS DEL REPRESENTANTE</div>
            <div class="fila">
                <div class="grupo-campo">
                    <label>Nombres y Apellidos:</label>
                    <input type="text" placeholder="Nombre completo">
                </div>
                <div class="grupo-campo">
                    <label>Cédula:</label>
                    <input type="text" placeholder="V-000.000.000">
                </div>
            </div>

            <div class="fila tres">
                <div class="grupo-campo">
                    <label>Parentesco:</label>
                    <select>
                        <option value="">-- Selecciona --</option>
                        <option value="padre">Padre</option>
                        <option value="madre">Madre</option>
                        <option value="abuelo">Abuelo/a</option>
                        <option value="tutor">Tutor Legal</option>
                    </select>
                </div>
                <div class="grupo-campo">
                    <label>Teléfono:</label>
                    <input type="text" placeholder="+58 414 000-0000">
                </div>
                <div class="grupo-campo">
                    <label>Ocupación:</label>
                    <input type="text" placeholder="Profesión u oficio">
                </div>
            </div>

            <div class="fila completa">
                <div class="grupo-campo">
                    <label>Dirección del Representante:</label>
                    <input type="text" placeholder="Calle, número, sector, ciudad">
                </div>
            </div>

            <!-- CONDICIONES DE SALUD -->
            <div class="seccion-titulo">CONDICIONES DE SALUD</div>
            <div class="fila">
                <div class="grupo-campo">
                    <label>Alergias:</label>
                    <input type="text" placeholder="Especificar si las hay">
                </div>
                <div class="grupo-campo">
                    <label>Medicamentos Actuales:</label>
                    <input type="text" placeholder="Especificar medicamentos">
                </div>
            </div>

            <div class="fila completa">
                <div class="grupo-campo">
                    <label>Condiciones Médicas Especiales:</label>
                    <textarea placeholder="Asma, diabetes, problemas cardíacos, etc."></textarea>
                </div>
            </div>

            <!-- AUTORIZACIONES -->
            <div class="seccion-titulo">AUTORIZACIONES</div>
            <div class="fila completa">
                <div class="grupo-campo">
                    <label>Autorizo actividades extracurriculares:</label>
                    <select>
                        <option value="">-- Selecciona --</option>
                        <option value="si">Sí</option>
                        <option value="no">No</option>
                    </select>
                </div>
            </div>

            <div class="fila completa">
                <div class="grupo-campo">
                    <label>Autorizo excursiones y salidas pedagógicas:</label>
                    <select>
                        <option value="">-- Selecciona --</option>
                        <option value="si">Sí</option>
                        <option value="no">No</option>
                    </select>
                </div>
            </div>

            <div class="fila completa">
                <div class="grupo-campo">
                    <label>Observaciones Adicionales:</label>
                    <textarea placeholder="Espacio para observaciones"></textarea>
                </div>
            </div>

            <!-- FIRMAS -->
            <div class="firmas">
                <div class="firma-linea">Firma del Representante</div>
                <div class="firma-linea">Firma del Estudiante</div>
                <div class="firma-linea">Sello Institucional</div>
            </div>

            <!-- PIE DE PÁGINA -->
            <div class="pie-pagina">
                <p>Escuela Técnica Pedro Garcia Leal - Año Escolar 2024-2025</p>
                <p>Fecha: ___/___/_____</p>
            </div>
        </div>
    </div>

    <script>
        function descargarPDF() {
            const elemento = document.getElementById('contenidoPDF');
            const opciones = {
                margin: [6, 6, 6, 6],
                filename: 'Planilla_Inscripcion_' + new Date().toISOString().split('T')[0] + '.pdf',
                image: { type: 'png', quality: 0.98 },
                html2canvas: { 
                    scale: 2,
                    logging: false,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff'
                },
                jsPDF: { 
                    orientation: 'portrait', 
                    unit: 'mm', 
                    format: 'a4',
                    compress: true
                }
            };

            html2pdf().set(opciones).from(elemento).save();
        }
    </script>
</body>
</html>
