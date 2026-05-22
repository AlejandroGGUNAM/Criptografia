<?php
require_once __DIR__ . '/config/bootstrap.php';

app_start_session();
app_security_headers();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_redirect('recuperar-password.php');
}

if (!app_verify_csrf('solicitar_recuperacion', $_POST['csrf_token'] ?? '')) {
    app_flash('login_error', 'La sesion expiro. Intenta nuevamente.');
    app_redirect('recuperar-password.php');
}

$correo = trim($_POST['correo'] ?? '');

if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    app_flash('login_error', 'Ingresa un correo valido.');
    app_redirect('recuperar-password.php');
}

try {
    $pdo = app_db();
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE correo = :correo LIMIT 1');
    $stmt->execute(['correo' => $correo]);
    $usuarioId = $stmt->fetchColumn();

    if ($usuarioId) {
        $token = bin2hex(random_bytes(32));

        $pdo->prepare(
            'INSERT INTO tokens_seguridad (
                usuario_id,
                proposito,
                token_hash,
                expira_en,
                ip_creacion,
                user_agent_hash
             ) VALUES (
                :usuario_id,
                "recuperacion_password",
                :token_hash,
                DATE_ADD(NOW(), INTERVAL 30 MINUTE),
                :ip_creacion,
                :user_agent_hash
             )'
        )->execute([
            'usuario_id' => $usuarioId,
            'token_hash' => hash('sha256', $token, true),
            'ip_creacion' => inet_pton($_SERVER['REMOTE_ADDR'] ?? '') ?: null,
            'user_agent_hash' => hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '', true),
        ]);

        app_audit('auth.recuperacion.generar', 'usuario', (int) $usuarioId);

        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
        $path = ($base === '' ? '' : $base) . '/restablecer-password.php?token=' . urlencode($token);
        app_flash('recovery_link', $path);
    }

    app_flash('login_message', 'Si el correo existe, se genero un enlace temporal de recuperacion.');
    app_redirect('recuperar-password.php');
} catch (Throwable $exception) {
    app_flash('login_error', 'No fue posible generar el enlace de recuperacion.');
    app_redirect('recuperar-password.php');
}
