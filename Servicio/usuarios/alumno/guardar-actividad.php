<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('alumno');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_redirect('actividades.php');
}

if (!app_verify_csrf('guardar_actividad', $_POST['csrf_token'] ?? '')) {
    app_flash('alumno_error', 'La sesion expiro. Intenta registrar la actividad nuevamente.');
    app_redirect('actividades.php');
}

$alumno = app_current_alumno();

if (!$alumno) {
    app_flash('alumno_error', 'No se encontro informacion de alumno para esta cuenta.');
    app_redirect('actividades.php');
}

$fecha = trim($_POST['fecha_actividad'] ?? '');
$horas = (int) ($_POST['horas'] ?? 0);
$descripcion = trim($_POST['descripcion'] ?? '');

if ($fecha === '' || $horas < 1 || $horas > 24 || $descripcion === '') {
    app_flash('alumno_error', 'Captura fecha, horas y descripcion valida.');
    app_redirect('actividades.php');
}

try {
    $pdo = app_db();

    $stmt = $pdo->prepare(
        'SELECT * FROM servicios_sociales
         WHERE alumno_id = :alumno_id
         ORDER BY creado_en DESC
         LIMIT 1'
    );
    $stmt->execute(['alumno_id' => $alumno['alumno_id']]);
    $servicio = $stmt->fetch();

    if (!$servicio) {
        app_flash('alumno_error', 'Primero registra tu servicio social.');
        app_redirect('actividades.php');
    }

    if (in_array($servicio['estatus'], ['concluido', 'cancelado', 'rechazado'], true)) {
        app_flash('alumno_error', 'No puedes registrar actividades en el estado actual del servicio.');
        app_redirect('actividades.php');
    }

    $pdo->prepare(
        'INSERT INTO actividades_servicio_social (
            servicio_social_id,
            alumno_id,
            fecha_actividad,
            horas,
            descripcion,
            estatus
         ) VALUES (
            :servicio_social_id,
            :alumno_id,
            :fecha_actividad,
            :horas,
            :descripcion,
            "registrada"
         )'
    )->execute([
        'servicio_social_id' => $servicio['id'],
        'alumno_id' => $alumno['alumno_id'],
        'fecha_actividad' => $fecha,
        'horas' => $horas,
        'descripcion' => $descripcion,
    ]);

    app_flash('alumno_message', 'Actividad registrada. Queda pendiente de validacion.');
    app_redirect('actividades.php');
} catch (Throwable $exception) {
    app_flash('alumno_error', 'No fue posible registrar la actividad.');
    app_redirect('actividades.php');
}
