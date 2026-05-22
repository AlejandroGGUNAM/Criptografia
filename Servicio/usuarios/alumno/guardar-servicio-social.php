<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('alumno');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_redirect('servicio_social.php');
}

if (!app_verify_csrf('guardar_servicio_social', $_POST['csrf_token'] ?? '')) {
    app_flash('alumno_error', 'La sesion expiro. Intenta guardar nuevamente.');
    app_redirect('servicio_social.php');
}

$alumno = app_current_alumno();

if (!$alumno) {
    app_flash('alumno_error', 'No se encontro informacion de alumno para esta cuenta.');
    app_redirect('servicio_social.php');
}

$dependenciaId = (int) ($_POST['dependencia_id'] ?? 0);
$programaId = ($_POST['programa_id'] ?? '') === '' ? null : (int) $_POST['programa_id'];
$profesorId = ($_POST['profesor_id'] ?? '') === '' ? null : (int) $_POST['profesor_id'];
$cicloId = ($_POST['ciclo_escolar_id'] ?? '') === '' ? null : (int) $_POST['ciclo_escolar_id'];
$fechaInicio = trim($_POST['fecha_inicio'] ?? '');
$fechaFinProgramada = trim($_POST['fecha_fin_programada'] ?? '');
$horasRequeridas = (int) ($_POST['horas_requeridas'] ?? 480);
$horario = trim($_POST['horario'] ?? '');
$actividades = trim($_POST['actividades'] ?? '');
$responsableNombre = trim($_POST['responsable_externo_nombre'] ?? '');
$responsableCorreo = trim($_POST['responsable_externo_correo'] ?? '');

if ($dependenciaId <= 0) {
    app_flash('alumno_error', 'Selecciona una dependencia receptora.');
    app_redirect('servicio_social.php');
}

if ($horasRequeridas <= 0 || $horasRequeridas > 999) {
    app_flash('alumno_error', 'Las horas requeridas deben estar entre 1 y 999.');
    app_redirect('servicio_social.php');
}

if ($fechaInicio !== '' && $fechaFinProgramada !== '' && $fechaFinProgramada < $fechaInicio) {
    app_flash('alumno_error', 'La fecha fin programada no puede ser anterior a la fecha de inicio.');
    app_redirect('servicio_social.php');
}

if ($responsableCorreo !== '' && !filter_var($responsableCorreo, FILTER_VALIDATE_EMAIL)) {
    app_flash('alumno_error', 'El correo del responsable externo no es valido.');
    app_redirect('servicio_social.php');
}

try {
    $pdo = app_db();

    $stmt = $pdo->prepare('SELECT estatus FROM preregistros WHERE alumno_id = :alumno_id LIMIT 1');
    $stmt->execute(['alumno_id' => $alumno['alumno_id']]);
    $preregistro = $stmt->fetch();

    if (($preregistro['estatus'] ?? '') !== 'validado') {
        app_flash('alumno_error', 'Tu preregistro debe estar validado antes de registrar el servicio social.');
        app_redirect('servicio_social.php');
    }

    $stmt = $pdo->prepare(
        'SELECT * FROM servicios_sociales
         WHERE alumno_id = :alumno_id
         ORDER BY creado_en DESC
         LIMIT 1'
    );
    $stmt->execute(['alumno_id' => $alumno['alumno_id']]);
    $servicio = $stmt->fetch();

    $pdo->beginTransaction();

    if ($servicio) {
        $editables = ['borrador', 'enviado', 'observado_profesor', 'rechazado'];
        if (!in_array($servicio['estatus'], $editables, true)) {
            $pdo->rollBack();
            app_flash('alumno_error', 'La solicitud ya no puede modificarse por su estado actual.');
            app_redirect('servicio_social.php');
        }

        $pdo->prepare(
            'UPDATE servicios_sociales
             SET profesor_id = :profesor_id,
                 dependencia_id = :dependencia_id,
                 programa_id = :programa_id,
                 ciclo_escolar_id = :ciclo_escolar_id,
                 estatus = "enviado",
                 fecha_inicio = :fecha_inicio,
                 fecha_fin_programada = :fecha_fin_programada,
                 horario = :horario,
                 actividades = :actividades,
                 responsable_externo_nombre = :responsable_externo_nombre,
                 responsable_externo_correo = :responsable_externo_correo,
                 horas_requeridas = :horas_requeridas,
                 enviado_en = NOW()
             WHERE id = :id
               AND alumno_id = :alumno_id'
        )->execute([
            'profesor_id' => $profesorId,
            'dependencia_id' => $dependenciaId,
            'programa_id' => $programaId,
            'ciclo_escolar_id' => $cicloId,
            'fecha_inicio' => $fechaInicio ?: null,
            'fecha_fin_programada' => $fechaFinProgramada ?: null,
            'horario' => $horario ?: null,
            'actividades' => $actividades ?: null,
            'responsable_externo_nombre' => $responsableNombre ?: null,
            'responsable_externo_correo' => $responsableCorreo ?: null,
            'horas_requeridas' => $horasRequeridas,
            'id' => $servicio['id'],
            'alumno_id' => $alumno['alumno_id'],
        ]);

        $servicioId = (int) $servicio['id'];
        $estatusAnterior = $servicio['estatus'];
    } else {
        $folio = 'SS-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));

        $pdo->prepare(
            'INSERT INTO servicios_sociales (
                alumno_id,
                profesor_id,
                dependencia_id,
                programa_id,
                ciclo_escolar_id,
                folio,
                estatus,
                fecha_inicio,
                fecha_fin_programada,
                horario,
                actividades,
                responsable_externo_nombre,
                responsable_externo_correo,
                horas_requeridas,
                enviado_en
             ) VALUES (
                :alumno_id,
                :profesor_id,
                :dependencia_id,
                :programa_id,
                :ciclo_escolar_id,
                :folio,
                "enviado",
                :fecha_inicio,
                :fecha_fin_programada,
                :horario,
                :actividades,
                :responsable_externo_nombre,
                :responsable_externo_correo,
                :horas_requeridas,
                NOW()
             )'
        )->execute([
            'alumno_id' => $alumno['alumno_id'],
            'profesor_id' => $profesorId,
            'dependencia_id' => $dependenciaId,
            'programa_id' => $programaId,
            'ciclo_escolar_id' => $cicloId,
            'folio' => $folio,
            'fecha_inicio' => $fechaInicio ?: null,
            'fecha_fin_programada' => $fechaFinProgramada ?: null,
            'horario' => $horario ?: null,
            'actividades' => $actividades ?: null,
            'responsable_externo_nombre' => $responsableNombre ?: null,
            'responsable_externo_correo' => $responsableCorreo ?: null,
            'horas_requeridas' => $horasRequeridas,
        ]);

        $servicioId = (int) $pdo->lastInsertId();
        $estatusAnterior = null;
    }

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
            "enviado",
            "Solicitud enviada por el alumno"
         )'
    )->execute([
        'servicio_social_id' => $servicioId,
        'usuario_id' => $_SESSION['usuario_id'],
        'estatus_anterior' => $estatusAnterior,
    ]);

    $pdo->commit();

    app_flash('alumno_message', 'Solicitud de servicio social guardada correctamente.');
    app_redirect('servicio_social.php');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    app_flash('alumno_error', 'No fue posible guardar la solicitud. Revisa los datos e intenta nuevamente.');
    app_redirect('servicio_social.php');
}
