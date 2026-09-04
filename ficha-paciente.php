<?php
require_once 'conexion.php';

if (!isset($_GET['id'])) {
  die("Paciente no encontrado.");
}
$id_paciente = $_GET['id'];

$sql = "SELECT persona.nombres, persona.apellidos, persona.ci,
               persona.fecha_nacimiento, persona.sexo, persona.telefono,
              persona.direccion, persona.email,
              paciente.numero_historia, paciente.estado,
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
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PULSO - Ficha del paciente</title>
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
        <p class="subtitulo">Ficha del paciente</p>
        <h1>Nombre del paciente</h1>
        <p>Información del paciente y su historial clínico.</p>
      </div>
      <div class="acciones">
        <a class="boton boton-secundario" href="pacientes.html">Volver</a>
        <a class="boton" href="editar-paciente.php?id=<?php echo $paciente['id_paciente']; ?>">
          Editar paciente
        </a>
      </div>
    </section>
    <section class="tarjeta">
      <p> <strong>Nombre:</strong>
        <?php echo $paciente['nombres']; ?>
      </p>
      <p> <strong>Apellido:</strong>
        <?php echo $paciente['apellidos']; ?>
      </p>
      <p> <strong>Cédula de Identidad:</strong>
        <?php echo $paciente['ci']; ?>
      </p>
      <p> <strong>Teléfono:</strong>
        <?php echo $paciente['telefono']; ?>
      </p>
      <p> <strong>Sexo:</strong>
        <?php echo $paciente['sexo']; ?>
      </p>
      <p> <strong>Fecha de nacimiento:</strong>
        <?php echo $paciente['fecha_nacimiento']; ?>
      </p>
      <p> <strong>Dirección:</strong>
        <?php echo $paciente['direccion']; ?>
      </p>
      <p> <strong>Patología:</strong>
        <?php echo $paciente['patologia']; ?>
      </p>
      <p> <strong>Estado:</strong>
        <?php echo $paciente['estado']; ?>
      </p>
      <p> <strong>Fecha de registro:</strong>
        <?php echo $paciente['fecha_registro']; ?>
      </p> <br> <a href="pacientes.php">Volver a pacientes</a>

    </section>
  </main>
</body>

</html>