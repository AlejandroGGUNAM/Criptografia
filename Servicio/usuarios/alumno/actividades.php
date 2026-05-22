<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('alumno');

$alumno = app_current_alumno();
$servicio = null;
$actividades = [];
$totales = [
    'horas_registradas' => 0,
    'horas_validadas' => 0,
];
$error = app_take_flash('alumno_error');
$mensaje = app_take_flash('alumno_message');
$aviso = '';

try {
    if ($alumno) {
        $stmt = app_db()->prepare(
            'SELECT * FROM servicios_sociales
             WHERE alumno_id = :alumno_id
             ORDER BY creado_en DESC
             LIMIT 1'
        );
        $stmt->execute(['alumno_id' => $alumno['alumno_id']]);
        $servicio = $stmt->fetch();

        if ($servicio) {
            $stmt = app_db()->prepare(
                'SELECT a.*, CONCAT_WS(" ", u.nombres, u.apellido_paterno, u.apellido_materno) AS revisor
                 FROM actividades_servicio_social a
                 LEFT JOIN usuarios u ON u.id = a.revisada_por
                 WHERE a.servicio_social_id = :servicio_social_id
                 ORDER BY a.fecha_actividad DESC, a.creada_en DESC'
            );
            $stmt->execute(['servicio_social_id' => $servicio['id']]);
            $actividades = $stmt->fetchAll();

            $stmt = app_db()->prepare(
                'SELECT
                    COALESCE(SUM(horas), 0) AS horas_registradas,
                    COALESCE(SUM(CASE WHEN estatus = "validada" THEN horas ELSE 0 END), 0) AS horas_validadas
                 FROM actividades_servicio_social
                 WHERE servicio_social_id = :servicio_social_id'
            );
            $stmt->execute(['servicio_social_id' => $servicio['id']]);
            $totales = array_merge($totales, $stmt->fetch() ?: []);
        }
    }
} catch (Throwable $exception) {
    $aviso = 'No fue posible cargar las actividades.';
}

$puedeRegistrar = $servicio && !in_array($servicio['estatus'], ['concluido', 'cancelado', 'rechazado'], true);
$porcentaje = 0;
if ($servicio && (int) $servicio['horas_requeridas'] > 0) {
    $porcentaje = min(100, round(((int) $totales['horas_validadas'] / (int) $servicio['horas_requeridas']) * 100));
}

$tituloPagina = 'Actividades | Alumno';
$paginaActiva = 'actividades';
include __DIR__ . '/../../template/cabeceraAlu.php';
?>

<div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center mb-4">
    <div>
        <p class="text-secondary mb-1">Seguimiento de horas</p>
        <h1 class="h3 mb-0">Actividades y horas</h1>
    </div>
    <?php if ($servicio) : ?>
        <span class="badge fs-6 <?php echo app_e(app_badge_class($servicio['estatus'])); ?>">
            <?php echo app_e(app_status_text($servicio['estatus'])); ?>
        </span>
    <?php endif; ?>
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

<?php if (!$servicio) : ?>
    <div class="alert alert-info">
        Primero registra tu servicio social para poder capturar actividades.
        <a href="servicio_social.php" class="alert-link">Ir al registro</a>.
    </div>
<?php else : ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-secondary mb-1">Horas registradas</p>
                    <p class="display-6 fw-semibold mb-0"><?php echo app_e($totales['horas_registradas']); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-secondary mb-1">Horas validadas</p>
                    <p class="display-6 fw-semibold mb-0"><?php echo app_e($totales['horas_validadas']); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-secondary mb-1">Avance validado</p>
                    <p class="display-6 fw-semibold mb-0"><?php echo app_e($porcentaje); ?>%</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between gap-3 mb-2">
                <h2 class="h5 mb-0">Progreso</h2>
                <span class="text-secondary"><?php echo app_e($totales['horas_validadas']); ?> / <?php echo app_e($servicio['horas_requeridas']); ?> horas</span>
            </div>
            <div class="progress">
                <div class="progress-bar bg-success" style="width: <?php echo app_e($porcentaje); ?>%"><?php echo app_e($porcentaje); ?>%</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <form action="guardar-actividad.php" method="post" class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h5">Registrar actividad</h2>
                    <input type="hidden" name="csrf_token" value="<?php echo app_e(app_csrf_token('guardar_actividad')); ?>">

                    <div class="mb-3">
                        <label for="fecha_actividad" class="form-label">Fecha</label>
                        <input type="date" class="form-control" id="fecha_actividad" name="fecha_actividad" value="<?php echo app_e(date('Y-m-d')); ?>" required <?php echo !$puedeRegistrar ? 'disabled' : ''; ?>>
                    </div>

                    <div class="mb-3">
                        <label for="horas" class="form-label">Horas</label>
                        <input type="number" class="form-control" id="horas" name="horas" min="1" max="24" required <?php echo !$puedeRegistrar ? 'disabled' : ''; ?>>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripcion</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="5" required <?php echo !$puedeRegistrar ? 'disabled' : ''; ?>></textarea>
                    </div>

                    <button class="btn btn-success w-100" type="submit" <?php echo !$puedeRegistrar ? 'disabled' : ''; ?>>
                        Guardar actividad
                    </button>
                </div>
            </form>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h5">Historial</h2>
                    <?php if (!$actividades) : ?>
                        <p class="text-secondary mb-0">Aun no hay actividades registradas.</p>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Horas</th>
                                        <th>Estado</th>
                                        <th>Descripcion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($actividades as $actividad) : ?>
                                        <tr>
                                            <td><?php echo app_e($actividad['fecha_actividad']); ?></td>
                                            <td><?php echo app_e($actividad['horas']); ?></td>
                                            <td>
                                                <span class="badge <?php echo app_e(app_badge_class($actividad['estatus'])); ?>">
                                                    <?php echo app_e(app_status_text($actividad['estatus'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo app_e($actividad['descripcion']); ?></td>
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
<?php endif; ?>

<?php include __DIR__ . '/../../template/pie.php'; ?>
