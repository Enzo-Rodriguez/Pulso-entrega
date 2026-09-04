<?php

require_once "conexion.php";

// El id llega en la URL, por ejemplo: ficha-paciente.php?id=3.
$idPaciente = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if ($idPaciente === false || $idPaciente === null || $idPaciente < 1) {
    http_response_code(400);
    die("El identificador del paciente no es válido.");
}

// La consulta preparada evita que el id recibido se interprete como parte del SQL.
$consulta = $conexion->prepare(
    "SELECT
        pa.id_paciente,
        DATE_FORMAT(pa.fecha_registro, '%d/%m/%Y %H:%i') AS fecha_registro,
        pa.estado,
        pa.patologia,
        pa.activo,
        pe.ci,
        pe.nombres,
        pe.apellidos,
        pe.fecha_nacimiento,
        pe.sexo,
        pe.telefono,
        pe.direccion,
        pe.email
     FROM paciente AS pa
     INNER JOIN persona AS pe ON pe.id_persona = pa.id_persona
     WHERE pa.id_paciente = ?"
);
$consulta->bind_param("i", $idPaciente);
$consulta->execute();
$resultado = $consulta->get_result();
$paciente = $resultado->fetch_assoc();
$consulta->close();

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
        <a href="documentos.html">Documentos</a>
        <a href="ambulancia.html">Ambulancias</a>
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
