<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('administrador');

$preregistros = [];
$error = app_take_flash('admin_error');
$mensaje = app_take_flash('admin_message');
$aviso = '';

try {
    $preregistros = app_db()->query(
        'SELECT
            p.*,
            a.matricula,
            a.semestre_actual,
            a.creditos_aprobados,
            CONCAT_WS(" ", u.nombres, u.apellido_paterno, u.apellido_materno) AS alumno,
            u.correo,
            u.estatus AS estatus_usuario,
            c.nombre AS carrera,
            ce.nombre AS ciclo_escolar,
            CONCAT_WS(" ", ur.nombres, ur.apellido_paterno, ur.apellido_materno) AS revisado_por_nombre,
            CONCAT_WS(" ", uv.nombres, uv.apellido_paterno, uv.apellido_materno) AS validado_por_nombre
         FROM preregistros p
         INNER JOIN alumnos a ON a.id = p.alumno_id
         INNER JOIN usuarios u ON u.id = a.usuario_id
         LEFT JOIN carreras c ON c.id = a.carrera_id
         LEFT JOIN ciclos_escolares ce ON ce.id = p.ciclo_escolar_id
         LEFT JOIN usuarios ur ON ur.id = p.revisado_por
         LEFT JOIN usuarios uv ON uv.id = p.validado_por
         ORDER BY FIELD(p.estatus, "pendiente_revision", "observado", "borrador", "validado", "rechazado", "cancelado"),
                  p.enviado_en DESC,
                  p.actualizado_en DESC'
    )->fetchAll();
} catch (Throwable $exception) {
    $aviso = 'No fue posible cargar los preregistros.';
}

$tituloPagina = 'Preregistros | Administrador';
$paginaActiva = 'preregistros';
include __DIR__ . '/../../template/cabeceraAdmin.php';
?>

<div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center mb-4">
    <div>
        <p class="text-secondary mb-1">Revision administrativa</p>
        <h1 class="h3 mb-0">Preregistros</h1>
    </div>
    <a class="btn btn-outline-dark" href="servicios_sociales.php">Servicios sociales</a>
</div>

<?php if ($error !== '') : ?>
    <div class="alert alert-danger"><?php echo app_e($error); ?></div>
<?php endif; ?>

<?php if ($mensaje !== '') : ?>
    <div class="alert alert-success"><?php echo app_e($mensaje); ?></div>
<?php endif; ?>

<?php if ($aviso !== '') : ?>
    <div class="alert alert-warning"><?php echo app_e($aviso); ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h5">Listado de preregistros</h2>
        <?php if (!$preregistros) : ?>
            <p class="text-secondary mb-0">No hay preregistros para revisar.</p>
        <?php else : ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Alumno</th>
                            <th>Academico</th>
                            <th>Estados</th>
                            <th>Validacion</th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preregistros as $preregistro) : ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?php echo app_e($preregistro['alumno']); ?></div>
                                    <div class="small text-secondary"><?php echo app_e($preregistro['matricula']); ?> - <?php echo app_e($preregistro['correo']); ?></div>
                                </td>
                                <td>
                                    <div><?php echo app_e($preregistro['carrera'] ?? 'Sin carrera'); ?></div>
                                    <div class="small text-secondary">Semestre <?php echo app_e($preregistro['semestre_actual'] ?? 'N/D'); ?> - <?php echo app_e($preregistro['creditos_aprobados'] ?? '0'); ?> creditos</div>
                                </td>
                                <td>
                                    <div class="mb-1">
                                        <span class="badge <?php echo app_e(app_badge_class($preregistro['estatus'])); ?>">
                                            <?php echo app_e(app_status_text($preregistro['estatus'])); ?>
                                        </span>
                                    </div>
                                    <span class="badge <?php echo app_e(app_badge_class($preregistro['estatus_usuario'])); ?>">
                                        Cuenta: <?php echo app_e(app_status_text($preregistro['estatus_usuario'])); ?>
                                    </span>
                                </td>
                                <td class="small text-secondary">
                                    Revisado: <?php echo app_e($preregistro['revisado_por_nombre'] ?? 'Pendiente'); ?><br>
                                    Validado: <?php echo app_e($preregistro['validado_por_nombre'] ?? 'Pendiente'); ?>
                                </td>
                                <td style="min-width: 280px;">
                                    <form action="procesar-preregistro.php" method="post" class="vstack gap-2">
                                        <input type="hidden" name="csrf_token" value="<?php echo app_e(app_csrf_token('admin_preregistro_accion')); ?>">
                                        <input type="hidden" name="preregistro_id" value="<?php echo app_e($preregistro['id']); ?>">
                                        <textarea class="form-control form-control-sm" name="comentario" rows="2" placeholder="Comentario u observacion"><?php echo app_e($preregistro['comentario_general']); ?></textarea>
                                        <div class="d-flex flex-wrap gap-2">
                                            <button class="btn btn-sm btn-success" name="accion" value="aprobar" type="submit">Validar</button>
                                            <button class="btn btn-sm btn-outline-primary" name="accion" value="observar" type="submit">Observar</button>
                                            <button class="btn btn-sm btn-outline-danger" name="accion" value="rechazar" type="submit">Rechazar</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../template/pie.php'; ?>
