<?php
require_once "conexion.php";

if (isset($_GET ["id"])){
    $id=$_GET["id"];
    $sql="DELETE FROM paciente WHERE id_paciente='$id'";
    $stmt=$conexion->prepare($sql);
    if ($stmt->execute()){
        header("Location: pacientes.php");
        $sql="DELETE FROM persona WHERE id_persona='$id'";
        $stmt=$conexion->prepare($sql);
        $stmt->execute();
        $stmt->close();
        exit;
    
    } else {
        echo "Error al eliminar el paciente.";
    }
    $stmt->close();
}
$conexion->close();
?>