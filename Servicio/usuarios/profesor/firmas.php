<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('profesor');

$firmas = [];
$validacionesPreregistro = [];
$validacionesServicio = [];
$llaves = [];
$aviso = '';

try {
    $stmt = app_db()->prepare(
        'SELECT f.*
         FROM firmas_digitales f
         WHERE f.usuario_id = :usuario_id
         ORDER BY f.firmado_en DESC'
    );
    $stmt->execute(['usuario_id' => $_SESSION['usuario_id']]);
    $firmas = $stmt->fetchAll();

    $stmt = app_db()->prepare(
        'SELECT v.*, p.id AS preregistro_id, a.matricula,
                CONCAT_WS(" ", u.nombres, u.apellido_paterno, u.apellido_materno) AS alumno
         FROM validaciones_preregistro v
         INNER JOIN preregistros p ON p.id = v.preregistro_id
         INNER JOIN alumnos a ON a.id = p.alumno_id
         INNER JOIN usuarios u ON u.id = a.usuario_id
         WHERE v.usuario_validador_id = :usuario_id
         ORDER BY v.creado_en DESC
         LIMIT 20'
    );
    $stmt->execute(['usuario_id' => $_SESSION['usuario_id']]);
    $validacionesPreregistro = $stmt->fetchAll();

    $stmt = app_db()->prepare(
        'SELECT v.*, ss.folio,
                CONCAT_WS(" ", u.nombres, u.apellido_paterno, u.apellido_materno) AS alumno
         FROM validaciones_servicio_social v
         INNER JOIN servicios_sociales ss ON ss.id = v.servicio_social_id
         INNER JOIN alumnos a ON a.id = ss.alumno_id
         INNER JOIN usuarios u ON u.id = a.usuario_id
         WHERE v.usuario_validador_id = :usuario_id
         ORDER BY v.creado_en DESC
         LIMIT 20'
    );
    $stmt->execute(['usuario_id' => $_SESSION['usuario_id']]);
    $validacionesServicio = $stmt->fetchAll();

    $stmt = app_db()->prepare(
        'SELECT * FROM llaves_publicas_usuario
         WHERE usuario_id = :usuario_id
         ORDER BY creada_en DESC'
    );
    $stmt->execute(['usuario_id' => $_SESSION['usuario_id']]);
    $llaves = $stmt->fetchAll();
} catch (Throwable $exception) {
    $aviso = 'No fue posible cargar la informacion de firmas.';
}

$tituloPagina = 'Firmas | Profesor';
$paginaActiva = 'firmas';
include __DIR__ . '/../../template/cabeceraProf.php';
?>

<div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center mb-4">
    <div>
        <p class="text-secondary mb-1">Evidencia criptografica</p>
        <h1 class="h3 mb-0">Firmas y validaciones</h1>
    </div>
    <a class="btn btn-outline-primary" href="seguridad.php">Mis llaves</a>
</div>

<?php if ($aviso !== '') : ?>
    <div class="alert alert-warning"><?php echo app_e($aviso); ?></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Llaves publicas</h2>
                <?php if (!$llaves) : ?>
                    <p class="text-secondary mb-0">No hay llaves publicas registradas para tu cuenta.</p>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Algoritmo</th>
                                    <th>Estado</th>
                                    <th>Fingerprint</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($llaves as $llave) : ?>
                                    <tr>
                                        <td><?php echo app_e($llave['algoritmo']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $llave['activa'] ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                                                <?php echo $llave['activa'] ? 'Activa' : 'Revocada'; ?>
                                            </span>
                                        </td>
                                        <td><code><?php echo app_e(bin2hex($llave['fingerprint_sha256'])); ?></code></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Firmas digitales</h2>
                <?php if (!$firmas) : ?>
                    <p class="text-secondary mb-0">Aun no hay firmas digitales emitidas por tu cuenta.</p>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Entidad</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Hash</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($firmas as $firma) : ?>
                                    <tr>
                                        <td><?php echo app_e($firma['entidad_tipo']); ?> #<?php echo app_e($firma['entidad_id']); ?></td>
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
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Validaciones de preregistro</h2>
                <?php if (!$validacionesPreregistro) : ?>
                    <p class="text-secondary mb-0">No hay validaciones de preregistro.</p>
                <?php else : ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($validacionesPreregistro as $validacion) : ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between gap-3">
                                    <p class="fw-semibold mb-1"><?php echo app_e($validacion['alumno']); ?></p>
                                    <span class="badge <?php echo app_e(app_badge_class($validacion['resultado'])); ?>">
                                        <?php echo app_e(app_status_text($validacion['resultado'])); ?>
                                    </span>
                                </div>
                                <p class="small text-secondary mb-0"><?php echo app_e($validacion['matricula']); ?> - <?php echo app_e($validacion['creado_en']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Validaciones de servicio</h2>
                <?php if (!$validacionesServicio) : ?>
                    <p class="text-secondary mb-0">No hay validaciones de servicio social.</p>
                <?php else : ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($validacionesServicio as $validacion) : ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between gap-3">
                                    <p class="fw-semibold mb-1"><?php echo app_e($validacion['folio']); ?> - <?php echo app_e($validacion['alumno']); ?></p>
                                    <span class="badge <?php echo app_e(app_badge_class($validacion['resultado'])); ?>">
                                        <?php echo app_e(app_status_text($validacion['resultado'])); ?>
                                    </span>
                                </div>
                                <p class="small text-secondary mb-0"><?php echo app_e($validacion['etapa']); ?> - <?php echo app_e($validacion['creado_en']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../template/pie.php'; ?>
