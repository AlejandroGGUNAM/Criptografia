<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('administrador');

$algoritmos = ['Ed25519', 'ECDSA_P256', 'RSA_PSS_2048'];
$error = app_take_flash('admin_error');
$mensaje = app_take_flash('admin_message');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!app_verify_csrf('admin_llaves_accion', $_POST['csrf_token'] ?? '')) {
        app_flash('admin_error', 'La sesion expiro. Intenta nuevamente.');
        app_redirect('llaves_publicas.php');
    }

    $accion = $_POST['accion'] ?? '';

    try {
        $pdo = app_db();

        if ($accion === 'crear') {
            $usuarioId = (int) ($_POST['usuario_id'] ?? 0);
            $algoritmo = $_POST['algoritmo'] ?? '';
            $llavePublica = trim($_POST['llave_publica'] ?? '');

            if ($usuarioId <= 0 || !in_array($algoritmo, $algoritmos, true) || $llavePublica === '') {
                app_flash('admin_error', 'Captura usuario, algoritmo y llave publica.');
                app_redirect('llaves_publicas.php');
            }

            $pdo->prepare(
                'INSERT INTO llaves_publicas_usuario (
                    usuario_id,
                    algoritmo,
                    llave_publica,
                    fingerprint_sha256,
                    activa
                 ) VALUES (
                    :usuario_id,
                    :algoritmo,
                    :llave_publica,
                    :fingerprint_sha256,
                    1
                 )'
            )->execute([
                'usuario_id' => $usuarioId,
                'algoritmo' => $algoritmo,
                'llave_publica' => $llavePublica,
                'fingerprint_sha256' => hash('sha256', $llavePublica, true),
            ]);

            app_audit('admin.llave.crear', 'llave_publica', (int) $pdo->lastInsertId(), ['usuario_id' => $usuarioId]);
            app_flash('admin_message', 'Llave publica registrada correctamente.');
            app_redirect('llaves_publicas.php');
        }

        if ($accion === 'revocar') {
            $llaveId = (int) ($_POST['llave_id'] ?? 0);
            $motivo = trim($_POST['motivo_revocacion'] ?? '');

            if ($llaveId <= 0) {
                app_flash('admin_error', 'Llave no valida.');
                app_redirect('llaves_publicas.php');
            }

            $pdo->prepare(
                'UPDATE llaves_publicas_usuario
                 SET activa = 0,
                     revocada_en = NOW(),
                     motivo_revocacion = :motivo
                 WHERE id = :id'
            )->execute([
                'motivo' => $motivo ?: 'Revocada por administrador',
                'id' => $llaveId,
            ]);

            app_audit('admin.llave.revocar', 'llave_publica', $llaveId);
            app_flash('admin_message', 'Llave revocada correctamente.');
            app_redirect('llaves_publicas.php');
        }
    } catch (Throwable $exception) {
        app_flash('admin_error', 'No fue posible procesar la llave. Revisa si el fingerprint ya existe.');
        app_redirect('llaves_publicas.php');
    }
}

$usuarios = [];
$llaves = [];
$aviso = '';

try {
    $pdo = app_db();
    $usuarios = $pdo->query(
        'SELECT id, CONCAT_WS(" ", nombres, apellido_paterno, apellido_materno) AS nombre, correo
         FROM usuarios
         ORDER BY nombres, apellido_paterno'
    )->fetchAll();

    $llaves = $pdo->query(
        'SELECT l.*, CONCAT_WS(" ", u.nombres, u.apellido_paterno, u.apellido_materno) AS usuario, u.correo
         FROM llaves_publicas_usuario l
         INNER JOIN usuarios u ON u.id = l.usuario_id
         ORDER BY l.creada_en DESC'
    )->fetchAll();
} catch (Throwable $exception) {
    $aviso = 'No fue posible cargar las llaves publicas.';
}

$tituloPagina = 'Llaves publicas | Administrador';
$paginaActiva = 'llaves_publicas';
include __DIR__ . '/../../template/cabeceraAdmin.php';
?>

<div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center mb-4">
    <div>
        <p class="text-secondary mb-1">Seguridad</p>
        <h1 class="h3 mb-0">Llaves publicas</h1>
    </div>
    <a class="btn btn-outline-dark" href="firmas_digitales.php">Firmas digitales</a>
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
    <div class="col-lg-4">
        <form method="post" class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5">Registrar llave</h2>
                <input type="hidden" name="csrf_token" value="<?php echo app_e(app_csrf_token('admin_llaves_accion')); ?>">
                <input type="hidden" name="accion" value="crear">

                <div class="mb-3">
                    <label for="usuario_id" class="form-label">Usuario</label>
                    <select class="form-select" id="usuario_id" name="usuario_id" required>
                        <option value="">Selecciona usuario</option>
                        <?php foreach ($usuarios as $usuario) : ?>
                            <option value="<?php echo app_e($usuario['id']); ?>"><?php echo app_e($usuario['nombre']); ?> - <?php echo app_e($usuario['correo']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="algoritmo" class="form-label">Algoritmo</label>
                    <select class="form-select" id="algoritmo" name="algoritmo" required>
                        <?php foreach ($algoritmos as $algoritmo) : ?>
                            <option value="<?php echo app_e($algoritmo); ?>"><?php echo app_e($algoritmo); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="llave_publica" class="form-label">Llave publica</label>
                    <textarea class="form-control" id="llave_publica" name="llave_publica" rows="6" required></textarea>
                </div>

                <button class="btn btn-dark w-100" type="submit">Guardar llave</button>
            </div>
        </form>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5">Llaves registradas</h2>
                <?php if (!$llaves) : ?>
                    <p class="text-secondary mb-0">No hay llaves publicas registradas.</p>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Algoritmo</th>
                                    <th>Fingerprint</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($llaves as $llave) : ?>
                                    <tr>
                                        <td><?php echo app_e($llave['usuario']); ?><div class="small text-secondary"><?php echo app_e($llave['correo']); ?></div></td>
                                        <td><?php echo app_e($llave['algoritmo']); ?></td>
                                        <td><code><?php echo app_e(bin2hex($llave['fingerprint_sha256'])); ?></code></td>
                                        <td>
                                            <span class="badge <?php echo $llave['activa'] ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                                                <?php echo $llave['activa'] ? 'Activa' : 'Revocada'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="post" class="d-flex gap-2">
                                                <input type="hidden" name="csrf_token" value="<?php echo app_e(app_csrf_token('admin_llaves_accion')); ?>">
                                                <input type="hidden" name="accion" value="revocar">
                                                <input type="hidden" name="llave_id" value="<?php echo app_e($llave['id']); ?>">
                                                <input type="hidden" name="motivo_revocacion" value="Revocada por administrador">
                                                <button class="btn btn-sm btn-outline-danger" type="submit" <?php echo $llave['activa'] ? '' : 'disabled'; ?>>Revocar</button>
                                            </form>
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
