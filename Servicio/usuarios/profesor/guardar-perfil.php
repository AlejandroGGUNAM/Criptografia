<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('profesor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_redirect('perfil.php');
}

if (!app_verify_csrf('guardar_perfil_profesor', $_POST['csrf_token'] ?? '')) {
    app_flash('profesor_error', 'La sesion expiro. Intenta guardar nuevamente.');
    app_redirect('perfil.php');
}

$profesor = app_current_profesor();

if (!$profesor) {
    app_flash('profesor_error', 'No se encontro informacion de profesor para esta cuenta.');
    app_redirect('perfil.php');
}

$nombres = trim($_POST['nombres'] ?? '');
$apellidoPaterno = trim($_POST['apellido_paterno'] ?? '');
$apellidoMaterno = trim($_POST['apellido_materno'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$numeroEmpleado = trim($_POST['numero_empleado'] ?? '');
$departamentoId = ($_POST['departamento_id'] ?? '') === '' ? null : (int) $_POST['departamento_id'];
$gradoAcademico = trim($_POST['grado_academico'] ?? '');

if ($nombres === '' || $apellidoPaterno === '' || $numeroEmpleado === '') {
    app_flash('profesor_error', 'Nombre, apellido paterno y numero de empleado son obligatorios.');
    app_redirect('perfil.php');
}

try {
    $pdo = app_db();

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM profesores
         WHERE numero_empleado = :numero_empleado
           AND id <> :profesor_id'
    );
    $stmt->execute([
        'numero_empleado' => $numeroEmpleado,
        'profesor_id' => $profesor['profesor_id'],
    ]);

    if ((int) $stmt->fetchColumn() > 0) {
        app_flash('profesor_error', 'Ya existe otro profesor con ese numero de empleado.');
        app_redirect('perfil.php');
    }

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
        'UPDATE profesores
         SET departamento_id = :departamento_id,
             numero_empleado = :numero_empleado,
             grado_academico = :grado_academico
         WHERE id = :profesor_id'
    )->execute([
        'departamento_id' => $departamentoId,
        'numero_empleado' => $numeroEmpleado,
        'grado_academico' => $gradoAcademico ?: null,
        'profesor_id' => $profesor['profesor_id'],
    ]);

    app_audit('profesor.perfil.actualizar', 'profesor', $profesor['profesor_id']);
    $pdo->commit();

    $_SESSION['usuario_nombre'] = trim($nombres . ' ' . $apellidoPaterno . ' ' . $apellidoMaterno);
    app_flash('profesor_message', 'Perfil actualizado correctamente.');
    app_redirect('perfil.php');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    app_flash('profesor_error', 'No fue posible actualizar el perfil.');
    app_redirect('perfil.php');
}
