<?php
//index.php
session_start();
$db_file = 'biblioteca.db'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario_ingresado = $_POST['usuario'] ?? '';
    $password_ingresada = $_POST['contrasena'] ?? '';

    try {
        $db = new PDO("sqlite:" . __DIR__ . "/" . $db_file);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        function limpiarTexto($texto) {
            $busqueda   = array('á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ');
            $reemplazo  = array('a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u', 'n', 'n');
            $texto = str_replace($busqueda, $reemplazo, $texto);
            return strtolower($texto);
        }

        $usuario_limpio = limpiarTexto($usuario_ingresado);

        $stmt = $db->query("SELECT * FROM Usuario");
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $usuario_encontrado = null;

        foreach ($usuarios as $u) {
            if (limpiarTexto($u['nombre']) === $usuario_limpio || 
                limpiarTexto($u['username']) === $usuario_limpio || 
                limpiarTexto($u['codigo_de_carnet']) === $usuario_limpio) {
                $usuario_encontrado = $u;
                break;
            }
        }

        if ($usuario_encontrado && $password_ingresada === $usuario_encontrado['contrasenia']) {
            $_SESSION['usuario_id'] = $usuario_encontrado['id_usuario'];
            $_SESSION['usuario_nombre'] = $usuario_encontrado['nombre'];
            $_SESSION['usuario_rol'] = $usuario_encontrado['id_rol'];

            // --- LÓGICA DE REDIRECCIÓN SEGÚN ROL Y NIVEL ---
            if ($_SESSION['usuario_rol'] == 3) {
                // Administrador
                header("Location: admin.php");
            } elseif ($_SESSION['usuario_rol'] == 1) {
                // Estudiante: Verificamos su clase en la tabla Alumnado
                $stmt_alu = $db->prepare("SELECT clase FROM Alumnado WHERE codigo_de_carnet = :cod");
                $stmt_alu->execute([':cod' => $usuario_encontrado['codigo_de_carnet']]);
                $alumno = $stmt_alu->fetch(PDO::FETCH_ASSOC);

                $clase = $alumno['clase'] ?? '';

                // Filtro para Infantil y Primer Ciclo
                if ($clase === 'Infantil' || $clase === '1º Primaria' || $clase === '2º Primaria') {
                    header("Location: estudiante_pequeno.php");
                } else {
                    header("Location: estudiante_mayor.php");
                }
            } else {
                // Profesores (Rol 2)
                header("Location: profesor.php");
            }
            exit();
        } else {
            $error = "Usuario o contraseña incorrectos.";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilos.css">
    <title>Login</title>
</head>
<body>
    <section class="login">
        <img src="Imagenes/logoAndresManjon.jpg" alt="Logo Colegio" class="logo-colegio">
        <p class="titulo">Biblioteca Escolar</p>
        <p class="subtitulo">Sistema de Gestión de Préstamos</p>
        
        <?php if(isset($error)): ?>
            <div class="texto-error-login">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <section class="grupo-formulario">
                <label class="etiqueta">Nombre de Usuario</label>
                <input type="text" name="usuario" class="campo" placeholder="Introduce tu nombre" required>
            </section>

            <section class="grupo-formulario">
                <label class="etiqueta">Contraseña</label>
                <input type="password" name="contrasena" class="campo" placeholder="********" required>
            </section>

            <button type="submit" class="boton-login">Iniciar Sesión</button>
        </form>

        <div class="seccion-infantil">
            <p class="pregunta-infantil">¿Eres de Infantil o 1er Ciclo?</p>
            <a href="estudiante_pequeno.php?modo=invitado" class="boton-catalogo-verde">Catálogo</a>
        </div>
    </section>

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