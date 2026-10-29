<?php

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/acceso.php';
$metodo = $_SERVER['REQUEST_METHOD'] ?? '';
if (!in_array($metodo, ['GET', 'POST'], true)) {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}
$funcionario = exigirAcceso(['Administrativo', 'Conductor', 'Médico', 'Enfermería'], true);
if ($metodo === 'POST') {
    if (($_POST['accion'] ?? '') !== 'cerrar') {
        http_response_code(400);
        echo json_encode(['error' => 'Acción no válida.']);
        exit;
    }
    iniciarSesionPulso();
    $_SESSION = [];
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 3600, 'path' => $cookie['path'], 'domain' => $cookie['domain'],
        'secure' => $cookie['secure'], 'httponly' => true, 'samesite' => $cookie['samesite'],
    ]);
    session_destroy();
    echo json_encode(['exito' => true, 'destino' => rutaDelProyecto('index.html')]);
    exit;
}
$csrf = $funcionario['csrf'];
unset($funcionario['csrf']);
echo json_encode([
    'funcionario' => $funcionario,
    'modulos' => modulosDelFuncionario($funcionario['tipo']),
    'destino' => rutaDelProyecto(destinoDelFuncionario($funcionario['tipo'])),
    'csrf' => $csrf,
], JSON_UNESCAPED_UNICODE);
