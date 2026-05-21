<!-- estudiante_catalogo.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilos.css">
    <title>Catálogo Escolar</title>
</head>
<body>
    <div class="contenedor-principal">
        <header class="cabecera-principal">
            <div class="usuario-identificado">
                <?php if ($esta_logueado): ?>
                    <h1>Hola, <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></h1>
                    <a href="salir.php" class="boton-salir">Cerrar Sesión</a>
                <?php else: ?>
                    <h1>Catálogo de Libros</h1>
                    <a href="index.php" class="boton-salir" style="background: var(--azul);">Volver al login</a>
                <?php endif; ?>
            </div>
        </header>

        <main>
            <div class="grid-libros">
                <?php if (count($libros) > 0): ?>
                    <?php foreach ($libros as $libro): ?>
                        <article class="ficha-libro">
                            <div class="cuerpo-superior">
                                <div class="portada">
                                    <img src="<?php echo htmlspecialchars($libro['portada'] ?? 'img/default.png'); ?>" alt="Portada">
                                </div>
                                <div class="info-principal">
                                    <h2><?php echo htmlspecialchars($libro['titulo']); ?></h2>
                                    <p class="autor"><?php echo htmlspecialchars($libro['autor']); ?></p>
                                    <p class="isbn">ISBN: <?php echo htmlspecialchars($libro['isbn']); ?></p>
                                </div>
                                
                                <?php if ($esta_logueado): ?>
                                    <div class="botones-accion">
                                        <button class="boton-primario">Pedir Libro</button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align:center;">No se han encontrado libros en la base de datos.</p>
                <?php endif; ?>
            </div>

            <nav class="paginacion">
                <?php 
                // Creamos la base del enlace para que no se pierda el modo invitado
                $url_base = "?";
                if ($es_invitado) $url_base .= "modo=invitado&";
                ?>

                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="<?php echo $url_base; ?>pag=<?php echo $i; ?>" 
                       class="btn-pag <?php echo ($i == $pagina_actual) ? 'activo' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($pagina_actual < $total_paginas): ?>
                    <a href="<?php echo $url_base; ?>pag=<?php echo ($pagina_actual + 1); ?>" class="btn-pag">
                        Siguiente &raquo;
                    </a>
                <?php endif; ?>
            </nav>
        </main>
    </div>
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