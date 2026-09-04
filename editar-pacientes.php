<?php
require_once 'conexion.php';

// Obtener el ID del paciente desde la URL
$idPaciente = $_GET['id'] ?? null;

$sql = "SELECT persona.nombres, persona.apellidos, persona.ci,
               persona.fecha_nacimiento, persona.sexo, persona.telefono,
               persona.direccion, persona.email,
               paciente.id_persona, paciente.estado,
               paciente.patologia
        FROM paciente
        INNER JOIN persona ON paciente.id_persona = persona.id_persona
        WHERE paciente.id_paciente = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_paciente);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows == 0) {
  die("Paciente no encontrado.");
}

$paciente = $resultado->fetch_assoc();


// Si se presionó Guardar cambios

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  $nombres = $_POST['nombres'];
  $apellidos = $_POST['apellidos'];
  $ci = $_POST['ci'];
  $fecha_nacimiento = $_POST['fecha_nacimiento'];
  $sexo = $_POST['sexo'];
  $telefono = $_POST['telefono'];
  $direccion = $_POST['direccion'];
  $email = $_POST['email'];
  $estado = $_POST['estado'];
  $patologia = $_POST['patologia'];

  // Actualizar datos de persona

  $sql = "UPDATE persona SET
                nombres = ?,
                apellidos = ?,
                ci = ?,
                fecha_nacimiento = ?,
                sexo = ?,
                telefono = ?,
                direccion = ?,
                email = ?
            WHERE id_persona = ?";

  $stmt = $conexion->prepare($sql);

  $stmt->bind_param(
    "ssssssssi",
    $nombres,
    $apellidos,
    $ci,
    $fecha_nacimiento,
    $sexo,
    $telefono,
    $direccion,
    $email,
    $paciente['id_persona']
  );

  $stmt->execute();


  // Actualizar datos de paciente

  $sql = "UPDATE paciente SET
                estado = ?,
                patologia = ?
            WHERE id_paciente = ?";

  $stmt = $conexion->prepare($sql);

  $stmt->bind_param(
    "ssi",
    $estado,
    $patologia,
    $id_paciente
  );

  $stmt->execute();


  // Volver a la ficha del paciente

  header("Location: ficha-paciente.php?id=" . $id_paciente);
  exit;
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
      <a class="enlace-activo" href="pacientes.html">Pacientes</a>
      <a href="documentos.html">Documentos</a>
      <a href="encuestas.html">Encuestas</a>
      <a href="ambulancia.html">Ambulancias</a>
      <a href="index.html">Cerrar sesión</a>
    </nav>
  </header>

  <main class="pagina">
    <section class="encabezado-pagina">
      <div>
        <p class="subtitulo">Registro asistencial</p>
        <h1>Editar paciente</h1>
        <p>Actualice los datos disponibles del paciente.</p>
      </div>
      <form class="formulario formulario-dos-columnas" action="editar-paciente.php?id=<?php echo $id_paciente; ?>"
        method="post">
        <label for="nombres">
          Nombre/s
          <input id="nombres" type="text" name="nombres" value="<?php echo $paciente['nombres']; ?>" required>
        </label>
        <label for="apellidos">
          Apellidos
          <input id="apellidos" type="text" name="apellidos" value="<?php echo $paciente['apellidos']; ?>" required>
        </label>
        <label for="ci">
          Cédula
          <input id="ci" type="text" name="ci" value="<?php echo $paciente['ci']; ?>" required>
        </label>
        <label for="telefono">
          Teléfono
          <input id="telefono" type="tel" name="telefono" value="<?php echo $paciente['telefono']; ?>" required>
        </label>
        <label for="fecha-nacimiento">
          Fecha de nacimiento
          <input id="fecha-nacimiento" type="date" name="fecha_nacimiento"
            value="<?php echo $paciente['fecha_nacimiento']; ?>" required>
        </label>
        <label for="sexo">
          Sexo
          <select id="sexo" name="sexo" required>
            <option value="masculino" <?php echo $paciente['sexo'] == "masculino" ? "selected" : ""; ?>>
              Masculino
            </option>
            <option value="femenino" <?php echo $paciente['sexo'] == "femenino" ? "selected" : ""; ?>>
              Femenino
            </option>
            <option value="otro" <?php echo $paciente['sexo'] == "otro" ? "selected" : ""; ?>>
              Otro
            </option>
          </select>
        </label>
        <label for="estado">
          Estado del paciente
          <select id="estado" name="estado" required>
            <option value="estable" <?php echo $paciente['estado'] == "estable" ? "selected" : ""; ?>>
              Estable
            </option>
            <option value="en_revision" <?php echo $paciente['estado'] == "en_revision" ? "selected" : ""; ?>>
              En revisión
            </option>
            <option value="critico" <?php echo $paciente['estado'] == "critico" ? "selected" : ""; ?>>
              Crítico
            </option>
            <option value="alta" <?php echo $paciente['estado'] == "alta" ? "selected" : ""; ?>>
              Alta
            </option>
          </select>
        </label>
        <label class="campo-completo" for="direccion">
          Dirección
          <input id="direccion" type="text" name="direccion" value="<?php echo $paciente['direccion']; ?>" required>
        </label>
        <label class="campo-completo" for="email">
          Email
          <input id="email" type="email" name="email" value="<?php echo $paciente['email']; ?>">
        </label>
        <label class="campo-completo" for="patologia">
          Patología o motivo de atención
          <textarea id="patologia" name="patologia" maxlength="255"><?php echo $paciente['patologia']; ?></textarea>
        </label>
        <div class="acciones campo-completo">
          <a class="boton boton-secundario" href="ficha-paciente.php?id=<?php echo $id_paciente; ?>">
            Cancelar
          </a>
          <button class="boton" type="submit">
            Guardar cambios
          </button>
        </div>
      </form>
    </section>
  </main>
</body>
</html>