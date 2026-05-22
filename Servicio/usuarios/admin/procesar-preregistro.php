<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('administrador');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_redirect('preregistros.php');
}

if (!app_verify_csrf('admin_preregistro_accion', $_POST['csrf_token'] ?? '')) {
    app_flash('admin_error', 'La sesion expiro. Intenta nuevamente.');
    app_redirect('preregistros.php');
}

$preregistroId = (int) ($_POST['preregistro_id'] ?? 0);
$accion = $_POST['accion'] ?? '';
$comentario = trim($_POST['comentario'] ?? '');

if ($preregistroId <= 0 || !in_array($accion, ['aprobar', 'observar', 'rechazar'], true)) {
    app_flash('admin_error', 'La accion solicitada no es valida.');
    app_redirect('preregistros.php');
}

try {
    $pdo = app_db();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT p.*, a.usuario_id
         FROM preregistros p
         INNER JOIN alumnos a ON a.id = p.alumno_id
         WHERE p.id = :id
         LIMIT 1
         FOR UPDATE'
    );
    $stmt->execute(['id' => $preregistroId]);
    $preregistro = $stmt->fetch();

    if (!$preregistro) {
        $pdo->rollBack();
        app_flash('admin_error', 'No se encontro el preregistro.');
        app_redirect('preregistros.php');
    }

    $estatus = [
        'aprobar' => 'validado',
        'observar' => 'observado',
        'rechazar' => 'rechazado',
    ][$accion];
    $resultado = [
        'aprobar' => 'aprobado',
        'observar' => 'observado',
        'rechazar' => 'rechazado',
    ][$accion];

    $validacionSql = '';
    $paramsPreregistro = [
        'estatus' => $estatus,
        'revisado_por' => $_SESSION['usuario_id'],
        'comentario' => $comentario ?: null,
        'id' => $preregistroId,
    ];

    if ($estatus === 'validado') {
        $validacionSql = ', validado_por = :validado_por, validado_en = NOW()';
        $paramsPreregistro['validado_por'] = $_SESSION['usuario_id'];
    }

    $pdo->prepare(
        'UPDATE preregistros
         SET estatus = :estatus,
             revisado_por = COALESCE(revisado_por, :revisado_por),
             revisado_en = COALESCE(revisado_en, NOW()),
             comentario_general = :comentario
             ' . $validacionSql . '
         WHERE id = :id'
    )->execute($paramsPreregistro);

    $pdo->prepare(
        'INSERT INTO validaciones_preregistro (
            preregistro_id,
            usuario_validador_id,
            resultado,
            comentario
         ) VALUES (
            :preregistro_id,
            :usuario_validador_id,
            :resultado,
            :comentario
         )'
    )->execute([
        'preregistro_id' => $preregistroId,
        'usuario_validador_id' => $_SESSION['usuario_id'],
        'resultado' => $resultado,
            'comentario' => $comentario ?: null,
        ]);
    $validacionId = (int) $pdo->lastInsertId();

    $firmaId = app_sign_payload('validacion_preregistro', $preregistroId, [
        'tipo' => 'validacion_preregistro',
        'validacion_id' => $validacionId,
        'preregistro_id' => $preregistroId,
        'resultado' => $resultado,
        'estatus' => $estatus,
        'comentario_hash' => hash('sha256', $comentario),
    ]);

    $pdo->prepare(
        'UPDATE validaciones_preregistro
         SET firma_digital_id = :firma_digital_id
         WHERE id = :id'
    )->execute([
        'firma_digital_id' => $firmaId,
        'id' => $validacionId,
    ]);

    if ($comentario !== '' && $accion === 'observar') {
        $pdo->prepare(
            'INSERT INTO preregistro_observaciones (
                preregistro_id,
                usuario_id,
                observacion,
                visible_para_alumno
             ) VALUES (
                :preregistro_id,
                :usuario_id,
                :observacion,
                1
             )'
        )->execute([
            'preregistro_id' => $preregistroId,
            'usuario_id' => $_SESSION['usuario_id'],
            'observacion' => $comentario,
        ]);
    }

    if ($estatus === 'validado') {
        $pdo->prepare(
            'UPDATE usuarios
             SET estatus = "activo",
                 correo_verificado_en = COALESCE(correo_verificado_en, NOW())
             WHERE id = :usuario_id'
        )->execute(['usuario_id' => $preregistro['usuario_id']]);
    }

    app_audit('admin.preregistro.' . $accion, 'preregistro', $preregistroId, ['estatus' => $estatus]);
    $pdo->commit();

    app_flash('admin_message', 'Preregistro actualizado correctamente.');
    app_redirect('preregistros.php');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    app_flash('admin_error', 'No fue posible procesar el preregistro.');
    app_redirect('preregistros.php');
}
