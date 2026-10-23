<?php

// El HTML consulta los datos por AJAX; ver=1 muestra el archivo y descargar=1 lo descarga.
$mostrarInformacion = ($_GET["informacion"] ?? "") === "1";
header("Cache-Control: no-store");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");

function errorDocumento(int $codigo, string $mensaje): void
{
    global $mostrarInformacion;
    http_response_code($codigo);
    header("Content-Type: " . ($mostrarInformacion ? "application/json" : "text/plain") . "; charset=UTF-8");
    echo $mostrarInformacion ? json_encode(["error" => $mensaje], JSON_UNESCAPED_UNICODE) : $mensaje;
    exit;
}

if (!in_array($_SERVER["REQUEST_METHOD"] ?? "", ["GET", "HEAD"], true)) {
    header("Allow: GET, HEAD");
    errorDocumento(405, "Método no permitido.");
}

$token = $_GET["token"] ?? "";
if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    errorDocumento(400, "El enlace no es válido. Volvé a escanear el código QR.");
}

// Evita devolver texto en lugar de JSON si la conexión compartida falla.
ob_start(function () use ($mostrarInformacion): string {
    return $mostrarInformacion
        ? json_encode(["error" => "No se pudo conectar con la base de datos."], JSON_UNESCAPED_UNICODE)
        : "No se pudo conectar con la base de datos.";
});
require_once __DIR__ . "/../conexion.php";
ob_end_clean();

try {
    $consulta = $conexion->prepare(
        "SELECT titulo, ruta_archivo FROM documento WHERE token_descarga = ?"
    );
    $consulta->bind_param("s", $token);
    $consulta->execute();
    $documento = $consulta->get_result()->fetch_assoc();
    $consulta->close();
} catch (mysqli_sql_exception $error) {
    errorDocumento(500, "No se pudo consultar el documento. Intentá nuevamente.");
}

if (!$documento) {
    errorDocumento(404, "Este documento no está disponible. Consultá con el personal del hospital.");
}

$carpetaDocumentos = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "documentos_subidos";
$nombreArchivo = basename($documento["ruta_archivo"]);
$rutaFisica = $carpetaDocumentos . DIRECTORY_SEPARATOR . $nombreArchivo;
if (!is_file($rutaFisica) || !is_readable($rutaFisica)) {
    errorDocumento(404, "El archivo no está disponible. Consultá con el personal del hospital.");
}

$extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
$tiposArchivo = [
    "pdf" => "application/pdf",
    "jpg" => "image/jpeg",
    "jpeg" => "image/jpeg",
    "png" => "image/png",
    "txt" => "text/plain; charset=UTF-8",
    "doc" => "application/msword",
    "docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
];
if (!isset($tiposArchivo[$extension])) {
    errorDocumento(415, "El formato de este archivo no está permitido.");
}

$enlace = "php/documentos/descargar-documento.php?token=" . rawurlencode($token);
if ($mostrarInformacion) {
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode(["documento" => [
        "titulo" => $documento["titulo"],
        "extension" => $extension,
        "tamano_bytes" => filesize($rutaFisica),
        "url_ver" => $enlace . "&ver=1",
        "url_descarga" => $enlace . "&descargar=1",
    ]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Los QR anteriores también llegan ahora al portal, sin dejar de identificar su documento.
if (($_GET["ver"] ?? "") !== "1" && ($_GET["descargar"] ?? "") !== "1") {
    header("Location: ../../acceso-documentacion.html?token=" . rawurlencode($token));
    exit;
}

$nombreDescarga = trim($documento["titulo"]);
if (strtolower(pathinfo($nombreDescarga, PATHINFO_EXTENSION)) !== $extension) {
    $nombreDescarga .= "." . $extension;
}
$nombreDescarga = str_replace(["\"", "\r", "\n"], "", basename($nombreDescarga));
$disposicion = ($_GET["descargar"] ?? "") === "1" ? "attachment" : "inline";
header("Content-Type: " . $tiposArchivo[$extension]);
header('Content-Disposition: ' . $disposicion . '; filename="documento.' . $extension
    . '"; filename*=UTF-8\'\'' . rawurlencode($nombreDescarga));
header("Content-Length: " . filesize($rutaFisica));
readfile($rutaFisica);
exit;
