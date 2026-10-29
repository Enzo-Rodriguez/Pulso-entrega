<?php

require_once __DIR__ . '/conexion.php';

// Todas las páginas consultan la identidad real; el navegador no elige el rol.
function iniciarSesionPulso(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    // Evita mezclar la sesión de PULSO con otros proyectos del mismo servidor.
    session_name('PULSO_SESION');
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'path' => rutaDelProyecto(''),
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    if (!session_start()) throw new RuntimeException('No se pudo iniciar la sesión.');
}

function rutaDelProyecto(string $destino): string
{
    $pagina = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/inicio.php');
    $posicionPhp = strrpos($pagina, '/php/');
    $base = $posicionPhp === false ? rtrim(dirname($pagina), '/.') : substr($pagina, 0, $posicionPhp);
    return $base . '/' . ltrim($destino, '/');
}

function destinoDelFuncionario(string $tipo): string
{
    return $tipo === 'Conductor' ? 'php/traslados/chofer.html' : 'panel.html';
}

function modulosDelFuncionario(string $tipo): array
{
    return match ($tipo) {
        'Administrativo' => ['panel', 'pacientes', 'documentos', 'funcionarios', 'ambulancias', 'equipamientos', 'traslados'],
        'Conductor' => ['chofer'],
        'Médico', 'Enfermería' => ['panel', 'pacientes', 'documentos'],
        default => [],
    };
}

function funcionarioActual(): ?array
{
    global $conexion;
    static $consultado = false;
    static $funcionario = null;
    if ($consultado) return $funcionario;
    $consultado = true;
    iniciarSesionPulso();
    $id = filter_var($_SESSION['id_funcionario'] ?? null, FILTER_VALIDATE_INT);
    if (!is_string($_SESSION['csrf'] ?? null) || strlen($_SESSION['csrf']) !== 64) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    $csrf = $_SESSION['csrf'];
    session_write_close(); // No bloquea otras consultas AJAX mientras se trabaja.
    if ($id === false || $id === null || $id < 1) return null;

    $consulta = $conexion->prepare(
        "SELECT funcionario.id_funcionario, funcionario.id_persona,
                persona.nombres AS nombre, persona.apellidos, tipo_funcionario.nombre AS tipo
         FROM funcionario
         INNER JOIN persona ON persona.id_persona = funcionario.id_persona
         INNER JOIN tipo_funcionario ON tipo_funcionario.id_tipo_funcionario = funcionario.id_tipo_funcionario
         WHERE funcionario.id_funcionario = ? AND funcionario.activo = 1"
    );
    $consulta->bind_param('i', $id);
    $consulta->execute();
    $fila = $consulta->get_result()->fetch_assoc();
    $consulta->close();
    if (!$fila || modulosDelFuncionario($fila['tipo']) === []) return null;
    $fila['id_funcionario'] = (int) $fila['id_funcionario'];
    $fila['id_persona'] = (int) $fila['id_persona'];
    $fila['csrf'] = $csrf;
    $funcionario = $fila;
    return $funcionario;
}

function denegarAcceso(int $codigo, string $mensaje, bool $json, string $destino): never
{
    http_response_code($codigo);
    header('Cache-Control: no-store');
    if ($json) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['error' => $mensaje, 'destino' => rutaDelProyecto($destino)], JSON_UNESCAPED_UNICODE);
    } elseif ($codigo === 401) {
        header('Location: ' . rutaDelProyecto('index.html'), true, 302);
    } else {
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>Acceso no permitido</title>';
        echo '<h1>Acceso no permitido</h1><p>' . htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . '</p>';
        echo '<a href="' . htmlspecialchars(rutaDelProyecto($destino), ENT_QUOTES, 'UTF-8') . '">Volver a mi página</a></html>';
    }
    exit;
}

function exigirAcceso(array $tipos, bool $json = false): array
{
    header('Cache-Control: no-store');
    $funcionario = funcionarioActual();
    if (!$funcionario) denegarAcceso(401, 'Iniciá sesión para continuar.', $json, 'index.html');
    $destino = destinoDelFuncionario($funcionario['tipo']);
    if (!in_array($funcionario['tipo'], $tipos, true)) {
        denegarAcceso(403, 'Tu tipo de funcionario no tiene permiso para esta operación.', $json, $destino);
    }
    if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
        $csrf = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!is_string($csrf) || !hash_equals($funcionario['csrf'], $csrf)) {
            denegarAcceso(403, 'La sesión del formulario cambió. Recargá la página antes de continuar.', $json, $destino);
        }
    }
    return $funcionario;
}

function esAdministrativo(): bool
{
    return (funcionarioActual()['tipo'] ?? '') === 'Administrativo';
}

function campoCsrf(): string
{
    $csrf = funcionarioActual()['csrf'] ?? '';
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') . '">';
}
