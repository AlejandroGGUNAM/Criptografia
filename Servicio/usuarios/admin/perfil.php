<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('administrador');

$admin = app_current_administrador();
$error = app_take_flash('admin_error');
$mensaje = app_take_flash('admin_message');

$tituloPagina = 'Perfil | Administrador';
$paginaActiva = 'perfil';
include __DIR__ . '/../../template/cabeceraAdmin.php';
?>

<div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center mb-4">
    <div>
        <p class="text-secondary mb-1">Cuenta administrativa</p>
        <h1 class="h3 mb-0">Perfil</h1>
    </div>
    <a class="btn btn-outline-dark" href="seguridad.php">Seguridad de la cuenta</a>
</div>

<?php if ($error !== '') : ?>
    <div class="alert alert-danger"><?php echo app_e($error); ?></div>
<?php endif; ?>

<?php if ($mensaje !== '') : ?>
    <div class="alert alert-success"><?php echo app_e($mensaje); ?></div>
<?php endif; ?>

<?php if (!$admin) : ?>
    <div class="alert alert-danger">No se encontro informacion de administrador para esta cuenta.</div>
<?php else : ?>
    <form action="guardar-perfil.php" method="post" class="card border-0 shadow-sm">
        <div class="card-body">
            <input type="hidden" name="csrf_token" value="<?php echo app_e(app_csrf_token('guardar_perfil_admin')); ?>">

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="nombres" class="form-label">Nombre(s)</label>
                    <input type="text" class="form-control" id="nombres" name="nombres" maxlength="120" required value="<?php echo app_e($admin['nombres']); ?>">
                </div>

                <div class="col-md-4">
                    <label for="apellido_paterno" class="form-label">Apellido paterno</label>
                    <input type="text" class="form-control" id="apellido_paterno" name="apellido_paterno" maxlength="120" required value="<?php echo app_e($admin['apellido_paterno']); ?>">
                </div>

                <div class="col-md-4">
                    <label for="apellido_materno" class="form-label">Apellido materno</label>
                    <input type="text" class="form-control" id="apellido_materno" name="apellido_materno" maxlength="120" value="<?php echo app_e($admin['apellido_materno']); ?>">
                </div>

                <div class="col-md-6">
                    <label for="correo" class="form-label">Correo institucional</label>
                    <input type="email" class="form-control" id="correo" value="<?php echo app_e($admin['correo']); ?>" disabled>
                </div>

                <div class="col-md-6">
                    <label for="telefono" class="form-label">Telefono</label>
                    <input type="tel" class="form-control" id="telefono" name="telefono" maxlength="25" value="<?php echo app_e($admin['telefono']); ?>">
                </div>

                <div class="col-md-12">
                    <label for="area" class="form-label">Area administrativa</label>
                    <input type="text" class="form-control" id="area" name="area" maxlength="120" value="<?php echo app_e($admin['area']); ?>">
                </div>
            </div>
        </div>

        <div class="card-footer bg-white d-flex justify-content-end">
            <button class="btn btn-dark" type="submit">Guardar cambios</button>
        </div>
    </form>
<?php endif; ?>

<?php include __DIR__ . '/../../template/pie.php'; ?>
