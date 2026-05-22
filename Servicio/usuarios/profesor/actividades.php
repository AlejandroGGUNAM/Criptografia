<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('profesor');

$profesor = app_current_profesor();
$actividades = [];
$error = app_take_flash('profesor_error');
$mensaje = app_take_flash('profesor_message');
$aviso = '';

try {
    if ($profesor) {
        $stmt = app_db()->prepare(
            'SELECT
                act.*,
                ss.folio,
                ss.horas_requeridas,
                ss.horas_acumuladas,
                CONCAT_WS(" ", u.nombres, u.apellido_paterno, u.apellido_materno) AS alumno,
                a.matricula
             FROM actividades_servicio_social act
             INNER JOIN servicios_sociales ss ON ss.id = act.servicio_social_id
             INNER JOIN alumnos a ON a.id = act.alumno_id
             INNER JOIN usuarios u ON u.id = a.usuario_id
             WHERE ss.profesor_id = :profesor_id
             ORDER BY FIELD(act.estatus, "registrada", "observada", "rechazada", "validada"),
                      act.fecha_actividad DESC,
                      act.creada_en DESC'
        );
        $stmt->execute(['profesor_id' => $profesor['profesor_id']]);
        $actividades = $stmt->fetchAll();
    }
} catch (Throwable $exception) {
    $aviso = 'No fue posible cargar las actividades.';
}

$tituloPagina = 'Actividades | Profesor';
$paginaActiva = 'actividades';
include __DIR__ . '/../../template/cabeceraProf.php';
?>

<div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center mb-4">
    <div>
        <p class="text-secondary mb-1">Validacion de horas</p>
        <h1 class="h3 mb-0">Actividades</h1>
    </div>
    <a class="btn btn-outline-primary" href="alumnos_asignados.php">Alumnos asignados</a>
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

<?php if (!$profesor) : ?>
    <div class="alert alert-danger">No se encontro informacion de profesor para esta cuenta.</div>
<?php elseif (!$actividades) : ?>
    <div class="alert alert-info">No hay actividades registradas por tus alumnos.</div>
<?php else : ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h5">Actividades registradas</h2>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Alumno</th>
                            <th>Actividad</th>
                            <th>Estado</th>
                            <th>Revision</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($actividades as $actividad) : ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?php echo app_e($actividad['alumno']); ?></div>
                                    <div class="small text-secondary"><?php echo app_e($actividad['matricula']); ?> - <?php echo app_e($actividad['folio']); ?></div>
                                </td>
                                <td>
                                    <div><?php echo app_e($actividad['fecha_actividad']); ?> - <?php echo app_e($actividad['horas']); ?> horas</div>
                                    <div class="small text-secondary"><?php echo app_e($actividad['descripcion']); ?></div>
                                </td>
                                <td>
                                    <span class="badge <?php echo app_e(app_badge_class($actividad['estatus'])); ?>">
                                        <?php echo app_e(app_status_text($actividad['estatus'])); ?>
                                    </span>
                                </td>
                                <td style="min-width: 280px;">
                                    <form action="procesar-actividad.php" method="post" class="vstack gap-2">
                                        <input type="hidden" name="csrf_token" value="<?php echo app_e(app_csrf_token('profesor_actividad_accion')); ?>">
                                        <input type="hidden" name="actividad_id" value="<?php echo app_e($actividad['id']); ?>">
                                        <textarea class="form-control form-control-sm" name="comentario" rows="2" placeholder="Comentario de revision"><?php echo app_e($actividad['comentario_revision']); ?></textarea>
                                        <div class="d-flex flex-wrap gap-2">
                                            <button class="btn btn-sm btn-success" name="accion" value="validar" type="submit">Validar</button>
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
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../../template/pie.php'; ?>
