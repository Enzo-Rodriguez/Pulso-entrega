<?php

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

function responderIngreso(int $codigo, array $datos): never
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    responderIngreso(405, ['error' => 'Método no permitido.']);
}
// El ingreso se hace desde el propio sitio, no desde un formulario de otra web.
if (($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') === 'cross-site') {
    responderIngreso(403, ['error' => 'Ingresá desde la página de inicio del sistema.']);
}
require_once __DIR__ . '/php/acceso.php';
$ci = is_string($_POST['ci'] ?? null) ? trim($_POST['ci']) : '';
$contrasenia = is_string($_POST['contrasenia'] ?? null) ? $_POST['contrasenia'] : '';
if ($ci === '' || $contrasenia === '') {
    responderIngreso(400, ['error' => 'Debe ingresar la cédula y la contraseña.']);
}

try {
    $consultaIngreso = $conexion->prepare(
        "SELECT persona.id_persona, funcionario.id_funcionario, persona.nombres,
                persona.apellidos, persona.email, persona.contrasenia,
                funcionario.activo, tipo_funcionario.nombre AS tipo
         FROM persona
         INNER JOIN funcionario ON funcionario.id_persona = persona.id_persona
         INNER JOIN tipo_funcionario ON tipo_funcionario.id_tipo_funcionario = funcionario.id_tipo_funcionario
         WHERE persona.ci = ? LIMIT 1"
    );
    $consultaIngreso->bind_param('s', $ci);
    $consultaIngreso->execute();
    $fila = $consultaIngreso->get_result()->fetch_assoc();
    $consultaIngreso->close();
    if (!$fila) responderIngreso(401, ['error' => 'La cédula o la contraseña no son correctas.']);
    if (!(int) $fila['activo']) responderIngreso(403, ['error' => 'El funcionario está inactivo.']);
    if (modulosDelFuncionario($fila['tipo']) === []) responderIngreso(403, ['error' => 'El tipo de funcionario no tiene acceso configurado.']);

    // Se conserva el primer ingreso acordado para esta entrega.
    if ($fila['contrasenia'] === null) {
        $claveGuardada = password_hash($contrasenia, PASSWORD_DEFAULT);
        $guardarPrimeraClave = $conexion->prepare('UPDATE persona SET contrasenia = ? WHERE id_persona = ? AND contrasenia IS NULL');
        $guardarPrimeraClave->bind_param('si', $claveGuardada, $fila['id_persona']);
        $guardarPrimeraClave->execute();
        $creada = $guardarPrimeraClave->affected_rows === 1;
        $guardarPrimeraClave->close();
        if (!$creada) {
            // Otro ingreso simultáneo pudo registrar la primera clave antes.
            $consultaClave = $conexion->prepare('SELECT contrasenia FROM persona WHERE id_persona = ?');
            $consultaClave->bind_param('i', $fila['id_persona']);
            $consultaClave->execute();
            $claveGuardada = $consultaClave->get_result()->fetch_column();
            $consultaClave->close();
        }
    } else {
        $claveGuardada = $fila['contrasenia'];
    }
    if (!is_string($claveGuardada) || !password_verify($contrasenia, $claveGuardada)) {
        responderIngreso(401, ['error' => 'La cédula o la contraseña no son correctas.']);
    }

    iniciarSesionPulso();
    session_regenerate_id(true);
    $_SESSION = [
        'id_persona' => (int) $fila['id_persona'],
        'id_funcionario' => (int) $fila['id_funcionario'],
        'ci' => $ci, 'nombre' => $fila['nombres'], 'apellidos' => $fila['apellidos'],
        'email' => $fila['email'], 'csrf' => bin2hex(random_bytes(32)),
    ];
    session_write_close();
    responderIngreso(200, ['exito' => true, 'destino' => rutaDelProyecto(destinoDelFuncionario($fila['tipo']))]);
} catch (Throwable $error) {
    responderIngreso(500, ['error' => 'No se pudo completar el ingreso. Intentá nuevamente.']);
}
