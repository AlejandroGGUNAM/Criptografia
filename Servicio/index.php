<?php
require_once __DIR__ . '/config/bootstrap.php';

app_start_session();
app_security_headers();

$error = $_SESSION['login_error'] ?? '';
$mensaje = $_SESSION['login_message'] ?? '';
unset($_SESSION['login_error'], $_SESSION['login_message']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso | Servicio Social Universitario</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
    <main class="access-page d-flex align-items-center py-4 py-lg-5">
        <div class="container access-shell">
            <div class="row g-4 g-xl-5 align-items-stretch">
                <section class="col-lg-6 d-flex">
                    <div class="access-info rounded-3 p-4 p-xl-5 w-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-3 mb-4">
                                <span class="brand-mark">SS</span>
                                <div>
                                    <p class="mb-0 small text-secondary text-uppercase fw-semibold">Universidad</p>
                                    <h1 class="h3 mb-0">Servicio Social</h1>
                                </div>
                            </div>

                            <p class="fs-5 mb-4">
                                Gestiona el preregistro, validacion y seguimiento del servicio social con control de roles y seguridad criptografica.
                            </p>

                            <div class="security-strip rounded-3 p-3 mb-4">
                                <p class="fw-semibold mb-1">Acceso seguro</p>
                                <p class="small text-secondary mb-0">
                                    El sistema esta preparado para usar contrasenas con Argon2id, tokens de seguridad, sesiones protegidas y firmas digitales.
                                </p>
                            </div>
                        </div>

                        <div class="vstack gap-3">
                            <div class="d-flex gap-3">
                                <span class="step-badge">1</span>
                                <div>
                                    <p class="fw-semibold mb-0">Preregistro</p>
                                    <p class="small text-secondary mb-0">El alumno captura sus datos iniciales.</p>
                                </div>
                            </div>
                            <div class="d-flex gap-3">
                                <span class="step-badge">2</span>
                                <div>
                                    <p class="fw-semibold mb-0">Validacion</p>
                                    <p class="small text-secondary mb-0">Profesor y administrador revisan la informacion.</p>
                                </div>
                            </div>
                            <div class="d-flex gap-3">
                                <span class="step-badge">3</span>
                                <div>
                                    <p class="fw-semibold mb-0">Registro del servicio social</p>
                                    <p class="small text-secondary mb-0">Se registra programa, dependencia, fechas y seguimiento.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="col-lg-6 d-flex">
                    <div class="login-panel rounded-3 p-4 p-xl-5 w-100">
                        <div class="mb-4">
                            <p class="text-secondary small text-uppercase fw-semibold mb-1">Acceso al sistema</p>
                            <h2 class="h4 mb-2">Iniciar sesion</h2>
                            <p class="text-secondary mb-0">Entra con tu correo institucional. El rol se detectara automaticamente.</p>
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

                        <form action="login.php" method="post" autocomplete="on" class="vstack gap-3">
                            <div>
                                <label for="correo" class="form-label">Correo institucional</label>
                                <input type="email" class="form-control form-control-lg" id="correo" name="correo" placeholder="usuario@universidad.edu" required autocomplete="username">
                            </div>

                            <div>
                                <div class="d-flex justify-content-between gap-3">
                                    <label for="password" class="form-label">Contrasena</label>
                                    <a href="recuperar-password.php" class="small">Olvide mi contrasena</a>
                                </div>
                                <input type="password" class="form-control form-control-lg" id="password" name="password" required autocomplete="current-password">
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="recordarme" name="recordarme">
                                <label class="form-check-label" for="recordarme">
                                    Mantener sesion iniciada en este equipo
                                </label>
                            </div>

                            <button type="submit" class="btn btn-accent btn-lg w-100">
                                Iniciar sesion
                            </button>
                        </form>

                        <div class="border-top mt-4 pt-4">
                            <p class="mb-2 text-secondary">¿Eres alumno y aun no tienes cuenta?</p>
                            <a class="btn btn-outline-dark w-100" href="preregistro.php">
                                Crear preregistro de alumno
                            </a>
                        </div>

                        <div class="mt-4 text-center">
                            <a class="small text-secondary" href="verificar.php">
                                Verificar constancia o firma digital
                            </a>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
