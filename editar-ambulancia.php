<?php

require_once "conexion.php";

$matricula = trim($_GET["matricula"] ?? "");

if ($matricula === "") {
    http_response_code(400);
    die("La matrícula no es válida.");
}

$resultado = $conexion->query(
    "SELECT matricula, id_estado_ambulancia, marca, modelo, capacidad
     FROM ambulancia
     WHERE matricula = '$matricula'"
);
$ambulancia = $resultado->fetch_assoc();

if ($ambulancia === null) {
    http_response_code(404);
    die("Ambulancia no encontrada.");
}

$mensajeError = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $marca = trim($_POST["marca"] ?? "");
    $modelo = trim($_POST["modelo"] ?? "");
    $capacidad = filter_input(INPUT_POST, "capacidad", FILTER_VALIDATE_INT);
    $idEstado = filter_input(INPUT_POST, "id_estado_ambulancia", FILTER_VALIDATE_INT);

    if (
        $marca === "" || $modelo === "" ||
        $capacidad === false || $capacidad < 1 ||
        $idEstado === false || $idEstado === null || $idEstado < 1
    ) {
        $mensajeError = "Complete correctamente todos los campos.";
    } else {
        try {
            $conexion->query(
                "UPDATE ambulancia
                 SET marca = '$marca', modelo = '$modelo', capacidad = $capacidad,
                     id_estado_ambulancia = $idEstado
                 WHERE matricula = '$matricula'"
            );

            header("Location: ficha-ambulancia.php?matricula=" . urlencode($matricula));
            exit;
        } catch (mysqli_sql_exception $error) {
            $mensajeError = "No se pudieron guardar los cambios.";
        }
    }
}

$resultadoEstados = $conexion->query(
    "SELECT id_estado_ambulancia, nombre
     FROM estado_ambulancia
     ORDER BY id_estado_ambulancia"
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
    <title>PULSO - Editar ambulancia</title>
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
        <a href="documentos.html">Documentos</a>
        <a class="enlace-activo" href="ambulancia.php">Ambulancias</a>
        <a href="index.html">Inicio</a>
      </nav>
    </header>

    <main class="pagina">
      <section class="encabezado-pagina">
        <div>
          <p class="subtitulo">Gestión de unidades</p>
          <h1>Editar ambulancia</h1>
          <p>Actualice los datos de la unidad <?= escapar($matricula) ?>.</p>
        </div>
      </section>

      <section class="tarjeta">
        <?php if ($mensajeError !== ""): ?>
          <p class="estado estado-critico" role="alert"><?= $mensajeError ?></p>
        <?php endif; ?>

        <form class="formulario formulario-dos-columnas" action="editar-ambulancia.php?matricula=<?= urlencode($matricula) ?>" method="post">
          <label for="matricula">
            Matrícula
            <input id="matricula" type="text" value="<?= escapar($matricula) ?>" readonly>
          </label>

          <label for="estado">
            Estado
            <select id="estado" name="id_estado_ambulancia" required>
              <?php foreach ($estados as $estado): ?>
                <option
                  value="<?= $estado["id_estado_ambulancia"] ?>"
                  <?= (int) $ambulancia["id_estado_ambulancia"] === (int) $estado["id_estado_ambulancia"] ? "selected" : "" ?>
                ><?= escapar($estado["nombre"]) ?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <label for="marca">
            Marca
            <input id="marca" type="text" name="marca" value="<?= escapar($ambulancia["marca"]) ?>" maxlength="50" required>
          </label>

          <label for="modelo">
            Modelo
            <input id="modelo" type="text" name="modelo" value="<?= escapar($ambulancia["modelo"]) ?>" maxlength="50" required>
          </label>

          <label for="capacidad">
            Capacidad
            <input id="capacidad" type="number" name="capacidad" value="<?= escapar($ambulancia["capacidad"]) ?>" min="1" required>
          </label>

          <div class="acciones campo-completo">
            <a class="boton boton-secundario" href="ficha-ambulancia.php?matricula=<?= urlencode($matricula) ?>">Cancelar</a>
            <button class="boton" type="submit">Guardar cambios</button>
          </div>
        </form>
      </section>
    </main>
  </body>
</html>
