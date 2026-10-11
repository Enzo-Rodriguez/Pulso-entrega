<?php
require_once "../conexion.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    exit("Documento no válido.");
}

$consulta = $conexion->prepare(
    "SELECT titulo, token_descarga
     FROM documento
     WHERE id_documento = ?"
);

$consulta->bind_param("i", $id);
$consulta->execute();

$resultado = $consulta->get_result();
$documento = $resultado->fetch_assoc();

$consulta->close();

if (!$documento || empty($documento["token_descarga"])) {
    exit("Documento no encontrado o sin token. Generá su token primero.");
}

$urlArchivo = "https://" . $_SERVER["HTTP_HOST"]
    . dirname($_SERVER["SCRIPT_NAME"])
    . "/descargar-documento.php?token="
    . urlencode($documento["token_descarga"]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código QR del documento</title>
</head>
<body>
    <h1>Código QR del documento</h1>

    <h2><?= htmlspecialchars($documento["titulo"], ENT_QUOTES, "UTF-8") ?></h2>

    <div id="codigoQR"></div>

    <p>Escaneá este código con tu celular para abrir el documento.</p>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <script>
        new QRCode(document.getElementById("codigoQR"), {
            text: <?= json_encode($urlArchivo) ?>,
            width: 250,
            height: 250
        });
    </script>
</body>
</html>