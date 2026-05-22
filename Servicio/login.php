<?php
require_once __DIR__ . '/config/bootstrap.php';

app_start_session();
app_security_headers();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_redirect('index.php');
}

$correo = trim($_POST['correo'] ?? '');
$password = (string) ($_POST['password'] ?? '');

if ($correo === '' || $password === '') {
    app_flash('login_error', 'Ingresa tu correo institucional y contrasena.');
    app_redirect('index.php');
}

try {
    $pdo = app_db();

    $stmt = $pdo->prepare(
        'SELECT u.id, u.rol_id, u.correo, u.password_hash, u.nombres, u.apellido_paterno,
                u.apellido_materno, u.estatus, u.bloqueado_hasta, u.intentos_fallidos,
                r.codigo AS rol_codigo, r.nombre AS rol_nombre
         FROM usuarios u
         INNER JOIN roles r ON r.id = u.rol_id
         WHERE u.correo = :correo
         LIMIT 1'
    );
    $stmt->execute(['correo' => $correo]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        app_flash('login_error', 'Los datos de acceso no son correctos.');
        app_redirect('index.php');
    }

    if (!empty($usuario['bloqueado_hasta']) && strtotime($usuario['bloqueado_hasta']) > time()) {
        app_flash('login_error', 'La cuenta esta bloqueada temporalmente. Intenta mas tarde.');
        app_redirect('index.php');
    }

    if ($usuario['estatus'] !== 'activo') {
        app_flash('login_error', 'La cuenta aun no esta activa o fue deshabilitada.');
        app_redirect('index.php');
    }

    if (!password_verify($password, $usuario['password_hash'])) {
        $intentos = min(((int) $usuario['intentos_fallidos']) + 1, 20);
        $bloquear = $intentos >= 5;

        $update = $pdo->prepare(
            'UPDATE usuarios
             SET intentos_fallidos = :intentos,
                 bloqueado_hasta = ' . ($bloquear ? 'DATE_ADD(NOW(), INTERVAL 15 MINUTE)' : 'NULL') . '
             WHERE id = :id'
        );
        $update->execute([
            'intentos' => $intentos,
            'id' => $usuario['id'],
        ]);

        app_flash('login_error', 'Los datos de acceso no son correctos.');
        app_redirect('index.php');
    }

    session_regenerate_id(true);

    $nombreCompleto = trim(
        $usuario['nombres'] . ' ' .
        $usuario['apellido_paterno'] . ' ' .
        ($usuario['apellido_materno'] ?? '')
    );

    $_SESSION['usuario_id'] = (int) $usuario['id'];
    $_SESSION['usuario_nombre'] = $nombreCompleto;
    $_SESSION['correo'] = $usuario['correo'];
    $_SESSION['rol_id'] = (int) $usuario['rol_id'];
    $_SESSION['rol_codigo'] = $usuario['rol_codigo'];
    $_SESSION['rol_nombre'] = $usuario['rol_nombre'];

    $sessionIdHash = hash('sha256', session_id(), true);
    $csrfSecret = bin2hex(random_bytes(32));
    $_SESSION['csrf_secret'] = $csrfSecret;

    $pdo->prepare(
        'UPDATE usuarios
         SET intentos_fallidos = 0,
             bloqueado_hasta = NULL,
             ultimo_acceso_en = NOW()
         WHERE id = :id'
    )->execute(['id' => $usuario['id']]);

    $pdo->prepare(
        'INSERT INTO sesiones_usuario (
            usuario_id,
            session_id_hash,
            csrf_secret_hash,
            ip_hash,
            user_agent_hash,
            expira_en
         ) VALUES (
            :usuario_id,
            :session_id_hash,
            :csrf_secret_hash,
            :ip_hash,
            :user_agent_hash,
            DATE_ADD(NOW(), INTERVAL 2 HOUR)
         )'
    )->execute([
        'usuario_id' => $usuario['id'],
        'session_id_hash' => $sessionIdHash,
        'csrf_secret_hash' => hash('sha256', $csrfSecret, true),
        'ip_hash' => hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '', true),
        'user_agent_hash' => hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '', true),
    ]);

    app_redirect(app_user_home($usuario['rol_codigo']));
} catch (Throwable $exception) {
    app_flash('login_error', 'No fue posible iniciar sesion en este momento.');
    app_redirect('index.php');
}
