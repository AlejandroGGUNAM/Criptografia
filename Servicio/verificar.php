<?php
require_once __DIR__ . '/config/bootstrap.php';

app_start_session();
app_security_headers();

$consulta = trim($_GET['q'] ?? '');
$servicio = null;
$firmas = [];
$error = '';

if ($consulta !== '') {
    try {
        $stmt = app_db()->prepare(
            'SELECT
                ss.*,
                d.nombre AS dependencia,
                ps.nombre AS programa,
                CONCAT_WS(" ", u.nombres, u.apellido_paterno, u.apellido_materno) AS alumno,
                a.matricula
             FROM servicios_sociales ss
             INNER JOIN alumnos a ON a.id = ss.alumno_id
             INNER JOIN usuarios u ON u.id = a.usuario_id
             INNER JOIN dependencias d ON d.id = ss.dependencia_id
             LEFT JOIN programas_servicio_social ps ON ps.id = ss.programa_id
             WHERE ss.folio = :folio
             LIMIT 1'
        );
        $stmt->execute(['folio' => $consulta]);
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
        } else {
            $error = 'No se encontro un servicio social con ese folio.';
        }
    } catch (Throwable $exception) {
        $error = 'No fue posible realizar la verificacion.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar firma | Servicio Social Universitario</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
    <main class="access-page d-flex align-items-center py-4">
        <div class="container" style="max-width: 860px;">
            <div class="login-panel rounded-3 p-4 p-xl-5">
                <div class="d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <span class="brand-mark">SS</span>
                        <div>
                            <p class="small text-secondary text-uppercase fw-semibold mb-0">Verificacion publica</p>
                            <h1 class="h4 mb-0">Constancia o firma digital</h1>
                        </div>
                    </div>
                    <a class="btn btn-outline-secondary" href="index.php">Inicio</a>
                </div>

                <form method="get" class="row g-3 mb-4">
                    <div class="col-md-8">
                        <label for="q" class="form-label">Folio de servicio social</label>
                        <input type="text" class="form-control form-control-lg" id="q" name="q" placeholder="SS-2026-0001" value="<?php echo app_e($consulta); ?>" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button class="btn btn-accent btn-lg w-100" type="submit">Verificar</button>
                    </div>
                </form>

                <?php if ($error !== '') : ?>
                    <div class="alert alert-warning"><?php echo app_e($error); ?></div>
                <?php endif; ?>

                <?php if ($servicio) : ?>
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body">
                            <div class="d-flex flex-column flex-md-row gap-3 justify-content-between">
                                <div>
                                    <p class="text-secondary mb-1">Folio</p>
                                    <h2 class="h4 mb-2"><?php echo app_e($servicio['folio']); ?></h2>
                                    <p class="mb-1"><strong>Alumno:</strong> <?php echo app_e($servicio['alumno']); ?> (<?php echo app_e($servicio['matricula']); ?>)</p>
                                    <p class="mb-1"><strong>Dependencia:</strong> <?php echo app_e($servicio['dependencia']); ?></p>
                                    <p class="mb-0"><strong>Programa:</strong> <?php echo app_e($servicio['programa'] ?? 'Sin programa'); ?></p>
                                </div>
                                <span class="badge align-self-start <?php echo app_e(app_badge_class($servicio['estatus'])); ?>">
                                    <?php echo app_e(app_status_text($servicio['estatus'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h2 class="h5">Firmas digitales registradas</h2>
                            <?php if (!$firmas) : ?>
                                <p class="text-secondary mb-0">Este folio existe, pero aun no tiene firmas digitales asociadas.</p>
                            <?php else : ?>
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Firmante</th>
                                                <th>Algoritmo</th>
                                                <th>Fecha</th>
                                                <th>Estado</th>
                                                <th>Hash</th>
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
            </div>
        </div>
    </main>
</body>
</html>
