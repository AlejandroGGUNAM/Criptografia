<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('administrador');

$propositos = ['verificacion_correo', 'recuperacion_password', 'csrf', 'refresh_sesion', 'api', 'firma_operacion'];
$error = app_take_flash('admin_error');
$mensaje = app_take_flash('admin_message');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!app_verify_csrf('admin_tokens_accion', $_POST['csrf_token'] ?? '')) {
        app_flash('admin_error', 'La sesion expiro. Intenta nuevamente.');
        app_redirect('tokens.php');
    }

    $accion = $_POST['accion'] ?? '';

    try {
        $pdo = app_db();

        if ($accion === 'crear') {
            $usuarioId = ($_POST['usuario_id'] ?? '') === '' ? null : (int) $_POST['usuario_id'];
            $proposito = $_POST['proposito'] ?? '';
            $ttl = (int) ($_POST['ttl_minutos'] ?? 30);

            if (!in_array($proposito, $propositos, true) || $ttl < 1 || $ttl > 10080) {
                app_flash('admin_error', 'Captura un proposito y vigencia validos.');
                app_redirect('tokens.php');
            }

            $tokenPlano = bin2hex(random_bytes(32));
            $ip = inet_pton($_SERVER['REMOTE_ADDR'] ?? '') ?: null;

            $stmt = $pdo->prepare(
                'INSERT INTO tokens_seguridad (
                    usuario_id,
                    proposito,
                    token_hash,
                    expira_en,
                    ip_creacion,
                    user_agent_hash
                 ) VALUES (
                    :usuario_id,
                    :proposito,
                    :token_hash,
                    DATE_ADD(NOW(), INTERVAL ' . $ttl . ' MINUTE),
                    :ip_creacion,
                    :user_agent_hash
                 )'
            );
            $stmt->execute([
                'usuario_id' => $usuarioId,
                'proposito' => $proposito,
                'token_hash' => hash('sha256', $tokenPlano, true),
                'ip_creacion' => $ip,
                'user_agent_hash' => hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '', true),
            ]);

            app_audit('admin.token.crear', 'token_seguridad', (int) $pdo->lastInsertId(), ['proposito' => $proposito]);
            app_flash('admin_message', 'Token generado. Valor para entregar una sola vez: ' . $tokenPlano);
            app_redirect('tokens.php');
        }

        if ($accion === 'revocar') {
            $tokenId = (int) ($_POST['token_id'] ?? 0);
            if ($tokenId <= 0) {
                app_flash('admin_error', 'Token no valido.');
                app_redirect('tokens.php');
            }

            $pdo->prepare('UPDATE tokens_seguridad SET revocado_en = NOW() WHERE id = :id')->execute(['id' => $tokenId]);
            app_audit('admin.token.revocar', 'token_seguridad', $tokenId);
            app_flash('admin_message', 'Token revocado correctamente.');
            app_redirect('tokens.php');
        }
    } catch (Throwable $exception) {
        app_flash('admin_error', 'No fue posible procesar el token.');
        app_redirect('tokens.php');
    }
}

$tokens = [];
$usuarios = [];
$aviso = '';

try {
    $pdo = app_db();
    $usuarios = $pdo->query(
        'SELECT id, CONCAT_WS(" ", nombres, apellido_paterno, apellido_materno) AS nombre, correo
         FROM usuarios
         ORDER BY nombres, apellido_paterno'
    )->fetchAll();

    $tokens = $pdo->query(
        'SELECT t.*, CONCAT_WS(" ", u.nombres, u.apellido_paterno, u.apellido_materno) AS usuario, u.correo
         FROM tokens_seguridad t
         LEFT JOIN usuarios u ON u.id = t.usuario_id
         ORDER BY t.creado_en DESC
         LIMIT 100'
    )->fetchAll();
} catch (Throwable $exception) {
    $aviso = 'No fue posible cargar los tokens.';
}

$tituloPagina = 'Tokens | Administrador';
$paginaActiva = 'tokens';
include __DIR__ . '/../../template/cabeceraAdmin.php';
?>

<div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center mb-4">
    <div>
        <p class="text-secondary mb-1">Seguridad</p>
        <h1 class="h3 mb-0">Tokens</h1>
    </div>
    <a class="btn btn-outline-dark" href="configuracion_seguridad.php">Configuracion</a>
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
                <h2 class="h5">Generar token</h2>
                <input type="hidden" name="csrf_token" value="<?php echo app_e(app_csrf_token('admin_tokens_accion')); ?>">
                <input type="hidden" name="accion" value="crear">

                <div class="mb-3">
                    <label for="usuario_id" class="form-label">Usuario</label>
                    <select class="form-select" id="usuario_id" name="usuario_id">
                        <option value="">Token de sistema</option>
                        <?php foreach ($usuarios as $usuario) : ?>
                            <option value="<?php echo app_e($usuario['id']); ?>">
                                <?php echo app_e($usuario['nombre']); ?> - <?php echo app_e($usuario['correo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="proposito" class="form-label">Proposito</label>
                    <select class="form-select" id="proposito" name="proposito" required>
                        <?php foreach ($propositos as $proposito) : ?>
                            <option value="<?php echo app_e($proposito); ?>"><?php echo app_e($proposito); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="ttl_minutos" class="form-label">Vigencia en minutos</label>
                    <input type="number" class="form-control" id="ttl_minutos" name="ttl_minutos" min="1" max="10080" value="30" required>
                </div>

                <button class="btn btn-dark w-100" type="submit">Generar</button>
            </div>
        </form>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5">Tokens recientes</h2>
                <?php if (!$tokens) : ?>
                    <p class="text-secondary mb-0">No hay tokens registrados.</p>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Proposito</th>
                                    <th>Expira</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tokens as $token) : ?>
                                    <?php
                                    $activo = empty($token['usado_en']) && empty($token['revocado_en']) && strtotime($token['expira_en']) > time();
                                    ?>
                                    <tr>
                                        <td>
                                            <?php echo app_e($token['usuario'] ?? 'Sistema'); ?>
                                            <div class="small text-secondary"><?php echo app_e($token['correo'] ?? ''); ?></div>
                                        </td>
                                        <td><?php echo app_e($token['proposito']); ?></td>
                                        <td><?php echo app_e($token['expira_en']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $activo ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                                                <?php echo $activo ? 'Activo' : 'Cerrado'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="post">
                                                <input type="hidden" name="csrf_token" value="<?php echo app_e(app_csrf_token('admin_tokens_accion')); ?>">
                                                <input type="hidden" name="accion" value="revocar">
                                                <input type="hidden" name="token_id" value="<?php echo app_e($token['id']); ?>">
                                                <button class="btn btn-sm btn-outline-danger" type="submit" <?php echo $activo ? '' : 'disabled'; ?>>Revocar</button>
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
