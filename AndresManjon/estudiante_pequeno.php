<?php
// estudiante_pequeno.php
session_start();
$db_file = 'biblioteca.db';

// 1. DETERMINAR EL MODO DE ACCESO
$es_invitado = (isset($_GET['modo']) && $_GET['modo'] === 'invitado');
$esta_logueado = isset($_SESSION['usuario_id']);

// Si no es invitado ni está logueado, mandamos al login
if (!$es_invitado && !$esta_logueado) {
    header("Location: index.php");
    exit();
}

// Inicializamos variables para evitar errores de "variable no definida"
$clase_actual = $es_invitado ? "Invitado" : "Cargando...";
$total_prestados = 0;

try {
    $db = new PDO("sqlite:" . __DIR__ . "/" . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. OBTENER DATOS SI ESTÁ LOGUEADO
    if ($esta_logueado) {
        // Obtener el carnet del usuario
        $stmt_u = $db->prepare("SELECT codigo_de_carnet FROM Usuario WHERE id_usuario = ?");
        $stmt_u->execute([$_SESSION['usuario_id']]);
        $mi_carnet = $stmt_u->fetchColumn();

        if ($mi_carnet) {
            // Obtener clase e ID real del alumno
            $stmt_a = $db->prepare("SELECT id_alumnado, clase FROM Alumnado WHERE codigo_de_carnet = ?");
            $stmt_a->execute([$mi_carnet]);
            $alumno_data = $stmt_a->fetch(PDO::FETCH_ASSOC);
            
            if ($alumno_data) {
                $clase_actual = $alumno_data['clase'];
                $id_alumnado_real = $alumno_data['id_alumnado'];

                // CONTAR PRÉSTAMOS ACTIVOS (Para el badge de la cabecera)
                $stmt_count = $db->prepare("SELECT COUNT(*) FROM Prestamo WHERE id_alumnado = ? AND estado_del_prestamo = 'Activo'");
                $stmt_count->execute([$id_alumnado_real]);
                $total_prestados = $stmt_count->fetchColumn();
            }
        }
    }

    // 3. LÓGICA DE BÚSQUEDA
    // --- CONFIGURACIÓN Y LIMPIEZA ---
    if (!function_exists('limpiarTexto')) {
        function limpiarTexto($texto) {
            if ($texto === null) return '';
            $busqueda   = array('á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ');
            $reemplazo  = array('a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u', 'n', 'n');
            $texto = str_replace($busqueda, $reemplazo, $texto);
            return strtolower($texto);
        }
    }

    $categorias_map = [
        'Rojo' => 'Valores', 'Rosa' => 'Emociones', 'Morado' => 'Igualdad',
        'Amarillo' => 'Inglés', 'Marron' => 'Colecciones', 'Blanco' => 'Cómics',
        'Negro' => 'Música', 'Verde' => 'Infantil y 1.º ciclo',
        'Naranja' => '2.º y 3.º ciclo', 'Azul' => 'Naturaleza'
    ];

    $busqueda_ingresada = isset($_GET['busqueda_libro']) ? trim($_GET['busqueda_libro']) : '';
    $busqueda_limpia = limpiarTexto($busqueda_ingresada);

    // --- CONSULTA BASE ---
    // Traemos el libro y verificamos si tiene algún préstamo activo
    $sql_base = "SELECT L.*, 
                 P.id_prestamo AS prestamo_activo
                 FROM Libro L
                 LEFT JOIN Prestamo P ON L.id_libro = P.id_libro AND P.estado_del_prestamo = 'Activo'
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
            
            // Campos donde el sistema buscará el texto
            $campos_busqueda = [
                $l['titulo'],
                $l['autor'],
                $l['isbn'],
                $l['codigo_de_barra'],
                $color,
                $nombre_cat
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

    // Paginación
    $libros_por_pagina = 10;
    $total_libros = count($libros_filtrados);
    $total_paginas = ceil($total_libros / $libros_por_pagina);
    $pagina_actual = isset($_GET['pag']) ? (int)$_GET['pag'] : 1;
    $offset = ($pagina_actual - 1) * $libros_por_pagina;
    $libros = array_slice($libros_filtrados, $offset, $libros_por_pagina);

} catch (PDOException $e) {
    die("Error crítico: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo - Biblioteca Escolar</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>

    <header class="cabecera-principal">
        <section class="usuario-identificado">
            <span class="icono-ajustes">📖</span>
            <div>
                <h1>Catálogo de Libros</h1>
                <p style="margin: 0; color: #64748b;">
                    <?php if ($es_invitado): ?>
                        <strong>Modo Lectura </strong>(Invitado)</span>
                    <?php else: ?>
                        <p><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?> (<?php echo htmlspecialchars($clase_actual); ?>)</p>
                    <?php endif; ?>
                </p>
            </div>
        </section>
        <nav class="navegacion-principal">
            <ul>
                <li><a href="estudiante_pequeno.php<?php echo $es_invitado ? '?modo=invitado' : ''; ?>" class="enlace-nav activo">Catálogo</a></li>
                <?php if (!$es_invitado): ?>
                    <li><a href="estudiante_mislibros.php" class="enlace-nav">Mis Libros</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <div style="display: flex; align-items: center; gap: 15px;">
            <?php if (!$es_invitado): ?>
                <div class="badge-mis-libros" style="background-color: #fee2e2; color: #dc2626; padding: 8px 16px; border-radius: 8px; font-weight: 500;">
                    Mis Libros: <?php echo (int)$total_prestados; ?> de 2
                </div>
                <form action="salir.php" method="POST">
                    <button type="submit" class="boton-salir">Salir</button>
                </form>
            <?php else: ?>
                <a href="index.php" class="boton-salir" style="text-decoration: none;">Iniciar Sesión</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="contenedor-principal">
        <section class="barra-herramientas">
            <form action="estudiante_pequeno.php" method="GET" class="buscador" style="display: flex; align-items: center; gap: 0; flex-grow: 1;">
                <?php if ($es_invitado): ?><input type="hidden" name="modo" value="invitado"><?php endif; ?>
                <input type="search" name="busqueda_libro" placeholder="Escribe el nombre del libro o autor..." value="<?php echo htmlspecialchars($busqueda_ingresada); ?>">
                <button type="submit" class="boton-primario">Buscar</button>
                <?php if ($busqueda_ingresada !== ''): ?>
                    <a href="estudiante_pequeno.php<?php echo $es_invitado ? '?modo=invitado' : ''; ?>" class="boton-salir" style="text-decoration: none; font-size: 0.8em; padding: 10px;">Limpiar</a>
                <?php endif; ?>
            </form>
        </section>

        <?php
        $ultima_categoria = "";
        $categorias = [
            'Rojo' => 'Valores', 'Rosa' => 'Emociones', 'Morado' => 'Igualdad',
            'Amarillo' => 'Inglés', 'Marron' => 'Colecciones', 'Blanco' => 'Cómics',
            'Negro' => 'Música', 'Verde' => 'Infantil y 1.º ciclo',
            'Naranja' => '2.º y 3.º ciclo', 'Azul' => 'Naturaleza'
        ];

        if ($total_libros > 0):
            foreach ($libros as $libro):
                $color_db = $libro['ubicacion_por_colores'];
                $color_class = strtolower($color_db);
                $nombre_largo = $categorias[$color_db] ?? $color_db;

                if ($ultima_categoria != $color_db):
                    $ultima_categoria = $color_db;
                    echo "<h2 class='separador-categoria border-$color_class'>{$color_db} - {$nombre_largo}</h2>";
                endif;
        ?>
            <article class="ficha-libro">
                <div class="cuerpo-superior">
                    <figure class="portada">
                        <img src="Imagenes/Portadas/<?php echo $color_db; ?>/<?php echo $libro['isbn']; ?>.jpg" onerror="this.src='Imagenes/Portadas/default.jpg'">
                    </figure>
                    <div class="info-principal">
                        <h2><?php echo htmlspecialchars($libro['titulo']); ?></h2>
                        <p class="autor"><?php echo htmlspecialchars($libro['autor']); ?></p>
                        <p class="etiqueta-color">
                            <span class="punto <?php echo $color_class; ?>"></span>
                            <strong><?php echo $color_db; ?></strong>
                            <span class="texto-categoria">(<?php echo $nombre_largo; ?>)</span>
                        </p>
                    </div>
                </div>
                <hr class="separador">
                <div class="seccion-ejemplares">
                    <div class="grid-ejemplares">
                        <div class="cuadro-ejemplar">
                            <span class="id-ejemplar">EJ<?php echo str_pad($libro['id_libro'], 3, '0', STR_PAD_LEFT); ?></span>
                            
                            <?php
                                // Si prestamo_activo no es nulo, el libro está fuera
                                $realmente_prestado = !empty($libro['prestamo_activo']);
                                
                                $texto_mostrar = $realmente_prestado ? 'En préstamo' : 'Disponible';
                                $clase_visual = $realmente_prestado ? 'estado-prestado' : 'estado-disponible';
                            ?>
                            
                            <span class="<?php echo $clase_visual; ?>">
                                <?php echo $texto_mostrar; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>

            <nav class="paginacion">
                <?php 
                $query_params = ($es_invitado) ? "modo=invitado" : "";
                if ($busqueda_ingresada !== '') {
                    $query_params .= ($query_params ? "&" : "") . "busqueda_libro=" . urlencode($busqueda_ingresada);
                }
                
                if ($pagina_actual > 1): ?>
                    <a href="?pag=<?php echo ($pagina_actual - 1) . ($query_params ? "&$query_params" : ""); ?>" class="btn-pag">&laquo; Anterior</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="?pag=<?php echo $i . ($query_params ? "&$query_params" : ""); ?>" class="btn-pag <?php echo ($i == $pagina_actual) ? 'activo' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($pagina_actual < $total_paginas): ?>
                    <a href="?pag=<?php echo ($pagina_actual + 1) . ($query_params ? "&$query_params" : ""); ?>" class="btn-pag">Siguiente &raquo;</a>
                <?php endif; ?>
            </nav>

        <?php else: ?>
            <div style="text-align: center; padding: 50px;">
                <p>No hay resultados para "<strong><?php echo htmlspecialchars($busqueda_ingresada); ?></strong>".</p>
                <a href="estudiante_pequeno.php<?php echo $es_invitado ? '?modo=invitado' : ''; ?>" class="boton-primario">Ver todo el catálogo</a>
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