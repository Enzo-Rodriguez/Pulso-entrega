<?php

$mensajeError = "";

// Este bloque solo se ejecuta cuando el usuario envía el formulario.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_once "conexion.php";

    // Lee los campos enviados; trim quita espacios al principio y al final.
    $nombres = trim($_POST["nombres"] ?? "");
    $apellidos = trim($_POST["apellidos"] ?? "");
    $ci = trim($_POST["ci"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $fechaNacimiento = trim($_POST["fecha_nacimiento"] ?? "");
    $sexo = trim($_POST["sexo"] ?? "");
    $estado = trim($_POST["estado"] ?? "");
    $direccion = trim($_POST["direccion"] ?? "");
    $patologia = trim($_POST["patologia"] ?? "");
    $email = trim($_POST["email"] ?? "");

    // Listas cerradas para impedir valores que no existen en los select del formulario.
    $sexosValidos = ["masculino", "femenino", "otro"];
    $estadosValidos = ["estable", "en_revision", "critico"];

    // PHP vuelve a validar lo obligatorio aunque el navegador ya use required.
    if (
        $nombres === "" || $apellidos === "" || $ci === "" ||
        !in_array($estado, $estadosValidos, true) ||
        ($sexo !== "" && !in_array($sexo, $sexosValidos, true))
    ) {
        $mensajeError = "Complete correctamente todos los campos obligatorios.";
    } else {
        try {
            // MySQL espera NULL, no una cadena vacía, cuando no se informa una fecha.
            if ($fechaNacimiento === "") {
                $fechaNacimiento = null;
            }

            $fechaNacimientoParaSQL = $fechaNacimiento === null
                ? "NULL"
                : "'$fechaNacimiento'";

            // Guarda primero los datos personales.
            $conexion->query(
                "INSERT INTO persona
                    (nombres, apellidos, ci, telefono, fecha_nacimiento, sexo, direccion, email)
                 VALUES
                    ('$nombres', '$apellidos', '$ci', '$telefono',
                     $fechaNacimientoParaSQL, '$sexo', '$direccion', '$email')"
            );

            // Recupera el id generado para relacionar ambas tablas.
            $idPersona = $conexion->insert_id;

            // MySQL completa fecha_registro y activo con sus valores predeterminados.
            $conexion->query(
                "INSERT INTO paciente
                    (id_persona, estado, patologia)
                 VALUES
                    ($idPersona, '$estado', '$patologia')"
            );

            // Vuelve al listado para mostrar el nuevo paciente.
            header("Location: pacientes.php");
            exit;
        } catch (mysqli_sql_exception $error) {
            $mensajeError = $error->getCode() === 1062
                ? "Ya existe una persona registrada con esa cédula."
                : "No se pudo registrar el paciente. Intente nuevamente.";
        }
    }
}

?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PULSO - Nuevo paciente</title>
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
          <h1>Nuevo paciente</h1>
          <p>Complete los datos disponibles al momento del ingreso.</p>
        </div>
      </section>

      <section class="tarjeta">
        <?php if ($mensajeError !== ""): ?>
          <p class="estado estado-critico" role="alert"><?= $mensajeError ?></p>
        <?php endif; ?>

        <!-- required ayuda al usuario; la validación definitiva igualmente se realiza en PHP. -->
        <form class="formulario formulario-dos-columnas" action="nuevo-paciente.php" method="post">
          <label for="nombres">
            Nombre/s
            <input id="nombres" type="text" name="nombres" required>
          </label>

          <label for="apellidos">
            Apellidos
            <input id="apellidos" type="text" name="apellidos" required>
          </label>

          <label for="ci">
            Cédula
            <input id="ci" type="text" name="ci" required>
          </label>

          <label for="telefono">
            Teléfono
            <input id="telefono" type="tel" name="telefono">
          </label>

          <label for="fecha-nacimiento">
            Fecha de nacimiento
            <input id="fecha-nacimiento" type="date" name="fecha_nacimiento">
          </label>

          <label for="sexo">
            Sexo
            <select id="sexo" name="sexo">
              <option value="">Sin especificar</option>
              <option value="masculino">Masculino</option>
              <option value="femenino">Femenino</option>
              <option value="otro">Otro</option>
            </select>
          </label>

          <label for="estado">
            Estado del paciente
            <select id="estado" name="estado" required>
              <option value="" disabled selected>Seleccionar</option>
              <option value="estable">Estable</option>
              <option value="en_revision">En revisión</option>
              <option value="critico">Crítico</option>
            </select>
          </label>

          <label class="campo-completo" for="direccion">
            Dirección
            <input id="direccion" type="text" name="direccion">
          </label>
          <label class="campo-completo" for="email">
            Correo electrónico
            <input id="email" type="email" name="email">
          </label>

          <label class="campo-completo" for="patologia">
            Patología o motivo de atención
            <textarea id="patologia" name="patologia" maxlength="255"></textarea>
          </label>

          <div class="acciones campo-completo">
            <a class="boton boton-secundario" href="pacientes.php">Cancelar</a>
            <button class="boton" type="submit">Registrar paciente</button>
          </div>
        </form>
      </section>
    </main>
  </body>
</html>
