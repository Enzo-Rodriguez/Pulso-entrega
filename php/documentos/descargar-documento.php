<?php
require_once "../conexion.php";

$token = $_GET["token"] ?? "";

if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    http_response_code(400);
    exit("Enlace no válido.");
}

$consulta = $conexion->prepare(
    "SELECT titulo, ruta_archivo
     FROM documento
     WHERE token_descarga = ?"
);

$consulta->bind_param("s", $token);
$consulta->execute();
$resultado = $consulta->get_result();
$documento = $resultado->fetch_assoc();
$consulta->close();

if (!$documento) {
    http_response_code(404);
    exit("Documento no encontrado.");
}

$carpetaDocumentos = dirname(__DIR__, 2)
    . DIRECTORY_SEPARATOR . "documentos_subidos";

$nombreArchivo = basename($documento["ruta_archivo"]);
$rutaFisica = $carpetaDocumentos
    . DIRECTORY_SEPARATOR . $nombreArchivo;

if (!is_file($rutaFisica)) {
    http_response_code(404);
    exit("El archivo no existe.");
}

header("Content-Type: application/octet-stream");
header(
    'Content-Disposition: attachment; filename="'
    . str_replace('"', '', basename($documento["titulo"]))
    . '"'
);
header("Content-Length: " . filesize($rutaFisica));

readfile($rutaFisica);
exit;