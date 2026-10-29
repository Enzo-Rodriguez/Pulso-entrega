<?php

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store");

require_once __DIR__ . "/../acceso.php";
$funcionarioSesion = exigirAcceso(["Administrativo", "Conductor"], true);
$esConductor = $funcionarioSesion["tipo"] === "Conductor";
$idFuncionarioSesion = (int) $funcionarioSesion["id_funcionario"];

function responderSeguimiento(int $codigo, array $datos): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function textoSeguimiento(string $campo): string
{
    $valor = $_POST[$campo] ?? "";
    return is_string($valor) ? trim($valor) : "";
}

function idSeguimiento(string $campo): int
{
    $id = filter_var(textoSeguimiento($campo), FILTER_VALIDATE_INT);
    if ($id === false || $id < 1) {
        responderSeguimiento(400, ["error" => "El campo $campo no es válido."]);
    }
    return $id;
}

function numeroSeguimiento(string $campo, float $minimo, float $maximo): float
{
    $numero = filter_var(textoSeguimiento($campo), FILTER_VALIDATE_FLOAT);
    if ($numero === false || !is_finite($numero) || $numero < $minimo || $numero > $maximo) {
        responderSeguimiento(400, ["error" => "El campo $campo no es válido."]);
    }
    return $numero;
}

function horaSeguimiento(): string
{
    return (new DateTimeImmutable("now", new DateTimeZone("America/Montevideo")))->format("Y-m-d H:i:s");
}

$metodo = $_SERVER["REQUEST_METHOD"] ?? "";
if (!in_array($metodo, ["GET", "POST"], true)) {
    responderSeguimiento(405, ["error" => "Método no permitido."]);
}
if ($metodo === "POST") {
    if (!$esConductor) {
        responderSeguimiento(403, ["error" => "Solo el conductor que inició sesión puede registrar su ubicación."]);
    }
    if (textoSeguimiento("accion") !== "registrar_ubicacion") {
        responderSeguimiento(400, ["error" => "Acción no válida."]);
    }
    $idTraslado = idSeguimiento("id_traslado");
    $idConductor = $idFuncionarioSesion;
    if (array_key_exists("id_conductor", $_POST) && idSeguimiento("id_conductor") !== $idConductor) {
        responderSeguimiento(403, ["error" => "No se puede registrar la ubicación de otro conductor."]);
    }
    $latitud = round(numeroSeguimiento("latitud", -90, 90), 7);
    $longitud = round(numeroSeguimiento("longitud", -180, 180), 7);
    $precisionMetros = round(numeroSeguimiento("precision_metros", 0, 1000000), 2);
}

if ($metodo === "GET") {
    try {
        $horaActual = horaSeguimiento();
        $consultarSeguimientos = $conexion->prepare(
            "SELECT traslado.id_traslado, traslado.id_conductor, traslado.matricula, traslado.ida_vuelta, traslado.margen_minutos,
                    CONCAT_WS(' ', persona.nombres, persona.apellidos) AS nombre_conductor,
                    estado_traslado.nombre AS estado,
                    DATE_FORMAT(traslado.salida_real, '%d/%m/%Y %H:%i') AS salida_real,
                    DATE_FORMAT(traslado.llegada_destino, '%d/%m/%Y %H:%i') AS llegada_destino,
                    DATE_FORMAT(traslado.llegada_destino, '%Y-%m-%dT%H:%i:%s') AS llegada_destino_calculo,
                    DATE_FORMAT(traslado.inicio_regreso, '%d/%m/%Y %H:%i') AS inicio_regreso,
                    DATE_FORMAT(traslado.inicio_regreso, '%Y-%m-%dT%H:%i:%s') AS inicio_regreso_calculo,
                    DATE_FORMAT(traslado.llegada_real, '%d/%m/%Y %H:%i') AS llegada_real,
                    DATE_FORMAT(COALESCE(traslado.salida_real, traslado.salida_prevista), '%Y-%m-%dT%H:%i:%s') AS salida_calculo,
                    ubicacion.latitud, ubicacion.longitud, ubicacion.precision_metros,
                    DATE_FORMAT(ubicacion.fecha_registro, '%d/%m/%Y %H:%i:%s') AS fecha_registro,
                    GREATEST(0, TIMESTAMPDIFF(SECOND, ubicacion.fecha_registro, ?)) AS segundos_desde_registro
             FROM traslado
             INNER JOIN estado_traslado ON estado_traslado.id_estado_traslado = traslado.id_estado_traslado
             INNER JOIN funcionario ON funcionario.id_funcionario = traslado.id_conductor
             INNER JOIN persona ON persona.id_persona = funcionario.id_persona
             LEFT JOIN ubicacion_traslado AS ubicacion ON ubicacion.id_ubicacion = (
                 SELECT MAX(historial.id_ubicacion) FROM ubicacion_traslado AS historial
                 WHERE historial.id_traslado = traslado.id_traslado
                   AND historial.id_conductor = traslado.id_conductor
             ) "
             . ($esConductor ? "WHERE traslado.id_conductor = ? " : "")
             . "ORDER BY traslado.id_traslado DESC"
        );
        if ($esConductor) {
            $consultarSeguimientos->bind_param("si", $horaActual, $idFuncionarioSesion);
        } else {
            $consultarSeguimientos->bind_param("s", $horaActual);
        }
        $consultarSeguimientos->execute();
        $seguimientos = $consultarSeguimientos->get_result()->fetch_all(MYSQLI_ASSOC);
        $consultarSeguimientos->close();
        foreach ($seguimientos as &$seguimiento) {
            $seguimiento["id_traslado"] = (int) $seguimiento["id_traslado"];
            $seguimiento["id_conductor"] = (int) $seguimiento["id_conductor"];
            $seguimiento["ida_vuelta"] = (int) $seguimiento["ida_vuelta"];
            $seguimiento["margen_minutos"] = (int) $seguimiento["margen_minutos"];
            foreach (["latitud", "longitud", "precision_metros"] as $campo) {
                if ($seguimiento[$campo] !== null) $seguimiento[$campo] = (float) $seguimiento[$campo];
            }
            if ($seguimiento["segundos_desde_registro"] !== null) {
                $seguimiento["segundos_desde_registro"] = (int) $seguimiento["segundos_desde_registro"];
            }
        }
        unset($seguimiento);
        responderSeguimiento(200, ["seguimientos" => $seguimientos]);
    } catch (mysqli_sql_exception $error) {
        responderSeguimiento(500, ["error" => "No se pudieron cargar las ubicaciones de los traslados."]);
    }
}

$enTransaccion = false;
try {
    // Igual que en el ABM: funcionario, ambulancia y finalmente el traslado.
    $buscarAsignacion = $conexion->prepare("SELECT matricula, id_conductor FROM traslado WHERE id_traslado = ?");
    $buscarAsignacion->bind_param("i", $idTraslado);
    $buscarAsignacion->execute();
    $asignacionAnterior = $buscarAsignacion->get_result()->fetch_assoc();
    $buscarAsignacion->close();
    if (!$asignacionAnterior) {
        responderSeguimiento(404, ["error" => "El traslado no existe."]);
    }
    $matriculaAnterior = $asignacionAnterior["matricula"];
    $idConductorAnterior = (int) $asignacionAnterior["id_conductor"];
    $conexion->begin_transaction();
    $enTransaccion = true;
    $bloquearConductor = $conexion->prepare("SELECT id_funcionario FROM funcionario WHERE id_funcionario = ? FOR UPDATE");
    $bloquearConductor->bind_param("i", $idConductorAnterior);
    $bloquearConductor->execute();
    $bloquearConductor->get_result()->fetch_assoc();
    $bloquearConductor->close();
    $bloquearAmbulancia = $conexion->prepare("SELECT matricula FROM ambulancia WHERE matricula = ? FOR UPDATE");
    $bloquearAmbulancia->bind_param("s", $matriculaAnterior);
    $bloquearAmbulancia->execute();
    $bloquearAmbulancia->get_result()->fetch_assoc();
    $bloquearAmbulancia->close();
    $buscarTraslado = $conexion->prepare(
        "SELECT traslado.matricula, traslado.id_conductor, estado_traslado.nombre AS estado,
                funcionario.activo, tipo_funcionario.nombre AS tipo_conductor
         FROM traslado
         INNER JOIN estado_traslado ON estado_traslado.id_estado_traslado = traslado.id_estado_traslado
         INNER JOIN funcionario ON funcionario.id_funcionario = traslado.id_conductor
         INNER JOIN tipo_funcionario ON tipo_funcionario.id_tipo_funcionario = funcionario.id_tipo_funcionario
         WHERE traslado.id_traslado = ? FOR UPDATE"
    );
    $buscarTraslado->bind_param("i", $idTraslado);
    $buscarTraslado->execute();
    $traslado = $buscarTraslado->get_result()->fetch_assoc();
    $buscarTraslado->close();

    if (!$traslado) {
        $conexion->rollback();
        responderSeguimiento(404, ["error" => "El traslado no existe."]);
    }
    if ($traslado["matricula"] !== $matriculaAnterior) {
        $conexion->rollback();
        responderSeguimiento(409, ["error" => "La ambulancia del traslado cambió. Recargá la página."]);
    }
    if ((int) $traslado["id_conductor"] !== $idConductorAnterior) {
        $conexion->rollback();
        responderSeguimiento(409, ["error" => "El conductor del traslado cambió. Recargá la página."]);
    }
    if ((int) $traslado["id_conductor"] !== $idConductor) {
        $conexion->rollback();
        responderSeguimiento(403, ["error" => "El traslado no está asignado al conductor seleccionado."]);
    }
    if ((int) $traslado["activo"] !== 1 || $traslado["tipo_conductor"] !== "Conductor") {
        $conexion->rollback();
        responderSeguimiento(403, ["error" => "El conductor seleccionado no está disponible para registrar ubicaciones."]);
    }
    if (!in_array($traslado["estado"], ["En curso", "En destino", "En regreso", "En regreso por cancelación"], true)) {
        $conexion->rollback();
        responderSeguimiento(409, ["error" => "Solo se puede registrar la ubicación mientras el traslado está en curso, en destino o regresando al origen."]);
    }

    $fechaRegistro = horaSeguimiento();
    $guardarUbicacion = $conexion->prepare(
        "INSERT INTO ubicacion_traslado
            (id_traslado, id_conductor, latitud, longitud, precision_metros, fecha_registro)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $guardarUbicacion->bind_param("iiddds", $idTraslado, $idConductor, $latitud, $longitud, $precisionMetros, $fechaRegistro);
    $guardarUbicacion->execute();
    $guardarUbicacion->close();
    $conexion->commit();
    $enTransaccion = false;
    $fechaParaMostrar = (new DateTimeImmutable($fechaRegistro, new DateTimeZone("America/Montevideo")))->format("d/m/Y H:i:s");
    responderSeguimiento(201, ["mensaje" => "Ubicación registrada correctamente.", "fecha_registro" => $fechaParaMostrar]);
} catch (mysqli_sql_exception $error) {
    if ($enTransaccion) $conexion->rollback();
    responderSeguimiento(500, ["error" => "No se pudo guardar la ubicación del traslado."]);
}
