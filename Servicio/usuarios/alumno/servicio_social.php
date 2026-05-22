<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('alumno');

$alumno = app_current_alumno();
$preregistro = null;
$servicio = null;
$dependencias = [];
$programas = [];
$profesores = [];
$ciclos = [];
$error = app_take_flash('alumno_error');
$mensaje = app_take_flash('alumno_message');
$aviso = '';

try {
    if ($alumno) {
        $stmt = app_db()->prepare('SELECT * FROM preregistros WHERE alumno_id = :alumno_id LIMIT 1');
        $stmt->execute(['alumno_id' => $alumno['alumno_id']]);
        $preregistro = $stmt->fetch();

        $stmt = app_db()->prepare(
            'SELECT ss.*, d.nombre AS dependencia, ps.nombre AS programa,
                    CONCAT_WS(" ", up.nombres, up.apellido_paterno, up.apellido_materno) AS profesor
             FROM servicios_sociales ss
             INNER JOIN dependencias d ON d.id = ss.dependencia_id
             LEFT JOIN programas_servicio_social ps ON ps.id = ss.programa_id
             LEFT JOIN profesores p ON p.id = ss.profesor_id
             LEFT JOIN usuarios up ON up.id = p.usuario_id
             WHERE ss.alumno_id = :alumno_id
             ORDER BY ss.creado_en DESC
             LIMIT 1'
        );
        $stmt->execute(['alumno_id' => $alumno['alumno_id']]);
        $servicio = $stmt->fetch();
    }

    $dependencias = app_db()->query('SELECT id, nombre FROM dependencias WHERE activo = 1 ORDER BY nombre')->fetchAll();
    $programas = app_db()->query(
        'SELECT id, dependencia_id, nombre FROM programas_servicio_social WHERE activo = 1 ORDER BY nombre'
    )->fetchAll();
    $profesores = app_db()->query(
        'SELECT p.id, CONCAT_WS(" ", u.nombres, u.apellido_paterno, u.apellido_materno) AS nombre
         FROM profesores p
         INNER JOIN usuarios u ON u.id = p.usuario_id
         WHERE u.estatus = "activo"
         ORDER BY nombre'
    )->fetchAll();
    $ciclos = app_db()->query('SELECT id, nombre FROM ciclos_escolares WHERE activo = 1 ORDER BY fecha_inicio DESC')->fetchAll();
} catch (Throwable $exception) {
    $aviso = 'No fue posible cargar la informacion del servicio social.';
}

$preregistroValidado = (($preregistro['estatus'] ?? '') === 'validado');
$estatusServicio = $servicio['estatus'] ?? 'borrador';
$estatusEditables = ['borrador', 'enviado', 'observado_profesor', 'rechazado'];
$puedeEditar = $preregistroValidado && (!$servicio || in_array($estatusServicio, $estatusEditables, true));
$catalogosListos = count($dependencias) > 0;
$bloqueado = !$puedeEditar || !$catalogosListos;

$tituloPagina = 'Mi servicio social | Alumno';
$paginaActiva = 'servicio_social';
include __DIR__ . '/../../template/cabeceraAlu.php';
?>

<div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center mb-4">
    <div>
        <p class="text-secondary mb-1">Tercera etapa</p>
        <h1 class="h3 mb-0">Registro del servicio social</h1>
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

<?php if (!$preregistroValidado) : ?>
    <div class="alert alert-info">
        Para registrar el servicio social, tu preregistro debe estar validado. Estado actual:
        <strong><?php echo app_e(app_status_text($preregistro['estatus'] ?? 'sin preregistro')); ?></strong>.
    </div>
<?php endif; ?>

<?php if (!$catalogosListos) : ?>
    <div class="alert alert-warning">
        Aun no hay dependencias activas en el catalogo. Un administrador debe registrarlas antes de que puedas enviar esta solicitud.
    </div>
<?php endif; ?>

<form action="guardar-servicio-social.php" method="post" class="card border-0 shadow-sm">
    <div class="card-body">
        <input type="hidden" name="csrf_token" value="<?php echo app_e(app_csrf_token('guardar_servicio_social')); ?>">
        <?php if ($servicio) : ?>
            <input type="hidden" name="servicio_social_id" value="<?php echo app_e($servicio['id']); ?>">
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="dependencia_id" class="form-label">Dependencia receptora</label>
                <select class="form-select" id="dependencia_id" name="dependencia_id" required <?php echo $bloqueado ? 'disabled' : ''; ?>>
                    <option value="">Selecciona una dependencia</option>
                    <?php foreach ($dependencias as $dependencia) : ?>
                        <option value="<?php echo app_e($dependencia['id']); ?>" <?php echo ((string) ($servicio['dependencia_id'] ?? '') === (string) $dependencia['id']) ? 'selected' : ''; ?>>
                            <?php echo app_e($dependencia['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="programa_id" class="form-label">Programa</label>
                <select class="form-select" id="programa_id" name="programa_id" <?php echo $bloqueado ? 'disabled' : ''; ?>>
                    <option value="">Sin programa asignado</option>
                    <?php foreach ($programas as $programa) : ?>
                        <option value="<?php echo app_e($programa['id']); ?>" <?php echo ((string) ($servicio['programa_id'] ?? '') === (string) $programa['id']) ? 'selected' : ''; ?>>
                            <?php echo app_e($programa['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="profesor_id" class="form-label">Profesor asesor</label>
                <select class="form-select" id="profesor_id" name="profesor_id" <?php echo $bloqueado ? 'disabled' : ''; ?>>
                    <option value="">Pendiente de asignar</option>
                    <?php foreach ($profesores as $profesor) : ?>
                        <option value="<?php echo app_e($profesor['id']); ?>" <?php echo ((string) ($servicio['profesor_id'] ?? '') === (string) $profesor['id']) ? 'selected' : ''; ?>>
                            <?php echo app_e($profesor['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="ciclo_escolar_id" class="form-label">Ciclo escolar</label>
                <select class="form-select" id="ciclo_escolar_id" name="ciclo_escolar_id" <?php echo $bloqueado ? 'disabled' : ''; ?>>
                    <option value="">Sin ciclo asignado</option>
                    <?php foreach ($ciclos as $ciclo) : ?>
                        <option value="<?php echo app_e($ciclo['id']); ?>" <?php echo ((string) ($servicio['ciclo_escolar_id'] ?? '') === (string) $ciclo['id']) ? 'selected' : ''; ?>>
                            <?php echo app_e($ciclo['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="fecha_inicio" class="form-label">Fecha de inicio</label>
                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?php echo app_e($servicio['fecha_inicio'] ?? ''); ?>" <?php echo $bloqueado ? 'disabled' : ''; ?>>
            </div>

            <div class="col-md-4">
                <label for="fecha_fin_programada" class="form-label">Fecha fin programada</label>
                <input type="date" class="form-control" id="fecha_fin_programada" name="fecha_fin_programada" value="<?php echo app_e($servicio['fecha_fin_programada'] ?? ''); ?>" <?php echo $bloqueado ? 'disabled' : ''; ?>>
            </div>

            <div class="col-md-4">
                <label for="horas_requeridas" class="form-label">Horas requeridas</label>
                <input type="number" class="form-control" id="horas_requeridas" name="horas_requeridas" min="1" max="999" value="<?php echo app_e($servicio['horas_requeridas'] ?? 480); ?>" <?php echo $bloqueado ? 'disabled' : ''; ?>>
            </div>

            <div class="col-md-6">
                <label for="responsable_externo_nombre" class="form-label">Responsable externo</label>
                <input type="text" class="form-control" id="responsable_externo_nombre" name="responsable_externo_nombre" maxlength="160" value="<?php echo app_e($servicio['responsable_externo_nombre'] ?? ''); ?>" <?php echo $bloqueado ? 'disabled' : ''; ?>>
            </div>

            <div class="col-md-6">
                <label for="responsable_externo_correo" class="form-label">Correo del responsable</label>
                <input type="email" class="form-control" id="responsable_externo_correo" name="responsable_externo_correo" maxlength="190" value="<?php echo app_e($servicio['responsable_externo_correo'] ?? ''); ?>" <?php echo $bloqueado ? 'disabled' : ''; ?>>
            </div>

            <div class="col-12">
                <label for="horario" class="form-label">Horario</label>
                <textarea class="form-control" id="horario" name="horario" rows="2" <?php echo $bloqueado ? 'disabled' : ''; ?>><?php echo app_e($servicio['horario'] ?? ''); ?></textarea>
            </div>

            <div class="col-12">
                <label for="actividades" class="form-label">Actividades principales</label>
                <textarea class="form-control" id="actividades" name="actividades" rows="4" <?php echo $bloqueado ? 'disabled' : ''; ?>><?php echo app_e($servicio['actividades'] ?? ''); ?></textarea>
            </div>
        </div>
    </div>

    <div class="card-footer bg-white d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center">
        <div class="text-secondary small">
            <?php if ($servicio) : ?>
                Folio: <strong><?php echo app_e($servicio['folio']); ?></strong>
            <?php else : ?>
                El folio se generara al enviar la solicitud.
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-success" <?php echo $bloqueado ? 'disabled' : ''; ?>>
            Guardar y enviar solicitud
        </button>
    </div>
</form>

<?php include __DIR__ . '/../../template/pie.php'; ?>
