<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('administrador');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_redirect('seguridad.php');
}

if (!app_verify_csrf('cambiar_password_admin', $_POST['csrf_token'] ?? '')) {
    app_flash('admin_error', 'La sesion expiro. Intenta nuevamente.');
    app_redirect('seguridad.php');
}

$actual = (string) ($_POST['password_actual'] ?? '');
$nuevo = (string) ($_POST['password_nuevo'] ?? '');
$confirmacion = (string) ($_POST['password_confirmacion'] ?? '');

if ($actual === '' || strlen($nuevo) < 10) {
    app_flash('admin_error', 'Captura tu contrasena actual y una nueva de al menos 10 caracteres.');
    app_redirect('seguridad.php');
}

if (!hash_equals($nuevo, $confirmacion)) {
    app_flash('admin_error', 'La confirmacion de contrasena no coincide.');
    app_redirect('seguridad.php');
}

try {
    $pdo = app_db();

    $stmt = $pdo->prepare('SELECT password_hash FROM usuarios WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $_SESSION['usuario_id']]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($actual, $usuario['password_hash'])) {
        app_flash('admin_error', 'La contrasena actual no es correcta.');
        app_redirect('seguridad.php');
    }

    $nuevoHash = password_hash($nuevo, PASSWORD_ARGON2ID);

    $pdo->beginTransaction();

    $pdo->prepare(
        'UPDATE usuarios
         SET password_hash = :password_hash,
             intentos_fallidos = 0,
             bloqueado_hasta = NULL
         WHERE id = :id'
    )->execute([
        'password_hash' => $nuevoHash,
        'id' => $_SESSION['usuario_id'],
    ]);

    $pdo->prepare(
        'INSERT INTO historial_passwords (usuario_id, password_hash)
         VALUES (:usuario_id, :password_hash)'
    )->execute([
        'usuario_id' => $_SESSION['usuario_id'],
        'password_hash' => $nuevoHash,
    ]);

    app_audit('admin.password.actualizar', 'usuario', $_SESSION['usuario_id']);
    $pdo->commit();

    app_flash('admin_message', 'Contrasena actualizada correctamente.');
    app_redirect('seguridad.php');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    app_flash('admin_error', 'No fue posible actualizar la contrasena.');
    app_redirect('seguridad.php');
}
