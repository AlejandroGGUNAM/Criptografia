<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('administrador');

$servicios = [];
$profesores = [];
$error = app_take_flash('admin_error');
$mensaje = app_take_flash('admin_message');
$aviso = '';

try {
    $pdo = app_db();
    $profesores = $pdo->query(
        'SELECT p.id, CONCAT_WS(" ", u.nombres, u.apellido_paterno, u.apellido_materno) AS profesor
         FROM profesores p
         INNER JOIN usuarios u ON u.id = p.usuario_id
         WHERE u.estatus = "activo"
         ORDER BY profesor'
    )->fetchAll();

    $servicios = $pdo->query(
        'SELECT
            ss.*,
            a.matricula,
            CONCAT_WS(" ", ua.nombres, ua.apellido_paterno, ua.apellido_materno) AS alumno,
            ua.correo AS correo_alumno,
            c.nombre AS carrera,
            CONCAT_WS(" ", up.nombres, up.apellido_paterno, up.apellido_materno) AS profesor,
            d.nombre AS dependencia,
            ps.nombre AS programa,
            ce.nombre AS ciclo_escolar,
            COALESCE(SUM(act.horas), 0) AS horas_registradas,
            COALESCE(SUM(CASE WHEN act.estatus = "validada" THEN act.horas ELSE 0 END), 0) AS horas_validadas
         FROM servicios_sociales ss
         INNER JOIN alumnos a ON a.id = ss.alumno_id
         INNER JOIN usuarios ua ON ua.id = a.usuario_id
         LEFT JOIN carreras c ON c.id = a.carrera_id
         LEFT JOIN profesores pr ON pr.id = ss.profesor_id
         LEFT JOIN usuarios up ON up.id = pr.usuario_id
         INNER JOIN dependencias d ON d.id = ss.dependencia_id
         LEFT JOIN programas_servicio_social ps ON ps.id = ss.programa_id
         LEFT JOIN ciclos_escolares ce ON ce.id = ss.ciclo_escolar_id
         LEFT JOIN actividades_servicio_social act ON act.servicio_social_id = ss.id
         GROUP BY ss.id, a.id, ua.id, c.id, pr.id, up.id, d.id, ps.id, ce.id
         ORDER BY FIELD(ss.estatus, "aprobado_profesor", "en_validacion_admin", "enviado", "en_revision_profesor", "observado_profesor", "aprobado", "activo", "concluido", "rechazado", "cancelado", "borrador"),
                  ss.actualizado_en DESC'
    )->fetchAll();
} catch (Throwable $exception) {
    $aviso = 'No fue posible cargar los servicios sociales.';
}

$tituloPagina = 'Servicios sociales | Administrador';
$paginaActiva = 'servicios_sociales';
include __DIR__ . '/../../template/cabeceraAdmin.php';
?>

<div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center mb-4">
    <div>
        <p class="text-secondary mb-1">Validacion administrativa</p>
        <h1 class="h3 mb-0">Servicios sociales</h1>
    </div>
    <a class="btn btn-outline-dark" href="preregistros.php">Preregistros</a>
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

<?php if (!$servicios) : ?>
    <div class="alert alert-info">No hay servicios sociales registrados.</div>
<?php else : ?>
    <div class="vstack gap-3">
        <?php foreach ($servicios as $servicio) : ?>
            <?php
            $porcentaje = 0;
            if ((int) $servicio['horas_requeridas'] > 0) {
                $porcentaje = min(100, round(((int) $servicio['horas_validadas'] / (int) $servicio['horas_requeridas']) * 100));
            }
            ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between mb-3">
                        <div>
                            <h2 class="h5 mb-1"><?php echo app_e($servicio['folio']); ?> - <?php echo app_e($servicio['alumno']); ?></h2>
                            <p class="text-secondary mb-0"><?php echo app_e($servicio['matricula']); ?> - <?php echo app_e($servicio['carrera'] ?? 'Sin carrera'); ?></p>
                        </div>
                        <span class="badge align-self-start <?php echo app_e(app_badge_class($servicio['estatus'])); ?>">
                            <?php echo app_e(app_status_text($servicio['estatus'])); ?>
                        </span>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <p class="text-secondary mb-1">Profesor</p>
                            <p class="fw-semibold mb-0"><?php echo app_e($servicio['profesor'] ?? 'Sin asignar'); ?></p>
                        </div>
                        <div class="col-md-3">
                            <p class="text-secondary mb-1">Dependencia</p>
                            <p class="fw-semibold mb-0"><?php echo app_e($servicio['dependencia']); ?></p>
                        </div>
                        <div class="col-md-3">
                            <p class="text-secondary mb-1">Programa</p>
                            <p class="fw-semibold mb-0"><?php echo app_e($servicio['programa'] ?? 'Sin programa'); ?></p>
                        </div>
                        <div class="col-md-3">
                            <p class="text-secondary mb-1">Horas</p>
                            <p class="fw-semibold mb-0"><?php echo app_e($servicio['horas_validadas']); ?> / <?php echo app_e($servicio['horas_requeridas']); ?> (<?php echo app_e($porcentaje); ?>%)</p>
                        </div>
                    </div>

                    <form action="procesar-servicio.php" method="post" class="row g-2 align-items-end">
                        <input type="hidden" name="csrf_token" value="<?php echo app_e(app_csrf_token('admin_servicio_accion')); ?>">
                        <input type="hidden" name="servicio_social_id" value="<?php echo app_e($servicio['id']); ?>">
                        <div class="col-lg-3">
                            <label class="form-label" for="profesor_<?php echo app_e($servicio['id']); ?>">Profesor asesor</label>
                            <select class="form-select form-select-sm" id="profesor_<?php echo app_e($servicio['id']); ?>" name="profesor_id">
                                <option value="">Sin asignar</option>
                                <?php foreach ($profesores as $profesor) : ?>
                                    <option value="<?php echo app_e($profesor['id']); ?>" <?php echo ((string) ($servicio['profesor_id'] ?? '') === (string) $profesor['id']) ? 'selected' : ''; ?>>
                                        <?php echo app_e($profesor['profesor']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg">
                            <label class="form-label" for="comentario_<?php echo app_e($servicio['id']); ?>">Comentario</label>
                            <textarea class="form-control form-control-sm" id="comentario_<?php echo app_e($servicio['id']); ?>" name="comentario" rows="2"></textarea>
                        </div>
                        <div class="col-lg-auto d-flex flex-wrap gap-2">
                            <button class="btn btn-sm btn-outline-dark" name="accion" value="asignar" type="submit">Asignar</button>
                            <button class="btn btn-sm btn-success" name="accion" value="validar" type="submit">Validar</button>
                            <button class="btn btn-sm btn-outline-primary" name="accion" value="observar" type="submit">Observar</button>
                            <button class="btn btn-sm btn-outline-danger" name="accion" value="rechazar" type="submit">Rechazar</button>
                            <button class="btn btn-sm btn-primary" name="accion" value="activar" type="submit">Activar</button>
                            <button class="btn btn-sm btn-dark" name="accion" value="concluir" type="submit">Concluir</button>
                            <button class="btn btn-sm btn-outline-secondary" name="accion" value="cancelar" type="submit">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../../template/pie.php'; ?>
