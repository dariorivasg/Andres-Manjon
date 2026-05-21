<?php
//eliminar-usuario.php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] != 3) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $db = new PDO('sqlite:biblioteca.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $db->prepare("SELECT codigo_de_carnet FROM Usuario WHERE id_usuario = :id");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $db->prepare("DELETE FROM Alumnado WHERE codigo_de_carnet = ?")->execute([$user['codigo_de_carnet']]);
            $db->prepare("DELETE FROM Usuario WHERE id_usuario = ?")->execute([$id]);
        }
        header("Location: admin-usuarios.php");
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    header("Location: admin-usuarios.php");
} 