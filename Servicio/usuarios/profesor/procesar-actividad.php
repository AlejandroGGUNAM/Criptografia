<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('profesor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_redirect('actividades.php');
}

if (!app_verify_csrf('profesor_actividad_accion', $_POST['csrf_token'] ?? '')) {
    app_flash('profesor_error', 'La sesion expiro. Intenta nuevamente.');
    app_redirect('actividades.php');
}

$profesor = app_current_profesor();
$actividadId = (int) ($_POST['actividad_id'] ?? 0);
$accion = $_POST['accion'] ?? '';
$comentario = trim($_POST['comentario'] ?? '');

if (!$profesor || $actividadId <= 0 || !in_array($accion, ['validar', 'observar', 'rechazar'], true)) {
    app_flash('profesor_error', 'La accion solicitada no es valida.');
    app_redirect('actividades.php');
}

try {
    $pdo = app_db();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT act.*, ss.profesor_id
         FROM actividades_servicio_social act
         INNER JOIN servicios_sociales ss ON ss.id = act.servicio_social_id
         WHERE act.id = :id
         LIMIT 1
         FOR UPDATE'
    );
    $stmt->execute(['id' => $actividadId]);
    $actividad = $stmt->fetch();

    if (!$actividad || (int) $actividad['profesor_id'] !== (int) $profesor['profesor_id']) {
        $pdo->rollBack();
        app_flash('profesor_error', 'No se encontro la actividad o no esta asignada a tu cuenta.');
        app_redirect('actividades.php');
    }

    $estatus = [
        'validar' => 'validada',
        'observar' => 'observada',
        'rechazar' => 'rechazada',
    ][$accion];

    $pdo->prepare(
        'UPDATE actividades_servicio_social
         SET estatus = :estatus,
             revisada_por = :usuario_id,
             revisada_en = NOW(),
             comentario_revision = :comentario
         WHERE id = :id'
    )->execute([
        'estatus' => $estatus,
        'usuario_id' => $_SESSION['usuario_id'],
        'comentario' => $comentario ?: null,
        'id' => $actividadId,
    ]);

    $pdo->prepare(
        'UPDATE servicios_sociales ss
         SET horas_acumuladas = (
             SELECT COALESCE(SUM(horas), 0)
             FROM actividades_servicio_social
             WHERE servicio_social_id = ss.id
               AND estatus = "validada"
         )
         WHERE ss.id = :servicio_social_id'
    )->execute(['servicio_social_id' => $actividad['servicio_social_id']]);

    app_audit('profesor.actividad.' . $accion, 'actividad', $actividadId, ['estatus' => $estatus]);
    $pdo->commit();

    app_flash('profesor_message', 'Actividad actualizada correctamente.');
    app_redirect('actividades.php');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    app_flash('profesor_error', 'No fue posible procesar la actividad.');
    app_redirect('actividades.php');
}
