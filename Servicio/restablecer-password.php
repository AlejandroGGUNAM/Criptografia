<?php
require_once __DIR__ . '/config/bootstrap.php';

app_start_session();
app_security_headers();

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = app_take_flash('login_error');
$mensaje = app_take_flash('login_message');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer contrasena | Servicio Social Universitario</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
    <main class="access-page d-flex align-items-center py-4">
        <div class="container" style="max-width: 640px;">
            <div class="login-panel rounded-3 p-4 p-xl-5">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <span class="brand-mark">SS</span>
                    <div>
                        <p class="small text-secondary text-uppercase fw-semibold mb-0">Seguridad</p>
                        <h1 class="h4 mb-0">Restablecer contrasena</h1>
                    </div>
                </div>

                <?php if ($error !== '') : ?>
                    <div class="alert alert-danger"><?php echo app_e($error); ?></div>
                <?php endif; ?>

                <?php if ($mensaje !== '') : ?>
                    <div class="alert alert-success"><?php echo app_e($mensaje); ?></div>
                <?php endif; ?>

                <?php if ($token === '') : ?>
                    <div class="alert alert-warning">El token de recuperacion no fue proporcionado.</div>
                    <a class="btn btn-outline-secondary" href="recuperar-password.php">Solicitar otro enlace</a>
                <?php else : ?>
                    <form action="guardar-nueva-password.php" method="post" class="vstack gap-3">
                        <input type="hidden" name="csrf_token" value="<?php echo app_e(app_csrf_token('guardar_nueva_password')); ?>">
                        <input type="hidden" name="token" value="<?php echo app_e($token); ?>">

                        <div>
                            <label for="password" class="form-label">Nueva contrasena</label>
                            <input type="password" class="form-control form-control-lg" id="password" name="password" minlength="10" required autocomplete="new-password">
                        </div>

                        <div>
                            <label for="password_confirmacion" class="form-label">Confirmar contrasena</label>
                            <input type="password" class="form-control form-control-lg" id="password_confirmacion" name="password_confirmacion" minlength="10" required autocomplete="new-password">
                        </div>

                        <button class="btn btn-accent btn-lg" type="submit">Guardar nueva contrasena</button>
                        <a class="btn btn-outline-secondary" href="index.php">Volver al inicio</a>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
