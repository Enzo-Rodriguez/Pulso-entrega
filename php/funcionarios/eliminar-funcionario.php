<?php

require_once "../conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    die("Método no permitido.");
}

$idFuncionario = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if ($idFuncionario === false || $idFuncionario === null || $idFuncionario < 1) {
    http_response_code(400);
    die("La ID del funcionario no es válida.");
}

$resultado = $conexion->query(
    "SELECT id_persona FROM funcionario WHERE id_funcionario = $idFuncionario"
);
$funcionario = $resultado->fetch_assoc();

if (!$funcionario) {
    $conexion->close();
    http_response_code(404);
    die("El funcionario no existe.");
}

$idPersona = $funcionario["id_persona"];

$conexion->begin_transaction();

try {
    // Se borra primero funcionario porque depende de persona.
    $conexion->query("DELETE FROM funcionario WHERE id_funcionario = $idFuncionario");
    $conexion->query("DELETE FROM persona WHERE id_persona = $idPersona");
    $conexion->commit();
    $conexion->close();
    header("Location: funcionario.php");
    exit;
} catch (Exception $e) {
    $conexion->rollback();
    $conexion->close();
    http_response_code(500);
    die("No se pudo eliminar el funcionario.");
}
?>
