<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('administrador');

$error = app_take_flash('admin_error');
$mensaje = app_take_flash('admin_message');
$editarId = (int) ($_GET['editar'] ?? 0);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!app_verify_csrf('admin_configuracion_accion', $_POST['csrf_token'] ?? '')) {
        app_flash('admin_error', 'La sesion expiro. Intenta nuevamente.');
        app_redirect('configuracion_seguridad.php');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $clave = trim($_POST['clave'] ?? '');
    $valor = trim($_POST['valor'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if ($clave === '' || $valor === '') {
        app_flash('admin_error', 'Clave y valor son obligatorios.');
        app_redirect('configuracion_seguridad.php' . ($id > 0 ? '?editar=' . $id : ''));
    }

    try {
        $pdo = app_db();

        if ($id > 0) {
            $pdo->prepare(
                'UPDATE configuracion_seguridad
                 SET clave = :clave,
                     valor = :valor,
                     descripcion = :descripcion
                 WHERE id = :id'
            )->execute([
                'clave' => $clave,
                'valor' => $valor,
                'descripcion' => $descripcion ?: null,
                'id' => $id,
            ]);
        } else {
            $pdo->prepare(
                'INSERT INTO configuracion_seguridad (clave, valor, descripcion)
                 VALUES (:clave, :valor, :descripcion)
                 ON DUPLICATE KEY UPDATE
                    valor = VALUES(valor),
                    descripcion = VALUES(descripcion)'
            )->execute([
                'clave' => $clave,
                'valor' => $valor,
                'descripcion' => $descripcion ?: null,
            ]);
        }

        app_audit('admin.configuracion.guardar', 'configuracion_seguridad', $id ?: null, ['clave' => $clave]);
        app_flash('admin_message', 'Configuracion guardada correctamente.');
        app_redirect('configuracion_seguridad.php');
    } catch (Throwable $exception) {
        app_flash('admin_error', 'No fue posible guardar la configuracion.');
        app_redirect('configuracion_seguridad.php' . ($id > 0 ? '?editar=' . $id : ''));
    }
}

$configuraciones = [];
$registro = null;
$aviso = '';

try {
    $pdo = app_db();
    if ($editarId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM configuracion_seguridad WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $editarId]);
        $registro = $stmt->fetch() ?: null;
    }
    $configuraciones = $pdo->query('SELECT * FROM configuracion_seguridad ORDER BY clave')->fetchAll();
} catch (Throwable $exception) {
    $aviso = 'No fue posible cargar la configuracion.';
}

$tituloPagina = 'Configuracion | Administrador';
$paginaActiva = 'configuracion_seguridad';
include __DIR__ . '/../../template/cabeceraAdmin.php';
?>

<div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center mb-4">
    <div>
        <p class="text-secondary mb-1">Seguridad</p>
        <h1 class="h3 mb-0">Configuracion</h1>
    </div>
    <?php if ($editarId > 0) : ?>
        <a class="btn btn-outline-dark" href="configuracion_seguridad.php">Nueva clave</a>
    <?php endif; ?>
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
                <h2 class="h5"><?php echo $registro ? 'Editar clave' : 'Registrar clave'; ?></h2>
                <input type="hidden" name="csrf_token" value="<?php echo app_e(app_csrf_token('admin_configuracion_accion')); ?>">
                <input type="hidden" name="id" value="<?php echo app_e($registro['id'] ?? 0); ?>">

                <div class="mb-3">
                    <label for="clave" class="form-label">Clave</label>
                    <input type="text" class="form-control" id="clave" name="clave" maxlength="120" required value="<?php echo app_e($registro['clave'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label for="valor" class="form-label">Valor</label>
                    <input type="text" class="form-control" id="valor" name="valor" maxlength="255" required value="<?php echo app_e($registro['valor'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripcion</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo app_e($registro['descripcion'] ?? ''); ?></textarea>
                </div>

                <button class="btn btn-dark w-100" type="submit">Guardar</button>
            </div>
        </form>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5">Claves configuradas</h2>
                <?php if (!$configuraciones) : ?>
                    <p class="text-secondary mb-0">No hay configuracion registrada.</p>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Clave</th>
                                    <th>Valor</th>
                                    <th>Descripcion</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($configuraciones as $configuracion) : ?>
                                    <tr>
                                        <td><code><?php echo app_e($configuracion['clave']); ?></code></td>
                                        <td><?php echo app_e($configuracion['valor']); ?></td>
                                        <td><?php echo app_e($configuracion['descripcion']); ?></td>
                                        <td><a class="btn btn-sm btn-outline-dark" href="configuracion_seguridad.php?editar=<?php echo app_e($configuracion['id']); ?>">Editar</a></td>
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
