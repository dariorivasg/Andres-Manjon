<?php session_start(); ?>
<!-- contacto.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contacto - Biblioteca Escolar</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <main class="login">
        <h1 class="titulo">Contacto</h1>
        <p class="subtitulo">Envía un mensaje al bibliotecario</p>
        
        <form action="#">
            <div class="grupo-formulario">
                <label class="etiqueta">Asunto</label>
                <input type="text" class="campo" placeholder="Ej: Libro perdido, sugerencia...">
            </div>

            <div class="grupo-formulario">
                <label class="etiqueta">Mensaje</label>
                <textarea class="campo" style="height: 100px; resize: none;" placeholder="Escribe aquí tu mensaje..."></textarea>
            </div>

            <button type="button" class="boton-login" onclick="alert('Mensaje enviado correctamente (Simulación)')">Enviar Mensaje</button>
        </form>

        <p style="margin-top: 20px;">
            <a href="<?php 
                if (isset($_SESSION['usuario_rol'])) {
                    switch ($_SESSION['usuario_rol']) {
                        case 3: echo 'admin.php'; break;
                        case 2: echo 'profesor.php'; break;
                        case 1: echo 'estudiante_mayor.php'; break;
                        default: echo 'index.php';
                    }
                } else {
                    echo 'index.php';
                }
            ?>" style="color: var(--azul); font-size: 0.85rem; text-decoration: none;">Volver</a>
        </p> 
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