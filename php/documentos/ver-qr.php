<?php

require_once "../conexion.php";

$idDocumento = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if ($idDocumento === false || $idDocumento === null || $idDocumento < 1) {
    http_response_code(400);
    die("El documento no es válido.");
}

$buscarDocumento = $conexion->prepare(
    "SELECT titulo, token_descarga
     FROM documento
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
        "UPDATE documento SET token_descarga = ? WHERE id_documento = ? AND (token_descarga IS NULL OR token_descarga = '')"
    );
    $guardarToken->bind_param("si", $documento["token_descarga"], $idDocumento);
    $guardarToken->execute();
    $guardarToken->close();
    // Si se abrió el QR en dos pestañas, ambas deben usar el mismo enlace guardado.
    $buscarToken = $conexion->prepare("SELECT token_descarga FROM documento WHERE id_documento = ?");
    $buscarToken->bind_param("i", $idDocumento);
    $buscarToken->execute();
    $documento["token_descarga"] = $buscarToken->get_result()->fetch_column();
    $buscarToken->close();
}

$usaHttps = !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off";
$protocolo = $usaHttps ? "https" : "http";
$servidor = $_SERVER["HTTP_HOST"] ?? "localhost";
$carpeta = rtrim(dirname($_SERVER["SCRIPT_NAME"] ?? "", 3), "/\\");
$urlArchivo = $protocolo . "://" . $servidor . $carpeta
    . "/acceso-documentacion.html?token=" . urlencode($documento["token_descarga"]);
$hostDelQr = trim(strtolower(parse_url($protocolo . "://" . $servidor, PHP_URL_HOST) ?: ""), "[]");
$esDireccionLocal = in_array($hostDelQr, ["localhost", "127.0.0.1", "::1"], true);

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
        <p>Documentación informativa para consultar desde un celular.</p>

        <div id="codigoQR" class="codigo-qr" aria-label="Código QR para abrir el documento"></div>
        <p id="errorQr" role="alert" hidden>No se pudo cargar el generador del código QR. Revisá la conexión y recargá esta página. Podés usar «Abrir vista del documento» mientras tanto.</p>

        <p>Escaneá este código para ver el documento y acceder a la encuesta de satisfacción.</p>
        <?php if ($esDireccionLocal): ?>
          <p class="aviso-qr-local" role="note">Este QR usa localhost y solo funciona en esta computadora. Para escanearlo con un celular, abrí esta página desde la dirección del servidor o desde la IP de esta computadora accesible por el celular.</p>
        <?php endif; ?>
        <div class="acciones acciones-qr">
          <a class="boton" href="<?= escapar($urlArchivo) ?>" target="_blank" rel="noopener noreferrer">Abrir vista del documento</a>
          <a class="boton boton-secundario" href="documentos.php">Volver</a>
        </div>
      </section>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
      if (typeof QRCode === "undefined") {
        document.getElementById("errorQr").hidden = false;
      } else {
        new QRCode(document.getElementById("codigoQR"), {
          text: <?= json_encode($urlArchivo, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
          width: 250,
          height: 250,
          correctLevel: QRCode.CorrectLevel.M
        });
      }
    </script>
  </body>
</html>
