<?php

require_once "../conexion.php";

$idFuncionario = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if ($idFuncionario === false || $idFuncionario === null || $idFuncionario < 1) {
  http_response_code(400);
  die("El identificador del funcionario no es válido.");
}

function escapar($valor): string
{
  return htmlspecialchars((string) ($valor ?? ""), ENT_QUOTES, "UTF-8");
}


// Consulta de los datos
$resultadoFuncionario = $conexion->query(
  "SELECT
            funcionario.id_persona,
            funcionario.activo,
            persona.nombres,
            persona.apellidos,
            persona.ci,
            persona.fecha_nacimiento,
            persona.sexo,
            persona.telefono,
            persona.direccion,
            persona.email
         FROM funcionario
         INNER JOIN persona ON persona.id_persona = funcionario.id_persona
         WHERE funcionario.id_funcionario = $idFuncionario"
);

$funcionario = $resultadoFuncionario->fetch_assoc();

if ($funcionario === null) {
  http_response_code(404);
  die("Funcionario no encontrado.");
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
  $activo = trim($_POST["activo"] ?? "");

  $sexosValidos = ["masculino", "femenino", "otro"];

  if (
    $nombres === "" || $apellidos === "" || $ci === "" || $activo === "" ||
    ($sexo !== "" && !in_array($sexo, $sexosValidos, true)) ||
    ($email !== "" && filter_var($email, FILTER_VALIDATE_EMAIL) === false)
  ) {
    $mensajeError = "Complete correctamente todos los campos obligatorios.";
  } else {
    try {
      $fechaNacimientoParaSQL = $fechaNacimiento === "" ? "NULL" : "'$fechaNacimiento'";
      $emailParaSQL = $email === "" ? "NULL" : "'$email'";
      $idPersona = $funcionario["id_persona"];

      $conexion->query(
        "UPDATE persona
                 SET nombres = '$nombres', apellidos = '$apellidos', ci = '$ci',
                     fecha_nacimiento = $fechaNacimientoParaSQL, sexo = '$sexo',
                     telefono = '$telefono', direccion = '$direccion', email = $emailParaSQL
                 WHERE id_persona = $idPersona"
      );

      $conexion->query(
        "UPDATE funcionario
                 SET activo = '$activo'
                 WHERE id_funcionario = $idFuncionario"
      );

      header("Location: funcionario.php?actualizado=ok");
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
  <title>PULSO - Editar funcionario</title>
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
      <a class="enlace-activo" href="../funcionarios/funcionario.php">Funcionarios</a>
      <a href="../documentos/documentos.php">Documentos</a>
      <a href="../ambulancias/ambulancia.php">Ambulancias</a>
      <a href="../equipamientos/equipamientos.php">Equipamientos</a>
      <a href="../../index.html">Inicio</a>
    </nav>
  </header>

  <main class="pagina">
    <section class="encabezado-pagina">
      <div>
        <p class="subtitulo">Gestión de personal</p>
        <h1>Editar funcionario</h1>
        <p>Actualice los datos disponibles del funcionario.</p>
      </div>
    </section>

    <section class="tarjeta">
      <?php if ($mensajeError !== ""): ?>
        <p class="estado estado-critico" role="alert"><?= escapar($mensajeError) ?></p>
      <?php endif; ?>

      <form class="formulario formulario-dos-columnas" action="editar-funcionario.php?id=<?= escapar($idFuncionario) ?>"
        method="post">
        <input type="hidden" name="id_funcionario" value="<?= escapar($idFuncionario) ?>">

        <label for="nombres">
          Nombre/s
          <input id="nombres" type="text" name="nombres" value="<?= escapar($funcionario["nombres"]) ?>" required>
        </label>

        <label for="apellidos">
          Apellidos
          <input id="apellidos" type="text" name="apellidos" value="<?= escapar($funcionario["apellidos"]) ?>" required>
        </label>

        <label for="ci">
          Cédula
          <input id="ci" type="text" name="ci" value="<?= escapar($funcionario["ci"]) ?>" required>
        </label>

        <label for="telefono">
          Teléfono
          <input id="telefono" type="tel" name="telefono" value="<?= escapar($funcionario["telefono"]) ?>">
        </label>

        <label for="fecha-nacimiento">
          Fecha de nacimiento
          <input id="fecha-nacimiento" type="date" name="fecha_nacimiento"
            value="<?= escapar($funcionario["fecha_nacimiento"]) ?>">
        </label>

        <label for="sexo">
          Sexo
          <select id="sexo" name="sexo">
            <option value="">Sin especificar</option>
            <option value="masculino" <?= $funcionario["sexo"] === "masculino" ? "selected" : "" ?>>Masculino</option>
            <option value="femenino" <?= $funcionario["sexo"] === "femenino" ? "selected" : "" ?>>Femenino</option>
            <option value="otro" <?= $funcionario["sexo"] === "otro" ? "selected" : "" ?>>Otro</option>
          </select>
        </label>

        <label for="activo">
          Estado
          <select id="activo" name="activo">
            <option value="1" <?= $funcionario["activo"] == 1 ? "selected" : "" ?>>Activo</option>
<option value="0" <?= $funcionario["activo"] == 0 ? "selected" : "" ?>>Inactivo</option>
          </select>
        </label>

        <label class="campo-completo" for="direccion">
          Dirección
          <input id="direccion" type="text" name="direccion" value="<?= escapar($funcionario["direccion"]) ?>">
        </label>

        <label class="campo-completo" for="email">
          Correo electrónico
          <input id="email" type="email" name="email" value="<?= escapar($funcionario["email"]) ?>">
        </label>

        <div class="acciones campo-completo">
          <a class="boton boton-secundario" href="../funcionarios/funcionario.php">Cancelar</a>
          <button class="boton" type="submit">Guardar cambios</button>
        </div>
      </form>
    </section>
  </main>
</body>

</html>
