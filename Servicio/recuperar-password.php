<?php
require_once __DIR__ . '/config/bootstrap.php';

app_start_session();
app_security_headers();

$mensaje = app_take_flash('login_message');
$error = app_take_flash('login_error');
$enlaceDesarrollo = app_take_flash('recovery_link');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contrasena | Servicio Social Universitario</title>
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
                        <h1 class="h4 mb-0">Recuperar contrasena</h1>
                    </div>
                </div>

                <?php if ($error !== '') : ?>
                    <div class="alert alert-danger"><?php echo app_e($error); ?></div>
                <?php endif; ?>

                <?php if ($mensaje !== '') : ?>
                    <div class="alert alert-success"><?php echo app_e($mensaje); ?></div>
                <?php endif; ?>

                <?php if ($enlaceDesarrollo !== '') : ?>
                    <div class="alert alert-info">
                        Enlace local de recuperacion:
                        <a href="<?php echo app_e($enlaceDesarrollo); ?>"><?php echo app_e($enlaceDesarrollo); ?></a>
                    </div>
                <?php endif; ?>

                <p class="text-secondary">
                    Ingresa tu correo institucional. El sistema generara un token temporal para restablecer la contrasena.
                </p>

                <form action="solicitar-recuperacion.php" method="post" class="vstack gap-3">
                    <input type="hidden" name="csrf_token" value="<?php echo app_e(app_csrf_token('solicitar_recuperacion')); ?>">
                    <div>
                        <label for="correo" class="form-label">Correo institucional</label>
                        <input type="email" class="form-control form-control-lg" id="correo" name="correo" placeholder="usuario@universidad.edu" required autocomplete="username">
                    </div>
                    <button class="btn btn-accent btn-lg" type="submit">Generar enlace de recuperacion</button>
                    <a class="btn btn-outline-secondary" href="index.php">Volver al inicio</a>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
