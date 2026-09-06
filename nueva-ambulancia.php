<?php

require_once "conexion.php";

$mensajeError = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $matricula = strtoupper(trim($_POST["matricula"] ?? ""));
    $marca = trim($_POST["marca"] ?? "");
    $modelo = trim($_POST["modelo"] ?? "");
    $capacidad = filter_input(INPUT_POST, "capacidad", FILTER_VALIDATE_INT);
    $idEstado = filter_input(INPUT_POST, "id_estado_ambulancia", FILTER_VALIDATE_INT);

    if (
        $matricula === "" || $marca === "" || $modelo === "" ||
        $capacidad === false || $capacidad < 1 ||
        $idEstado === false || $idEstado === null || $idEstado < 1
    ) {
        $mensajeError = "Complete correctamente todos los campos.";
    } else {
        try {
            $conexion->query(
                "INSERT INTO ambulancia
                    (matricula, id_estado_ambulancia, marca, modelo, capacidad)
                 VALUES
                    ('$matricula', $idEstado, '$marca', '$modelo', $capacidad)"
            );

            header("Location: ambulancia.php");
            exit;
        } catch (mysqli_sql_exception $error) {
            $mensajeError = $error->getCode() === 1062
                ? "Ya existe una ambulancia con esa matrícula."
                : "No se pudo registrar la ambulancia.";
        }
    }
}

$resultadoEstados = $conexion->query(
    "SELECT id_estado_ambulancia, nombre
     FROM estado_ambulancia
     ORDER BY id_estado_ambulancia"
);
$estados = $resultadoEstados->fetch_all(MYSQLI_ASSOC);

?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PULSO - Nueva ambulancia</title>
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
        <a href="documentos.php">Documentos</a>
        <a class="enlace-activo" href="ambulancia.php">Ambulancias</a>
        <a href="index.html">Inicio</a>
      </nav>
    </header>

    <main class="pagina">
      <section class="encabezado-pagina">
        <div>
          <p class="subtitulo">Gestión de unidades</p>
          <h1>Nueva ambulancia</h1>
          <p>Complete los datos de la unidad.</p>
        </div>
      </section>

      <section class="tarjeta">
        <?php if ($mensajeError !== ""): ?>
          <p class="estado estado-critico" role="alert"><?= $mensajeError ?></p>
        <?php endif; ?>

        <form class="formulario formulario-dos-columnas" action="nueva-ambulancia.php" method="post">
          <label for="matricula">
            Matrícula
            <input id="matricula" type="text" name="matricula" maxlength="20" required>
          </label>

          <label for="estado">
            Estado
            <select id="estado" name="id_estado_ambulancia" required>
              <option value="" disabled selected>Seleccionar</option>
              <?php foreach ($estados as $estado): ?>
                <option value="<?= $estado["id_estado_ambulancia"] ?>"><?= $estado["nombre"] ?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <label for="marca">
            Marca
            <input id="marca" type="text" name="marca" maxlength="50" required>
          </label>

          <label for="modelo">
            Modelo
            <input id="modelo" type="text" name="modelo" maxlength="50" required>
          </label>

          <label for="capacidad">
            Capacidad
            <input id="capacidad" type="number" name="capacidad" min="1" required>
          </label>

          <div class="acciones campo-completo">
            <a class="boton boton-secundario" href="ambulancia.php">Cancelar</a>
            <button class="boton" type="submit">Registrar ambulancia</button>
          </div>
        </form>
      </section>
    </main>
  </body>
</html>
