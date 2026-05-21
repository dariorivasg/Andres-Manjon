<?php
//admin-nuevo-usuario.php
$archivo_db = 'biblioteca.db';
$mensaje = "";
$tipo_alerta = ""; 

// Limpia tildes y convierte Ñ en N
function limpiarTexto($texto) {
    $originales = ['á','é','í','ó','ú','Á','É','Í','Ó','Ú','ñ','Ñ','ü','Ü'];
    $reemplazos = ['a','e','i','o','u','A','E','I','O','U','n','N','u','U'];
    return str_replace($originales, $reemplazos, $texto);
}

// Obtiene letras específicas sin tildes ni caracteres raros
function obtenerLetra($texto, $posicion) {
    $texto = limpiarTexto($texto);
    $texto = str_replace(' ', '', $texto); // Quitar espacios
    
    // mb_substr para seguridad con strings
    $letra = mb_substr($texto, $posicion - 1, 1); 
    
    if ($letra === false || $letra === "") {
        $letra = mb_substr($texto, -1, 1); // Si es corto, coge la última
    }
    return strtoupper($letra);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $db = new PDO("sqlite:" . $archivo_db);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $nombre = $_POST['nombre'];
        $apellido = $_POST['apellido'];
        $rol_form = $_POST['rol'];
        $password = $_POST['password'];
        $curso = isset($_POST['curso']) ? $_POST['curso'] : ""; 

        // Generamos el username (aquí sí solemos quitar tildes también)
        $user_limpio = strtolower(limpiarTexto($nombre) . "." . limpiarTexto($apellido));
        
        $sufijo_user = "";
        $sufijo_carnet_prefijo = "";
        $id_rol = 0;

        if ($rol_form === 'alumno') {
            $id_rol = 1; 
            $sufijo_user = ($curso === 'inf') ? "inf" : $curso . "pri";
            $sufijo_carnet_prefijo = ($curso === 'inf') ? "INF" : $curso . "PRI";
        } elseif ($rol_form === 'profesor') {
            $id_rol = 2; 
            $sufijo_user = "prof";
            $sufijo_carnet_prefijo = "PRO";
        } else {
            $id_rol = 3; 
            $sufijo_user = "adm";
            $sufijo_carnet_prefijo = "ADM";
        }

        $username = "$user_limpio.$sufijo_user";

        // --- LÓGICA DE 4 LETRAS SIN TILDES NI Ñ ---
        $v1 = obtenerLetra($nombre, 1) . obtenerLetra($nombre, 2) . obtenerLetra($apellido, 1) . obtenerLetra($apellido, 2);
        $v2 = obtenerLetra($nombre, 1) . obtenerLetra($nombre, 3) . obtenerLetra($apellido, 1) . obtenerLetra($apellido, 3);
        
        $intentos = [$v1, $v2];
        $codigo_final = "";

        foreach ($intentos as $letras) {
            $temp_codigo = "26-" . strtoupper($sufijo_carnet_prefijo) . "-" . $letras;
            $check = $db->prepare("SELECT COUNT(*) FROM Usuario WHERE codigo_de_carnet = ?");
            $check->execute([$temp_codigo]);
            if ($check->fetchColumn() == 0) {
                $codigo_final = $temp_codigo;
                break;
            }
        }

        if ($codigo_final === "") {
            $codigo_final = "26-" . strtoupper($sufijo_carnet_prefijo) . "-" . $v1 . "2";
        }
        
        $codigo_carnet = $codigo_final;

        // Inserción en BD (Aquí se guardan con tildes originales para que el nombre se vea bien)
        $db->beginTransaction();

        if ($rol_form === 'alumno') {
            $sql = "INSERT INTO Alumnado (nombre, apellidos, clase, codigo_de_carnet, estado_de_sancion) 
                    VALUES (:nom, :ape, :clase, :carnet, 'Ninguna')";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':nom' => $nombre,
                ':ape' => $apellido,
                ':clase' => ($curso === 'inf') ? 'Infantil' : "$curso" . "º Primaria",
                ':carnet' => $codigo_carnet
            ]);
        } 
        
        $sql_user = "INSERT INTO Usuario (nombre, username, contrasenia, id_rol, codigo_de_carnet) 
                     VALUES (:nom, :user, :pass, :rol, :carnet)";
        $stmt_user = $db->prepare($sql_user);
        $stmt_user->execute([
            ':nom' => $nombre . " " . $apellido,
            ':user' => $username,
            ':pass' => $password,
            ':rol' => $id_rol,
            ':carnet' => $codigo_carnet
        ]);

        $db->commit();
        $mensaje = "Registro completado: Código [$codigo_carnet]";
        $tipo_alerta = "exito";

    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        $mensaje = "Error: " . $e->getMessage();
        $tipo_alerta = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Usuario</title>
    <link rel="stylesheet" href="estilos2.css">
    <script>
        function actualizarFormulario() {
            const rol = document.getElementById('rol').value;
            const seccionCurso = document.getElementById('seccion-curso');
            seccionCurso.style.display = (rol === 'alumno') ? 'block' : 'none';
        }
    </script>
</head>
<body>
    <div class="boton-flotante-derecha">
        <button type="button" class="btn-volver" onclick="window.location.href='admin-usuarios.php'">
            <span style="margin-right: 8px;">←</span> Volver a Usuarios
        </button>
    </div>

    <main class="contenedor-principal">
        <section class="tarjeta-formulario">
            <header>
                <h2>Nuevo Usuario</h2>
            </header>

            <?php if ($mensaje): ?>
                <div style="padding: 10px; margin-bottom: 15px; border-radius: 5px; 
                     background-color: <?= $tipo_alerta == 'exito' ? '#d4edda' : '#f8d7da' ?>; 
                     color: <?= $tipo_alerta == 'exito' ? '#155724' : '#721c24' ?>;">
                    <?= $mensaje ?>
                </div>
            <?php endif; ?>

            <form action="admin-nuevo-usuario.php" method="POST">
                <div class="fila-doble">
                    <fieldset>
                        <label for="nombre">Nombre *</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </fieldset>
                    <fieldset>
                        <label for="apellido">Apellido *</label>
                        <input type="text" id="apellido" name="apellido" required>
                    </fieldset>
                </div>

                <fieldset>
                    <label for="rol">Rol</label>
                    <select id="rol" name="rol" onchange="actualizarFormulario()" required>
                        <option value="">Selecciona una opción</option>
                        <option value="alumno">Alumno</option>
                        <option value="profesor">Profesor</option>
                        <option value="admin">Administrador</option>
                    </select>
                </fieldset>

                <fieldset id="seccion-curso" style="display: none;">
                    <label for="curso">Curso</label>
                    <select id="curso" name="curso">
                        <option value="inf">Infantil</option>
                        <option value="1">1º Primaria</option>
                        <option value="2">2º Primaria</option>
                        <option value="3">3º Primaria</option>
                        <option value="4">4º Primaria</option>
                        <option value="5">5º Primaria</option>
                        <option value="6">6º Primaria</option>
                    </select>
                </fieldset>

                <fieldset>
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required>
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