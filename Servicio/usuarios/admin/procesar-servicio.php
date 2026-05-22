<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('administrador');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_redirect('servicios_sociales.php');
}

if (!app_verify_csrf('admin_servicio_accion', $_POST['csrf_token'] ?? '')) {
    app_flash('admin_error', 'La sesion expiro. Intenta nuevamente.');
    app_redirect('servicios_sociales.php');
}

$servicioId = (int) ($_POST['servicio_social_id'] ?? 0);
$profesorId = ($_POST['profesor_id'] ?? '') === '' ? null : (int) $_POST['profesor_id'];
$accion = $_POST['accion'] ?? '';
$comentario = trim($_POST['comentario'] ?? '');

if ($servicioId <= 0 || !in_array($accion, ['asignar', 'validar', 'observar', 'rechazar', 'activar', 'concluir', 'cancelar'], true)) {
    app_flash('admin_error', 'La accion solicitada no es valida.');
    app_redirect('servicios_sociales.php');
}

try {
    $pdo = app_db();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT * FROM servicios_sociales
         WHERE id = :id
         LIMIT 1
         FOR UPDATE'
    );
    $stmt->execute(['id' => $servicioId]);
    $servicio = $stmt->fetch();

    if (!$servicio) {
        $pdo->rollBack();
        app_flash('admin_error', 'No se encontro el servicio social.');
        app_redirect('servicios_sociales.php');
    }

    if ($profesorId !== null) {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM profesores p
             INNER JOIN usuarios u ON u.id = p.usuario_id
             WHERE p.id = :id
               AND u.estatus = "activo"'
        );
        $stmt->execute(['id' => $profesorId]);
        if ((int) $stmt->fetchColumn() === 0) {
            $pdo->rollBack();
            app_flash('admin_error', 'El profesor seleccionado no esta disponible.');
            app_redirect('servicios_sociales.php');
        }
    }

    $estatusAnterior = $servicio['estatus'];
    $nuevoEstatus = $estatusAnterior;
    $resultado = null;
    $etapa = 'validacion_admin';
    $actualizarFin = false;

    if ($accion === 'asignar') {
        $nuevoEstatus = $profesorId !== null && in_array($estatusAnterior, ['enviado', 'borrador'], true)
            ? 'en_revision_profesor'
            : $estatusAnterior;
    } elseif ($accion === 'validar') {
        $nuevoEstatus = 'aprobado';
        $resultado = 'aprobado';
    } elseif ($accion === 'observar') {
        $nuevoEstatus = 'en_validacion_admin';
        $resultado = 'observado';
    } elseif ($accion === 'rechazar') {
        $nuevoEstatus = 'rechazado';
        $resultado = 'rechazado';
    } elseif ($accion === 'activar') {
        $nuevoEstatus = 'activo';
    } elseif ($accion === 'concluir') {
        $nuevoEstatus = 'concluido';
        $resultado = 'aprobado';
        $etapa = 'cierre';
        $actualizarFin = true;
    } elseif ($accion === 'cancelar') {
        $nuevoEstatus = 'cancelado';
    }

    $pdo->prepare(
        'UPDATE servicios_sociales
         SET profesor_id = :profesor_id,
             estatus = :estatus,
             aprobado_admin_en = CASE WHEN :estatus_aprobado = "aprobado" THEN NOW() ELSE aprobado_admin_en END,
             fecha_fin_real = CASE WHEN :actualizar_fin = 1 THEN COALESCE(fecha_fin_real, CURDATE()) ELSE fecha_fin_real END
         WHERE id = :id'
    )->execute([
        'profesor_id' => $profesorId,
        'estatus' => $nuevoEstatus,
        'estatus_aprobado' => $nuevoEstatus,
        'actualizar_fin' => $actualizarFin ? 1 : 0,
        'id' => $servicioId,
    ]);

    $pdo->prepare(
        'INSERT INTO servicio_estado_historial (
            servicio_social_id,
            usuario_id,
            estatus_anterior,
            estatus_nuevo,
            comentario
         ) VALUES (
            :servicio_social_id,
            :usuario_id,
            :estatus_anterior,
            :estatus_nuevo,
            :comentario
         )'
    )->execute([
        'servicio_social_id' => $servicioId,
        'usuario_id' => $_SESSION['usuario_id'],
        'estatus_anterior' => $estatusAnterior,
        'estatus_nuevo' => $nuevoEstatus,
        'comentario' => $comentario ?: ('Accion administrativa: ' . $accion),
    ]);

    if ($resultado !== null) {
        $pdo->prepare(
            'INSERT INTO validaciones_servicio_social (
                servicio_social_id,
                usuario_validador_id,
                etapa,
                resultado,
                comentario
             ) VALUES (
                :servicio_social_id,
                :usuario_validador_id,
                :etapa,
                :resultado,
                :comentario
             )'
        )->execute([
            'servicio_social_id' => $servicioId,
            'usuario_validador_id' => $_SESSION['usuario_id'],
            'etapa' => $etapa,
            'resultado' => $resultado,
                'comentario' => $comentario ?: null,
            ]);
        $validacionId = (int) $pdo->lastInsertId();

        $firmaId = app_sign_payload('validacion_servicio', $servicioId, [
            'tipo' => 'validacion_servicio_social',
            'validacion_id' => $validacionId,
            'servicio_social_id' => $servicioId,
            'etapa' => $etapa,
            'resultado' => $resultado,
            'estatus' => $nuevoEstatus,
            'comentario_hash' => hash('sha256', $comentario),
        ]);

        $pdo->prepare(
            'UPDATE validaciones_servicio_social
             SET firma_digital_id = :firma_digital_id
             WHERE id = :id'
        )->execute([
            'firma_digital_id' => $firmaId,
            'id' => $validacionId,
        ]);
    }

    app_audit('admin.servicio.' . $accion, 'servicio_social', $servicioId, ['estatus' => $nuevoEstatus]);
    $pdo->commit();

    app_flash('admin_message', 'Servicio social actualizado correctamente.');
    app_redirect('servicios_sociales.php');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    app_flash('admin_error', 'No fue posible procesar el servicio social.');
    app_redirect('servicios_sociales.php');
}
