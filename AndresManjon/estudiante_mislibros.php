<?php
// estudiante_mislibros.php
session_start();

// Verificación de seguridad: solo estudiantes (rol 1)
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] != 1) {
    header("Location: index.php");
    exit();
}

$db_file = 'biblioteca.db';
$hoy = date('Y-m-d'); // Fecha actual para comparar vencimientos

try {
    $db = new PDO("sqlite:" . __DIR__ . "/" . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Obtener el carnet del usuario logueado
    $stmt_u = $db->prepare("SELECT codigo_de_carnet FROM Usuario WHERE id_usuario = ?");
    $stmt_u->execute([$_SESSION['usuario_id']]);
    $mi_carnet = $stmt_u->fetchColumn();

    // 2. Obtener datos del Alumno (usando 'clase' según tu base de datos)
    $stmt_a = $db->prepare("SELECT id_alumnado, clase FROM Alumnado WHERE codigo_de_carnet = ?");
    $stmt_a->execute([$mi_carnet]);
    $alumno_data = $stmt_a->fetch(PDO::FETCH_ASSOC);
    
    $id_alumnado_real = $alumno_data['id_alumnado'];
    $clase_actual = $alumno_data['clase']; 

    // 3. DETERMINAR ENLACE DE CATÁLOGO: Solo 5º y 6º son "mayores"
    if (preg_match('/[56]/', $clase_actual)) {
        $url_catalogo = "estudiante_mayor.php";
    } else {
        $url_catalogo = "estudiante_pequeno.php";
    }

    // 4. Lógica para cancelar o devolver el libro
    if (isset($_POST['cancelar_pedido'])) {
        $id_p = $_POST['id_prestamo'];
        $id_l = $_POST['id_libro'];
        
        // Eliminamos el préstamo y ponemos el libro como Disponible
        $db->prepare("DELETE FROM Prestamo WHERE id_prestamo = ? AND id_alumnado = ?")->execute([$id_p, $id_alumnado_real]);
        $db->prepare("UPDATE Libro SET estado_de_actividad = 'Disponible' WHERE id_libro = ?")->execute([$id_l]);
        
        header("Location: estudiante_mislibros.php");
        exit();
    }

    // 5. Consulta de libros con estado 'Activo' para este alumno
    $query = "SELECT p.*, l.id_libro, l.titulo, l.autor, l.isbn, l.ubicacion_por_colores 
              FROM Prestamo p 
              JOIN Libro l ON p.id_libro = l.id_libro 
              WHERE p.id_alumnado = ? AND p.estado_del_prestamo = 'Activo'";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_alumnado_real]);
    $mis_libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_prestados = count($mis_libros);

} catch (PDOException $e) { 
    die("Error en la base de datos: " . $e->getMessage()); 
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Libros - Biblioteca</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        /* Estilos para libros con fecha de devolución vencida */
        .ficha-vencida {
            border: 2px solid #ef4444 !important;
            background-color: #fef2f2 !important;
        }
        .texto-vencido {
            color: #b91c1c;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.8rem;
            display: block;
            margin-top: 5px;
        }
        .badge-vencido {
            background: #dc2626;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-left: 5px;
        }
    </style>
</head>
<body>

    <header class="cabecera-principal">
        <section class="usuario-identificado">
            <span class="icono-ajustes">📖</span>
            <div>
                <h1>Mis Libros Prestados</h1>
                <p><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?> (<?php echo htmlspecialchars($clase_actual); ?>)</p>
            </div>
        </section>
        <nav class="navegacion-principal">
            <ul>
                <li><a href="<?php echo $url_catalogo; ?>" class="enlace-nav">Catálogo</a></li>
                <li><a href="estudiante_mislibros.php" class="enlace-nav activo">Mis Libros</a></li>
            </ul>
        </nav>
        <div style="display: flex; align-items: center; gap: 15px;">
            <div class="badge-mis-libros" style="background-color: #fee2e2; color: #dc2626; padding: 8px 16px; border-radius: 8px; font-weight: 500;">
                Mis Libros: <?php echo $total_prestados; ?> de 2
            </div>
            <form action="salir.php" method="POST">
                <button type="submit" class="boton-salir">Salir</button>
            </form>
        </div>
    </header>


    <main class="contenedor-principal">
        <?php if ($total_prestados > 0): ?>
            <?php foreach ($mis_libros as $libro): 
                $color_db = $libro['ubicacion_por_colores'];
                $fecha_devo = $libro['fecha_de_devolucion'];
                $es_vencido = ($fecha_devo < $hoy); 
            ?>
            <article class="ficha-libro <?php echo $es_vencido ? 'ficha-vencida' : ''; ?>">
                <div class="cuerpo-superior">
                    <figure class="portada">
                        <img src="Imagenes/Portadas/<?php echo $color_db; ?>/<?php echo $libro['isbn']; ?>.jpg" onerror="this.src='Imagenes/Portadas/default.jpg'">
                    </figure>
                    <div class="info-principal">
                        <h2>
                            <?php echo htmlspecialchars($libro['titulo']); ?>
                            <?php if($es_vencido) echo '<span class="badge-vencido">VENCIDO</span>'; ?>
                        </h2>
                        <p class="autor"><?php echo htmlspecialchars($libro['autor']); ?></p>
                        
                        <div style="margin-top: 15px; padding: 12px; background: white; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <p style="margin: 0; font-size: 0.9rem; color: #64748b;">📅 Prestado el: <strong><?php echo date('d/m/Y', strtotime($libro['fecha_de_salida'])); ?></strong></p>
                            <p style="margin: 5px 0 0 0; font-size: 0.9rem; color: <?php echo $es_vencido ? '#dc2626' : '#1e293b'; ?>;">
                                ⚠️ Devolución: <strong><?php echo date('d/m/Y', strtotime($fecha_devo)); ?></strong>
                                <?php if($es_vencido): ?>
                                    <span class="texto-vencido">¡Plazo de entrega superado! Acude a biblioteca.</span>
                                <?php endif; ?>
                            </p>
                        </div>

                        <form method="POST" style="margin-top: 15px;">
                            <input type="hidden" name="id_prestamo" value="<?php echo $libro['id_prestamo']; ?>">
                            <input type="hidden" name="id_libro" value="<?php echo $libro['id_libro']; ?>">
                            <button type="submit" name="cancelar_pedido" class="btn-solicitar-prestamo" 
                                    style="background: #f8fafc; color: #64748b; border: 1px solid #cbd5e1; font-size: 0.85rem;" 
                                    onclick="return confirm('¿Seguro que quieres devolver o cancelar este libro?')">
                                Cancelar / Devolver
                            </button>
                        </form>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 60px; background: white; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 2px dashed #e2e8f0;">
                <p style="font-size: 1.2rem; color: #64748b;">No tienes libros prestados en este momento.</p>
                <a href="<?php echo $url_catalogo; ?>" class="boton-primario" style="display: inline-block; margin-top: 20px; text-decoration: none; padding: 12px 25px;">Ir al Catálogo</a>
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