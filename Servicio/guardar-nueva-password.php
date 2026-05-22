<?php
require_once __DIR__ . '/config/bootstrap.php';

app_start_session();
app_security_headers();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_redirect('recuperar-password.php');
}

$token = trim($_POST['token'] ?? '');

if (!app_verify_csrf('guardar_nueva_password', $_POST['csrf_token'] ?? '')) {
    app_flash('login_error', 'La sesion expiro. Intenta nuevamente.');
    app_redirect('restablecer-password.php?token=' . urlencode($token));
}

$password = (string) ($_POST['password'] ?? '');
$confirmacion = (string) ($_POST['password_confirmacion'] ?? '');

if ($token === '' || strlen($password) < 10 || !hash_equals($password, $confirmacion)) {
    app_flash('login_error', 'El token o la nueva contrasena no son validos.');
    app_redirect('restablecer-password.php?token=' . urlencode($token));
}

try {
    $pdo = app_db();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT *
         FROM tokens_seguridad
         WHERE proposito = "recuperacion_password"
           AND token_hash = :token_hash
           AND usado_en IS NULL
           AND revocado_en IS NULL
           AND expira_en > NOW()
         LIMIT 1
         FOR UPDATE'
    );
    $stmt->execute(['token_hash' => hash('sha256', $token, true)]);
    $tokenRow = $stmt->fetch();

    if (!$tokenRow) {
        $pdo->rollBack();
        app_flash('login_error', 'El enlace de recuperacion no es valido o ya expiro.');
        app_redirect('recuperar-password.php');
    }

    $passwordHash = password_hash($password, PASSWORD_ARGON2ID);

    $pdo->prepare(
        'UPDATE usuarios
         SET password_hash = :password_hash,
             intentos_fallidos = 0,
             bloqueado_hasta = NULL
         WHERE id = :usuario_id'
    )->execute([
        'password_hash' => $passwordHash,
        'usuario_id' => $tokenRow['usuario_id'],
    ]);

    $pdo->prepare(
        'INSERT INTO historial_passwords (usuario_id, password_hash)
         VALUES (:usuario_id, :password_hash)'
    )->execute([
        'usuario_id' => $tokenRow['usuario_id'],
        'password_hash' => $passwordHash,
    ]);

    $pdo->prepare('UPDATE tokens_seguridad SET usado_en = NOW() WHERE id = :id')
        ->execute(['id' => $tokenRow['id']]);

    app_audit('auth.recuperacion.restablecer', 'usuario', (int) $tokenRow['usuario_id']);
    $pdo->commit();

    app_flash('login_message', 'Contrasena restablecida correctamente. Ya puedes iniciar sesion.');
    app_redirect('index.php');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    app_flash('login_error', 'No fue posible restablecer la contrasena.');
    app_redirect('recuperar-password.php');
}
