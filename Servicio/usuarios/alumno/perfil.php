<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('alumno');

$alumno = app_current_alumno();
$carreras = [];
$error = app_take_flash('alumno_error');
$mensaje = app_take_flash('alumno_message');
$aviso = '';

try {
    $carreras = app_db()->query('SELECT id, nombre FROM carreras WHERE activo = 1 ORDER BY nombre')->fetchAll();
} catch (Throwable $exception) {
    $aviso = 'No fue posible cargar el catalogo de carreras.';
}

$tituloPagina = 'Perfil | Alumno';
$paginaActiva = 'perfil';
include __DIR__ . '/../../template/cabeceraAlu.php';
?>

<div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center mb-4">
    <div>
        <p class="text-secondary mb-1">Cuenta del alumno</p>
        <h1 class="h3 mb-0">Perfil</h1>
    </div>
    <a class="btn btn-outline-success" href="seguridad.php">Seguridad de la cuenta</a>
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

<?php if (!$alumno) : ?>
    <div class="alert alert-danger">No se encontro informacion de alumno para esta cuenta.</div>
<?php else : ?>
    <form action="guardar-perfil.php" method="post" class="card border-0 shadow-sm">
        <div class="card-body">
            <input type="hidden" name="csrf_token" value="<?php echo app_e(app_csrf_token('guardar_perfil_alumno')); ?>">

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="nombres" class="form-label">Nombre(s)</label>
                    <input type="text" class="form-control" id="nombres" name="nombres" maxlength="120" required value="<?php echo app_e($alumno['nombres']); ?>">
                </div>

                <div class="col-md-4">
                    <label for="apellido_paterno" class="form-label">Apellido paterno</label>
                    <input type="text" class="form-control" id="apellido_paterno" name="apellido_paterno" maxlength="120" required value="<?php echo app_e($alumno['apellido_paterno']); ?>">
                </div>

                <div class="col-md-4">
                    <label for="apellido_materno" class="form-label">Apellido materno</label>
                    <input type="text" class="form-control" id="apellido_materno" name="apellido_materno" maxlength="120" value="<?php echo app_e($alumno['apellido_materno']); ?>">
                </div>

                <div class="col-md-6">
                    <label for="correo" class="form-label">Correo institucional</label>
                    <input type="email" class="form-control" id="correo" value="<?php echo app_e($alumno['correo']); ?>" disabled>
                </div>

                <div class="col-md-6">
                    <label for="telefono" class="form-label">Telefono</label>
                    <input type="tel" class="form-control" id="telefono" name="telefono" maxlength="25" value="<?php echo app_e($alumno['telefono']); ?>">
                </div>

                <div class="col-md-4">
                    <label for="matricula" class="form-label">Matricula</label>
                    <input type="text" class="form-control" id="matricula" value="<?php echo app_e($alumno['matricula']); ?>" disabled>
                </div>

                <div class="col-md-4">
                    <label for="carrera_id" class="form-label">Carrera</label>
                    <select class="form-select" id="carrera_id" name="carrera_id">
                        <option value="">Sin asignar</option>
                        <?php foreach ($carreras as $carrera) : ?>
                            <option value="<?php echo app_e($carrera['id']); ?>" <?php echo ((string) ($alumno['carrera_id'] ?? '') === (string) $carrera['id']) ? 'selected' : ''; ?>>
                                <?php echo app_e($carrera['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="semestre_actual" class="form-label">Semestre</label>
                    <input type="number" class="form-control" id="semestre_actual" name="semestre_actual" min="1" max="20" value="<?php echo app_e($alumno['semestre_actual']); ?>">
                </div>

                <div class="col-md-2">
                    <label for="creditos_aprobados" class="form-label">Creditos</label>
                    <input type="number" class="form-control" id="creditos_aprobados" name="creditos_aprobados" min="0" max="999" value="<?php echo app_e($alumno['creditos_aprobados']); ?>">
                </div>
            </div>
        </div>

        <div class="card-footer bg-white d-flex justify-content-end">
            <button class="btn btn-success" type="submit">Guardar cambios</button>
        </div>
    </form>
<?php endif; ?>

<?php include __DIR__ . '/../../template/pie.php'; ?>
