<?php

require_once "../conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    die("Método no permitido.");
}

$idDocumento = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if ($idDocumento === false || $idDocumento === null || $idDocumento < 1) {
    http_response_code(400);
    die("El identificador del documento no es válido.");
}

try {
    // Busca la ruta antes de eliminar el registro para poder borrar también el archivo.
    $buscarDocumento = $conexion->prepare(
        "SELECT ruta_archivo FROM documento WHERE id_documento = ?"
    );
    $buscarDocumento->bind_param("i", $idDocumento);
    $buscarDocumento->execute();
    $buscarDocumento->bind_result($rutaArchivo);
    $documentoExiste = $buscarDocumento->fetch();
    $buscarDocumento->close();

    if (!$documentoExiste) {
        http_response_code(404);
        die("El documento no existe.");
    }

    $eliminarDocumento = $conexion->prepare(
        "DELETE FROM documento WHERE id_documento = ?"
    );
    $eliminarDocumento->bind_param("i", $idDocumento);
    $eliminarDocumento->execute();
    $eliminarDocumento->close();

    // basename impide que una ruta guardada pueda borrar archivos fuera de esta carpeta.
    $rutaFisica = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "documentos_subidos"
        . DIRECTORY_SEPARATOR . basename($rutaArchivo);

    if (is_file($rutaFisica)) {
        unlink($rutaFisica);
    }
} catch (mysqli_sql_exception $error) {
    http_response_code(500);
    die("No se pudo eliminar el documento.");
}

$conexion->close();

header("Location: documentos.php?eliminacion=exitosa");
exit;
?>
