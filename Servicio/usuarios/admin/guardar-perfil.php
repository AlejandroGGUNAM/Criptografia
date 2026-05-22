<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('administrador');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_redirect('perfil.php');
}

if (!app_verify_csrf('guardar_perfil_admin', $_POST['csrf_token'] ?? '')) {
    app_flash('admin_error', 'La sesion expiro. Intenta guardar nuevamente.');
    app_redirect('perfil.php');
}

$admin = app_current_administrador();

if (!$admin) {
    app_flash('admin_error', 'No se encontro informacion de administrador para esta cuenta.');
    app_redirect('perfil.php');
}

$nombres = trim($_POST['nombres'] ?? '');
$apellidoPaterno = trim($_POST['apellido_paterno'] ?? '');
$apellidoMaterno = trim($_POST['apellido_materno'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$area = trim($_POST['area'] ?? '');

if ($nombres === '' || $apellidoPaterno === '') {
    app_flash('admin_error', 'Nombre y apellido paterno son obligatorios.');
    app_redirect('perfil.php');
}

try {
    $pdo = app_db();
    $pdo->beginTransaction();

    $pdo->prepare(
        'UPDATE usuarios
         SET nombres = :nombres,
             apellido_paterno = :apellido_paterno,
             apellido_materno = :apellido_materno,
             telefono = :telefono
         WHERE id = :usuario_id'
    )->execute([
        'nombres' => $nombres,
        'apellido_paterno' => $apellidoPaterno,
        'apellido_materno' => $apellidoMaterno ?: null,
        'telefono' => $telefono ?: null,
        'usuario_id' => $_SESSION['usuario_id'],
    ]);

    $pdo->prepare(
        'UPDATE administradores
         SET area = :area
         WHERE id = :administrador_id'
    )->execute([
        'area' => $area ?: null,
        'administrador_id' => $admin['administrador_id'],
    ]);

    app_audit('admin.perfil.actualizar', 'administrador', $admin['administrador_id']);
    $pdo->commit();

    $_SESSION['usuario_nombre'] = trim($nombres . ' ' . $apellidoPaterno . ' ' . $apellidoMaterno);
    app_flash('admin_message', 'Perfil actualizado correctamente.');
    app_redirect('perfil.php');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    app_flash('admin_error', 'No fue posible actualizar el perfil.');
    app_redirect('perfil.php');
}
