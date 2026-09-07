<?php

require_once "conexion.php";

$mensajeError = "";
$titulo = "";

function llamar($contenido): string
{
    return htmlspecialchars((string) ($contenido ?? ""), ENT_QUOTES, "UTF-8");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titulo = trim($_POST["titulo"] ?? "");
    $archivo = $_FILES["documento"] ?? null;

    $extensionesPermitidas = ["pdf", "doc", "docx", "txt", "jpg", "jpeg", "png"];
    $tamanoMaximo = 10 * 1024 * 1024;

    if ($titulo === "" || strlen($titulo) > 200) {
        $mensajeError = "Ingrese un título de hasta 200 caracteres.";
    } elseif ($archivo === null || $archivo["error"] !== UPLOAD_ERR_OK) {
        $mensajeError = "Seleccione un archivo válido.";
    } elseif ($archivo["size"] <= 0 || $archivo["size"] > $tamanoMaximo) {
        $mensajeError = "El archivo debe pesar como máximo 10 MB.";
    } else {
        $extension = strtolower(pathinfo($archivo["name"], PATHINFO_EXTENSION));

        if (!in_array($extension, $extensionesPermitidas, true)) {
            $mensajeError = "El formato del archivo no está permitido.";
        } else {
            $carpetaDocumentos = __DIR__ . DIRECTORY_SEPARATOR . "documentos_subidos";

            if (!is_dir($carpetaDocumentos) && !mkdir($carpetaDocumentos, 0775, true)) {
                $mensajeError = "No se pudo preparar la carpeta para guardar el archivo.";
            } else {
                $nombreOriginal = pathinfo($archivo["name"], PATHINFO_FILENAME);
                $nombreSeguro = preg_replace("/[^a-zA-Z0-9_-]/", "_", $nombreOriginal);
                $nombreSeguro = substr($nombreSeguro ?: "documento", 0, 80);
                $nombreGuardado = date("YmdHis") . "_" . uniqid() . "_" . $nombreSeguro . "." . $extension;

                $rutaFisica = $carpetaDocumentos . DIRECTORY_SEPARATOR . $nombreGuardado;
                $rutaParaBase = "documentos_subidos/" . $nombreGuardado;

                if (!move_uploaded_file($archivo["tmp_name"], $rutaFisica)) {
                    $mensajeError = "No se pudo guardar el archivo seleccionado.";
                } else {
                    try {
                        $guardarDocumento = $conexion->prepare(
                            "INSERT INTO documento (titulo, ruta_archivo) VALUES (?, ?)"
                        );
                        $guardarDocumento->bind_param("ss", $titulo, $rutaParaBase);
                        $guardarDocumento->execute();
                        $guardarDocumento->close();

                        header("Location: documentos.php?carga=exitosa");
                        exit;
                    } catch (mysqli_sql_exception $error) {
                        if (is_file($rutaFisica)) {
                            unlink($rutaFisica);
                        }

                        $mensajeError = "No se pudo registrar el documento. Intente nuevamente.";
                    }
                }
            }
        }
    }
}

?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilos.css">
    <link rel="icon" type="image/png" href="recursos/logo-pulsoA.png">
    <title>PULSO - Cargar Documento</title>
</head>

<body>
    <header class="barra-superior">
        <a class="marca" href="panel.html">
            <img src="recursos/logo-pulsoA.png" alt="">
            <span>PULSO</span>
        </a>
        <nav class="navegacion" aria-label="Páginas principales">
            <a href="panel.html">Panel</a>
            <a href="pacientes.php">Pacientes</a>
            <a class="enlace-activo" href="documentos.php">Documentos</a>
            <a href="ambulancia.php">Ambulancias</a>
            <a href="index.html">Inicio</a>
        </nav>
    </header>
    <main class="pagina">
        <section class="encabezado-pagina">
            <div>
                <p class="subtitulo">Registro asistencial</p>
                <h1>Cargar documento</h1>
                <p>Seleccione el documento que desea cargar.</p>
            </div>
        </section>
        <section class="tarjeta">
            <?php if ($mensajeError !== ""): ?>
                <p class="estado estado-critico" role="alert"><?= llamar($mensajeError) ?></p>
            <?php endif; ?>

            <form class="formulario" action="cargar-documento.php" method="post" enctype="multipart/form-data">
                <label for="titulo">
                    Título del documento
                    <input type="text" id="titulo" name="titulo" value="<?= llamar($titulo) ?>" maxlength="200" required>
                </label>

                <label for="documento">
                    Seleccione el archivo
                    <input type="file" id="documento" name="documento" accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png" required>
                </label>

                <div class="acciones">
                    <a class="boton boton-secundario" href="documentos.php">Cancelar</a>
                    <button class="boton" type="submit">Cargar documento</button>
                </div>
            </form>
        </section>
    </main>
</body>

</html>
