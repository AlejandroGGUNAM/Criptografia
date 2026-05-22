<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('administrador');

$eventos = [];
$aviso = '';

try {
    $eventos = app_db()->query(
        'SELECT b.*, CONCAT_WS(" ", u.nombres, u.apellido_paterno, u.apellido_materno) AS usuario, u.correo
         FROM bitacora_auditoria b
         LEFT JOIN usuarios u ON u.id = b.usuario_id
         ORDER BY b.creado_en DESC
         LIMIT 200'
    )->fetchAll();
} catch (Throwable $exception) {
    $aviso = 'No fue posible cargar la auditoria.';
}

$tituloPagina = 'Auditoria | Administrador';
$paginaActiva = 'auditoria';
include __DIR__ . '/../../template/cabeceraAdmin.php';
?>

<div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center mb-4">
    <div>
        <p class="text-secondary mb-1">Bitacora criptografica</p>
        <h1 class="h3 mb-0">Auditoria</h1>
    </div>
    <a class="btn btn-outline-dark" href="firmas_digitales.php">Firmas digitales</a>
</div>

<?php if ($aviso !== '') : ?>
    <div class="alert alert-warning"><?php echo app_e($aviso); ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h5">Eventos recientes</h2>
        <?php if (!$eventos) : ?>
            <p class="text-secondary mb-0">No hay eventos de auditoria registrados.</p>
        <?php else : ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Accion</th>
                            <th>Entidad</th>
                            <th>Hash</th>
                            <th>HMAC</th>
                            <th>Hash anterior</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eventos as $evento) : ?>
                            <tr>
                                <td><?php echo app_e($evento['creado_en']); ?></td>
                                <td>
                                    <?php echo app_e($evento['usuario'] ?? 'Sistema'); ?>
                                    <div class="small text-secondary"><?php echo app_e($evento['correo'] ?? ''); ?></div>
                                </td>
                                <td><code><?php echo app_e($evento['accion']); ?></code></td>
                                <td><?php echo app_e($evento['entidad_tipo'] ?? 'N/D'); ?> #<?php echo app_e($evento['entidad_id'] ?? ''); ?></td>
                                <td><code><?php echo app_e(bin2hex($evento['hash_evento'])); ?></code></td>
                                <td><code><?php echo app_e($evento['hmac_evento'] === null ? 'pendiente' : bin2hex($evento['hmac_evento'])); ?></code></td>
                                <td><code><?php echo app_e($evento['hash_anterior'] === null ? 'genesis' : bin2hex($evento['hash_anterior'])); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../template/pie.php'; ?>
