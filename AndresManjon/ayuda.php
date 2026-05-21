<?php session_start(); ?>
<!-- ayuda.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ayuda - Biblioteca Escolar</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <main class="login" style="width: 500px;"> <h1 class="titulo">Centro de Ayuda</h1>
        <p class="subtitulo">¿Cómo podemos ayudarte hoy?</p>
        
        <section style="text-align: left; margin-bottom: 20px;">
            <details style="margin-bottom: 10px; cursor: pointer;">
                <summary><strong>¿Cómo pido un libro prestado?</strong></summary>
                <p style="font-size: 0.9rem; color: var(--texto-gris); padding: 10px;">Debes acudir al mostrador con tu carnet escolar o indicar tu nombre de usuario al administrador.</p>
            </details>

            <details style="margin-bottom: 10px; cursor: pointer;">
                <summary><strong>¿Cuál es el plazo de devolución?</strong></summary>
                <p style="font-size: 0.9rem; color: var(--texto-gris); padding: 10px;">El plazo estándar es de 14 días naturales, prorrogables por otros 7 si no hay reservas.</p>
            </details>

            <details style="margin-bottom: 10px; cursor: pointer;">
                <summary><strong>He olvidado mi contraseña</strong></summary>
                <p style="font-size: 0.9rem; color: var(--texto-gris); padding: 10px;">Contacta con el administrador de la biblioteca para restablecer tus credenciales.</p>
            </details>
        </section>

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
            ?>" class="boton-login" style="text-decoration: none; display: block;">Volver al Inicio</a>
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