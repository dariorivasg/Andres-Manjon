<?php
// profesor-usuario.php
session_start();

// 1. Verificación de seguridad: Solo profesores (Rol 2)
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] != 2) {
    header("Location: index.php");
    exit();
}

// 2. Conexión a la base de datos
try {
    $db = new PDO('sqlite:biblioteca.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Error al conectar con la base de datos: " . $e->getMessage());
}

// 3. Lógica de búsqueda
$busqueda = isset($_GET['busqueda_usuario']) ? trim($_GET['busqueda_usuario']) : '';

function limpiarTildes($cadena) {
    $buscar = array('á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ü', 'Ü');
    $reemplazar = array('a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u', 'u', 'u');
    return str_replace($buscar, $reemplazar, $cadena);
}

$sql = "SELECT u.id_usuario, u.id_rol, u.nombre, u.codigo_de_carnet, r.nombre as rol_nombre_db, 
               a.clase, a.estado_de_sancion 
        FROM Usuario u
        JOIN Rol r ON u.id_rol = r.id_rol
        LEFT JOIN Alumnado a ON u.codigo_de_carnet = a.codigo_de_carnet";

if ($busqueda !== '') {
    $norm = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(%s), 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u')";
    $sql .= " WHERE " . sprintf($norm, 'u.nombre') . " LIKE :query 
              OR u.codigo_de_carnet LIKE :query 
              OR " . sprintf($norm, 'a.clase') . " LIKE :query";
    
    $stmt = $db->prepare($sql);
    $terminoLimpio = '%' . limpiarTildes(mb_strtolower($busqueda)) . '%';
    $stmt->bindValue(':query', $terminoLimpio);
    $stmt->execute();
} else {
    $stmt = $db->query($sql);
}

$usuarios_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. AGRUPACIÓN PARA LOS DESPLEGABLES
$grupos = [
    'Administradores' => [],
    'Profesores'      => [],
    'Estudiantes'     => []
];

foreach ($usuarios_raw as $user) {
    $carnet = $user['codigo_de_carnet'] ?? '';
    if (stripos($carnet, 'ADM') !== false) {
        $grupos['Administradores'][] = $user;
    } elseif (stripos($carnet, 'PRO') !== false) {
        $grupos['Profesores'][] = $user;
    } else {
        $grupos['Estudiantes'][] = $user;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Panel Profesor</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <header class="cabecera-principal">
        <section class="usuario-identificado">
            <span class="icono-ajustes">📖</span>
            <div>
                <h1>Panel de Profesor</h1>
                <p><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></p>
            </div>
        </section>
        <nav class="navegacion-principal">
            <ul>
                <li><a href="profesor.php" class="enlace-nav">Catálogo</a></li>
                <li><a href="profesor-usuario.php" class="enlace-nav activo">Usuarios</a></li>
                <li><a href="profesor-devolucion.php" class="enlace-nav">Préstamos</a></li>
            </ul>
        </nav>
        <form action="salir.php" method="POST">
            <button type="submit" class="boton-salir">Salir</button>
        </form>
    </header>

    <main class="contenedor-principal">
        <section class="barra-herramientas">
            <form action="profesor-usuario.php" method="GET" class="buscador" style="display: flex; align-items: center; gap: 0; flex-grow: 1;">
                <input type="search" name="busqueda_usuario" placeholder="Buscar por nombre, carnet o clase..." value="<?php echo htmlspecialchars($busqueda); ?>">
                <button type="submit" class="boton-primario">Buscar</button>
                <?php if ($busqueda !== ''): ?>
                    <a href="profesor-usuario.php" class="boton-salir" style="text-decoration: none; font-size: 0.8em; display: inline-block; padding: 10px; margin-left: 5px;">Limpiar</a>
                <?php endif; ?>
            </form>
            <a href="admin-nuevo-usuario.php" class="boton-primario-nuevo" style="text-decoration:none; margin-left: 15px;">+ Nuevo Usuario</a>
        </section>

        <?php if (empty($usuarios_raw)): ?>
            <div style="text-align: center; padding: 3rem; color: #666;">
                <p>No se encontraron resultados.</p>
            </div>
        <?php else: ?>

            <?php foreach ($grupos as $nombre_grupo => $lista_usuarios): ?>
                <?php if (!empty($lista_usuarios)): ?>
                    
                    <details class="desplegable-grupo" open>
                        <summary>
                            <span><?php echo $nombre_grupo; ?></span>
                            <span class="contador-badge"><?php echo count($lista_usuarios); ?> usuarios</span>
                        </summary>
                        
                        <div class="contenido-desplegable">
                            <?php foreach ($lista_usuarios as $user): ?>
                                <?php 
                                    $carnet = $user['codigo_de_carnet'] ?? '';
                                    $clase = $user['clase'] ?? '';
                                    
                                    // Determinar Rol lógico
                                    $es_admin = (stripos($carnet, 'ADM') !== false);
                                    $es_profe = (stripos($carnet, 'PRO') !== false);
                                    $rol_final = $es_admin ? "Administrador" : ($es_profe ? "Profesor" : "Estudiante");
                                    
                                    // Colores de Avatar
                                    $colorAvatar = "naranja";
                                    if ($es_admin) $colorAvatar = "azul-oscuro";
                                    elseif ($es_profe) $colorAvatar = "azul-claro";
                                    elseif (preg_match('/infantil|1º|2º/iu', $clase)) $colorAvatar = "verde";
                                ?>
                                <article class="tarjeta-entidad-usuarios">
                                    <section class="info-principal">
                                        <span class="avatar <?php echo $colorAvatar; ?>"></span>
                                        <section class="datos">
                                            <h3><?php echo htmlspecialchars($user['nombre']); ?></h3>
                                            <p><strong>Carnet:</strong> <?php echo htmlspecialchars($carnet ?: 'N/A'); ?></p>
                                            
                                            <?php if ($rol_final === 'Estudiante'): ?>
                                                <p><strong>Clase:</strong> <?php echo htmlspecialchars($clase ?: 'No asignada'); ?></p>
                                                <?php if (!empty($user['estado_de_sancion']) && $user['estado_de_sancion'] !== 'Ninguna'): ?>
                                                    <p style="color: #d9534f; font-weight: bold; font-size: 0.85rem;">⚠️ Sanción: <?php echo htmlspecialchars($user['estado_de_sancion']); ?></p>
                                                <?php else: ?>
                                                    <p style="color: #28a745; font-size: 0.85rem;">✅ Sin sanciones</p>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <p><strong>Puesto:</strong> <?php echo $rol_final; ?></p>
                                            <?php endif; ?>
                                            
                                            <mark class="etiqueta <?php echo ($rol_final === 'Estudiante') ? 'est-pequeno' : 'est-mayor'; ?>" style="margin-top: 8px; display: inline-block;">
                                                <?php echo $rol_final; ?>
                                            </mark>
                                        </section>

                                        <?php if (!$es_admin): ?>
                                        <nav class="acciones-rapidas">
                                            <a href="admin-editar-usuario.php?id=<?php echo $user['id_usuario']; ?>" class="btn-accion">✏️</a>
                                            <a href="eliminar-usuario.php?id=<?php echo $user['id_usuario']; ?>" class="btn-accion" onclick="return confirm('¿Eliminar?')">🗑️</a>
                                        </nav>
                                        <?php endif; ?>

                                    </section>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </details>

                <?php endif; ?>
            <?php endforeach; ?>

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