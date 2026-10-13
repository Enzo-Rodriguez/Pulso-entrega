<?php

header("Content-Type: application/json; charset=UTF-8");

function responder(int $codigo, array $datos): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function textoDelFormulario(string $campo): string
{
    $valor = $_POST[$campo] ?? "";
    return is_string($valor) ? trim($valor) : "";
}

function idDelFormulario(string $campo, bool $opcional = false): ?int
{
    $valor = textoDelFormulario($campo);
    if ($opcional && $valor === "") {
        return null;
    }

    $id = filter_var($valor, FILTER_VALIDATE_INT);
    if ($id === false || $id < 1) {
        responder(400, ["error" => "El campo $campo no es válido."]);
    }
    return $id;
}

function coordenadasDelFormulario(string $punto, bool $opcionales = true): array
{
    $textoLatitud = textoDelFormulario($punto . "_latitud");
    $textoLongitud = textoDelFormulario($punto . "_longitud");

    if ($textoLatitud === "" && $textoLongitud === "") {
        if ($opcionales) {
            return [null, null];
        }
        responder(400, ["error" => "Marcá el $punto en el mapa para calcular el recorrido."]);
    }

    $latitud = filter_var($textoLatitud, FILTER_VALIDATE_FLOAT);
    $longitud = filter_var($textoLongitud, FILTER_VALIDATE_FLOAT);

    if (
        $latitud === false || $longitud === false ||
        !is_finite($latitud) || !is_finite($longitud) ||
        $latitud < -90 || $latitud > 90 || $longitud < -180 || $longitud > 180
    ) {
        responder(400, ["error" => "Las coordenadas del $punto no son válidas."]);
    }

    return [round($latitud, 7), round($longitud, 7)];
}

function existe(mysqli $conexion, string $sql, string $tipo, int|string $valor): bool
{
    $consulta = $conexion->prepare($sql);
    $consulta->bind_param($tipo, $valor);
    $consulta->execute();
    $encontrado = $consulta->get_result()->num_rows > 0;
    $consulta->close();
    return $encontrado;
}

$metodo = $_SERVER["REQUEST_METHOD"] ?? "";
if (!in_array($metodo, ["GET", "POST"], true)) {
    responder(405, ["error" => "Método no permitido."]);
}

$accion = $metodo === "POST" ? textoDelFormulario("accion") : "";

if ($accion === "buscar_ubicacion") {
    $direccion = textoDelFormulario("direccion");
    if (mb_strlen($direccion, "UTF-8") < 3 || mb_strlen($direccion, "UTF-8") > 200) {
        responder(400, ["error" => "Escribí una dirección válida."]);
    }

    $archivoClave = __DIR__ . "/geoapify.local.php";
    if (!is_file($archivoClave)) {
        responder(503, ["error" => "Falta configurar Geoapify."]);
    }
    $claveGeoapify = require $archivoClave;
    if (!is_string($claveGeoapify) || trim($claveGeoapify) === "") {
        responder(503, ["error" => "La configuración de Geoapify no es válida."]);
    }
    if (!function_exists("curl_init")) {
        responder(503, ["error" => "PHP necesita cURL para buscar direcciones."]);
    }

    $parametros = http_build_query([
        "text" => $direccion,
        "lang" => "es",
        "format" => "json",
        "limit" => 5,
        "bias" => "countrycode:uy",
        "apiKey" => trim($claveGeoapify),
    ]);
    $consulta = curl_init("https://api.geoapify.com/v1/geocode/search?" . $parametros);
    curl_setopt_array($consulta, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 12,
    ]);
    $contenido = curl_exec($consulta);
    $codigoHttp = curl_getinfo($consulta, CURLINFO_HTTP_CODE);
    curl_close($consulta);

    if ($contenido === false || $codigoHttp !== 200) {
        responder(502, ["error" => "No se pudo consultar Geoapify."]);
    }

    $respuesta = json_decode($contenido, true);
    $resultados = [];
    foreach ($respuesta["results"] ?? [] as $ubicacion) {
        if (!isset($ubicacion["formatted"], $ubicacion["lat"], $ubicacion["lon"])) {
            continue;
        }
        $resultados[] = [
            "direccion" => $ubicacion["formatted"],
            "latitud" => (float) $ubicacion["lat"],
            "longitud" => (float) $ubicacion["lon"],
        ];
    }
    responder(200, ["resultados" => $resultados]);
}

// La vista previa del recorrido tampoco necesita guardar datos en MySQL.
if ($accion === "calcular_ruta") {
    [$origenLatitud, $origenLongitud] = coordenadasDelFormulario("origen", false);
    [$destinoLatitud, $destinoLongitud] = coordenadasDelFormulario("destino", false);
    require_once __DIR__ . "/rutas.php";
    try {
        responder(200, calcularRutaTraslado($origenLatitud, $origenLongitud, $destinoLatitud, $destinoLongitud));
    } catch (RuntimeException $error) {
        responder($error->getCode(), ["error" => $error->getMessage()]);
    }
}

require_once "../conexion.php";

if ($metodo === "GET") {
    try {
        $resultadoTraslados = $conexion->query(
            "SELECT
                traslado.id_traslado,
                traslado.id_paciente,
                traslado.matricula,
                traslado.id_creador,
                traslado.id_conductor,
                traslado.id_acompanante,
                traslado.id_tipo_carga,
                traslado.id_tipo_prioridad,
                traslado.origen,
                traslado.destino,
                traslado.origen_latitud,
                traslado.origen_longitud,
                traslado.destino_latitud,
                traslado.destino_longitud,
                traslado.descripcion_carga,
                DATE_FORMAT(traslado.salida_prevista, '%d/%m/%Y %H:%i') AS salida_prevista,
                DATE_FORMAT(traslado.salida_prevista, '%Y-%m-%dT%H:%i') AS salida_prevista_formulario,
                DATE_FORMAT(traslado.salida_real, '%d/%m/%Y %H:%i') AS salida_real,
                DATE_FORMAT(traslado.llegada_real, '%d/%m/%Y %H:%i') AS llegada_real,
                DATE_FORMAT(COALESCE(traslado.salida_real, traslado.salida_prevista), '%Y-%m-%dT%H:%i:%s') AS salida_calculo,
                tipo_carga.nombre AS tipo_carga,
                tipo_prioridad.nombre AS prioridad,
                estado_traslado.nombre AS estado,
                CONCAT_WS(' ', persona_paciente.nombres, persona_paciente.apellidos) AS nombre_paciente,
                CONCAT_WS(' ', persona_conductor.nombres, persona_conductor.apellidos) AS nombre_conductor,
                CONCAT_WS(' ', persona_acompanante.nombres, persona_acompanante.apellidos) AS nombre_acompanante,
                CONCAT_WS(' ', persona_creador.nombres, persona_creador.apellidos) AS nombre_creador
             FROM traslado
             LEFT JOIN paciente ON paciente.id_paciente = traslado.id_paciente
             LEFT JOIN persona AS persona_paciente ON persona_paciente.id_persona = paciente.id_persona
             INNER JOIN funcionario AS conductor ON conductor.id_funcionario = traslado.id_conductor
             INNER JOIN persona AS persona_conductor ON persona_conductor.id_persona = conductor.id_persona
             LEFT JOIN funcionario AS acompanante ON acompanante.id_funcionario = traslado.id_acompanante
             LEFT JOIN persona AS persona_acompanante ON persona_acompanante.id_persona = acompanante.id_persona
             INNER JOIN funcionario AS creador ON creador.id_funcionario = traslado.id_creador
             INNER JOIN persona AS persona_creador ON persona_creador.id_persona = creador.id_persona
             INNER JOIN tipo_carga ON tipo_carga.id_tipo_carga = traslado.id_tipo_carga
             INNER JOIN tipo_prioridad ON tipo_prioridad.id_tipo_prioridad = traslado.id_tipo_prioridad
             INNER JOIN estado_traslado ON estado_traslado.id_estado_traslado = traslado.id_estado_traslado
             ORDER BY traslado.salida_prevista DESC, traslado.id_traslado DESC"
        );

        $pacientes = $conexion->query(
            "SELECT paciente.id_paciente,
                    CONCAT_WS(' ', persona.nombres, persona.apellidos) AS nombre
             FROM paciente
             INNER JOIN persona ON persona.id_persona = paciente.id_persona
             WHERE paciente.activo = 1
             ORDER BY persona.apellidos, persona.nombres"
        )->fetch_all(MYSQLI_ASSOC);

        $funcionarios = $conexion->query(
            "SELECT funcionario.id_funcionario,
                    CONCAT_WS(' ', persona.nombres, persona.apellidos) AS nombre,
                    tipo_funcionario.nombre AS tipo
             FROM funcionario
             INNER JOIN persona ON persona.id_persona = funcionario.id_persona
             INNER JOIN tipo_funcionario ON tipo_funcionario.id_tipo_funcionario = funcionario.id_tipo_funcionario
             WHERE funcionario.activo = 1
             ORDER BY persona.apellidos, persona.nombres"
        )->fetch_all(MYSQLI_ASSOC);

        $ambulancias = $conexion->query(
            "SELECT matricula, marca, modelo
             FROM ambulancia
             WHERE activa = 1
             ORDER BY matricula"
        )->fetch_all(MYSQLI_ASSOC);

        responder(200, [
            "traslados" => $resultadoTraslados->fetch_all(MYSQLI_ASSOC),
            "opciones" => [
                "pacientes" => $pacientes,
                "funcionarios" => $funcionarios,
                "ambulancias" => $ambulancias,
                "tipos_carga" => $conexion->query(
                    "SELECT id_tipo_carga, nombre FROM tipo_carga ORDER BY id_tipo_carga"
                )->fetch_all(MYSQLI_ASSOC),
                "prioridades" => $conexion->query(
                    "SELECT id_tipo_prioridad, nombre FROM tipo_prioridad ORDER BY id_tipo_prioridad"
                )->fetch_all(MYSQLI_ASSOC),
            ],
        ]);
    } catch (mysqli_sql_exception $error) {
        responder(500, ["error" => "No se pudieron cargar los traslados."]);
    }
}

if (in_array($accion, ["iniciar", "finalizar", "cancelar"], true)) {
    $idTraslado = idDelFormulario("id_traslado");
    $idConductorSeleccionado = array_key_exists("id_conductor", $_POST) ? idDelFormulario("id_conductor") : null;
    $estadosEsperados = [
        "iniciar" => ["Programado"],
        "finalizar" => ["En curso"],
        "cancelar" => ["Programado", "En curso"],
    ];
    $estadosNuevos = [
        "iniciar" => "En curso",
        "finalizar" => "Finalizado",
        "cancelar" => "Cancelado",
    ];

    $conexion->begin_transaction();
    try {
        $buscarEstado = $conexion->prepare(
            "SELECT estado_traslado.nombre AS estado, traslado.id_conductor
             FROM traslado
             INNER JOIN estado_traslado ON estado_traslado.id_estado_traslado = traslado.id_estado_traslado
             WHERE traslado.id_traslado = ? FOR UPDATE"
        );
        $buscarEstado->bind_param("i", $idTraslado);
        $buscarEstado->execute();
        $trasladoEstado = $buscarEstado->get_result()->fetch_assoc();
        $buscarEstado->close();

        if (!$trasladoEstado) {
            $conexion->rollback();
            responder(404, ["error" => "El traslado no existe."]);
        }
        // En la vista de demostración, evita actuar sobre otro conductor por accidente.
        if ($idConductorSeleccionado !== null && (int) $trasladoEstado["id_conductor"] !== $idConductorSeleccionado) {
            $conexion->rollback();
            responder(403, ["error" => "El traslado no está asignado al conductor seleccionado."]);
        }
        $estadoActual = $trasladoEstado["estado"];
        if (!in_array($estadoActual, $estadosEsperados[$accion], true)) {
            $conexion->rollback();
            responder(409, ["error" => "No se puede hacer ese cambio desde el estado actual."]);
        }

        $buscarEstadoNuevo = $conexion->prepare(
            "SELECT id_estado_traslado FROM estado_traslado WHERE nombre = ?"
        );
        $buscarEstadoNuevo->bind_param("s", $estadosNuevos[$accion]);
        $buscarEstadoNuevo->execute();
        $idEstadoNuevo = $buscarEstadoNuevo->get_result()->fetch_column();
        $buscarEstadoNuevo->close();
        if ($idEstadoNuevo === false) {
            throw new RuntimeException("Falta un estado de traslado.");
        }

        if ($accion === "cancelar") {
            $actualizarEstado = $conexion->prepare(
                "UPDATE traslado SET id_estado_traslado = ? WHERE id_traslado = ?"
            );
            $actualizarEstado->bind_param("ii", $idEstadoNuevo, $idTraslado);
        } else {
            $campoHora = $accion === "iniciar" ? "salida_real" : "llegada_real";
            $horaActual = (new DateTimeImmutable("now", new DateTimeZone("America/Montevideo")))
                ->format("Y-m-d H:i:s");
            $actualizarEstado = $conexion->prepare(
                "UPDATE traslado SET id_estado_traslado = ?, $campoHora = ? WHERE id_traslado = ?"
            );
            $actualizarEstado->bind_param("isi", $idEstadoNuevo, $horaActual, $idTraslado);
        }
        $actualizarEstado->execute();
        $actualizarEstado->close();
        $conexion->commit();

        $mensajes = [
            "iniciar" => "Traslado iniciado correctamente.",
            "finalizar" => "Traslado finalizado correctamente.",
            "cancelar" => "Traslado dado de baja y conservado como Cancelado.",
        ];
        responder(200, ["mensaje" => $mensajes[$accion]]);
    } catch (Throwable $error) {
        $conexion->rollback();
        responder(500, ["error" => "No se pudo cambiar el estado del traslado."]);
    }
}

if ($accion !== "guardar") {
    responder(400, ["error" => "Acción no válida."]);
}

$idTraslado = idDelFormulario("id_traslado", true);
$esNuevo = $idTraslado === null;
$idPaciente = idDelFormulario("id_paciente", true);
$idConductor = idDelFormulario("id_conductor");
$idAcompanante = idDelFormulario("id_acompanante", true);
$idTipoCarga = idDelFormulario("id_tipo_carga");
$idTipoPrioridad = idDelFormulario("id_tipo_prioridad");
$idCreador = $esNuevo ? idDelFormulario("id_creador") : null;
$matricula = textoDelFormulario("matricula");
$origen = textoDelFormulario("origen");
$destino = textoDelFormulario("destino");
$descripcion = textoDelFormulario("descripcion_carga");
$salidaPrevista = textoDelFormulario("salida_prevista");

$fechaPrevista = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $salidaPrevista);
if (
    $origen === "" || $destino === "" || $matricula === "" ||
    mb_strlen($origen, "UTF-8") > 200 || mb_strlen($destino, "UTF-8") > 200 ||
    mb_strlen($descripcion, "UTF-8") > 255 ||
    !$fechaPrevista || $fechaPrevista->format('Y-m-d\TH:i') !== $salidaPrevista
) {
    responder(400, ["error" => "Completá correctamente los datos del traslado."]);
}
if ($idAcompanante !== null && $idAcompanante === $idConductor) {
    responder(400, ["error" => "El conductor y el acompañante deben ser distintos."]);
}

[$origenLatitud, $origenLongitud] = coordenadasDelFormulario("origen");
[$destinoLatitud, $destinoLongitud] = coordenadasDelFormulario("destino");

try {
    $buscarTipoCarga = $conexion->prepare(
        "SELECT nombre FROM tipo_carga WHERE id_tipo_carga = ?"
    );
    $buscarTipoCarga->bind_param("i", $idTipoCarga);
    $buscarTipoCarga->execute();
    $nombreTipoCarga = $buscarTipoCarga->get_result()->fetch_column();
    $buscarTipoCarga->close();

    if ($nombreTipoCarga === false || !existe(
        $conexion,
        "SELECT 1 FROM tipo_prioridad WHERE id_tipo_prioridad = ?",
        "i",
        $idTipoPrioridad
    )) {
        responder(400, ["error" => "El tipo de carga o la prioridad no son válidos."]);
    }
    if ($nombreTipoCarga === "Paciente" && $idPaciente === null) {
        responder(400, ["error" => "Seleccioná un paciente para este tipo de carga."]);
    }
    if ($nombreTipoCarga !== "Paciente") {
        $idPaciente = null;
    }
    if ($idPaciente !== null && !existe(
        $conexion,
        "SELECT 1 FROM paciente WHERE id_paciente = ? AND activo = 1",
        "i",
        $idPaciente
    )) {
        responder(400, ["error" => "El paciente seleccionado no está disponible."]);
    }
    if (!existe(
        $conexion,
        "SELECT 1 FROM ambulancia WHERE matricula = ? AND activa = 1",
        "s",
        $matricula
    )) {
        responder(400, ["error" => "La ambulancia seleccionada no está disponible."]);
    }
    if (!existe(
        $conexion,
        "SELECT 1 FROM funcionario
         INNER JOIN tipo_funcionario ON tipo_funcionario.id_tipo_funcionario = funcionario.id_tipo_funcionario
         WHERE funcionario.id_funcionario = ? AND funcionario.activo = 1 AND tipo_funcionario.nombre = 'Conductor'",
        "i",
        $idConductor
    )) {
        responder(400, ["error" => "Seleccioná un conductor activo."]);
    }
    if ($idAcompanante !== null && !existe(
        $conexion,
        "SELECT 1 FROM funcionario WHERE id_funcionario = ? AND activo = 1",
        "i",
        $idAcompanante
    )) {
        responder(400, ["error" => "El acompañante seleccionado no está disponible."]);
    }
    if ($esNuevo && !existe(
        $conexion,
        "SELECT 1 FROM funcionario WHERE id_funcionario = ? AND activo = 1",
        "i",
        $idCreador
    )) {
        responder(400, ["error" => "Seleccioná quién registra el traslado."]);
    }

    $fechaParaGuardar = $fechaPrevista->format("Y-m-d H:i:s");
    $descripcionParaGuardar = $descripcion === "" ? null : $descripcion;

    if ($esNuevo) {
        $idProgramado = $conexion->query(
            "SELECT id_estado_traslado FROM estado_traslado WHERE nombre = 'Programado'"
        )->fetch_column();
        if ($idProgramado === false) {
            responder(500, ["error" => "Falta el estado Programado en la base de datos."]);
        }

        $guardarTraslado = $conexion->prepare(
            "INSERT INTO traslado
                (id_paciente, matricula, id_creador, id_conductor, id_acompanante,
                 id_tipo_carga, id_tipo_prioridad, id_estado_traslado, descripcion_carga,
                 origen, destino, salida_prevista,
                 origen_latitud, origen_longitud, destino_latitud, destino_longitud)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $guardarTraslado->bind_param(
            "isiiiiiissssdddd",
            $idPaciente, $matricula, $idCreador, $idConductor, $idAcompanante,
            $idTipoCarga, $idTipoPrioridad, $idProgramado, $descripcionParaGuardar,
            $origen, $destino, $fechaParaGuardar,
            $origenLatitud, $origenLongitud, $destinoLatitud, $destinoLongitud
        );
        $guardarTraslado->execute();
        $guardarTraslado->close();
        responder(201, ["mensaje" => "Traslado registrado correctamente."]);
    }

    $buscarTraslado = $conexion->prepare(
        "SELECT estado_traslado.nombre AS estado
         FROM traslado
         INNER JOIN estado_traslado ON estado_traslado.id_estado_traslado = traslado.id_estado_traslado
         WHERE traslado.id_traslado = ?"
    );
    $buscarTraslado->bind_param("i", $idTraslado);
    $buscarTraslado->execute();
    $trasladoActual = $buscarTraslado->get_result()->fetch_assoc();
    $buscarTraslado->close();

    if (!$trasladoActual) {
        responder(404, ["error" => "El traslado no existe."]);
    }
    if ($trasladoActual["estado"] === "Cancelado") {
        responder(409, ["error" => "Un traslado cancelado no se puede actualizar."]);
    }

    $guardarTraslado = $conexion->prepare(
        "UPDATE traslado SET
            id_paciente = ?, matricula = ?, id_conductor = ?, id_acompanante = ?,
            id_tipo_carga = ?, id_tipo_prioridad = ?, descripcion_carga = ?,
            origen = ?, destino = ?, salida_prevista = ?,
            origen_latitud = ?, origen_longitud = ?, destino_latitud = ?, destino_longitud = ?
         WHERE id_traslado = ?"
    );
    $guardarTraslado->bind_param(
        "isiiiissssddddi",
        $idPaciente, $matricula, $idConductor, $idAcompanante,
        $idTipoCarga, $idTipoPrioridad, $descripcionParaGuardar,
        $origen, $destino, $fechaParaGuardar,
        $origenLatitud, $origenLongitud, $destinoLatitud, $destinoLongitud, $idTraslado
    );
    $guardarTraslado->execute();
    $guardarTraslado->close();
    responder(200, ["mensaje" => "Traslado actualizado correctamente."]);
} catch (mysqli_sql_exception $error) {
    responder(500, ["error" => "No se pudo guardar el traslado."]);
}
