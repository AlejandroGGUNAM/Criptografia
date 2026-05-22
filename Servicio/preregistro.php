<?php
require_once __DIR__ . '/config/bootstrap.php';

app_start_session();
app_security_headers();

$error = $_SESSION['preregistro_error'] ?? '';
$mensaje = $_SESSION['preregistro_message'] ?? '';
$old = $_SESSION['preregistro_old'] ?? [];
unset($_SESSION['preregistro_error'], $_SESSION['preregistro_message'], $_SESSION['preregistro_old']);

$carreras = [];

try {
    $carreras = app_db()
        ->query('SELECT id, nombre FROM carreras WHERE activo = 1 ORDER BY nombre')
        ->fetchAll();
} catch (Throwable $exception) {
    $error = $error ?: 'No fue posible cargar el catalogo de carreras.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preregistro | Servicio Social Universitario</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
    <main class="py-4 py-lg-5">
        <div class="container" style="max-width: 980px;">
            <div class="d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center mb-4">
                <div class="d-flex align-items-center gap-3">
                    <span class="brand-mark">SS</span>
                    <div>
                        <p class="small text-secondary text-uppercase fw-semibold mb-0">Primera etapa</p>
                        <h1 class="h3 mb-0">Preregistro de alumno</h1>
                    </div>
                </div>
                <a class="btn btn-outline-dark" href="index.php">Volver al inicio de sesion</a>
            </div>

            <?php if ($error !== '') : ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo app_e($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($mensaje !== '') : ?>
                <div class="alert alert-success" role="alert">
                    <?php echo app_e($mensaje); ?>
                </div>
            <?php endif; ?>

            <form action="guardar-preregistro.php" method="post" class="login-panel rounded-3 p-4 p-xl-5">
                <div class="row g-3">
                    <div class="col-12">
                        <h2 class="h5 mb-0">Datos personales</h2>
                    </div>

                    <div class="col-md-4">
                        <label for="nombres" class="form-label">Nombre(s)</label>
                        <input type="text" class="form-control" id="nombres" name="nombres" value="<?php echo app_e($old['nombres'] ?? ''); ?>" required maxlength="120">
                    </div>

                    <div class="col-md-4">
                        <label for="apellido_paterno" class="form-label">Apellido paterno</label>
                        <input type="text" class="form-control" id="apellido_paterno" name="apellido_paterno" value="<?php echo app_e($old['apellido_paterno'] ?? ''); ?>" required maxlength="120">
                    </div>

                    <div class="col-md-4">
                        <label for="apellido_materno" class="form-label">Apellido materno</label>
                        <input type="text" class="form-control" id="apellido_materno" name="apellido_materno" value="<?php echo app_e($old['apellido_materno'] ?? ''); ?>" maxlength="120">
                    </div>

                    <div class="col-md-6">
                        <label for="correo" class="form-label">Correo institucional</label>
                        <input type="email" class="form-control" id="correo" name="correo" value="<?php echo app_e($old['correo'] ?? ''); ?>" required maxlength="190" autocomplete="username">
                    </div>

                    <div class="col-md-6">
                        <label for="telefono" class="form-label">Telefono</label>
                        <input type="tel" class="form-control" id="telefono" name="telefono" value="<?php echo app_e($old['telefono'] ?? ''); ?>" maxlength="25">
                    </div>

                    <div class="col-12 pt-3">
                        <h2 class="h5 mb-0">Datos academicos</h2>
                    </div>

                    <div class="col-md-4">
                        <label for="matricula" class="form-label">Matricula</label>
                        <input type="text" class="form-control" id="matricula" name="matricula" value="<?php echo app_e($old['matricula'] ?? ''); ?>" required maxlength="40">
                    </div>

                    <div class="col-md-4">
                        <label for="carrera_id" class="form-label">Carrera</label>
                        <select class="form-select" id="carrera_id" name="carrera_id">
                            <option value="">Selecciona una carrera</option>
                            <?php foreach ($carreras as $carrera) : ?>
                                <option value="<?php echo app_e($carrera['id']); ?>" <?php echo ((string) ($old['carrera_id'] ?? '') === (string) $carrera['id']) ? 'selected' : ''; ?>>
                                    <?php echo app_e($carrera['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="semestre_actual" class="form-label">Semestre</label>
                        <input type="number" class="form-control" id="semestre_actual" name="semestre_actual" value="<?php echo app_e($old['semestre_actual'] ?? ''); ?>" min="1" max="20">
                    </div>

                    <div class="col-md-2">
                        <label for="creditos_aprobados" class="form-label">Creditos</label>
                        <input type="number" class="form-control" id="creditos_aprobados" name="creditos_aprobados" value="<?php echo app_e($old['creditos_aprobados'] ?? ''); ?>" min="0" max="999">
                    </div>

                    <div class="col-12 pt-3">
                        <h2 class="h5 mb-0">Acceso seguro</h2>
                    </div>

                    <div class="col-md-6">
                        <label for="password" class="form-label">Contrasena</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="10" autocomplete="new-password">
                        <div class="form-text">Usa al menos 10 caracteres.</div>
                    </div>

                    <div class="col-md-6">
                        <label for="password_confirmacion" class="form-label">Confirmar contrasena</label>
                        <input type="password" class="form-control" id="password_confirmacion" name="password_confirmacion" required minlength="10" autocomplete="new-password">
                    </div>

                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="acepta_aviso_privacidad" name="acepta_aviso_privacidad" required <?php echo !empty($old['acepta_aviso_privacidad']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="acepta_aviso_privacidad">
                                Confirmo que la informacion capturada es correcta y acepto el aviso de privacidad.
                            </label>
                        </div>
                    </div>

                    <div class="col-12 d-flex flex-column flex-md-row gap-2 justify-content-end pt-3">
                        <a class="btn btn-outline-secondary" href="index.php">Cancelar</a>
                        <button type="submit" class="btn btn-accent">Enviar preregistro</button>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
