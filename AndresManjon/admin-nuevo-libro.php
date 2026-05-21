<?php
// admin-nuevo-libro.php
$archivo_db = 'biblioteca.db';
$mensaje = "";
$tipo_alerta = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $db = new PDO("sqlite:" . $archivo_db); 
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $titulo = $_POST['titulo'];
        $autor = $_POST['autor'];
        $isbn = $_POST['isbn']; 
        $color = $_POST['color'];

        // Consulta corregida: 6 columnas para 6 valores
        // Estructura: titulo, autor, isbn, codigo_de_barra, ubicacion_por_colores, estado_de_actividad
        $sql = "INSERT INTO Libro (titulo, autor, isbn, codigo_de_barra, ubicacion_por_colores, estado_de_actividad) 
                VALUES (:titulo, :autor, :isbn, :codigo_barra, :color, 'Disponible')";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':titulo'       => $titulo,
            ':autor'        => $autor,
            ':isbn'         => $isbn,
            ':codigo_barra' => $isbn, // Se usa el ISBN como código de barras
            ':color'        => $color
        ]);

        $mensaje = "Libro añadido correctamente: $titulo";
        $tipo_alerta = "exito";

    } catch (PDOException $e) {
        // Manejo específico del error de duplicado (Código 23000 / 19)
        if ($e->getCode() == 23000 || strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
            $mensaje = "Error: Ya existe un libro registrado con el ISBN $isbn.";
        } else {
            $mensaje = "Error al guardar: " . $e->getMessage();
        }
        $tipo_alerta = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Nuevo Libro</title>
    <link rel="stylesheet" href="estilos2.css">
</head>
<body>
    <div class="boton-flotante-derecha">
        <button type="button" class="btn-volver" onclick="window.location.href='admin.php'">
            <span style="margin-right: 8px;">←</span> Volver a Libros
        </button>
    </div>

    <main class="contenedor-principal">
        <section class="tarjeta-formulario">
            <header>
                <h2>Nuevo Libro</h2>
            </header>

            <?php if ($mensaje): ?>
                <div style="padding: 10px; margin-bottom: 15px; border-radius: 5px; 
                     background-color: <?= $tipo_alerta == 'exito' ? '#d4edda' : '#f8d7da' ?>; 
                     color: <?= $tipo_alerta == 'exito' ? '#155724' : '#721c24' ?>;">
                    <?= $mensaje ?>
                </div>
            <?php endif; ?>

            <form action="admin-nuevo-libro.php" method="POST">
                <fieldset>
                    <label for="titulo">Título *</label>
                    <input type="text" id="titulo" name="titulo" value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>" required>
                </fieldset>

                <fieldset>
                    <label for="autor">Autor *</label>
                    <input type="text" id="autor" name="autor" value="<?= htmlspecialchars($_POST['autor'] ?? '') ?>" required>
                </fieldset>

                <fieldset>
                    <label for="isbn">ISBN / Código de Barras *</label>
                    <input type="text" id="isbn" name="isbn" value="<?= htmlspecialchars($_POST['isbn'] ?? '') ?>" required>
                </fieldset>

                <fieldset>
                    <label for="color">Código de Color (Nivel)</label>
                    <select id="color" name="color">
                        <option value="Azul">Azul</option>
                        <option value="Naranja">Naranja</option>
                        <option value="Verde">Verde</option>
                        <option value="Negro">Negro</option>
                        <option value="Blanco">Blanco</option>
                        <option value="Marron">Marron</option>
                        <option value="Amarillo">Amarillo</option>
                        <option value="Morado">Morado</option>
                        <option value="Rosa">Rosa</option>
                        <option value="Rojo">Rojo</option>
                    </select>
                </fieldset>

                <nav class="acciones">
                    <button type="submit" class="btn-guardar">Guardar</button>
                    <button type="button" class="btn-cancelar" onclick="window.history.back()">Cancelar</button>
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