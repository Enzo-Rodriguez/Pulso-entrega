<?php

require_once "conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    die("Método no permitido.");
}

$idDocumento = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if ($idDocumento === false || $idDocumento === null || $idDocumento < 1) {
    http_response_code(400);
    die("El identificador del documento no es válido.");
}

$stmt = $conexion->prepare(
    "DELETE FROM documento WHERE id_documento = ?"
);

$stmt->bind_param("i", $idDocumento);

$stmt->execute();

$stmt->close();
$conexion->close();

header("Location: documentos.php");
exit;
?>