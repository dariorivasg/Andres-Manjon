<?php
// profesor-prestamo.php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$db_file = 'biblioteca.db';
$id_libro = $_GET['id'] ?? null;
$es_estudiante = ($_SESSION['usuario_rol'] == 1);
$mensaje_error = "";

try {
    $db = new PDO("sqlite:" . __DIR__ . "/" . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $carnet_auto = "";
    $nombre_auto = "";

    // 1. CARGA DE DATOS SEGÚN ROL
    if ($es_estudiante) {
        $stmt_u = $db->prepare("SELECT codigo_de_carnet FROM Usuario WHERE id_usuario = ?");
        $stmt_u->execute([$_SESSION['usuario_id']]);
        $carnet_auto = $stmt_u->fetchColumn();

        $stmt_a = $db->prepare("SELECT nombre, apellidos FROM Alumnado WHERE codigo_de_carnet = ?");
        $stmt_a->execute([$carnet_auto]);
        $alum = $stmt_a->fetch(PDO::FETCH_ASSOC);
        $nombre_auto = $alum ? $alum['nombre'] . " " . $alum['apellidos'] : $_SESSION['usuario_nombre'];
    } else {
        $alumnos = $db->query("SELECT nombre || ' ' || apellidos AS completo, codigo_de_carnet FROM Alumnado ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
        $profesores = $db->query("SELECT nombre AS completo, codigo_de_carnet FROM Usuario WHERE id_rol IN (2, 3) ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. DATOS DEL LIBRO
    $stmt_l = $db->prepare("SELECT * FROM Libro WHERE id_libro = ?");
    $stmt_l->execute([$id_libro]);
    $libro = $stmt_l->fetch(PDO::FETCH_ASSOC);
    if (!$libro) die("Libro no encontrado.");

    // 3. PROCESAR PRÉSTAMO (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $codigo_carnet = trim($_POST['codigo_usuario']);
        
        // Buscamos el id_alumnado
        $stmt_f = $db->prepare("SELECT id_alumnado FROM Alumnado WHERE codigo_de_carnet = ?");
        $stmt_f->execute([$codigo_carnet]);
        $id_alumnado = $stmt_f->fetchColumn();

        // --- VALIDACIÓN DE LÍMITE DE PRÉSTAMOS (MÁXIMO 2) ---
        if ($id_alumnado) {
            $stmt_count = $db->prepare("SELECT COUNT(*) FROM Prestamo WHERE id_alumnado = ? AND estado_del_prestamo = 'Activo'");
            $stmt_count->execute([$id_alumnado]);
            $prestamos_activos = $stmt_count->fetchColumn();

            if ($prestamos_activos >= 2) {
                $mensaje_error = "Este estudiante ya tiene 2 libros en préstamo. Debe devolver uno antes de pedir otro.";
            }
        }

        // Si no hay errores de validación, procedemos
        if (empty($mensaje_error)) {
            $f_salida = date('Y-m-d');
            $f_devo = date('Y-m-d', strtotime('+15 days'));

            $sql = "INSERT INTO Prestamo (id_alumnado, id_libro, id_usuario, fecha_de_salida, fecha_de_devolucion, estado_del_prestamo) 
                    VALUES (?, ?, ?, ?, ?, 'Activo')";
            
            $db->prepare($sql)->execute([$id_alumnado ?: null, $id_libro, $_SESSION['usuario_id'], $f_salida, $f_devo]);
            $db->prepare("UPDATE Libro SET estado_de_actividad = 'En préstamo' WHERE id_libro = ?")->execute([$id_libro]);

            header("Location: " . ($es_estudiante ? "estudiante_mislibros.php" : "profesor.php"));
            exit();
        }
    }
} catch (PDOException $e) { 
    $mensaje_error = "Error: " . $e->getMessage(); 
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Confirmar Préstamo</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        .card-prestamo { max-width: 450px; margin: 40px auto; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); font-family: sans-serif; }
        .mini-ficha { display: flex; gap: 15px; background: #f8fafc; padding: 15px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #e2e8f0; }
        .mini-ficha img { width: 60px; height: 85px; object-fit: cover; border-radius: 4px; }
        .campo { margin-bottom: 15px; }
        label { display: block; font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 5px; }
        input, select { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; box-sizing: border-box; font-size: 1rem; }
        .readonly { background: #f1f5f9; color: #475569; }
        .boton-confirmar { width: 100%; padding: 15px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; }
        .hidden { display: none; }
        .alerta-error { background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; border: 1px solid #fecaca; text-align: center; }
    </style>
</head>
<body>
    <div class="card-prestamo">
        <h2 style="text-align:center;">Realizar Préstamo</h2>

        <?php if ($mensaje_error): ?>
            <div class="alerta-error">
                <strong>⚠️ Error:</strong><br><?php echo $mensaje_error; ?>
            </div>
        <?php endif; ?>

        <div class="mini-ficha">
            <img src="Imagenes/Portadas/<?php echo $libro['ubicacion_por_colores']; ?>/<?php echo $libro['isbn']; ?>.jpg" onerror="this.src='Imagenes/Portadas/default.jpg'">
            <div>
                <strong><?php echo htmlspecialchars($libro['titulo']); ?></strong>
                <p style="margin:0; font-size:0.85rem; color:#64748b;"><?php echo htmlspecialchars($libro['autor']); ?></p>
            </div>
        </div>

        <form method="POST">
            <?php if($es_estudiante): ?>
                <div class="campo">
                    <label>Estudiante:</label>
                    <input type="text" value="<?php echo htmlspecialchars($nombre_auto); ?>" class="readonly" readonly>
                    <input type="hidden" name="codigo_usuario" value="<?php echo $carnet_auto; ?>">
                </div>
            <?php else: ?>
                <div class="campo">
                    <label>¿A quién se le presta el libro?</label>
                    <select id="tipo_usuario" onchange="cambiarTipo()">
                        <option value="">-- Seleccionar --</option>
                        <option value="alumno">Estudiante</option>
                        <option value="profesor">Administración o Profesor/a</option>
                    </select>
                </div>

                <div class="campo hidden" id="contenedor_nombre">
                    <label id="label_nombre">Nombre:</label>
                    <select id="select_nombre" onchange="document.getElementById('input_carnet').value = this.value" required>
                        <option value="">-- Selecciona un nombre --</option>
                    </select>
                </div>

                <div class="campo">
                    <label>Código de Carnet:</label>
                    <input type="text" name="codigo_usuario" id="input_carnet" required placeholder="Carnet automático" readonly class="readonly">
                </div>
            <?php endif; ?>

            <div style="background:#f0fdf4; padding:10px; border-radius:8px; text-align:center; margin-bottom:20px; color:#166534; font-size:0.9rem;">
                Devolución: <strong><?php echo date('d/m/Y', strtotime('+15 days')); ?></strong>
            </div>

            <button type="submit" class="boton-confirmar">Confirmar Préstamo</button>
            <a href="javascript:history.back()" style="display:block; text-align:center; margin-top:15px; color:#64748b; text-decoration:none;">Cancelar</a>
        </form>
    </div>

    <script>
    // Guardamos los datos en objetos de JS para usarlos rápido
    const alumnos = <?php echo json_encode($alumnos ?? []); ?>;
    const profesores = <?php echo json_encode($profesores ?? []); ?>;

    function cambiarTipo() {
        const tipo = document.getElementById('tipo_usuario').value;
        const contenedor = document.getElementById('contenedor_nombre');
        const select = document.getElementById('select_nombre');
        const inputCarnet = document.getElementById('input_carnet');
        const label = document.getElementById('label_nombre');

        // Limpiar
        select.innerHTML = '<option value="">-- Selecciona un nombre --</option>';
        inputCarnet.value = '';

        if (tipo === "") {
            contenedor.classList.add('hidden');
            return;
        }

        contenedor.classList.remove('hidden');
        let lista = (tipo === 'alumno') ? alumnos : profesores;
        label.innerText = (tipo === 'alumno') ? 'Nombre del Alumno:' : 'Nombre del Profesor:';

        lista.forEach(item => {
            let option = document.createElement('option');
            option.value = item.codigo_de_carnet;
            option.textContent = item.completo;
            select.appendChild(option);
        });
    }
    </script>
    <footer class="footer-login">
        <p>&copy; 2026 Sistema de Biblioteca Escolar - C.E.I.P. Andrés Manjón</p>
        <div class="enlaces-footer">
            <a href="ayuda.php">Ayuda</a>
            <span class="separador-punto">•</span>
            <a href="privacidad.php">Privacidad</a>
            <span class="separador-punto">•</span>
            <a href="contacto.php">Contacto</a>
        </div>
    </footer>
</body>
</html>