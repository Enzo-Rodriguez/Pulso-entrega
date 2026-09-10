<?php

require_once "../conexion.php";

$mensajeError = "";
$nombre = "";
$descripcion = "";

function escapar($valor): string
{
    return htmlspecialchars((string) ($valor ?? ""), ENT_QUOTES, "UTF-8");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = strtoupper(trim($_POST["nombre"] ?? ""));
    $descripcion = trim($_POST["descripcion"] ?? "");

    if ($nombre === "" || $descripcion === "") {
        $mensajeError = "Complete correctamente todos los campos.";
    } else {
        try {
            $nombreSeguro = $conexion->real_escape_string($nombre);
            $descripcionSegura = $conexion->real_escape_string($descripcion);

            $conexion->query(
                "INSERT INTO equipamiento (nombre, descripcion)
                 VALUES ('$nombreSeguro', '$descripcionSegura')"
            );

            header("Location: equipamientos.php?alta=exitosa");
            exit;
        } catch (mysqli_sql_exception $error) {
            $mensajeError = $error->getCode() === 1062
                ? "Ya existe un equipamiento con ese nombre."
                : "No se pudo registrar el equipamiento.";
        }
    }
}

?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PULSO - Nuevo equipamiento</title>
    <link rel="stylesheet" href="../../css/estilos.css">
    <link rel="icon" type="image/png" href="../../recursos/logo-pulsoA.png">
  </head>
  <body>
    <header class="barra-superior">
      <a class="marca" href="../../panel.html">
        <img src="../../recursos/logo-pulsoA.png" alt="">
        <span>PULSO</span>
      </a>
      <nav class="navegacion" aria-label="Páginas principales">
        <a href="../../panel.html">Panel</a>
        <a href="../pacientes/pacientes.php">Pacientes</a>
        <a href="../funcionarios/funcionario.php">Funcionarios</a>
        <a href="../documentos/documentos.php">Documentos</a>
        <a href="../ambulancias/ambulancia.php">Ambulancias</a>
        <a class="enlace-activo" href="../equipamientos/equipamientos.php">Equipamientos</a>
        <a href="../../index.html">Inicio</a>
      </nav>
    </header>

    <main class="pagina">
      <section class="encabezado-pagina">
        <div>
          <p class="subtitulo">Gestión de unidades</p>
          <h1>Nuevo equipamiento</h1>
          <p>Complete los datos del equipamiento.</p>
        </div>
      </section>

      <section class="tarjeta">
        <?php if ($mensajeError !== ""): ?>
          <p class="estado estado-critico" role="alert"><?= escapar($mensajeError) ?></p>
        <?php endif; ?>

        <form class="formulario formulario-dos-columnas" action="nuevo-equipamientos.php" method="post">
          <label for="nombre">
            Nombre
            <input id="nombre" type="text" name="nombre" value="<?= escapar($nombre) ?>" maxlength="100" required>
          </label>

          <label for="descripcion">
            Descripción
            <input id="descripcion" type="text" name="descripcion" value="<?= escapar($descripcion) ?>" maxlength="255" required>
          </label>

          <div class="acciones campo-completo">
            <a class="boton boton-secundario" href="../equipamientos/equipamientos.php">Cancelar</a>
            <button class="boton" type="submit">Registrar equipamiento</button>
          </div>
        </form>
      </section>
    </main>
  </body>
</html>
