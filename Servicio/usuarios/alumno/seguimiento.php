<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('alumno');

$alumno = app_current_alumno();
$preregistro = null;
$servicio = null;
$observaciones = [];
$historial = [];
$validacionesServicio = [];
$actividadesResumen = [];
$aviso = '';

try {
    if ($alumno) {
        $stmt = app_db()->prepare(
            'SELECT * FROM preregistros
             WHERE alumno_id = :alumno_id
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
        }

        $stmt = app_db()->prepare(
            'SELECT ss.*, d.nombre AS dependencia, ps.nombre AS programa
             FROM servicios_sociales ss
             INNER JOIN dependencias d ON d.id = ss.dependencia_id
             LEFT JOIN programas_servicio_social ps ON ps.id = ss.programa_id
             WHERE ss.alumno_id = :alumno_id
             ORDER BY ss.creado_en DESC
             LIMIT 1'
        );
        $stmt->execute(['alumno_id' => $alumno['alumno_id']]);
        $servicio = $stmt->fetch();

        if ($servicio) {
            $stmt = app_db()->prepare(
                'SELECT h.*, CONCAT_WS(" ", u.nombres, u.apellido_paterno, u.apellido_materno) AS usuario
                 FROM servicio_estado_historial h
                 INNER JOIN usuarios u ON u.id = h.usuario_id
                 WHERE h.servicio_social_id = :servicio_social_id
                 ORDER BY h.creado_en DESC'
            );
            $stmt->execute(['servicio_social_id' => $servicio['id']]);
            $historial = $stmt->fetchAll();

            $stmt = app_db()->prepare(
                'SELECT v.*, CONCAT_WS(" ", u.nombres, u.apellido_paterno, u.apellido_materno) AS validador
                 FROM validaciones_servicio_social v
                 INNER JOIN usuarios u ON u.id = v.usuario_validador_id
                 WHERE v.servicio_social_id = :servicio_social_id
                 ORDER BY v.creado_en DESC'
            );
            $stmt->execute(['servicio_social_id' => $servicio['id']]);
            $validacionesServicio = $stmt->fetchAll();

            $stmt = app_db()->prepare(
                'SELECT estatus, COUNT(*) AS total, COALESCE(SUM(horas), 0) AS horas
                 FROM actividades_servicio_social
                 WHERE servicio_social_id = :servicio_social_id
                 GROUP BY estatus'
            );
            $stmt->execute(['servicio_social_id' => $servicio['id']]);
            $actividadesResumen = $stmt->fetchAll();
        }
    }
} catch (Throwable $exception) {
    $aviso = 'No fue posible cargar el seguimiento.';
}

$tituloPagina = 'Seguimiento | Alumno';
$paginaActiva = 'seguimiento';
include __DIR__ . '/../../template/cabeceraAlu.php';
?>

<div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center mb-4">
    <div>
        <p class="text-secondary mb-1">Estado del tramite</p>
        <h1 class="h3 mb-0">Seguimiento</h1>
    </div>
    <a class="btn btn-outline-success" href="actividades.php">Ver actividades</a>
</div>

<?php if ($aviso !== '') : ?>
    <div class="alert alert-warning"><?php echo app_e($aviso); ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-secondary mb-1">Preregistro</p>
                <h2 class="h5 mb-2">
                    <span class="badge <?php echo app_e(app_badge_class($preregistro['estatus'] ?? 'borrador')); ?>">
                        <?php echo app_e(app_status_text($preregistro['estatus'] ?? 'sin preregistro')); ?>
                    </span>
                </h2>
                <p class="text-secondary mb-0">Enviado: <?php echo app_e($preregistro['enviado_en'] ?? 'Pendiente'); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-secondary mb-1">Servicio social</p>
                <?php if ($servicio) : ?>
                    <h2 class="h5 mb-2">
                        <span class="badge <?php echo app_e(app_badge_class($servicio['estatus'])); ?>">
                            <?php echo app_e(app_status_text($servicio['estatus'])); ?>
                        </span>
                    </h2>
                    <p class="text-secondary mb-0">Folio: <?php echo app_e($servicio['folio']); ?></p>
                <?php else : ?>
                    <h2 class="h5 mb-2">Sin registro</h2>
                    <a href="servicio_social.php">Registrar servicio social</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Historial del servicio</h2>
                <?php if (!$historial) : ?>
                    <p class="text-secondary mb-0">Aun no hay movimientos en el servicio social.</p>
                <?php else : ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($historial as $movimiento) : ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between gap-3">
                                    <p class="fw-semibold mb-1"><?php echo app_e(app_status_text($movimiento['estatus_nuevo'])); ?></p>
                                    <span class="small text-secondary"><?php echo app_e($movimiento['creado_en']); ?></span>
                                </div>
                                <p class="text-secondary mb-1"><?php echo app_e($movimiento['comentario'] ?? 'Sin comentario'); ?></p>
                                <p class="small text-secondary mb-0"><?php echo app_e($movimiento['usuario']); ?></p>
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
                <h2 class="h5">Observaciones</h2>
                <?php if (!$observaciones) : ?>
                    <p class="text-secondary mb-0">No tienes observaciones visibles.</p>
                <?php else : ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($observaciones as $observacion) : ?>
                            <div class="list-group-item px-0">
                                <p class="mb-1"><?php echo app_e($observacion['observacion']); ?></p>
                                <p class="small text-secondary mb-0"><?php echo app_e($observacion['usuario']); ?> · <?php echo app_e($observacion['creada_en']); ?></p>
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
                <h2 class="h5">Validaciones del servicio</h2>
                <?php if (!$validacionesServicio) : ?>
                    <p class="text-secondary mb-0">Aun no hay validaciones registradas.</p>
                <?php else : ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($validacionesServicio as $validacion) : ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between gap-3">
                                    <p class="fw-semibold mb-1"><?php echo app_e($validacion['etapa']); ?></p>
                                    <span class="badge <?php echo app_e(app_badge_class($validacion['resultado'])); ?>">
                                        <?php echo app_e(app_status_text($validacion['resultado'])); ?>
                                    </span>
                                </div>
                                <p class="small text-secondary mb-0"><?php echo app_e($validacion['validador']); ?> · <?php echo app_e($validacion['creado_en']); ?></p>
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
                <h2 class="h5">Resumen de actividades</h2>
                <?php if (!$actividadesResumen) : ?>
                    <p class="text-secondary mb-0">No hay actividades registradas.</p>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Estado</th>
                                    <th>Actividades</th>
                                    <th>Horas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($actividadesResumen as $actividad) : ?>
                                    <tr>
                                        <td>
                                            <span class="badge <?php echo app_e(app_badge_class($actividad['estatus'])); ?>">
                                                <?php echo app_e(app_status_text($actividad['estatus'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo app_e($actividad['total']); ?></td>
                                        <td><?php echo app_e($actividad['horas']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../template/pie.php'; ?>
