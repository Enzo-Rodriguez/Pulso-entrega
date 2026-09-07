<?php

require_once "conexion.php";

// El id llega en la URL, por ejemplo: ficha-paciente.php?id=3.
$idPaciente = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if ($idPaciente === false || $idPaciente === null || $idPaciente < 1) {
    http_response_code(400);
    die("El identificador del paciente no es válido.");
}

// Busca el paciente indicado por el id recibido en la dirección de la página.
$resultadoPaciente = $conexion->query(
    "SELECT
        paciente.id_paciente,
        DATE_FORMAT(paciente.fecha_registro, '%d/%m/%Y %H:%i') AS fecha_registro,
        paciente.estado,
        paciente.patologia,
        paciente.activo,
        persona.ci,
        persona.nombres,
        persona.apellidos,
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

function escapar($valor): string
{
    return htmlspecialchars((string) ($valor ?? ""), ENT_QUOTES, "UTF-8");
}

$nombresEstados = [
    "estable" => "Estable",
    "en_revision" => "En revisión",
    "critico" => "Crítico",
    "alta" => "Alta",
];

$estadoVisible = (int) $paciente["activo"] === 1
    ? ($nombresEstados[$paciente["estado"]] ?? "Sin especificar")
    : "Inactivo";

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
        <a class="enlace-activo" href="pacientes.php">Pacientes</a>
        <a href="funcionario.php">Funcionarios</a>
        <a href="documentos.php">Documentos</a>
        <a href="ambulancia.php">Ambulancias</a>
        <a href="index.html">Inicio</a>
      </nav>
    </header>

    <main class="pagina">
      <section class="encabezado-pagina">
        <div>
          <p class="subtitulo">Ficha del paciente</p>
          <h1><?= escapar($paciente["nombres"] . " " . $paciente["apellidos"]) ?></h1>
          <p>Información registrada en el sistema PULSO.</p>
        </div>
        <div class="acciones">
          <a class="boton boton-secundario" href="pacientes.php">Volver</a>
          <a class="boton" href="editar-pacientes.php?id=<?= escapar($paciente["id_paciente"]) ?>">
            Editar paciente
          </a>
          <form action="eliminar_paciente.php" method="post" onsubmit="return confirm('¿Eliminar este paciente?');">
            <input type="hidden" name="id" value="<?= escapar($paciente["id_paciente"]) ?>">
            <button class="boton_rojo" type="submit">Eliminar paciente</button>
          </form>
        </div>
      </section>

      <section class="tarjeta">
        <dl class="detalle-paciente">
          <div>
            <dt>Cédula de identidad</dt>
            <dd><?= escapar($paciente["ci"]) ?></dd>
          </div>
          <div>
            <dt>Teléfono</dt>
            <dd><?= escapar($paciente["telefono"] ?: "Sin especificar") ?></dd>
          </div>
          <div>
            <dt>Fecha de nacimiento</dt>
            <dd><?= escapar($paciente["fecha_nacimiento"] ?: "Sin especificar") ?></dd>
          </div>
          <div>
            <dt>Sexo</dt>
            <dd><?= escapar($paciente["sexo"] ?: "Sin especificar") ?></dd>
          </div>
          <div class="detalle-ancho">
            <dt>Dirección</dt>
            <dd><?= escapar($paciente["direccion"] ?: "Sin especificar") ?></dd>
          </div>
          <div class="detalle-ancho">
            <dt>Correo electrónico</dt>
            <dd><?= escapar($paciente["email"] ?: "Sin especificar") ?></dd>
          </div>
          <div class="detalle-ancho">
            <dt>Patología o motivo de atención</dt>
            <dd><?= escapar($paciente["patologia"] ?: "Sin especificar") ?></dd>
          </div>
          <div>
            <dt>Estado</dt>
            <dd><?= escapar($estadoVisible) ?></dd>
          </div>
          <div>
            <dt>Fecha de registro</dt>
            <dd><?= escapar($paciente["fecha_registro"]) ?></dd>
          </div>
        </dl>
      </section>
    </main>
  </body>
</html>
