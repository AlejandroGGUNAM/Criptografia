<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('profesor');

$profesor = app_current_profesor();
$departamentos = [];
$error = app_take_flash('profesor_error');
$mensaje = app_take_flash('profesor_message');
$aviso = '';

try {
    $departamentos = app_db()->query('SELECT id, nombre FROM departamentos WHERE activo = 1 ORDER BY nombre')->fetchAll();
} catch (Throwable $exception) {
    $aviso = 'No fue posible cargar el catalogo de departamentos.';
}

$tituloPagina = 'Perfil | Profesor';
$paginaActiva = 'perfil';
include __DIR__ . '/../../template/cabeceraProf.php';
?>

<div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center mb-4">
    <div>
        <p class="text-secondary mb-1">Cuenta del profesor</p>
        <h1 class="h3 mb-0">Perfil</h1>
    </div>
    <a class="btn btn-outline-primary" href="seguridad.php">Seguridad de la cuenta</a>
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
<?php else : ?>
    <form action="guardar-perfil.php" method="post" class="card border-0 shadow-sm">
        <div class="card-body">
            <input type="hidden" name="csrf_token" value="<?php echo app_e(app_csrf_token('guardar_perfil_profesor')); ?>">

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="nombres" class="form-label">Nombre(s)</label>
                    <input type="text" class="form-control" id="nombres" name="nombres" maxlength="120" required value="<?php echo app_e($profesor['nombres']); ?>">
                </div>

                <div class="col-md-4">
                    <label for="apellido_paterno" class="form-label">Apellido paterno</label>
                    <input type="text" class="form-control" id="apellido_paterno" name="apellido_paterno" maxlength="120" required value="<?php echo app_e($profesor['apellido_paterno']); ?>">
                </div>

                <div class="col-md-4">
                    <label for="apellido_materno" class="form-label">Apellido materno</label>
                    <input type="text" class="form-control" id="apellido_materno" name="apellido_materno" maxlength="120" value="<?php echo app_e($profesor['apellido_materno']); ?>">
                </div>

                <div class="col-md-6">
                    <label for="correo" class="form-label">Correo institucional</label>
                    <input type="email" class="form-control" id="correo" value="<?php echo app_e($profesor['correo']); ?>" disabled>
                </div>

                <div class="col-md-6">
                    <label for="telefono" class="form-label">Telefono</label>
                    <input type="tel" class="form-control" id="telefono" name="telefono" maxlength="25" value="<?php echo app_e($profesor['telefono']); ?>">
                </div>

                <div class="col-md-4">
                    <label for="numero_empleado" class="form-label">Numero de empleado</label>
                    <input type="text" class="form-control" id="numero_empleado" name="numero_empleado" maxlength="40" required value="<?php echo app_e($profesor['numero_empleado']); ?>">
                </div>

                <div class="col-md-4">
                    <label for="departamento_id" class="form-label">Departamento</label>
                    <select class="form-select" id="departamento_id" name="departamento_id">
                        <option value="">Sin asignar</option>
                        <?php foreach ($departamentos as $departamento) : ?>
                            <option value="<?php echo app_e($departamento['id']); ?>" <?php echo ((string) ($profesor['departamento_id'] ?? '') === (string) $departamento['id']) ? 'selected' : ''; ?>>
                                <?php echo app_e($departamento['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="grado_academico" class="form-label">Grado academico</label>
                    <input type="text" class="form-control" id="grado_academico" name="grado_academico" maxlength="80" value="<?php echo app_e($profesor['grado_academico']); ?>">
                </div>
            </div>
        </div>

        <div class="card-footer bg-white d-flex justify-content-end">
            <button class="btn btn-primary" type="submit">Guardar cambios</button>
        </div>
    </form>
<?php endif; ?>

<?php include __DIR__ . '/../../template/pie.php'; ?>
