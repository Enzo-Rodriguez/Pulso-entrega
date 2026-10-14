<?php

require_once "../conexion.php";

$idDocumento = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if ($idDocumento === false || $idDocumento === null || $idDocumento < 1) {
    http_response_code(400);
    die("El documento no es válido.");
}

$buscarDocumento = $conexion->prepare(
    "SELECT documento.titulo, documento.token_descarga,
            CONCAT_WS(' ', persona.nombres, persona.apellidos) AS paciente
     FROM documento
     LEFT JOIN paciente ON paciente.id_paciente = documento.id_paciente
     LEFT JOIN persona ON persona.id_persona = paciente.id_persona
     WHERE documento.id_documento = ?"
);
$buscarDocumento->bind_param("i", $idDocumento);
$buscarDocumento->execute();
$documento = $buscarDocumento->get_result()->fetch_assoc();
$buscarDocumento->close();

if (!$documento) {
    http_response_code(404);
    die("El documento no existe.");
}

// Los documentos anteriores a los QR reciben su token al abrir esta pantalla.
if (empty($documento["token_descarga"])) {
    $documento["token_descarga"] = bin2hex(random_bytes(32));
    $guardarToken = $conexion->prepare(
        "UPDATE documento SET token_descarga = ? WHERE id_documento = ?"
    );
    $guardarToken->bind_param("si", $documento["token_descarga"], $idDocumento);
    $guardarToken->execute();
    $guardarToken->close();
}

$usaHttps = !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off";
$protocolo = $usaHttps ? "https" : "http";
$servidor = $_SERVER["HTTP_HOST"] ?? "localhost";
$carpeta = rtrim(dirname($_SERVER["SCRIPT_NAME"] ?? ""), "/\\");
$urlArchivo = $protocolo . "://" . $servidor . $carpeta
    . "/descargar-documento.php?token=" . urlencode($documento["token_descarga"]);

function escapar($contenido): string
{
    return htmlspecialchars((string) ($contenido ?? ""), ENT_QUOTES, "UTF-8");
}

?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PULSO - Código QR</title>
    <link rel="stylesheet" href="../../css/estilos.css">
    <link rel="stylesheet" href="../../css/documentos.css">
    <link rel="icon" type="image/png" href="../../recursos/logo-pulsoA.png">
  </head>
  <body>
    <header class="barra-superior">
      <a class="marca" href="../../panel.html">
        <img src="../../recursos/logo-pulsoA.png" alt="">
        <span>PULSO</span>
      </a>
      <a class="boton boton-secundario" href="documentos.php">Volver a documentos</a>
    </header>

    <main class="pagina pagina-qr">
      <section class="tarjeta tarjeta-qr">
        <p class="subtitulo">Acceso del paciente</p>
        <h1>Código QR del documento</h1>
        <h2><?= escapar($documento["titulo"]) ?></h2>
        <p>
          Paciente:
          <strong><?= escapar($documento["paciente"] ?: "Sin asignar") ?></strong>
        </p>

        <div id="codigoQR" class="codigo-qr" aria-label="Código QR para abrir el documento"></div>

        <p>Escaneá este código con un celular para abrir el documento correspondiente.</p>
        <div class="acciones acciones-qr">
          <a class="boton" href="<?= escapar($urlArchivo) ?>" target="_blank" rel="noopener">Abrir documento</a>
          <a class="boton boton-secundario" href="documentos.php">Volver</a>
        </div>
      </section>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
      new QRCode(document.getElementById("codigoQR"), {
        text: <?= json_encode($urlArchivo, JSON_UNESCAPED_SLASHES) ?>,
        width: 250,
        height: 250
      });
    </script>
  </body>
</html>
