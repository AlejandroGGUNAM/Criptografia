<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('profesor');

$profesor = app_current_profesor();
$servicios = [];
$aviso = '';

try {
    if ($profesor) {
        $stmt = app_db()->prepare(
            'SELECT
                ss.*,
                a.matricula,
                CONCAT_WS(" ", ua.nombres, ua.apellido_paterno, ua.apellido_materno) AS alumno,
                ua.correo AS correo_alumno,
                c.nombre AS carrera,
                d.nombre AS dependencia,
                ps.nombre AS programa,
                COALESCE(SUM(act.horas), 0) AS horas_registradas,
                COALESCE(SUM(CASE WHEN act.estatus = "validada" THEN act.horas ELSE 0 END), 0) AS horas_validadas
             FROM servicios_sociales ss
             INNER JOIN alumnos a ON a.id = ss.alumno_id
             INNER JOIN usuarios ua ON ua.id = a.usuario_id
             LEFT JOIN carreras c ON c.id = a.carrera_id
             INNER JOIN dependencias d ON d.id = ss.dependencia_id
             LEFT JOIN programas_servicio_social ps ON ps.id = ss.programa_id
             LEFT JOIN actividades_servicio_social act ON act.servicio_social_id = ss.id
             WHERE ss.profesor_id = :profesor_id
             GROUP BY ss.id, a.id, ua.id, c.id, d.id, ps.id
             ORDER BY ua.apellido_paterno, ua.apellido_materno, ua.nombres'
        );
        $stmt->execute(['profesor_id' => $profesor['profesor_id']]);
        $servicios = $stmt->fetchAll();
    }
} catch (Throwable $exception) {
    $aviso = 'No fue posible cargar los alumnos asignados.';
}

$tituloPagina = 'Alumnos asignados | Profesor';
$paginaActiva = 'alumnos_asignados';
include __DIR__ . '/../../template/cabeceraProf.php';
?>

<div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center mb-4">
    <div>
        <p class="text-secondary mb-1">Seguimiento academico</p>
        <h1 class="h3 mb-0">Alumnos asignados</h1>
    </div>
    <a class="btn btn-primary" href="actividades.php">Validar actividades</a>
</div>

<?php if ($aviso !== '') : ?>
    <div class="alert alert-warning"><?php echo app_e($aviso); ?></div>
<?php endif; ?>

<?php if (!$profesor) : ?>
    <div class="alert alert-danger">No se encontro informacion de profesor para esta cuenta.</div>
<?php elseif (!$servicios) : ?>
    <div class="alert alert-info">Aun no tienes alumnos con servicio social asignado.</div>
<?php else : ?>
    <div class="row g-3">
        <?php foreach ($servicios as $servicio) : ?>
            <?php
            $porcentaje = 0;
            if ((int) $servicio['horas_requeridas'] > 0) {
                $porcentaje = min(100, round(((int) $servicio['horas_validadas'] / (int) $servicio['horas_requeridas']) * 100));
            }
            ?>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between gap-3 mb-3">
                            <div>
                                <h2 class="h5 mb-1"><?php echo app_e($servicio['alumno']); ?></h2>
                                <p class="text-secondary mb-0"><?php echo app_e($servicio['matricula']); ?> - <?php echo app_e($servicio['carrera'] ?? 'Sin carrera'); ?></p>
                            </div>
                            <span class="badge align-self-start <?php echo app_e(app_badge_class($servicio['estatus'])); ?>">
                                <?php echo app_e(app_status_text($servicio['estatus'])); ?>
                            </span>
                        </div>

                        <p class="mb-1"><strong>Folio:</strong> <?php echo app_e($servicio['folio']); ?></p>
                        <p class="mb-1"><strong>Dependencia:</strong> <?php echo app_e($servicio['dependencia']); ?></p>
                        <p class="text-secondary"><?php echo app_e($servicio['programa'] ?? 'Sin programa asignado'); ?></p>

                        <div class="d-flex justify-content-between small text-secondary mb-1">
                            <span>Horas validadas</span>
                            <span><?php echo app_e($servicio['horas_validadas']); ?> / <?php echo app_e($servicio['horas_requeridas']); ?></span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-primary" style="width: <?php echo app_e($porcentaje); ?>%"><?php echo app_e($porcentaje); ?>%</div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../../template/pie.php'; ?>
