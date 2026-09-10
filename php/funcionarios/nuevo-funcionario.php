<?php
require_once "../conexion.php";
$mensajeError = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombres = trim($_POST["nombres"] ?? "");
    $apellidos = trim($_POST["apellidos"] ?? "");
    $ci = trim($_POST["ci"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $fechaNacimiento = trim($_POST["fecha_nacimiento"] ?? "");
    $sexo = trim($_POST["sexo"] ?? "");
    $direccion = trim($_POST["direccion"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $activo = trim($_POST["activo"] ?? "");
    $nombreTipo = trim($_POST["tipo_funcionario"] ?? "");

    $sexosValidos = ["masculino", "femenino", "otro"];
    $tiposValidos = ["Médico", "Administrativo", "Conductor", "Enfermería"];

    if (
        $nombres === "" ||
        $apellidos === "" ||
        $ci === "" ||
        !in_array($activo, ["0", "1"], true) ||
        ($sexo !== "" && !in_array($sexo, $sexosValidos, true)) ||
        !in_array($nombreTipo, $tiposValidos, true)
    ) {
        $mensajeError = "Complete correctamente todos los campos obligatorios.";
    } else {
        try {
            $resultadoTipo = $conexion->query(
                "SELECT id_tipo_funcionario
                 FROM tipo_funcionario
                 WHERE nombre = '$nombreTipo'"
            );

            if ($resultadoTipo->num_rows === 0) {
                throw new Exception("Tipo de funcionario no válido.");
            }

            $tipoFuncionario = $resultadoTipo->fetch_assoc();
            $idTipoFuncionario = $tipoFuncionario["id_tipo_funcionario"];
            $fechaNacimientoParaSQL = $fechaNacimiento === ""
                ? "NULL"
                : "'$fechaNacimiento'";

            $conexion->query(
                "INSERT INTO persona
                    (nombres, apellidos, ci, telefono, fecha_nacimiento, sexo, direccion, email)
                 VALUES
                    ('$nombres', '$apellidos', '$ci', '$telefono',
                     $fechaNacimientoParaSQL, '$sexo', '$direccion', '$email')"
            );

            $idPersona = $conexion->insert_id;

            $conexion->query(
                "INSERT INTO funcionario
                    (id_persona, id_tipo_funcionario, activo)
                 VALUES
                    ($idPersona, $idTipoFuncionario, $activo)"
            );

            header("Location: funcionario.php");
            exit;
        } catch (mysqli_sql_exception $error) {
            if ($error->getCode() === 1062) {
                $mensajeError = "Ya existe una persona registrada con esa cédula.";
            } else {
                $mensajeError = "No se pudo registrar el funcionario. Intente nuevamente.";
            }
        } catch (Exception $error) {
            $mensajeError = $error->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PULSO - Nuevo funcionario</title>
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
          <p class="subtitulo">Registro asistencial</p>
          <h1>Nuevo funcionario</h1>
          <p>Complete los datos disponibles al momento del ingreso.</p>
        </div>
      </section>

      <section class="tarjeta">
        <?php if ($mensajeError !== ""): ?>
          <p class="estado estado-critico" role="alert"><?= $mensajeError ?></p>
        <?php endif; ?>

        <form class="formulario formulario-dos-columnas" action="nuevo-funcionario.php" method="post">
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
          <label for="tipo-funcionario">
            Tipo de funcionario
            <select id="tipo-funcionario" name="tipo_funcionario" required>
              <option value="">Seleccione un tipo</option>
              <option value="Médico">Médico</option>
              <option value="Administrativo">Administrativo</option>
              <option value="Conductor">Conductor</option>
              <option value="Enfermería">Enfermería</option>
            </select>
          </label>
           <label for="activo">
            Activo
            <select id="activo" name="activo">
              <option value="1">Sí</option>
              <option value="0">No</option>
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

         

          <div class="acciones campo-completo">
            <a class="boton boton-secundario" href="../funcionarios/funcionario.php">Cancelar</a>
            <button class="boton" type="submit">Registrar funcionario</button>
          </div>
        </form>
      </section>
    </main>
  </body>
</html>
