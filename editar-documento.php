<?php

require_once "conexion.php";

$mensajeError = "";
$idDocumento = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

function llamar($contenido): string
{
    return htmlspecialchars((string) ($contenido ?? ""), ENT_QUOTES, "UTF-8");
}

if ($idDocumento === false || $idDocumento === null || $idDocumento < 1) {
    http_response_code(400);
    die("El identificador del documento no es válido.");
}

$buscarDocumento = $conexion->prepare(
    "SELECT titulo, ruta_archivo FROM documento WHERE id_documento = ?"
);
$buscarDocumento->bind_param("i", $idDocumento);
$buscarDocumento->execute();
$buscarDocumento->bind_result($tituloGuardado, $rutaArchivoActual);
$documentoExiste = $buscarDocumento->fetch();
$buscarDocumento->close();

if (!$documentoExiste) {
    http_response_code(404);
    die("El documento no existe.");
}

$titulo = $tituloGuardado;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titulo = trim($_POST["titulo"] ?? "");
    $archivo = $_FILES["documento"] ?? null;
    $hayArchivoNuevo = $archivo !== null && $archivo["error"] !== UPLOAD_ERR_NO_FILE;

    $extensionesPermitidas = ["pdf", "doc", "docx", "txt", "jpg", "jpeg", "png"];
    $tamanoMaximo = 10 * 1024 * 1024;
    $extension = $hayArchivoNuevo
        ? strtolower(pathinfo($archivo["name"], PATHINFO_EXTENSION))
        : "";

    if ($titulo === "" || strlen($titulo) > 200) {
        $mensajeError = "Ingrese un título de hasta 200 caracteres.";
    } elseif ($hayArchivoNuevo && $archivo["error"] !== UPLOAD_ERR_OK) {
        $mensajeError = "Seleccione un archivo válido.";
    } elseif ($hayArchivoNuevo && ($archivo["size"] <= 0 || $archivo["size"] > $tamanoMaximo)) {
        $mensajeError = "El archivo debe pesar como máximo 10 MB.";
    } elseif ($hayArchivoNuevo && !in_array($extension, $extensionesPermitidas, true)) {
        $mensajeError = "El formato del archivo no está permitido.";
    } else {
        $rutaFisicaNueva = "";
        $rutaParaBase = $rutaArchivoActual;

        if ($hayArchivoNuevo) {
            $carpetaDocumentos = __DIR__ . DIRECTORY_SEPARATOR . "documentos_subidos";

            if (!is_dir($carpetaDocumentos) && !mkdir($carpetaDocumentos, 0775, true)) {
                $mensajeError = "No se pudo preparar la carpeta para guardar el archivo.";
            } else {
                $nombreOriginal = pathinfo($archivo["name"], PATHINFO_FILENAME);
                $nombreSeguro = preg_replace("/[^a-zA-Z0-9_-]/", "_", $nombreOriginal);
                $nombreSeguro = substr($nombreSeguro ?: "documento", 0, 80);
                $nombreGuardado = date("YmdHis") . "_" . uniqid() . "_" . $nombreSeguro . "." . $extension;

                $rutaFisicaNueva = $carpetaDocumentos . DIRECTORY_SEPARATOR . $nombreGuardado;
                $rutaParaBase = "documentos_subidos/" . $nombreGuardado;

                if (!move_uploaded_file($archivo["tmp_name"], $rutaFisicaNueva)) {
                    $mensajeError = "No se pudo guardar el archivo seleccionado.";
                }
            }
        }

        if ($mensajeError === "") {
            try {
                $actualizarDocumento = $conexion->prepare(
                    "UPDATE documento SET titulo = ?, ruta_archivo = ? WHERE id_documento = ?"
                );
                $actualizarDocumento->bind_param("ssi", $titulo, $rutaParaBase, $idDocumento);
                $actualizarDocumento->execute();
                $actualizarDocumento->close();

                if ($hayArchivoNuevo) {
                    $rutaFisicaAnterior = __DIR__ . DIRECTORY_SEPARATOR . "documentos_subidos"
                        . DIRECTORY_SEPARATOR . basename($rutaArchivoActual);

                    if (is_file($rutaFisicaAnterior)) {
                        unlink($rutaFisicaAnterior);
                    }
                }

                header("Location: documentos.php?actualizacion=exitosa");
                exit;
            } catch (mysqli_sql_exception $error) {
                if ($rutaFisicaNueva !== "" && is_file($rutaFisicaNueva)) {
                    unlink($rutaFisicaNueva);
                }

                $mensajeError = "No se pudo actualizar el documento. Intente nuevamente.";
            }
        }
    }
}

?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PULSO - Actualizar documento</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="icon" type="image/png" href="recursos/logo-pulsoA.png">
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
        <a href="funcionario.php">Funcionarios</a>
        <a class="enlace-activo" href="documentos.php">Documentos</a>
        <a href="ambulancia.php">Ambulancias</a>
        <a href="equipamientos.php">Equipamientos</a>
        <a href="index.html">Inicio</a>
      </nav>
    </header>

    <main class="pagina">
      <section class="encabezado-pagina">
        <div>
          <p class="subtitulo">Documentación digital</p>
          <h1>Actualizar documento</h1>
          <p>Modifique el título o seleccione un archivo para reemplazar el actual.</p>
        </div>
      </section>

      <section class="tarjeta">
        <?php if ($mensajeError !== ""): ?>
          <p class="estado estado-critico" role="alert"><?= llamar($mensajeError) ?></p>
        <?php endif; ?>

        <form class="formulario" action="editar-documento.php?id=<?= llamar($idDocumento) ?>" method="post" enctype="multipart/form-data">
          <label for="titulo">
            Título del documento
            <input id="titulo" type="text" name="titulo" value="<?= llamar($titulo) ?>" maxlength="200" required>
          </label>

          <p>
            <strong>Archivo actual:</strong>
            <a href="<?= llamar($rutaArchivoActual) ?>" target="_blank" rel="noopener">
              <?= llamar(basename($rutaArchivoActual)) ?>
            </a>
          </p>

          <label for="documento">
            Reemplazar archivo (opcional)
            <input id="documento" type="file" name="documento" accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png">
          </label>

          <div class="acciones">
            <a class="boton boton-secundario" href="documentos.php">Cancelar</a>
            <button class="boton" type="submit">Guardar cambios</button>
          </div>
        </form>
      </section>
    </main>
  </body>
</html>
