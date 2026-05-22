<?php
require_once __DIR__ . '/config/bootstrap.php';

app_start_session();

if (!empty($_SESSION['usuario_id'])) {
    try {
        $sessionHash = hash('sha256', session_id(), true);
        app_db()
            ->prepare('UPDATE sesiones_usuario SET revocada_en = NOW() WHERE session_id_hash = :session_id_hash')
            ->execute(['session_id_hash' => $sessionHash]);
    } catch (Throwable $exception) {
        // La salida de sesion local debe continuar aunque no se pueda registrar en BD.
    }
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

session_destroy();

app_start_session();
app_flash('login_message', 'Sesion cerrada correctamente.');
app_redirect('index.php');
