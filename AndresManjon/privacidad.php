<?php session_start(); ?>
<!-- privacidad.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Privacidad - Biblioteca Escolar</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <main class="login" style="width: 600px; text-align: left;">
        <h1 class="titulo" style="text-align: center;">Política de Privacidad</h1>
        
        <div style="font-size: 0.9rem; color: #444; line-height: 1.6;">
            <p><strong>1. Datos Recopilados:</strong> El sistema almacena nombres, apellidos, curso y registros de préstamos de libros.</p>
            
            <p><strong>2. Uso de la Información:</strong> Los datos se utilizan exclusivamente para la gestión interna de la biblioteca y el control de devoluciones.</p>
            
            <p><strong>3. Seguridad:</strong> El acceso a los datos del alumnado está restringido a usuarios con rol de Profesor o Administrador.</p>
            
            <p><strong>4. Derechos:</strong> Puedes solicitar la revisión de tus datos personales acudiendo a la secretaría del centro.</p>
        </div>

        <br>
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