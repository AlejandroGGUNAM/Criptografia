<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('alumno');

$alumno = app_current_alumno();
$servicio = null;
$firmas = [];
$aviso = '';

try {
    if ($alumno) {
        $stmt = app_db()->prepare(
            'SELECT ss.*, d.nombre AS dependencia, ps.nombre AS programa
             FROM servicios_sociales ss
             INNER JOIN dependencias d ON d.id = ss.dependencia_id
             LEFT JOIN programas_servicio_social ps ON ps.id = ss.programa_id
             WHERE ss.alumno_id = :alumno_id
             ORDER BY ss.creado_en DESC
             LIMIT 1'
        );
        $stmt->execute(['alumno_id' => $alumno['alumno_id']]);
        $servicio = $stmt->fetch();

        if ($servicio) {
            $stmt = app_db()->prepare(
                'SELECT f.*, CONCAT_WS(" ", u.nombres, u.apellido_paterno, u.apellido_materno) AS firmante
                 FROM firmas_digitales f
                 INNER JOIN usuarios u ON u.id = f.usuario_id
                 WHERE f.entidad_tipo IN ("servicio_social", "validacion_servicio")
                   AND f.entidad_id = :servicio_social_id
                 ORDER BY f.firmado_en DESC'
            );
            $stmt->execute(['servicio_social_id' => $servicio['id']]);
            $firmas = $stmt->fetchAll();
        }
    }
} catch (Throwable $exception) {
    $aviso = 'No fue posible cargar la informacion de constancias.';
}

$tituloPagina = 'Constancias | Alumno';
$paginaActiva = 'constancias';
include __DIR__ . '/../../template/cabeceraAlu.php';
?>

<div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center mb-4">
    <div>
        <p class="text-secondary mb-1">Verificacion y cierre</p>
        <h1 class="h3 mb-0">Constancias</h1>
    </div>
    <a class="btn btn-outline-success" href="../../verificar.php">Verificador publico</a>
</div>

<?php if ($aviso !== '') : ?>
    <div class="alert alert-warning"><?php echo app_e($aviso); ?></div>
<?php endif; ?>

<?php if (!$servicio) : ?>
    <div class="alert alert-info">
        Aun no tienes un servicio social registrado. Cuando exista un servicio aprobado o concluido, aqui se mostraran tus constancias.
    </div>
<?php else : ?>
    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row gap-3 justify-content-between">
                        <div>
                            <p class="text-secondary mb-1">Folio</p>
                            <h2 class="h4 mb-2"><?php echo app_e($servicio['folio']); ?></h2>
                            <p class="text-secondary mb-1"><?php echo app_e($servicio['dependencia']); ?></p>
                            <p class="text-secondary mb-0"><?php echo app_e($servicio['programa'] ?? 'Sin programa asignado'); ?></p>
                        </div>
                        <span class="badge align-self-start <?php echo app_e(app_badge_class($servicio['estatus'])); ?>">
                            <?php echo app_e(app_status_text($servicio['estatus'])); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Disponibilidad</h2>
                    <?php if ($servicio['estatus'] === 'concluido') : ?>
                        <p class="text-secondary">Tu servicio esta concluido. La descarga de constancia se habilitara cuando el administrador genere y firme el comprobante.</p>
                        <button class="btn btn-success w-100" type="button" disabled>Descargar constancia</button>
                    <?php else : ?>
                        <p class="text-secondary mb-0">La constancia se habilitara cuando el servicio social cambie a estado concluido.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h5">Firmas digitales asociadas</h2>
            <?php if (!$firmas) : ?>
                <p class="text-secondary mb-0">Aun no hay firmas digitales asociadas a este servicio.</p>
            <?php else : ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Firmante</th>
                                <th>Algoritmo</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Hash del payload</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($firmas as $firma) : ?>
                                <tr>
                                    <td><?php echo app_e($firma['firmante']); ?></td>
                                    <td><?php echo app_e($firma['algoritmo']); ?></td>
                                    <td><?php echo app_e($firma['firmado_en']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $firma['valido'] ? 'text-bg-success' : 'text-bg-danger'; ?>">
                                            <?php echo $firma['valido'] ? 'Valida' : 'No valida'; ?>
                                        </span>
                                    </td>
                                    <td><code><?php echo app_e(bin2hex($firma['payload_hash'])); ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../../template/pie.php'; ?>
