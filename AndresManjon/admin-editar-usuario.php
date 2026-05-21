<?php
//admin-editar-usuario.php
session_start();
$archivo_db = 'biblioteca.db';
$mensaje = "";
$tipo_alerta = "";

if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] != 3 && $_SESSION['usuario_rol'] != 2)) {
    header("Location: index.php");
    exit();
}

try {
    $db = new PDO("sqlite:" . $archivo_db);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (isset($_GET['id'])) {
        $id_usuario = $_GET['id'];
        $stmt = $db->prepare("SELECT u.*, a.clase FROM Usuario u LEFT JOIN Alumnado a ON u.codigo_de_carnet = a.codigo_de_carnet WHERE u.id_usuario = ?");
        $stmt->execute([$id_usuario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            header("Location: admin-usuarios.php");
            exit();
        }
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $id_usuario = $_POST['id_usuario'];
        $nombre_completo = $_POST['nombre'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $rol_id = (int)$_POST['rol'];
        $carnet_actual = $_POST['carnet_actual'];

        $sql_u = "UPDATE Usuario SET nombre = :nom, username = :user, contrasenia = :pass, id_rol = :rol WHERE id_usuario = :id";
        $stmt_u = $db->prepare($sql_u);
        $stmt_u->execute([
            ':nom' => $nombre_completo,
            ':user' => $username,
            ':pass' => $password,
            ':rol' => $rol_id,
            ':id' => $id_usuario
        ]);

        if ($rol_id == 1) {
            $clase = ($_POST['curso'] == 'inf') ? 'Infantil' : $_POST['curso'] . "º Primaria";
            $stmt_a = $db->prepare("UPDATE Alumnado SET nombre = :nom, clase = :clase WHERE codigo_de_carnet = :carnet");
            $stmt_a->execute([
                ':nom' => explode(' ', $nombre_completo)[0],
                ':clase' => $clase,
                ':carnet' => $carnet_actual
            ]);
        }

        header("Location: admin-usuarios.php?editado=1");
        exit();
    }
} catch (PDOException $e) {
    $mensaje = "Error: " . $e->getMessage();
    $tipo_alerta = "error";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario</title>
    <link rel="stylesheet" href="estilos2.css">
    <script>
        function actualizarCampos() {
            const rol = document.getElementById('rol').value;
            document.getElementById('seccion-curso').style.display = (rol == "1") ? 'block' : 'none';
        }
    </script>
</head>
<body onload="actualizarCampos()">
    <main class="contenedor-principal">
        <section class="tarjeta-formulario">
            <header><h2>Editar Usuario</h2></header>
            
            <form action="admin-editar-usuario.php" method="POST">
                <input type="hidden" name="id_usuario" value="<?= $user['id_usuario'] ?>">
                <input type="hidden" name="carnet_actual" value="<?= $user['codigo_de_carnet'] ?>">

                <fieldset>
                    <label>Nombre Completo</label>
                    <input type="text" name="nombre" value="<?= htmlspecialchars($user['nombre']) ?>" required>
                </fieldset>

                <fieldset>
                    <label>Nombre de Usuario (Login)</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                </fieldset>

                <fieldset>
                    <label>Rol</label>
                    <select id="rol" name="rol" onchange="actualizarCampos()">
                        <option value="1" <?= $user['id_rol'] == 1 ? 'selected' : '' ?>>Alumno</option>
                        <option value="2" <?= $user['id_rol'] == 2 ? 'selected' : '' ?>>Profesor</option>
                        <option value="3" <?= $user['id_rol'] == 3 ? 'selected' : '' ?>>Administrador</option>
                    </select>
                </fieldset>

                <fieldset id="seccion-curso">
                    <label>Curso (Solo alumnos)</label>
                    <select name="curso">
                        <option value="inf" <?= (isset($user['clase']) && strpos($user['clase'], 'Infantil') !== false) ? 'selected' : '' ?>>Infantil</option>
                        <?php for($i=1; $i<=6; $i++): ?>
                            <option value="<?= $i ?>" <?= (isset($user['clase']) && strpos($user['clase'], "$i"."º") !== false) ? 'selected' : '' ?>><?= $i ?>º Primaria</option>
                        <?php endfor; ?>
                    </select>
                </fieldset>

                <fieldset>
                    <label>Contraseña</label>
                    <input type="password" name="password" value="<?= htmlspecialchars($user['contrasenia']) ?>" required>
                </fieldset>

                <nav class="acciones">
                    <button type="submit" class="btn-guardar">Actualizar Datos</button>
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