<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('administrador');

$error = app_take_flash('admin_error');
$mensaje = app_take_flash('admin_message');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!app_verify_csrf('admin_firmas_accion', $_POST['csrf_token'] ?? '')) {
        app_flash('admin_error', 'La sesion expiro. Intenta nuevamente.');
        app_redirect('firmas_digitales.php');
    }

    $firmaId = (int) ($_POST['firma_id'] ?? 0);
    $accion = (string) ($_POST['accion'] ?? 'verificar');

    if ($firmaId <= 0) {
        app_flash('admin_error', 'Firma no valida.');
        app_redirect('firmas_digitales.php');
    }

    try {
        if ($accion === 'invalidar') {
            app_db()->prepare(
                'UPDATE firmas_digitales
                 SET valido = 0,
                     verificado_en = NOW()
                 WHERE id = :id'
            )->execute(['id' => $firmaId]);

            app_audit('admin.firma.invalidar', 'firma_digital', $firmaId);
            app_flash('admin_message', 'La firma fue marcada como no valida.');
            app_redirect('firmas_digitales.php');
        }

        $valido = app_verify_signature($firmaId) ? 1 : 0;

        app_db()->prepare(
            'UPDATE firmas_digitales
             SET valido = :valido,
                 verificado_en = NOW()
             WHERE id = :id'
        )->execute([
            'valido' => $valido ? 1 : 0,
            'id' => $firmaId,
        ]);

        app_audit('admin.firma.verificar', 'firma_digital', $firmaId, ['valido' => $valido ? 1 : 0]);
        app_flash('admin_message', $valido ? 'Firma verificada criptograficamente.' : 'La firma no coincide con su payload o llave publica.');
        app_redirect('firmas_digitales.php');
    } catch (Throwable $exception) {
        app_flash('admin_error', 'No fue posible verificar la firma.');
        app_redirect('firmas_digitales.php');
    }
}

$firmas = [];
$aviso = '';

try {
    $firmas = app_db()->query(
        'SELECT f.*, CONCAT_WS(" ", u.nombres, u.apellido_paterno, u.apellido_materno) AS usuario, u.correo,
                l.fingerprint_sha256
         FROM firmas_digitales f
         INNER JOIN usuarios u ON u.id = f.usuario_id
         INNER JOIN llaves_publicas_usuario l ON l.id = f.llave_publica_id
         ORDER BY f.firmado_en DESC
         LIMIT 200'
    )->fetchAll();
} catch (Throwable $exception) {
    $aviso = 'No fue posible cargar las firmas digitales.';
}

$tituloPagina = 'Firmas digitales | Administrador';
$paginaActiva = 'firmas_digitales';
include __DIR__ . '/../../template/cabeceraAdmin.php';
?>

<div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center mb-4">
    <div>
        <p class="text-secondary mb-1">Seguridad</p>
        <h1 class="h3 mb-0">Firmas digitales</h1>
    </div>
    <a class="btn btn-outline-dark" href="llaves_publicas.php">Llaves publicas</a>
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

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h5">Firmas registradas</h2>
        <?php if (!$firmas) : ?>
            <p class="text-secondary mb-0">No hay firmas digitales registradas.</p>
        <?php else : ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Firmante</th>
                            <th>Entidad</th>
                            <th>Algoritmo</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Verificacion</th>
                            <th>Hash</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($firmas as $firma) : ?>
                            <tr>
                                <td><?php echo app_e($firma['usuario']); ?><div class="small text-secondary"><?php echo app_e($firma['correo']); ?></div></td>
                                <td><?php echo app_e($firma['entidad_tipo']); ?> #<?php echo app_e($firma['entidad_id']); ?></td>
                                <td><?php echo app_e($firma['algoritmo']); ?></td>
                                <td><?php echo app_e($firma['firmado_en']); ?></td>
                                <td>
                                    <span class="badge <?php echo $firma['valido'] ? 'text-bg-success' : 'text-bg-danger'; ?>">
                                        <?php echo $firma['valido'] ? 'Valida' : 'No valida'; ?>
                                    </span>
                                </td>
                                <td><?php echo app_e($firma['verificado_en'] ?? 'Pendiente'); ?></td>
                                <td><code><?php echo app_e(bin2hex($firma['payload_hash'])); ?></code></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <form method="post">
                                            <input type="hidden" name="csrf_token" value="<?php echo app_e(app_csrf_token('admin_firmas_accion')); ?>">
                                            <input type="hidden" name="firma_id" value="<?php echo app_e($firma['id']); ?>">
                                            <input type="hidden" name="accion" value="verificar">
                                            <button class="btn btn-sm btn-outline-success" type="submit">Verificar</button>
                                        </form>
                                        <form method="post">
                                            <input type="hidden" name="csrf_token" value="<?php echo app_e(app_csrf_token('admin_firmas_accion')); ?>">
                                            <input type="hidden" name="firma_id" value="<?php echo app_e($firma['id']); ?>">
                                            <input type="hidden" name="accion" value="invalidar">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Invalidar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../template/pie.php'; ?>
