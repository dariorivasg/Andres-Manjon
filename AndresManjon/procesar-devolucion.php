<?php
//procesar-devolucion.php
session_start();

// 1. Verificación de seguridad: Permitir Rol 2 (Profesor) y Rol 3 (Admin)
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] != 2 && $_SESSION['usuario_rol'] != 3)) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    $id_prestamo = $_GET['id'];
    
    try {
        $db = new PDO('sqlite:biblioteca.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Iniciamos transacción para que se hagan los dos cambios o ninguno
        $db->beginTransaction();

        // 2. Buscamos el ID del libro asociado a este préstamo
        $stmt_lib = $db->prepare("SELECT id_libro FROM Prestamo WHERE id_prestamo = :id");
        $stmt_lib->execute([':id' => $id_prestamo]);
        $prestamo = $stmt_lib->fetch(PDO::FETCH_ASSOC);

        if ($prestamo) {
            $id_libro = $prestamo['id_libro'];

            // 3. Marcamos el préstamo como 'Finalizado' o 'Devuelto'
            $stmt_p = $db->prepare("UPDATE Prestamo SET estado_del_prestamo = 'Finalizado' WHERE id_prestamo = :id");
            $stmt_p->execute([':id' => $id_prestamo]);

            // 4. IMPORTANTE: Volvemos a poner el libro como 'Disponible' en el catálogo
            $stmt_l = $db->prepare("UPDATE Libro SET estado_de_actividad = 'Disponible' WHERE id_libro = :id_l");
            $stmt_l->execute([':id_l' => $id_libro]);

            $db->commit();
            
            // 5. Redirigir según quién hizo la devolución
            if ($_SESSION['usuario_rol'] == 2) {
                header("Location: profesor-devolucion.php?mensaje=devuelto");
            } else {
                header("Location: admin-prestamos.php?mensaje=devuelto");
            }
        } else {
            $db->rollBack();
            header("Location: index.php");
        }
        exit();

    } catch (Exception $e) {
        if (isset($db)) $db->rollBack();
        die("Error al devolver: " . $e->getMessage());
    }
} else {
    header("Location: index.php");
}