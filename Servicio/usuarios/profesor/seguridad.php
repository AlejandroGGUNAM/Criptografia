<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('profesor');

$llaves = [];
$sesiones = [];
$error = app_take_flash('profesor_error');
$mensaje = app_take_flash('profesor_message');
$aviso = '';

try {
    $stmt = app_db()->prepare(
        'SELECT * FROM llaves_publicas_usuario
         WHERE usuario_id = :usuario_id
         ORDER BY creada_en DESC'
    );
    $stmt->execute(['usuario_id' => $_SESSION['usuario_id']]);
    $llaves = $stmt->fetchAll();

    $stmt = app_db()->prepare(
        'SELECT id, creada_en, ultima_actividad_en, expira_en, revocada_en
         FROM sesiones_usuario
         WHERE usuario_id = :usuario_id
         ORDER BY ultima_actividad_en DESC
         LIMIT 10'
    );
    $stmt->execute(['usuario_id' => $_SESSION['usuario_id']]);
    $sesiones = $stmt->fetchAll();
} catch (Throwable $exception) {
    $aviso = 'No fue posible cargar toda la informacion de seguridad.';
}

$tituloPagina = 'Seguridad | Profesor';
$paginaActiva = 'seguridad';
include __DIR__ . '/../../template/cabeceraProf.php';
?>

<div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center mb-4">
    <div>
        <p class="text-secondary mb-1">Cuenta y criptografia</p>
        <h1 class="h3 mb-0">Seguridad</h1>
    </div>
    <a class="btn btn-outline-primary" href="perfil.php">Perfil</a>
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

<div class="row g-3">
    <div class="col-lg-5">
        <form action="cambiar-password.php" method="post" class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5">Cambiar contrasena</h2>
                <p class="text-secondary">Las contrasenas se guardan con Argon2id.</p>
                <input type="hidden" name="csrf_token" value="<?php echo app_e(app_csrf_token('cambiar_password_profesor')); ?>">

                <div class="mb-3">
                    <label for="password_actual" class="form-label">Contrasena actual</label>
                    <input type="password" class="form-control" id="password_actual" name="password_actual" required autocomplete="current-password">
                </div>

                <div class="mb-3">
                    <label for="password_nuevo" class="form-label">Nueva contrasena</label>
                    <input type="password" class="form-control" id="password_nuevo" name="password_nuevo" required minlength="10" autocomplete="new-password">
                </div>

                <div class="mb-3">
                    <label for="password_confirmacion" class="form-label">Confirmar nueva contrasena</label>
                    <input type="password" class="form-control" id="password_confirmacion" name="password_confirmacion" required minlength="10" autocomplete="new-password">
                </div>

                <button class="btn btn-primary w-100" type="submit">Actualizar contrasena</button>
            </div>
        </form>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h5">Llaves publicas</h2>
                <?php if (!$llaves) : ?>
                    <p class="mb-0 text-secondary">No hay llaves publicas registradas.</p>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Algoritmo</th>
                                    <th>Fingerprint</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($llaves as $llave) : ?>
                                    <tr>
                                        <td><?php echo app_e($llave['algoritmo']); ?></td>
                                        <td><code><?php echo app_e(bin2hex($llave['fingerprint_sha256'])); ?></code></td>
                                        <td>
                                            <span class="badge <?php echo $llave['activa'] ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                                                <?php echo $llave['activa'] ? 'Activa' : 'Revocada'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5">Sesiones recientes</h2>
                <?php if (!$sesiones) : ?>
                    <p class="mb-0 text-secondary">No hay sesiones registradas.</p>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Inicio</th>
                                    <th>Ultima actividad</th>
                                    <th>Expira</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sesiones as $sesion) : ?>
                                    <tr>
                                        <td><?php echo app_e($sesion['creada_en']); ?></td>
                                        <td><?php echo app_e($sesion['ultima_actividad_en']); ?></td>
                                        <td><?php echo app_e($sesion['expira_en']); ?></td>
                                        <td>
                                            <span class="badge <?php echo empty($sesion['revocada_en']) ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                                                <?php echo empty($sesion['revocada_en']) ? 'Activa' : 'Revocada'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../template/pie.php'; ?>
