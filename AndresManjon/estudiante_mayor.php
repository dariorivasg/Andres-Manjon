<?php
// estudiante_mayor.php
session_start();

// 1. Verificación de seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] != 1){
    header("Location: index.php");
    exit();
}

$db_file = 'biblioteca.db';
try {
    $db = new PDO("sqlite:" . __DIR__ . "/" . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $usuario_id = $_SESSION['usuario_id'];

    // --- CORRECCIÓN DEL CONTADOR: Puente Usuario -> Alumnado ---
    // 1. Obtenemos el carnet del usuario logueado
    $stmt_u = $db->prepare("SELECT codigo_de_carnet FROM Usuario WHERE id_usuario = ?");
    $stmt_u->execute([$usuario_id]);
    $carnet_usuario = $stmt_u->fetchColumn();

    // 2. Buscamos el id_alumnado que corresponde a ese carnet
    $stmt_a = $db->prepare("SELECT id_alumnado, clase FROM Alumnado WHERE codigo_de_carnet = ?");
    $stmt_a->execute([$carnet_usuario]);
    $alumno_data = $stmt_a->fetch(PDO::FETCH_ASSOC);
    
    $id_alumnado_real = $alumno_data['id_alumnado'];
    $clase_actual = $alumno_data['clase'];

    // 3. Contamos los préstamos activos de ese id_alumnado
    $total_prestados = 0;
    if ($id_alumnado_real) {
        $stmt_p = $db->prepare("SELECT COUNT(*) FROM Prestamo WHERE id_alumnado = :id AND estado_del_prestamo = 'Activo'");
        $stmt_p->execute([':id' => $id_alumnado_real]);
        $total_prestados = $stmt_p->fetchColumn();
    }

    // --- FUNCIÓN DE LIMPIEZA PARA BÚSQUEDA ---
    if (!function_exists('limpiarTexto')) {
        function limpiarTexto($texto) {
            if ($texto === null) return '';
            $busqueda   = array('á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ');
            $reemplazo  = array('a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u', 'n', 'n');
            $texto = str_replace($busqueda, $reemplazo, $texto);
            return strtolower($texto);
        }
    }

    // --- MAPEO DE CATEGORÍAS ---
    $categorias_map = [
        'Rojo' => 'Valores', 'Rosa' => 'Emociones', 'Morado' => 'Igualdad',
        'Amarillo' => 'Inglés', 'Marron' => 'Colecciones', 'Blanco' => 'Cómics',
        'Negro' => 'Música', 'Verde' => 'Infantil y 1.º ciclo',
        'Naranja' => '2.º y 3.º ciclo', 'Azul' => 'Naturaleza'
    ];

    // --- LÓGICA DE BÚSQUEDA ---
    $busqueda_ingresada = isset($_GET['busqueda_libro']) ? trim($_GET['busqueda_libro']) : '';
    $busqueda_limpia = limpiarTexto($busqueda_ingresada);

    $sql_base = "SELECT L.*, 
                 P.id_prestamo AS hay_prestamo_activo
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
    
    $stmt_all = $db->query($sql_base);
    $todos_los_libros = $stmt_all->fetchAll(PDO::FETCH_ASSOC);

    // Filtrado manual multi-atributo
    $libros_filtrados = [];
    if ($busqueda_limpia !== '') {
        foreach ($todos_los_libros as $l) {
            $color = $l['ubicacion_por_colores'];
            $nombre_cat = $categorias_map[$color] ?? '';
            
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
            if ($coincide) $libros_filtrados[] = $l;
        }
    } else {
        $libros_filtrados = $todos_los_libros;
    }

    // Paginación
    $libros_por_pagina = 10;
    $total_libros = count($libros_filtrados);
    $total_paginas = ceil($total_libros / $libros_por_pagina);
    $pagina_actual = isset($_GET['pag']) ? (int)$_GET['pag'] : 1;
    if ($pagina_actual < 1) $pagina_actual = 1;
    $offset = ($pagina_actual - 1) * $libros_por_pagina;
    $libros = array_slice($libros_filtrados, $offset, $libros_por_pagina);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo - Estudiante 3er Ciclo</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>

    <header class="cabecera-principal">
        <section class="usuario-identificado">
            <span class="icono-ajustes">📖</span>
            <div>
                <h1>Panel estudiante mayor</h1>
                <p><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?> (<?php echo htmlspecialchars($clase_actual); ?>)</p>
            </div>
        </section>
            <nav class="navegacion-principal">
                <ul>
                    <li><a href="estudiante_mayor.php" class="enlace-nav activo">Catálogo</a></li>
                    <li><a href="estudiante_mislibros.php" class="enlace-nav">Mis Libros</a></li>
                </ul>
            </nav>
            <div style="display: flex; align-items: center; gap: 15px;">
            <div class="badge-mis-libros" style="background-color: #fee2e2; color: #dc2626; padding: 8px 16px; border-radius: 8px; font-weight: 500;">
                    Mis Libros: <?php echo (int)$total_prestados; ?> de 2
                </div>
            <form action="salir.php" method="POST">
                <button type="submit" class="boton-salir">Salir</button>
            </form>
        </div>
    </header>

    

    <main class="contenedor-principal">
        <section class="barra-herramientas">
            <form action="estudiante_mayor.php" method="GET" class="buscador" style="display: flex; align-items: center; gap: 0; flex-grow: 1;">    
                <input type="search" name="busqueda_libro" placeholder="Buscar por título, autor..." value="<?php echo htmlspecialchars($busqueda_ingresada); ?>">
                <button type="submit" class="boton-primario">Buscar</button>
                <?php if ($busqueda_ingresada !== ''): ?>
                    <a href="estudiante_mayor.php" class="boton-salir" style="text-decoration: none; font-size: 0.8em; padding: 10px;">Limpiar</a>
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
                $nombre_largo = isset($categorias[$color_db]) ? $categorias[$color_db] : $color_db;

                if ($ultima_categoria != $color_db):
                    $ultima_categoria = $color_db;
                    echo "<h2 class='separador-categoria border-$color_class'>{$color_db} - {$nombre_largo}</h2>";
                endif;

                $isbn = $libro['isbn'];
                $img_src = "Imagenes/Portadas/{$color_db}/{$isbn}.jpg";
        ?>
            <article class="ficha-libro">
                <div class="cuerpo-superior">
                    <figure class="portada">
                        <img src="<?php echo $img_src; ?>" onerror="this.src='Imagenes/Portadas/default.jpg'" alt="Portada">
                    </figure>
                    <div class="info-principal">
                        <h2><?php echo htmlspecialchars($libro['titulo']); ?></h2>
                        <p class="autor"><?php echo htmlspecialchars($libro['autor']); ?></p>
                        <p class="etiqueta-color">
                            <span class="punto <?php echo $color_class; ?>"></span>
                            <strong><?php echo $color_db; ?></strong>
                            <span class="texto-categoria">(<?php echo $nombre_largo; ?>)</span>
                        </p>
                        <div class="codigos-libro">
                            <p><strong>ISBN:</strong> <?php echo $libro['isbn']; ?></p>
                            <p><strong>Cód. Barras:</strong> <?php echo $libro['codigo_de_barra'] ?? '---'; ?></p>
                        </div>

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
                    </div>
                </div>
                <hr class="separador">
                <div class="seccion-ejemplares">
                    <span class="titulo-ejemplares">Estado del ejemplar:</span>
                    <div class="grid-ejemplares">
                        <div class="cuadro-ejemplar">
                            <span class="id-ejemplar">EJ<?php echo str_pad($libro['id_libro'], 3, '0', STR_PAD_LEFT); ?></span>
                            <?php
                                // Usamos la misma lógica de "realmente_disponible" definida arriba
                                $texto_estado = $realmente_disponible ? "Disponible en Biblioteca" : "Prestado";
                                $clase_estado = $realmente_disponible ? 'estado-disponible' : 'estado-prestado';
                            ?>
                            <span class="<?php echo $clase_estado; ?>">
                                <?php echo $texto_estado; ?>
                            </span>
                        </div>
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
            <p style="text-align:center; padding: 20px;">No se encontraron libros que coincidan con tu búsqueda.</p>
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