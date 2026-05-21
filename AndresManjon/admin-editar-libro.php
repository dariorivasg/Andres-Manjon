<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] != 3) {
    header("Location: index.php");
    exit();
}

$db_file = 'biblioteca.db';
$mensaje = "";
$error = "";

try {
    $db = new PDO("sqlite:" . __DIR__ . "/" . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!isset($_GET['id'])) {
        header("Location: admin.php");
        exit();
    }

    $id_libro = $_GET['id'];
    $stmt = $db->prepare("SELECT * FROM Libro WHERE id_libro = ?");
    $stmt->execute([$id_libro]);
    $libro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$libro) {
        die("Libro no encontrado.");
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $titulo = $_POST['titulo'] ?? '';
        $autor  = $_POST['autor'] ?? '';
        $isbn   = str_replace('-', '', $_POST['isbn'] ?? ''); 
        $color  = $_POST['color'] ?? '';

        $sql_update = "UPDATE Libro SET 
                        titulo = ?, autor = ?, 
                        isbn = ?, ubicacion_por_colores = ?
                       WHERE id_libro = ?";
        
        $stmt_upd = $db->prepare($sql_update);
        if ($stmt_upd->execute([$titulo, $autor, $isbn, $color, $id_libro])) {
            $mensaje = "Libro actualizado con éxito.";
            $libro['titulo'] = $titulo;
            $libro['autor'] = $autor;
            $libro['isbn'] = $isbn;
            $libro['ubicacion_por_colores'] = $color;
        } else {
            $error = "Error al guardar los cambios.";
        }
    }
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}

$categorias = [
    'rojo' => 'Valores', 'rosa' => 'Emociones', 'morado' => 'Igualdad',
    'amarillo' => 'Inglés', 'marron' => 'Colecciones', 'blanco' => 'Cómics',
    'negro' => 'Música', 'verde' => 'Infantil y 1º Ciclo',
    'naranja' => '2º y 3º Ciclo', 'azul' => 'Naturaleza'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Libro</title>
    <link rel="stylesheet" href="estilos2.css">
</head>
<body>

    <main class="contenedor-principal">
        <section class="tarjeta-formulario">
            <header>
                <h2>Editar Libro</h2>
                <p>ID: #<?php echo htmlspecialchars($id_libro); ?></p>
            </header>

            <?php if($mensaje): ?>
                <div style="background:#dcfce7; color:#166534; padding:12px; border-radius:8px; margin-bottom:15px; text-align:center;">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div style="background:#fee2e2; color:#991b1b; padding:12px; border-radius:8px; margin-bottom:15px; text-align:center;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <fieldset style="border:none; padding:0; margin:0;">
                    <label for="titulo">Título</label>
                    <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($libro['titulo'] ?? ''); ?>" required>

                    <label for="autor">Autor</label>
                    <input type="text" id="autor" name="autor" value="<?php echo htmlspecialchars($libro['autor'] ?? ''); ?>" required>

                    <label for="isbn">ISBN (Solo números)</label>
                    <input type="text" id="isbn" name="isbn" value="<?php echo htmlspecialchars($libro['isbn'] ?? ''); ?>">

                    <label for="color">Categoría (Color)</label>
                    <select id="color" name="color">
                        <?php 
                        $color_actual = strtolower($libro['ubicacion_por_colores'] ?? '');
                        foreach($categorias as $val => $nom): 
                        ?>
                            <option value="<?php echo $val; ?>" <?php echo ($color_actual == $val) ? 'selected' : ''; ?>>
                                <?php echo ucfirst($val) . " ($nom)"; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </fieldset>

                <nav style="display:flex; gap:10px; margin-top:20px;">
                    <button type="submit" class="btn-guardar" style="flex:2; padding:10px; background:#1a4b8c; color:white; border:none; border-radius:8px; cursor:pointer;">Actualizar</button>
                    <a href="admin.php" style="flex:1; text-align:center; text-decoration:none; background:#e2e8f0; color:#475569; padding:10px; border-radius:8px; display:flex; align-items:center; justify-content:center;">Volver</a>
                </nav>
            </form>
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