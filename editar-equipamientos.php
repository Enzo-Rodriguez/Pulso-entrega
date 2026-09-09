<?php

require_once "conexion.php";

$equipamientos = trim($_GET["equipamiento"] ?? "");

if ($equipamientos === "") {
    http_response_code(400);
    die("El equipamiento no es válido.");
}

$resultado = $conexion->query(
    "SELECT id_equipamiento, nombre, descripcion
     FROM equipamiento
     WHERE id_equipamiento = '$equipamientos'"
);
$equipamiento = $resultado->fetch_assoc();

if ($equipamiento === null) {
    http_response_code(404);
    die("Equipamiento no encontrado.");
}

$mensajeError = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");

    if (
        $nombre === "" || $descripcion === "" || $estado === ""
    ) {
        $mensajeError = "Complete correctamente todos los campos.";
    } else {
        try {
            $conexion->query(
                "UPDATE equipamiento
                 SET nombre = '$nombre', descripcion = '$descripcion', id_equipamiento = '$estado'
                 WHERE id_equipamiento = '$equipamientos'"
            );

            header("Location: equipamientos.php");
            exit;
        } catch (mysqli_sql_exception $error) {
            $mensajeError = "No se pudieron guardar los cambios.";
        }
    }
}

$resultadoEstados = $conexion->query(
    "SELECT id_equipamiento, nombre
     FROM id_equipamiento
     ORDER BY id_equipamiento"
);
$estados = $resultadoEstados->fetch_all(MYSQLI_ASSOC);

function escapar($valor): string
{
    return htmlspecialchars((string) ($valor ?? ""), ENT_QUOTES, "UTF-8");
}

?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PULSO - Editar equipamiento</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="icon" type="image/png" href="recursos/logo-pulsoA.png">
  </head>
  <body>
    <header class="barra-superior">
      <a class="nombre" href="panel.html">
        <img src="recursos/logo-pulsoA.png" alt="">
        <span>PULSO</span>
      </a>
      <nav class="navegacion" aria-label="Páginas principales">
        <a href="panel.html">Panel</a>
        <a href="pacientes.php">Pacientes</a>
        <a href="funcionario.php">Funcionarios</a>
        <a href="documentos.php">Documentos</a>
        <a class="enlace-activo" href="ambulancia.php">Ambulancias</a>
        <a href="equipamientos.php">Equipamientos</a>
        <a href="index.html">Inicio</a>
      </nav>
    </header>

    <main class="pagina">
      <section class="encabezado-pagina">
        <div>
          <p class="subtitulo">Gestión de unidades</p>
          <h1>Editar equipamiento</h1>
          <p>Actualice los datos del equipamiento <?= escapar($equipamiento["id_equipamiento"]) ?>.</p>
        </div>
      </section>

      <section class="tarjeta">
        <?php if ($mensajeError !== ""): ?>
          <p class="estado estado-critico" role="alert"><?= $mensajeError ?></p>
        <?php endif; ?>

        <form class="formulario formulario-dos-columnas" action="editar-equipamientos.php?id_equipamiento=<?= urlencode($equipamiento["id_equipamiento"]) ?>" method="post">
          <label for="equipamientos">
            Nombre
            <input id="equipamientos" type="text" value="<?= escapar($equipamiento["id_equipamiento"]) ?>" readonly>
          </label>

          <label for="estado">
            Estado
            <select id="estado" name="id_equipamiento" required>
              <?php foreach ($estados as $estado): ?>
                <option
                  value="<?= $estado["id_equipamiento"] ?>"
                  <?= (int) $equipamiento["id_estado_equipamiento"] === (int) $estado["id_equipamiento"] ? "selected" : "" ?>
                ><?= escapar($estado["nombre"]) ?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <label for="nombre">
            Nombre
            <input id="nombre" type="text" name="nombre" value="<?= escapar($equipamiento["nombre"]) ?>" maxlength="50" required>
          </label>

          <label for="descripcion">
            Descripcion
            <input id="descripcion" type="text" name="descripcion" value="<?= escapar($equipamiento["descripcion"]) ?>" maxlength="50" required>
          </label>


          <div class="acciones campo-completo">
            <a class="boton boton-secundario" href="ficha-equipamiento.php?id_equipamiento=<?= urlencode($equipamiento["id_equipamiento"]) ?>">Cancelar</a>
            <button class="boton" type="submit">Guardar cambios</button>
          </div>
        </form>
      </section>
    </main>
  </body>
</html>
