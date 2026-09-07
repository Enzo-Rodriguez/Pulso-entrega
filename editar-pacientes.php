<?php

require_once "conexion.php";

// El id identifica qué paciente se debe consultar y modificar.
$idPaciente = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if ($idPaciente === false || $idPaciente === null || $idPaciente < 1) {
    http_response_code(400);
    die("El identificador del paciente no es válido.");
}

function escapar($valor): string
{
    return htmlspecialchars((string) ($valor ?? ""), ENT_QUOTES, "UTF-8");
}

// Primero se consulta el registro para conocer también el id de la persona relacionada.
$resultadoPaciente = $conexion->query(
    "SELECT
        paciente.id_persona,
        paciente.estado,
        paciente.patologia,
        persona.nombres,
        persona.apellidos,
        persona.ci,
        persona.fecha_nacimiento,
        persona.sexo,
        persona.telefono,
        persona.direccion,
        persona.email
     FROM paciente
     INNER JOIN persona ON persona.id_persona = paciente.id_persona
     WHERE paciente.id_paciente = $idPaciente"
);
$paciente = $resultadoPaciente->fetch_assoc();

if ($paciente === null) {
    http_response_code(404);
    die("Paciente no encontrado.");
}

$mensajeError = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombres = trim($_POST["nombres"] ?? "");
    $apellidos = trim($_POST["apellidos"] ?? "");
    $ci = trim($_POST["ci"] ?? "");
    $fechaNacimiento = trim($_POST["fecha_nacimiento"] ?? "");
    $sexo = trim($_POST["sexo"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $direccion = trim($_POST["direccion"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $estado = trim($_POST["estado"] ?? "");
    $patologia = trim($_POST["patologia"] ?? "");

    $sexosValidos = ["masculino", "femenino", "otro"];
    $estadosValidos = ["estable", "en_revision", "critico", "alta"];

    if (
        $nombres === "" || $apellidos === "" || $ci === "" ||
        !in_array($estado, $estadosValidos, true) ||
        ($sexo !== "" && !in_array($sexo, $sexosValidos, true)) ||
        ($email !== "" && filter_var($email, FILTER_VALIDATE_EMAIL) === false)
    ) {
        $mensajeError = "Complete correctamente todos los campos obligatorios.";
    } else {
        try {
            $fechaNacimientoParaSQL = $fechaNacimiento === ""
                ? "NULL"
                : "'$fechaNacimiento'";
            $emailParaSQL = $email === "" ? "NULL" : "'$email'";
            $idPersona = $paciente["id_persona"];

            $conexion->query(
                "UPDATE persona
                 SET nombres = '$nombres', apellidos = '$apellidos', ci = '$ci',
                     fecha_nacimiento = $fechaNacimientoParaSQL, sexo = '$sexo',
                     telefono = '$telefono', direccion = '$direccion', email = $emailParaSQL
                 WHERE id_persona = $idPersona"
            );

            $conexion->query(
                "UPDATE paciente
                 SET estado = '$estado', patologia = '$patologia'
                 WHERE id_paciente = $idPaciente"
            );

            header("Location: ficha-paciente.php?id=" . $idPaciente . "&actualizado=ok");
            exit;
        } catch (mysqli_sql_exception $error) {
            $mensajeError = $error->getCode() === 1062
                ? "Ya existe otra persona registrada con esa cédula."
                : "No se pudieron guardar los cambios. Intente nuevamente.";
        }
    }
}

?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PULSO - Editar paciente</title>
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
        <a class="enlace-activo" href="pacientes.php">Pacientes</a>
        <a href="funcionario.php">Funcionarios</a>
        <a href="documentos.php">Documentos</a>
        <a href="ambulancia.php">Ambulancias</a>
        <a href="equipamientos.php">Equipamientos</a>
        <a href="index.html">Inicio</a>
      </nav>
    </header>

    <main class="pagina">
      <section class="encabezado-pagina">
        <div>
          <p class="subtitulo">Registro asistencial</p>
          <h1>Editar paciente</h1>
          <p>Actualice los datos disponibles del paciente.</p>
        </div>
      </section>

      <section class="tarjeta">
        <?php if ($mensajeError !== ""): ?>
          <p class="estado estado-critico" role="alert"><?= escapar($mensajeError) ?></p>
        <?php endif; ?>

        <form class="formulario formulario-dos-columnas" action="editar-pacientes.php?id=<?= escapar($idPaciente) ?>" method="post">
          <label for="nombres">
            Nombre/s
            <input id="nombres" type="text" name="nombres" value="<?= escapar($paciente["nombres"]) ?>" required>
          </label>

          <label for="apellidos">
            Apellidos
            <input id="apellidos" type="text" name="apellidos" value="<?= escapar($paciente["apellidos"]) ?>" required>
          </label>

          <label for="ci">
            Cédula
            <input id="ci" type="text" name="ci" value="<?= escapar($paciente["ci"]) ?>" required>
          </label>

          <label for="telefono">
            Teléfono
            <input id="telefono" type="tel" name="telefono" value="<?= escapar($paciente["telefono"]) ?>">
          </label>

          <label for="fecha-nacimiento">
            Fecha de nacimiento
            <input id="fecha-nacimiento" type="date" name="fecha_nacimiento" value="<?= escapar($paciente["fecha_nacimiento"]) ?>">
          </label>

          <label for="sexo">
            Sexo
            <select id="sexo" name="sexo">
              <option value="">Sin especificar</option>
              <option value="masculino" <?= $paciente["sexo"] === "masculino" ? "selected" : "" ?>>Masculino</option>
              <option value="femenino" <?= $paciente["sexo"] === "femenino" ? "selected" : "" ?>>Femenino</option>
              <option value="otro" <?= $paciente["sexo"] === "otro" ? "selected" : "" ?>>Otro</option>
            </select>
          </label>

          <label for="estado">
            Estado del paciente
            <select id="estado" name="estado" required>
              <option value="estable" <?= $paciente["estado"] === "estable" ? "selected" : "" ?>>Estable</option>
              <option value="en_revision" <?= $paciente["estado"] === "en_revision" ? "selected" : "" ?>>En revisión</option>
              <option value="critico" <?= $paciente["estado"] === "critico" ? "selected" : "" ?>>Crítico</option>
              <option value="alta" <?= $paciente["estado"] === "alta" ? "selected" : "" ?>>Alta</option>
            </select>
          </label>

          <label class="campo-completo" for="direccion">
            Dirección
            <input id="direccion" type="text" name="direccion" value="<?= escapar($paciente["direccion"]) ?>">
          </label>

          <label class="campo-completo" for="email">
            Correo electrónico
            <input id="email" type="email" name="email" value="<?= escapar($paciente["email"]) ?>">
          </label>

          <label class="campo-completo" for="patologia">
            Patología o motivo de atención
            <textarea id="patologia" name="patologia" maxlength="255"><?= escapar($paciente["patologia"]) ?></textarea>
          </label>

          <div class="acciones campo-completo">
            <a class="boton boton-secundario" href="ficha-paciente.php?id=<?= escapar($idPaciente) ?>">Cancelar</a>
            <button class="boton" type="submit">Guardar cambios</button>
          </div>
        </form>
      </section>
    </main>
  </body>
</html>
