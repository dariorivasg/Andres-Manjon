<?php
// admin-prestamos.php
session_start();

// 1. Verificación de seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] != 3) {
    header("Location: index.php");
    exit();
}

// 2. Conexión a la base de datos
try {
    $db = new PDO('sqlite:biblioteca.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// 3. Mapeo de subtítulos para las categorías (Ayuda a la búsqueda)
$subtitulos = [
    'Rojo' => 'Valores', 'Rosa' => 'Emociones', 'Morado' => 'Igualdad',
    'Amarillo' => 'Inglés', 'Marron' => 'Colecciones', 'Blanco' => 'Cómics',
    'Negro' => 'Música', 'Verde' => 'Infantil y 1.º ciclo',
    'Naranja' => '2.º y 3.º ciclo', 'Azul' => 'Naturaleza'
];

// 4. Capturar el término de búsqueda
$busqueda = isset($_GET['busqueda_prestamo']) ? trim($_GET['busqueda_prestamo']) : '';

// 5. Función para obtener préstamos filtrados
function obtenerPrestamos($db, $estado = 'Activo', $filtro = '', $subtitulos = []) {
    $orden = ($estado == 'Activo') ? 'ASC' : 'DESC';
    
    $query = "SELECT 
                p.id_prestamo, 
                p.fecha_de_salida, 
                p.fecha_de_devolucion, 
                p.estado_del_prestamo,
                l.titulo, 
                l.autor, 
                l.isbn,
                l.ubicacion_por_colores,
                COALESCE(a.nombre, u.nombre) AS persona_nombre,
                COALESCE(a.apellidos, '') AS persona_apellidos,
                COALESCE(a.codigo_de_carnet, u.codigo_de_carnet) AS carnet,
                CASE WHEN a.id_alumnado IS NULL THEN 'Profesor' ELSE 'Alumno' END as tipo_usuario
              FROM Prestamo p
              JOIN Libro l ON p.id_libro = l.id_libro
              LEFT JOIN Alumnado a ON p.id_alumnado = a.id_alumnado
              LEFT JOIN Usuario u ON p.id_usuario = u.id_usuario
              WHERE p.estado_del_prestamo = :estado";

    if ($filtro !== '') {
        // Lógica extra: Si buscan "Emociones", buscamos también por el color "Rosa"
        $filtroColorExtra = "";
        foreach ($subtitulos as $color => $sub) {
            if (stripos($sub, $filtro) !== false) {
                $filtroColorExtra = " OR l.ubicacion_por_colores LIKE '%$color%'";
            }
        }

        $query .= " AND (l.titulo LIKE :filtro 
                     OR l.autor LIKE :filtro 
                     OR l.ubicacion_por_colores LIKE :filtro
                     OR persona_nombre LIKE :filtro 
                     OR carnet LIKE :filtro 
                     $filtroColorExtra)";
    }

    $query .= " ORDER BY p.fecha_de_devolucion $orden";
    
    $stmt = $db->prepare($query);
    $params = ['estado' => $estado];
    if ($filtro !== '') {
        $params['filtro'] = '%' . $filtro . '%';
    }
    
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 6. Obtener datos
$prestamos_activos = obtenerPrestamos($db, 'Activo', $busqueda, $subtitulos);
$historial_prestamos = obtenerPrestamos($db, 'Devuelto', $busqueda, $subtitulos);
$hoy = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Préstamos</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <header class="cabecera-principal">
        <section class="usuario-identificado">
            <span class="icono-ajustes">⚙️</span>
            <div>
                <h1>Panel de Administrador</h1>
                <p><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Invitado'); ?></p>
            </div>
        </section>

        <nav class="navegacion-principal">
            <ul>
                <li><a href="admin.php" class="enlace-nav">Catálogo</a></li>
                <li><a href="admin-usuarios.php" class="enlace-nav">Usuarios</a></li>
                <li><a href="admin-prestamos.php" class="enlace-nav activo">Préstamos</a></li>
                <li><a href="admin-estadisticas.php" class="enlace-nav">Estadísticas</a></li>
            </ul>
        </nav>

        <form action="salir.php" method="POST">
            <button type="submit" class="boton-salir">Salir</button>
        </form>
    </header>

    <main class="contenedor-prestamos">
        <section class="barra-herramientas">
            <form action="admin-prestamos.php" method="GET" class="buscador" style="display: flex; align-items: center; gap: 0; flex-grow: 1;">
                <input type="search" name="busqueda_prestamo" placeholder="Buscar por título, autor, color, categoría o carnet..." value="<?php echo htmlspecialchars($busqueda); ?>">
                <button type="submit" class="boton-primario">Buscar</button>
                <?php if ($busqueda !== ''): ?>
                    <a href="admin-prestamos.php" class="boton-salir" style="text-decoration: none; font-size: 0.8em; display: inline-block; padding: 10px;">Limpiar</a>
                <?php endif; ?>
            </form>
        </section>

        
        <?php if ($busqueda !== '' && empty($prestamos_activos) && empty($historial_prestamos)): ?>
            <div style="text-align: center; padding: 50px;">
                <p>No se encontraron préstamos para "<strong><?php echo htmlspecialchars($busqueda); ?></strong>".</p>
                <a href="admin-prestamos.php" class="boton-primario" style="text-decoration:none;">Ver todos los préstamos</a>
            </div>
        <?php else: ?>

            <section class="seccion-activos">
                <div class="titulo-seccion">
                    <h2>Préstamos Activos</h2>
                    <span class="contador-prestamos"><?php echo count($prestamos_activos); ?> encontrados</span>
                </div>

                <div class="grid-prestamos">
                    <?php foreach ($prestamos_activos as $p): 
                        $es_retrasado = ($p['fecha_de_devolucion'] < $hoy);
                        $img_src = "Imagenes/Portadas/{$p['ubicacion_por_colores']}/{$p['isbn']}.jpg";
                    ?>
                        <article class="tarjeta-prestamo <?php echo $es_retrasado ? 'retrasado' : ''; ?>">
                            <div class="contenido-principal">
                                <img src="<?php echo $img_src; ?>" onerror="this.src='Imagenes/Portadas/default.jpg'" class="portada-prestamo" alt="Portada">
                                <div class="detalles-prestamo">
                                    <h3><?php echo htmlspecialchars($p['titulo']); ?></h3>
                                    <div class="indicador-color-foto">
                                        <span class="punto <?php echo strtolower($p['ubicacion_por_colores']); ?>"></span>
                                        <strong><?php echo $p['ubicacion_por_colores']; ?></strong>
                                        <span class="texto-categoria" style="font-size: 0.8em; color: #666; margin-left: 5px;">
                                            (<?php echo $subtitulos[$p['ubicacion_por_colores']] ?? ''; ?>)
                                        </span>
                                    </div>
                                    <p><strong><?php echo $p['tipo_usuario']; ?>:</strong> <?php echo htmlspecialchars($p['persona_nombre'] . " " . $p['persona_apellidos']); ?></p>
                                    <p><strong>Carnet:</strong> <?php echo htmlspecialchars($p['carnet']); ?></p>
                                    <p><strong>Devolución:</strong> 
                                        <span style="<?php echo $es_retrasado ? 'color:#ef4444; font-weight:bold;' : ''; ?>">
                                            <?php echo $p['fecha_de_devolucion']; ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="acciones-prestamo">
                                <button class="boton-devolver" onclick="confirmarDevolucion(<?php echo $p['id_prestamo']; ?>)">Devolver</button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="seccion-historial" style="margin-top: 40px;">
                <div class="titulo-seccion">
                    <h2>Historial de devoluciones</h2>
                </div>
                <table class="tabla-historial">
                    <thead>
                        <tr>
                            <th>Libro</th>
                            <th>Usuario</th>
                            <th>F. Salida</th>
                            <th>F. Devolución</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($historial_prestamos)): ?>
                            <tr><td colspan="5" style="text-align:center; padding: 20px;">No hay registros históricos para esta búsqueda.</td></tr>
                        <?php else: ?>
                            <?php foreach ($historial_prestamos as $h): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($h['titulo']); ?></strong></td>
                                    <td>
                                        <small style="display:block; color:#64748b; font-weight:bold;"><?php echo strtoupper($h['tipo_usuario']); ?></small>
                                        <?php echo htmlspecialchars($h['persona_nombre'] . " " . $h['persona_apellidos']); ?>
                                    </td>
                                    <td><?php echo $h['fecha_de_salida']; ?></td>
                                    <td><?php echo $h['fecha_de_devolucion']; ?></td>
                                    <td><span class="badge-devuelto">Devuelto</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        <?php endif; ?>
    </main>

    <script>
    function confirmarDevolucion(id) {
        if(confirm('¿Confirmar la devolución de este ejemplar?')) {
            window.location.href = 'procesar-devolucion.php?id=' + id;
        }
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