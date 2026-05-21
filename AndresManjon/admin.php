<?php
// admin.php
session_start();
$db_file = 'biblioteca.db';

$es_invitado = (isset($_GET['modo']) && $_GET['modo'] === 'invitado');
$esta_logueado = isset($_SESSION['usuario_id']);

if (!$es_invitado && !$esta_logueado) {
    header("Location: index.php");
    exit();
}

try {
    $db = new PDO("sqlite:" . __DIR__ . "/" . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- FUNCIÓN DE LIMPIEZA ---
    function limpiarTexto($texto) {
        if ($texto === null) return '';
        $busqueda   = array('á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ');
        $reemplazo  = array('a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u', 'n', 'n');
        $texto = str_replace($busqueda, $reemplazo, $texto);
        return strtolower($texto);
    }

    // --- MAPEO DE CATEGORÍAS PARA BÚSQUEDA ---
    $categorias_map = [
        'Rojo' => 'Valores', 'Rosa' => 'Emociones', 'Morado' => 'Igualdad',
        'Amarillo' => 'Inglés', 'Marron' => 'Colecciones', 'Blanco' => 'Cómics',
        'Negro' => 'Música', 'Verde' => 'Infantil y 1.º ciclo',
        'Naranja' => '2.º y 3.º ciclo', 'Azul' => 'Naturaleza'
    ];

    // --- LÓGICA DE BÚSQUEDA ---
    $busqueda_ingresada = isset($_GET['busqueda_libro']) ? trim($_GET['busqueda_libro']) : '';
    $busqueda_limpia = limpiarTexto($busqueda_ingresada);

    // 3. Traer todos los libros con su información de préstamo
    // 3. Traer todos los libros con su información de préstamo activo
    $sql_base = "SELECT L.*, 
             P.fecha_de_salida,
             P.id_prestamo AS hay_prestamo_activo, -- ESTA LÍNEA FALTA EN TU ADMIN.PHP
             CASE 
                WHEN A.nombre IS NOT NULL THEN A.nombre || ' ' || A.apellidos
                WHEN U.nombre IS NOT NULL THEN U.nombre
                ELSE NULL 
             END AS alumno_nombre,
             COALESCE(A.codigo_de_carnet, U.codigo_de_carnet) AS alumno_carnet 
             FROM Libro L
             LEFT JOIN Prestamo P ON L.id_libro = P.id_libro AND P.estado_del_prestamo = 'Activo'
             LEFT JOIN Alumnado A ON P.id_alumnado = A.id_alumnado
             LEFT JOIN Usuario U ON P.id_usuario = U.id_usuario AND P.id_alumnado IS NULL
             ORDER BY
                    CASE
                        WHEN L.ubicacion_por_colores LIKE 'Verde%' THEN 1
                        WHEN L.ubicacion_por_colores LIKE 'Naranja%' THEN 2
                        ELSE 3
                    END ASC,
                    L.ubicacion_por_colores ASC,
                    L.titulo ASC";
    
    $stmt = $db->query($sql_base);
    $todos_los_libros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- FILTRADO MANUAL MULTI-ATRIBUTO ---
    $libros_filtrados = [];
    if ($busqueda_limpia !== '') {
        foreach ($todos_los_libros as $l) {
            $color = $l['ubicacion_por_colores'];
            $nombre_cat = isset($categorias_map[$color]) ? $categorias_map[$color] : '';
            
            // Array de campos donde queremos buscar
            $campos_busqueda = [
                $l['titulo'],
                $l['autor'],
                $l['isbn'],
                $l['codigo_de_barra'],
                $color,
                $nombre_cat,
                $l['alumno_nombre'],
                $l['estado_de_actividad']
            ];

            $coincide = false;
            foreach ($campos_busqueda as $campo) {
                if (strpos(limpiarTexto($campo), $busqueda_limpia) !== false) {
                    $coincide = true;
                    break;
                }
            }

            if ($coincide) {
                $libros_filtrados[] = $l;
            }
        }
    } else {
        $libros_filtrados = $todos_los_libros;
    }

    // --- PÁGINACIÓN ---
    $libros_por_pagina = 10;
    $total_libros = count($libros_filtrados);
    $total_paginas = ceil($total_libros / $libros_por_pagina);
    $pagina_actual = isset($_GET['pag']) ? (int)$_GET['pag'] : 1;
    if ($pagina_actual < 1) $pagina_actual = 1;
    if ($pagina_actual > $total_paginas && $total_paginas > 0) $pagina_actual = $total_paginas;

    $offset = ($pagina_actual - 1) * $libros_por_pagina;
    $libros = array_slice($libros_filtrados, $offset, $libros_por_pagina);

} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador - Sistema de Biblioteca</title>
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
                <li><a href="admin.php" class="enlace-nav activo">Catálogo</a></li>
                <li><a href="admin-usuarios.php" class="enlace-nav">Usuarios</a></li>
                <li><a href="admin-prestamos.php" class="enlace-nav">Préstamos</a></li>
                <li><a href="admin-estadisticas.php" class="enlace-nav">Estadísticas</a></li>
            </ul>
        </nav>

        <form action="salir.php" method="POST">
            <button type="submit" class="boton-salir">Salir</button>
        </form>
    </header>

    <main class="contenedor-principal">
        <section class="barra-herramientas">
            <form action="admin.php" method="GET" class="buscador" style="display: flex; align-items: center; gap: 0; flex-grow: 1;">
                <input type="search" name="busqueda_libro" placeholder="Buscar por título, autor, ISBN..." value="<?php echo htmlspecialchars($busqueda_ingresada); ?>">
                <button type="submit" class="boton-primario">Buscar</button>
                <?php if ($busqueda_ingresada !== ''): ?>
                    <a href="admin.php" class="boton-salir" style="text-decoration: none; font-size: 0.8em; display: inline-block; padding: 10px;">Limpiar</a>
                <?php endif; ?>
            </form>
            <a href="admin-nuevo-libro.php" class="boton-primario-nuevo" style="text-decoration:none;">+ Nuevo Libro</a>
        </section>

        <?php 
        $ultima_categoria = ""; 

        if ($total_libros > 0):
            foreach ($libros as $libro):
                $color_db = $libro['ubicacion_por_colores'];
                $color_class = strtolower($color_db);
                $nombre_largo = isset($categorias_map[$color_db]) ? $categorias_map[$color_db] : $color_db;

                if ($ultima_categoria != $color_db):
                    $ultima_categoria = $color_db;
                    echo "<h2 class='separador-categoria border-$color_class'>{$color_db} - {$nombre_largo}</h2>";
                endif;

                $isbn = $libro['isbn'];
                $img_src = "Imagenes/Portadas/{$color_db}/{$isbn}.jpg";
                if (!file_exists(__DIR__ . "/" . $img_src)) { $img_src = "Imagenes/Portadas/default.jpg"; }
        ?>
            <article class="ficha-libro">
                <div class="cuerpo-superior">
                    <figure class="portada"><img src="<?php echo $img_src; ?>" alt="Portada"></figure>
                    <div class="info-principal">
                        <h2><?php echo htmlspecialchars($libro['titulo']); ?></h2>
                        <p class="autor"><?php echo htmlspecialchars($libro['autor']); ?></p>
                        <p class="etiqueta-color">
                            <span class="punto <?php echo strtolower($color_db); ?>"></span> 
                            <strong><?php echo $color_db; ?></strong> 
                            <span class="texto-categoria">(<?php echo $nombre_largo; ?>)</span>
                        </p>
                        <div class="codigos-libro">
                            <p><strong>ISBN:</strong> <?php echo $libro['isbn']; ?></p>
                            <p><strong>Cód. Barras:</strong> <?php echo $libro['codigo_de_barra']; ?></p>
                        </div>
                        
                        <?php if (!$es_invitado): ?>
                            <div style="margin-top: 12px;">
                                <?php 
                                    // Determinamos el estado real
                                    $realmente_disponible = empty($libro['hay_prestamo_activo']); 
                                    
                                    if ($realmente_disponible): ?>
                                        <a href="profesor-prestamo.php?id=<?php echo $libro['id_libro']; ?>" class="btn-solicitar-prestamo">
                                            Pedir este libro
                                        </a>
                                    <?php else: ?>
                                        <button class="btn-solicitar-prestamo" style="background:#cbd5e1; cursor:not-allowed;" disabled>
                                            No disponible
                                        </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>  

                    </div>
                    
                    <div class="botones-accion">
                        <a href="admin-editar-libro.php?id=<?php echo $libro['id_libro']; ?>" class="btn-icon">✏️</a>
                        <a href="eliminar-libro.php?id=<?php echo $libro['id_libro']; ?>" class="btn-icon borrar" onclick="return confirm('¿Borrar libro?')">🗑️</a>
                    </div>
                </div>
                <hr class="separador">
                <div class="seccion-ejemplares">
                    <span class="titulo-ejemplares">Ejemplares en la biblioteca:</span>
                    <div class="grid-ejemplares">
                        <div class="cuadro-ejemplar">
                            <span class="id-ejemplar">EJ<?php echo str_pad($libro['id_libro'], 3, '0', STR_PAD_LEFT); ?></span>
                            <span class="ubicacion-ejemplar"><?php echo strtoupper(substr($color_db, 0, 1)) . " / " . $nombre_largo; ?></span>
                            
                            <?php 
                                // Lógica mejorada: Si hay un nombre, es que está prestado, mande lo que mande la columna estado
                                $esta_prestado = !empty($libro['alumno_nombre']);
                                $texto_estado = $esta_prestado ? 'En préstamo' : 'Disponible';
                                $clase_estado = $esta_prestado ? 'estado-prestado' : 'estado-disponible';
                            ?>
                            <span class="<?php echo $clase_estado; ?>">
                                <?php 
                                    echo $texto_estado; 
                                    if ($esta_prestado) {
                                        echo " a: " . htmlspecialchars($libro['alumno_nombre']) . " (" . $libro['alumno_carnet'] . ")";
                                    }
                                ?>
                            </span>
                        </div>
                        <!-- <button class="btn-anadir-ejemplar" onclick="location.href='nuevo_ejemplar.php?id=<?//php echo $libro['id_libro']; ?>'">+ Añadir Ejemplar</button> -->
                    </div>
                </div>
            </article>
            <?php endforeach; ?>

            <nav class="paginacion">
                <?php 
                $query_busqueda = ($busqueda_ingresada !== '') ? "&busqueda_libro=" . urlencode($busqueda_ingresada) : "";
                
                if ($pagina_actual > 1): ?>
                    <a href="?pag=<?php echo $pagina_actual - 1 . $query_busqueda; ?>" class="btn-pag">&laquo; Anterior</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="?pag=<?php echo $i . $query_busqueda; ?>" class="btn-pag <?php echo ($i == $pagina_actual) ? 'activo' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($pagina_actual < $total_paginas): ?>
                    <a href="?pag=<?php echo $pagina_actual + 1 . $query_busqueda; ?>" class="btn-pag">Siguiente &raquo;</a>
                <?php endif; ?>
            </nav>

        <?php else: ?>
            <div style="text-align: center; padding: 50px;">
                <p>No se encontraron resultados para "<strong><?php echo htmlspecialchars($busqueda_ingresada); ?></strong>".</p>
                <a href="admin.php" class="boton-primario" style="text-decoration:none;">Ver todo el inventario</a>
            </div>
        <?php endif; ?>
    </main>

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