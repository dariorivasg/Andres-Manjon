<?php
// admin-estadisticas.php
session_start();

// 1. Verificar seguridad
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

// --- OBTENCIÓN DE DATOS REALES ---

// A. Totales para las tarjetas mini
$totalLibros = $db->query("SELECT COUNT(*) FROM Libro")->fetchColumn();
$prestamosActivos = $db->query("SELECT COUNT(*) FROM Prestamo WHERE estado_del_prestamo = 'Activo'")->fetchColumn();
$disponibles = $db->query("SELECT COUNT(*) FROM Libro WHERE estado_de_actividad = 'Disponible'")->fetchColumn();

// B. Libros más prestados (Ranking)
$sqlRanking = "SELECT l.titulo, l.autor, l.isbn, l.ubicacion_por_colores, 
                COUNT(p.id_prestamo) as total_prestamos
               FROM Libro l
               INNER JOIN Prestamo p ON l.id_libro = p.id_libro
               GROUP BY l.titulo, l.autor
               ORDER BY total_prestamos DESC
               LIMIT 5";
$ranking = $db->query($sqlRanking)->fetchAll(PDO::FETCH_ASSOC);

// C. Categorías (Colores) - HISTORIAL TOTAL
// He quitado el LIMIT 4 para que se vean las más importantes
$sqlCategoriasRanking = "SELECT l.ubicacion_por_colores, 
                CASE 
                    WHEN l.ubicacion_por_colores = 'Amarillo' THEN 'Inglés'
                    WHEN l.ubicacion_por_colores = 'Rosa' THEN 'Emociones'
                    WHEN l.ubicacion_por_colores = 'Morado' THEN 'Igualdad'
                    WHEN l.ubicacion_por_colores = 'Rojo' THEN 'Valores'
                    WHEN l.ubicacion_por_colores = 'Verde' THEN 'Infantil y 1º ciclo'
                    WHEN l.ubicacion_por_colores = 'Naranja' THEN '2º y 3º ciclo'
                    WHEN l.ubicacion_por_colores = 'Azul' THEN 'Naturaleza'
                    WHEN l.ubicacion_por_colores = 'Blanco' THEN 'Cómics'
                    WHEN l.ubicacion_por_colores = 'Negro' THEN 'Música'
                    WHEN l.ubicacion_por_colores = 'Marron' THEN 'Colecciones'
                    WHEN l.ubicacion_por_colores = 'Marrón' THEN 'Colecciones'
                    ELSE 'General'
                END as categoria_nombre,
                COUNT(p.id_prestamo) as total_solicitudes
                FROM Prestamo p
                JOIN Libro l ON p.id_libro = l.id_libro
                GROUP BY l.ubicacion_por_colores
                ORDER BY total_solicitudes DESC";
$categoriasRanking = $db->query($sqlCategoriasRanking)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas de Biblioteca</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        .lista-ranking li {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
            gap: 20px;
        }
        .info-libro {
            flex-grow: 1;
            min-width: 0;
        }
        .info-libro h4 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .info-libro p {
            margin: 5px 0 0 0;
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        .conteo {
            white-space: nowrap;
            font-weight: bold;
            color: #2980b9;
            background: #e1f5fe;
            padding: 5px 12px;
            border-radius: 15px;
        }
        /* Estilo para el bloque de color en el ranking de categorías */
        .bloque-color-ranking {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
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
                <li><a href="admin-prestamos.php" class="enlace-nav">Préstamos</a></li>
                <li><a href="admin-estadisticas.php" class="enlace-nav activo">Estadísticas</a></li>
            </ul>
        </nav>

        <form action="salir.php" method="POST">
            <button type="submit" class="boton-salir">Salir</button>
        </form>
    </header>

    <main class="contenedor-principal">
        <header class="indicadores">
            <section class="tarjeta-mini">
                <span class="icono-stats azul">📚</span>
                <section>
                    <p>Total Libros</p>
                    <strong><?php echo $totalLibros; ?></strong>
                </section>
            </section>
            <section class="tarjeta-mini">
                <span class="icono-stats azul">📖</span>
                <section>
                    <p>Libros Disponibles</p>
                    <strong><?php echo $disponibles; ?></strong>
                    <small>en estantería</small>
                </section>
            </section>
            <section class="tarjeta-mini">
                <span class="icono-stats verde">👥</span>
                <section>
                    <p>Préstamos Activos</p>
                    <strong><?php echo $prestamosActivos; ?></strong>
                </section>
            </section>
        </header>

        <section class="tarjeta-entidad bloque-estadistico">
            <h3>Los 5 Libros Más Prestados</h3>
            <ol class="lista-ranking">
                <?php 
            $puesto = 1;
            foreach ($ranking as $libro): 
                $isbn = $libro['isbn'];
                $color_db = $libro['ubicacion_por_colores']; // Ej: "Marrón"
                
                // Limpiamos el nombre de la carpeta para la ruta de la imagen
                $carpeta_limpia = mb_strtolower($color_db, 'UTF-8');
                $carpeta_limpia = str_replace(['á','é','í','ó','ú','ñ'], ['a','e','i','o','u','n'], $carpeta_limpia);
                
                // Intentamos buscar la imagen (Ej: Imagenes/Portadas/marron/978...jpg)
                $img_src = "Imagenes/Portadas/{$carpeta_limpia}/{$isbn}.jpg";
                
                if (!file_exists(__DIR__ . "/" . $img_src)) {
                    $img_src = "Imagenes/Portadas/default.jpg";
                }
            ?>
                <li>
                    <span class="puesto"><?php echo $puesto++; ?></span>
                    <img src="<?php echo htmlspecialchars($img_src); ?>" alt="Portada" class="mini-portada" style="width: 60px; height: 80px; border-radius: 4px; object-fit: cover;">
                    <section class="info-libro">
                        <h4 title="<?php echo htmlspecialchars($libro['titulo']); ?>">
                            <?php echo htmlspecialchars($libro['titulo']); ?>
                        </h4>
                        <p><?php echo htmlspecialchars($libro['autor']); ?></p>
                        <small style="color: #95a5a6;">Sección: <?php echo htmlspecialchars($color_db); ?></small>
                    </section>
                    <span class="conteo"><?php echo $libro['total_prestamos']; ?> préstamos</span>
                </li>
                <?php endforeach; ?>
            </ol>
        </section>

        <section class="tarjeta-entidad bloque-estadistico">
            <h3>Categorías más solicitadas</h3>
            <ol class="lista-ranking">
                <?php 
                $puestoCat = 1;
                    foreach ($categoriasRanking as $cat): 
                    // Limpieza para la clase CSS (Ej: de "Marrón" a "marron")
                    $colorOriginal = $cat['ubicacion_por_colores'];
                    $claseColor = mb_strtolower($colorOriginal, 'UTF-8');
                    $claseColor = str_replace(['á','é','í','ó','ú','ñ'], ['a','e','i','o','u','n'], $claseColor);
                ?>
                <li>
                    <span class="puesto"><?php echo $puestoCat++; ?></span>
                    <div class="bloque-color-ranking <?php echo $claseColor; ?>">
                        🎨
                    </div>
                    
                    <section class="info-libro">
                        <h4><?php echo htmlspecialchars($cat['categoria_nombre']); ?></h4>
                        <p>Sección: <?php echo htmlspecialchars($cat['ubicacion_por_colores']); ?></p>
                    </section>
                    
                    <span class="conteo"><?php echo $cat['total_solicitudes']; ?> solicitudes</span>
                </li>
                <?php endforeach; ?>
            </ol>
        </section>
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