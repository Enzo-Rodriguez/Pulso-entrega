<?php

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store");

require_once __DIR__ . "/../acceso.php";
$funcionarioSesion = exigirAcceso(["Administrativo", "Conductor"], true);
$idFuncionarioSesion = (int) $funcionarioSesion["id_funcionario"];
$esConductor = $funcionarioSesion["tipo"] === "Conductor";

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

function idaVueltaDelFormulario(): int
{
    $valor = array_key_exists("ida_vuelta", $_POST) ? textoDelFormulario("ida_vuelta") : "0";
    if (!in_array($valor, ["0", "1"], true)) {
        responder(400, ["error" => "Seleccioná Solo ida o Ida y vuelta."]);
    }
    return (int) $valor;
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

function puedeCancelarEnCurso(mysqli $conexion, ?int $idFuncionario): bool
{
    return $idFuncionario !== null && existe(
        $conexion,
        "SELECT 1 FROM funcionario
         INNER JOIN tipo_funcionario ON tipo_funcionario.id_tipo_funcionario = funcionario.id_tipo_funcionario
         WHERE funcionario.id_funcionario = ? AND funcionario.activo = 1 AND tipo_funcionario.nombre = 'Administrativo'",
        "i",
        $idFuncionario
    );
}

function asignacionDelTraslado(mysqli $conexion, int $idTraslado): ?array
{
    $consulta = $conexion->prepare("SELECT matricula, id_conductor FROM traslado WHERE id_traslado = ?");
    $consulta->bind_param("i", $idTraslado);
    $consulta->execute();
    $asignacion = $consulta->get_result()->fetch_assoc();
    $consulta->close();
    if (!$asignacion) return null;
    $asignacion["id_conductor"] = (int) $asignacion["id_conductor"];
    return $asignacion;
}

// Orden común: funcionarios por ID, ambulancias por matrícula y finalmente el traslado.
function bloquearFuncionarios(mysqli $conexion, array $ids): void
{
    $ids = array_values(array_unique(array_filter(array_map("intval", $ids))));
    sort($ids, SORT_NUMERIC);
    $consulta = $conexion->prepare("SELECT id_funcionario FROM funcionario WHERE id_funcionario = ? FOR UPDATE");
    foreach ($ids as $id) {
        $consulta->bind_param("i", $id);
        $consulta->execute();
        $consulta->get_result()->fetch_assoc();
    }
    $consulta->close();
}

function bloquearAmbulancias(mysqli $conexion, array $matriculas): array
{
    $matriculas = array_values(array_unique(array_map("strtoupper", $matriculas)));
    sort($matriculas, SORT_STRING);
    $ambulancias = [];
    $consulta = $conexion->prepare("SELECT matricula, activa FROM ambulancia WHERE matricula = ? FOR UPDATE");
    foreach ($matriculas as $matricula) {
        $consulta->bind_param("s", $matricula);
        $consulta->execute();
        $ambulancia = $consulta->get_result()->fetch_assoc();
        if ($ambulancia) {
            $ambulancias[$matricula] = $ambulancia;
        }
    }
    $consulta->close();
    return $ambulancias;
}

function trasladoQueOcupaAmbulancia(mysqli $conexion, string $matricula, int $idExcluir = 0): ?int
{
    $consulta = $conexion->prepare(
        "SELECT traslado.id_traslado FROM traslado
         INNER JOIN estado_traslado ON estado_traslado.id_estado_traslado = traslado.id_estado_traslado
         WHERE traslado.matricula = ? AND traslado.id_traslado <> ?
           AND estado_traslado.nombre IN ('En curso', 'En destino', 'En regreso', 'En regreso por cancelación')
         ORDER BY traslado.id_traslado LIMIT 1"
    );
    $consulta->bind_param("si", $matricula, $idExcluir);
    $consulta->execute();
    $idOcupado = $consulta->get_result()->fetch_column();
    $consulta->close();
    return $idOcupado === false ? null : (int) $idOcupado;
}

function trasladoQueOcupaConductor(mysqli $conexion, int $idConductor, int $idExcluir = 0): ?int
{
    // El funcionario ya está bloqueado: otro inicio no puede ocuparlo mientras se comprueba.
    $consulta = $conexion->prepare(
        "SELECT traslado.id_traslado FROM traslado
         INNER JOIN estado_traslado ON estado_traslado.id_estado_traslado = traslado.id_estado_traslado
         WHERE traslado.id_conductor = ? AND traslado.id_traslado <> ?
           AND estado_traslado.nombre IN ('En curso', 'En destino', 'En regreso', 'En regreso por cancelación')
         ORDER BY traslado.id_traslado LIMIT 1"
    );
    $consulta->bind_param("ii", $idConductor, $idExcluir);
    $consulta->execute();
    $idOcupado = $consulta->get_result()->fetch_column();
    $consulta->close();
    return $idOcupado === false ? null : (int) $idOcupado;
}

$metodo = $_SERVER["REQUEST_METHOD"] ?? "";
if (!in_array($metodo, ["GET", "POST"], true)) {
    responder(405, ["error" => "Método no permitido."]);
}

$accion = $metodo === "POST" ? textoDelFormulario("accion") : "";
if ($esConductor && $metodo === "POST" && !in_array($accion, ["iniciar", "llegar_destino", "iniciar_regreso", "finalizar"], true)) {
    responder(403, ["error" => "El conductor solo puede avanzar los traslados que tiene asignados."]);
}

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
    $idaVuelta = idaVueltaDelFormulario();
    [$origenLatitud, $origenLongitud] = coordenadasDelFormulario("origen", false);
    [$destinoLatitud, $destinoLongitud] = coordenadasDelFormulario("destino", false);
    require_once __DIR__ . "/rutas.php";
    try {
        responder(200, calcularRutaTraslado($origenLatitud, $origenLongitud, $destinoLatitud, $destinoLongitud, $idaVuelta === 1));
    } catch (RuntimeException $error) {
        responder($error->getCode(), ["error" => $error->getMessage()]);
    }
}

if ($metodo === "GET") {
    try {
        $consultaTraslados = $conexion->prepare(
            "SELECT
                traslado.id_traslado,
                traslado.id_paciente,
                traslado.matricula,
                traslado.id_creador,
                traslado.id_conductor,
                traslado.id_acompanante,
                traslado.id_tipo_carga,
                traslado.id_tipo_prioridad,
                traslado.ida_vuelta,
                traslado.margen_minutos,
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
                DATE_FORMAT(traslado.llegada_destino, '%d/%m/%Y %H:%i') AS llegada_destino,
                DATE_FORMAT(traslado.llegada_destino, '%Y-%m-%dT%H:%i:%s') AS llegada_destino_calculo,
                DATE_FORMAT(traslado.inicio_regreso, '%d/%m/%Y %H:%i') AS inicio_regreso,
                DATE_FORMAT(traslado.inicio_regreso, '%Y-%m-%dT%H:%i:%s') AS inicio_regreso_calculo,
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
             INNER JOIN estado_traslado ON estado_traslado.id_estado_traslado = traslado.id_estado_traslado "
             . ($esConductor ? "WHERE traslado.id_conductor = ? " : "")
             . "ORDER BY traslado.salida_prevista DESC, traslado.id_traslado DESC"
        );
        if ($esConductor) $consultaTraslados->bind_param("i", $idFuncionarioSesion);
        $consultaTraslados->execute();
        $resultadoTraslados = $consultaTraslados->get_result();

        $pacientes = $esConductor ? [] : $conexion->query(
            "SELECT paciente.id_paciente,
                    CONCAT_WS(' ', persona.nombres, persona.apellidos) AS nombre
             FROM paciente
             INNER JOIN persona ON persona.id_persona = paciente.id_persona
             WHERE paciente.activo = 1
             ORDER BY persona.apellidos, persona.nombres"
        )->fetch_all(MYSQLI_ASSOC);

        $funcionarios = $esConductor ? [] : $conexion->query(
            "SELECT funcionario.id_funcionario,
                    CONCAT_WS(' ', persona.nombres, persona.apellidos) AS nombre,
                    tipo_funcionario.nombre AS tipo,
                    CASE WHEN tipo_funcionario.nombre = 'Conductor' THEN
                        (SELECT MIN(ocupado.id_traslado) FROM traslado AS ocupado
                         INNER JOIN estado_traslado ON estado_traslado.id_estado_traslado = ocupado.id_estado_traslado
                         WHERE ocupado.id_conductor = funcionario.id_funcionario
                           AND estado_traslado.nombre IN ('En curso', 'En destino', 'En regreso', 'En regreso por cancelación'))
                    ELSE NULL END AS id_traslado_ocupado
             FROM funcionario
             INNER JOIN persona ON persona.id_persona = funcionario.id_persona
             INNER JOIN tipo_funcionario ON tipo_funcionario.id_tipo_funcionario = funcionario.id_tipo_funcionario
             WHERE funcionario.activo = 1
             ORDER BY persona.apellidos, persona.nombres"
        )->fetch_all(MYSQLI_ASSOC);

        $ambulancias = $esConductor ? [] : $conexion->query(
            "SELECT ambulancia.matricula, ambulancia.marca, ambulancia.modelo,
                    (SELECT MIN(ocupado.id_traslado) FROM traslado AS ocupado
                     INNER JOIN estado_traslado ON estado_traslado.id_estado_traslado = ocupado.id_estado_traslado
                     WHERE ocupado.matricula = ambulancia.matricula
                       AND estado_traslado.nombre IN ('En curso', 'En destino', 'En regreso', 'En regreso por cancelación')) AS id_traslado_ocupado
             FROM ambulancia
             WHERE ambulancia.activa = 1
             ORDER BY ambulancia.matricula"
        )->fetch_all(MYSQLI_ASSOC);

        $traslados = $resultadoTraslados->fetch_all(MYSQLI_ASSOC);
        $consultaTraslados->close();
        foreach ($traslados as &$traslado) {
            $traslado["ida_vuelta"] = (int) $traslado["ida_vuelta"];
            $traslado["margen_minutos"] = (int) $traslado["margen_minutos"];
        }
        unset($traslado);
        foreach ($funcionarios as &$funcionario) {
            if ($funcionario["id_traslado_ocupado"] !== null) {
                $funcionario["id_traslado_ocupado"] = (int) $funcionario["id_traslado_ocupado"];
            }
        }
        unset($funcionario);
        foreach ($ambulancias as &$ambulancia) {
            if ($ambulancia["id_traslado_ocupado"] !== null) {
                $ambulancia["id_traslado_ocupado"] = (int) $ambulancia["id_traslado_ocupado"];
            }
        }
        unset($ambulancia);

        responder(200, [
            "traslados" => $traslados,
            "permisos" => ["cancelar_en_curso" => !$esConductor && puedeCancelarEnCurso($conexion, $idFuncionarioSesion)],
            "opciones" => [
                "pacientes" => $pacientes,
                "funcionarios" => $funcionarios,
                "ambulancias" => $ambulancias,
                "tipos_carga" => $esConductor ? [] : $conexion->query(
                    "SELECT id_tipo_carga, nombre FROM tipo_carga ORDER BY id_tipo_carga"
                )->fetch_all(MYSQLI_ASSOC),
                "prioridades" => $esConductor ? [] : $conexion->query(
                    "SELECT id_tipo_prioridad, nombre FROM tipo_prioridad ORDER BY id_tipo_prioridad"
                )->fetch_all(MYSQLI_ASSOC),
            ],
        ]);
    } catch (mysqli_sql_exception $error) {
        responder(500, ["error" => "No se pudieron cargar los traslados."]);
    }
}

if (in_array($accion, ["iniciar", "llegar_destino", "iniciar_regreso", "finalizar", "cancelar"], true)) {
    $idTraslado = idDelFormulario("id_traslado");
    $idConductorSeleccionado = array_key_exists("id_conductor", $_POST) ? idDelFormulario("id_conductor") : null;
    $estadosEsperados = [
        "iniciar" => ["Programado"],
        "llegar_destino" => ["En curso"],
        "iniciar_regreso" => ["En destino"],
        "cancelar" => ["Programado", "En curso", "En destino", "En regreso"],
    ];
    $estadosNuevos = [
        "iniciar" => "En curso",
        "llegar_destino" => "En destino",
        "iniciar_regreso" => "En regreso",
        "finalizar" => "Finalizado",
        "cancelar" => "Cancelado",
    ];

    $enTransaccion = false;
    try {
        // Esta lectura inicial va antes de la transacción para no conservar una vista vieja.
        $asignacionAnterior = asignacionDelTraslado($conexion, $idTraslado);
        if ($asignacionAnterior === null) {
            throw new RuntimeException("El traslado no existe.", 404);
        }
        $matriculaAnterior = $asignacionAnterior["matricula"];
        $idConductorAnterior = $asignacionAnterior["id_conductor"];
        $conexion->begin_transaction();
        $enTransaccion = true;
        bloquearFuncionarios($conexion, [$idConductorAnterior]);
        $ambulanciasBloqueadas = bloquearAmbulancias($conexion, [$matriculaAnterior]);
        $buscarEstado = $conexion->prepare(
            "SELECT estado_traslado.nombre AS estado, traslado.matricula,
                    traslado.id_conductor, traslado.ida_vuelta
             FROM traslado
             INNER JOIN estado_traslado ON estado_traslado.id_estado_traslado = traslado.id_estado_traslado
             WHERE traslado.id_traslado = ? FOR UPDATE"
        );
        $buscarEstado->bind_param("i", $idTraslado);
        $buscarEstado->execute();
        $trasladoEstado = $buscarEstado->get_result()->fetch_assoc();
        $buscarEstado->close();

        if (!$trasladoEstado) {
            throw new RuntimeException("El traslado no existe.", 404);
        }
        if ($trasladoEstado["matricula"] !== $matriculaAnterior) {
            throw new RuntimeException("La ambulancia del traslado cambió. Recargá la página.", 409);
        }
        if ((int) $trasladoEstado["id_conductor"] !== $idConductorAnterior) {
            throw new RuntimeException("El conductor del traslado cambió. Recargá la página.", 409);
        }
        if ($esConductor && (int) $trasladoEstado["id_conductor"] !== $idFuncionarioSesion) {
            throw new RuntimeException("El traslado no está asignado al conductor que inició sesión.", 403);
        }
        if ($esConductor && $idConductorSeleccionado !== null && $idConductorSeleccionado !== $idFuncionarioSesion) {
            throw new RuntimeException("No se puede actuar en nombre de otro conductor.", 403);
        }
        if ($idConductorSeleccionado !== null && (int) $trasladoEstado["id_conductor"] !== $idConductorSeleccionado) {
            throw new RuntimeException("El traslado no está asignado al conductor seleccionado.", 403);
        }
        $esIdaVuelta = (int) $trasladoEstado["ida_vuelta"] === 1;
        $estadoActual = $trasladoEstado["estado"];
        $esRegresoCancelado = $estadoActual === "En regreso por cancelación";
        $estadosEsperados["finalizar"] = [$esRegresoCancelado ? "En regreso por cancelación" : ($esIdaVuelta ? "En regreso" : "En curso")];
        if ($esRegresoCancelado) $estadosNuevos["finalizar"] = "Cancelado";
        if (in_array($accion, ["llegar_destino", "iniciar_regreso"], true) && !$esIdaVuelta) {
            throw new RuntimeException("Esta acción corresponde a un traslado de ida y vuelta.", 409);
        }
        if (!in_array($estadoActual, $estadosEsperados[$accion], true)) {
            throw new RuntimeException("No se puede hacer ese cambio desde el estado actual.", 409);
        }
        $esCancelacionActiva = $accion === "cancelar" && $estadoActual !== "Programado";
        if ($esCancelacionActiva) {
            if ($idFuncionarioSesion === null) {
                throw new RuntimeException("Iniciá sesión como Administrativo para cancelar un traslado activo.", 401);
            }
            if (!puedeCancelarEnCurso($conexion, $idFuncionarioSesion)) {
                throw new RuntimeException("Solo un funcionario Administrativo activo puede cancelar un traslado activo.", 403);
            }
            $estadosNuevos["cancelar"] = "En regreso por cancelación";
        }
        if ($accion === "iniciar") {
            $ambulancia = $ambulanciasBloqueadas[strtoupper($matriculaAnterior)] ?? null;
            if (!$ambulancia || (int) $ambulancia["activa"] !== 1) {
                throw new RuntimeException("La ambulancia del traslado ya no está activa.", 409);
            }
            if (!existe(
                $conexion,
                "SELECT 1 FROM funcionario
                 INNER JOIN tipo_funcionario ON tipo_funcionario.id_tipo_funcionario = funcionario.id_tipo_funcionario
                 WHERE funcionario.id_funcionario = ? AND funcionario.activo = 1 AND tipo_funcionario.nombre = 'Conductor'",
                "i",
                (int) $trasladoEstado["id_conductor"]
            )) {
                throw new RuntimeException("El conductor del traslado ya no está activo o no es Conductor.", 409);
            }
            if (trasladoQueOcupaAmbulancia($conexion, $matriculaAnterior, $idTraslado) !== null) {
                throw new RuntimeException("La ambulancia está ocupada en otro traslado. Esperá a que finalice.", 409);
            }
            if (trasladoQueOcupaConductor($conexion, $idConductorAnterior, $idTraslado) !== null) {
                throw new RuntimeException("El conductor está ocupado en otro traslado. Esperá a que finalice.", 409);
            }
        }

        $buscarEstadoNuevo = $conexion->prepare(
            "SELECT id_estado_traslado FROM estado_traslado WHERE nombre = ?"
        );
        $buscarEstadoNuevo->bind_param("s", $estadosNuevos[$accion]);
        $buscarEstadoNuevo->execute();
        $idEstadoNuevo = $buscarEstadoNuevo->get_result()->fetch_column();
        $buscarEstadoNuevo->close();
        if ($idEstadoNuevo === false) {
            throw new RuntimeException("Falta un estado de traslado. Revisá la actualización de la base de datos.", 500);
        }

        $horaActual = (new DateTimeImmutable("now", new DateTimeZone("America/Montevideo")))
            ->format("Y-m-d H:i:s");
        if ($esCancelacionActiva) {
            $actualizarEstado = $conexion->prepare(
                "UPDATE traslado SET id_estado_traslado = ?, inicio_regreso = COALESCE(inicio_regreso, ?) WHERE id_traslado = ?"
            );
            $actualizarEstado->bind_param("isi", $idEstadoNuevo, $horaActual, $idTraslado);
        } elseif ($accion === "cancelar") {
            $actualizarEstado = $conexion->prepare(
                "UPDATE traslado SET id_estado_traslado = ? WHERE id_traslado = ?"
            );
            $actualizarEstado->bind_param("ii", $idEstadoNuevo, $idTraslado);
        } else {
            $campoHora = match ($accion) {
                "iniciar" => "salida_real",
                "llegar_destino" => "llegada_destino",
                "iniciar_regreso" => "inicio_regreso",
                "finalizar" => "llegada_real",
            };
            if ($accion === "finalizar" && !$esIdaVuelta && !$esRegresoCancelado) {
                $actualizarEstado = $conexion->prepare(
                    "UPDATE traslado SET id_estado_traslado = ?, llegada_real = ?, llegada_destino = ? WHERE id_traslado = ?"
                );
                $actualizarEstado->bind_param("issi", $idEstadoNuevo, $horaActual, $horaActual, $idTraslado);
            } else {
                $actualizarEstado = $conexion->prepare(
                    "UPDATE traslado SET id_estado_traslado = ?, $campoHora = ? WHERE id_traslado = ?"
                );
                $actualizarEstado->bind_param("isi", $idEstadoNuevo, $horaActual, $idTraslado);
            }
        }
        $actualizarEstado->execute();
        $actualizarEstado->close();
        $conexion->commit();
        $enTransaccion = false;

        $mensajes = [
            "iniciar" => "Traslado iniciado correctamente.",
            "llegar_destino" => "Llegada al destino registrada. La ambulancia continúa ocupada.",
            "iniciar_regreso" => "Regreso al origen iniciado correctamente.",
            "finalizar" => "Traslado finalizado correctamente.",
            "cancelar" => "Traslado dado de baja y conservado como Cancelado.",
        ];
        if ($esCancelacionActiva) {
            $mensajes["cancelar"] = "Cancelación registrada. La ambulancia debe regresar al origen para cerrar el traslado.";
        }
        if ($accion === "finalizar" && $esRegresoCancelado) {
            $mensajes["finalizar"] = "Regreso al origen registrado. El traslado quedó Cancelado.";
        }
        responder(200, ["mensaje" => $mensajes[$accion]]);
    } catch (mysqli_sql_exception $error) {
        if ($enTransaccion) $conexion->rollback();
        responder(500, ["error" => "No se pudo cambiar el estado del traslado."]);
    } catch (RuntimeException $error) {
        if ($enTransaccion) $conexion->rollback();
        responder($error->getCode(), ["error" => $error->getMessage()]);
    } catch (Throwable $error) {
        if ($enTransaccion) $conexion->rollback();
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
$idCreador = $esNuevo ? $idFuncionarioSesion : null;
if ($esNuevo && array_key_exists("id_creador", $_POST) && idDelFormulario("id_creador") !== $idFuncionarioSesion) {
    responder(403, ["error" => "El creador del traslado debe ser el funcionario que inició sesión."]);
}
$matricula = textoDelFormulario("matricula");
$origen = textoDelFormulario("origen");
$destino = textoDelFormulario("destino");
$descripcion = textoDelFormulario("descripcion_carga");
$salidaPrevista = textoDelFormulario("salida_prevista");
$idaVuelta = idaVueltaDelFormulario();
$textoMargen = array_key_exists("margen_minutos", $_POST) ? textoDelFormulario("margen_minutos") : "0";
$margenMinutos = filter_var($textoMargen, FILTER_VALIDATE_INT);
if ($margenMinutos === false || $margenMinutos < 0 || $margenMinutos > 1440) {
    responder(400, ["error" => "El margen debe ser un número entero entre 0 y 1440 minutos."]);
}

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

$enTransaccion = false;
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

    $asignacionAnterior = $esNuevo
        ? ["matricula" => $matricula, "id_conductor" => $idConductor]
        : asignacionDelTraslado($conexion, $idTraslado);
    if ($asignacionAnterior === null) {
        throw new RuntimeException("El traslado no existe.", 404);
    }
    $matriculaAnterior = $asignacionAnterior["matricula"];
    $idConductorAnterior = $asignacionAnterior["id_conductor"];
    $conexion->begin_transaction();
    $enTransaccion = true;
    // Acompañante y creador también son FK: se bloquean en el mismo orden para evitar cruces.
    bloquearFuncionarios($conexion, [$idConductorAnterior, $idConductor, $idAcompanante, $idCreador]);
    $ambulanciasBloqueadas = bloquearAmbulancias($conexion, [$matriculaAnterior, $matricula]);
    $ambulancia = $ambulanciasBloqueadas[strtoupper($matricula)] ?? null;
    if (!$ambulancia || (int) $ambulancia["activa"] !== 1) {
        throw new RuntimeException("La ambulancia seleccionada no está disponible.", 400);
    }
    $matricula = $ambulancia["matricula"];

    if (!$esNuevo) {
        $buscarTraslado = $conexion->prepare(
            "SELECT traslado.matricula, traslado.id_conductor, estado_traslado.nombre AS estado
             FROM traslado
             INNER JOIN estado_traslado ON estado_traslado.id_estado_traslado = traslado.id_estado_traslado
             WHERE traslado.id_traslado = ? FOR UPDATE"
        );
        $buscarTraslado->bind_param("i", $idTraslado);
        $buscarTraslado->execute();
        $trasladoActual = $buscarTraslado->get_result()->fetch_assoc();
        $buscarTraslado->close();
        if (!$trasladoActual) {
            throw new RuntimeException("El traslado no existe.", 404);
        }
        if ($trasladoActual["matricula"] !== $matriculaAnterior) {
            throw new RuntimeException("La ambulancia del traslado cambió. Recargá la página.", 409);
        }
        if ((int) $trasladoActual["id_conductor"] !== $idConductorAnterior) {
            throw new RuntimeException("El conductor del traslado cambió. Recargá la página.", 409);
        }
        if ($trasladoActual["estado"] !== "Programado") {
            throw new RuntimeException("Solo se pueden actualizar traslados Programados.", 409);
        }
    }
    if (trasladoQueOcupaAmbulancia($conexion, $matricula, $idTraslado ?? 0) !== null) {
        throw new RuntimeException("La ambulancia está ocupada en otro traslado. Esperá a que finalice.", 409);
    }
    if (!existe(
        $conexion,
        "SELECT 1 FROM funcionario
         INNER JOIN tipo_funcionario ON tipo_funcionario.id_tipo_funcionario = funcionario.id_tipo_funcionario
         WHERE funcionario.id_funcionario = ? AND funcionario.activo = 1 AND tipo_funcionario.nombre = 'Conductor'",
        "i",
        $idConductor
    )) {
        throw new RuntimeException("Seleccioná un conductor activo.", 400);
    }
    if (trasladoQueOcupaConductor($conexion, $idConductor, $idTraslado ?? 0) !== null) {
        throw new RuntimeException("El conductor está ocupado en otro traslado. Esperá a que finalice.", 409);
    }

    $fechaParaGuardar = $fechaPrevista->format("Y-m-d H:i:s");
    $descripcionParaGuardar = $descripcion === "" ? null : $descripcion;

    if ($esNuevo) {
        $idProgramado = $conexion->query(
            "SELECT id_estado_traslado FROM estado_traslado WHERE nombre = 'Programado'"
        )->fetch_column();
        if ($idProgramado === false) {
            throw new RuntimeException("Falta el estado Programado en la base de datos.", 500);
        }

        $guardarTraslado = $conexion->prepare(
            "INSERT INTO traslado
                (id_paciente, matricula, id_creador, id_conductor, id_acompanante,
                 id_tipo_carga, id_tipo_prioridad, id_estado_traslado, ida_vuelta, margen_minutos, descripcion_carga,
                 origen, destino, salida_prevista,
                 origen_latitud, origen_longitud, destino_latitud, destino_longitud)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $guardarTraslado->bind_param(
            "isiiiiiiiissssdddd",
            $idPaciente, $matricula, $idCreador, $idConductor, $idAcompanante,
            $idTipoCarga, $idTipoPrioridad, $idProgramado, $idaVuelta, $margenMinutos, $descripcionParaGuardar,
            $origen, $destino, $fechaParaGuardar,
            $origenLatitud, $origenLongitud, $destinoLatitud, $destinoLongitud
        );
        $guardarTraslado->execute();
        $guardarTraslado->close();
        $conexion->commit();
        $enTransaccion = false;
        responder(201, ["mensaje" => "Traslado registrado correctamente."]);
    }

    $guardarTraslado = $conexion->prepare(
        "UPDATE traslado SET
            id_paciente = ?, matricula = ?, id_conductor = ?, id_acompanante = ?,
            id_tipo_carga = ?, id_tipo_prioridad = ?, ida_vuelta = ?, margen_minutos = ?, descripcion_carga = ?,
            origen = ?, destino = ?, salida_prevista = ?,
            origen_latitud = ?, origen_longitud = ?, destino_latitud = ?, destino_longitud = ?
         WHERE id_traslado = ?"
    );
    $guardarTraslado->bind_param(
        "isiiiiiissssddddi",
        $idPaciente, $matricula, $idConductor, $idAcompanante,
        $idTipoCarga, $idTipoPrioridad, $idaVuelta, $margenMinutos, $descripcionParaGuardar,
        $origen, $destino, $fechaParaGuardar,
        $origenLatitud, $origenLongitud, $destinoLatitud, $destinoLongitud, $idTraslado
    );
    $guardarTraslado->execute();
    $guardarTraslado->close();
    $conexion->commit();
    $enTransaccion = false;
    responder(200, ["mensaje" => "Traslado actualizado correctamente."]);
} catch (mysqli_sql_exception $error) {
    if ($enTransaccion) $conexion->rollback();
    responder(500, ["error" => "No se pudo guardar el traslado."]);
} catch (RuntimeException $error) {
    if ($enTransaccion) $conexion->rollback();
    responder($error->getCode(), ["error" => $error->getMessage()]);
}
