<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('alumno');

$alumno = app_current_alumno();
$preregistro = null;
$observaciones = [];
$validaciones = [];
$aviso = '';

try {
    if ($alumno) {
        $stmt = app_db()->prepare(
            'SELECT p.*, ce.nombre AS ciclo_escolar
             FROM preregistros p
             LEFT JOIN ciclos_escolares ce ON ce.id = p.ciclo_escolar_id
             WHERE p.alumno_id = :alumno_id
             LIMIT 1'
        );
        $stmt->execute(['alumno_id' => $alumno['alumno_id']]);
        $preregistro = $stmt->fetch();

        if ($preregistro) {
            $stmt = app_db()->prepare(
                'SELECT o.*, CONCAT_WS(" ", u.nombres, u.apellido_paterno, u.apellido_materno) AS usuario
                 FROM preregistro_observaciones o
                 INNER JOIN usuarios u ON u.id = o.usuario_id
                 WHERE o.preregistro_id = :preregistro_id
                   AND o.visible_para_alumno = 1
                 ORDER BY o.creada_en DESC'
            );
            $stmt->execute(['preregistro_id' => $preregistro['id']]);
            $observaciones = $stmt->fetchAll();

            $stmt = app_db()->prepare(
                'SELECT v.*, CONCAT_WS(" ", u.nombres, u.apellido_paterno, u.apellido_materno) AS validador
                 FROM validaciones_preregistro v
                 INNER JOIN usuarios u ON u.id = v.usuario_validador_id
                 WHERE v.preregistro_id = :preregistro_id
                 ORDER BY v.creado_en DESC'
            );
            $stmt->execute(['preregistro_id' => $preregistro['id']]);
            $validaciones = $stmt->fetchAll();
        }
    }
} catch (Throwable $exception) {
    $aviso = 'No fue posible cargar el preregistro en este momento.';
}

$tituloPagina = 'Preregistro | Alumno';
$paginaActiva = 'preregistro';
include __DIR__ . '/../../template/cabeceraAlu.php';
?>

<div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center mb-4">
    <div>
        <p class="text-secondary mb-1">Primera etapa</p>
        <h1 class="h3 mb-0">Mi preregistro</h1>
    </div>
    <a class="btn btn-outline-success" href="menu.php">Volver al inicio</a>
</div>

<?php if ($aviso !== '') : ?>
    <div class="alert alert-warning"><?php echo app_e($aviso); ?></div>
<?php endif; ?>

<?php if (!$alumno) : ?>
    <div class="alert alert-danger">No se encontro informacion de alumno para esta cuenta.</div>
<?php else : ?>
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row gap-3 justify-content-between mb-3">
                        <div>
                            <h2 class="h5 mb-1"><?php echo app_e(trim($alumno['nombres'] . ' ' . $alumno['apellido_paterno'] . ' ' . ($alumno['apellido_materno'] ?? ''))); ?></h2>
                            <p class="text-secondary mb-0"><?php echo app_e($alumno['correo']); ?></p>
                        </div>
                        <span class="badge align-self-start <?php echo app_e(app_badge_class($preregistro['estatus'] ?? 'borrador')); ?>">
                            <?php echo app_e(app_status_text($preregistro['estatus'] ?? 'sin preregistro')); ?>
                        </span>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <p class="text-secondary mb-1">Matricula</p>
                            <p class="fw-semibold mb-0"><?php echo app_e($alumno['matricula']); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p class="text-secondary mb-1">Carrera</p>
                            <p class="fw-semibold mb-0"><?php echo app_e($alumno['carrera'] ?? 'Sin asignar'); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p class="text-secondary mb-1">Semestre</p>
                            <p class="fw-semibold mb-0"><?php echo app_e($alumno['semestre_actual'] ?? 'Sin capturar'); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p class="text-secondary mb-1">Creditos aprobados</p>
                            <p class="fw-semibold mb-0"><?php echo app_e($alumno['creditos_aprobados'] ?? 'Sin capturar'); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p class="text-secondary mb-1">Enviado</p>
                            <p class="fw-semibold mb-0"><?php echo app_e($preregistro['enviado_en'] ?? 'Sin enviar'); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p class="text-secondary mb-1">Validado</p>
                            <p class="fw-semibold mb-0"><?php echo app_e($preregistro['validado_en'] ?? 'Pendiente'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Siguiente paso</h2>
                    <?php if (($preregistro['estatus'] ?? '') === 'validado') : ?>
                        <p class="text-secondary">Tu preregistro ya fue validado. Puedes continuar con el registro del servicio social.</p>
                        <a class="btn btn-success w-100" href="servicio_social.php">Registrar servicio social</a>
                    <?php elseif (($preregistro['estatus'] ?? '') === 'observado') : ?>
                        <p class="text-secondary">Revisa las observaciones para corregir tu informacion cuando se habilite la edicion.</p>
                    <?php else : ?>
                        <p class="text-secondary">Tu preregistro se encuentra en revision. Espera la validacion administrativa.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Observaciones</h2>
                    <?php if (!$observaciones) : ?>
                        <p class="text-secondary mb-0">No tienes observaciones registradas.</p>
                    <?php else : ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($observaciones as $observacion) : ?>
                                <div class="list-group-item px-0">
                                    <p class="mb-1"><?php echo app_e($observacion['observacion']); ?></p>
                                    <p class="small text-secondary mb-0">
                                        <?php echo app_e($observacion['usuario']); ?> · <?php echo app_e($observacion['creada_en']); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Validaciones</h2>
                    <?php if (!$validaciones) : ?>
                        <p class="text-secondary mb-0">Aun no hay validaciones registradas.</p>
                    <?php else : ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($validaciones as $validacion) : ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between gap-3">
                                        <p class="fw-semibold mb-1"><?php echo app_e(app_status_text($validacion['resultado'])); ?></p>
                                        <span class="badge <?php echo app_e(app_badge_class($validacion['resultado'])); ?>">
                                            <?php echo app_e($validacion['resultado']); ?>
                                        </span>
                                    </div>
                                    <p class="small text-secondary mb-0">
                                        <?php echo app_e($validacion['validador']); ?> · <?php echo app_e($validacion['creado_en']); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../../template/pie.php'; ?>
