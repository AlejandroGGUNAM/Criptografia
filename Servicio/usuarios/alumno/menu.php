<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('alumno');

$dashboard = [
    'estatus_preregistro' => 'sin preregistro',
    'folio' => null,
    'estatus_servicio' => null,
    'horas_requeridas' => 480,
    'horas_acumuladas' => 0,
];

try {
    $stmt = app_db()->prepare('SELECT * FROM vw_dashboard_alumno WHERE usuario_id = :usuario_id LIMIT 1');
    $stmt->execute(['usuario_id' => $_SESSION['usuario_id']]);
    $row = $stmt->fetch();
    if ($row) {
        $dashboard = array_merge($dashboard, $row);
    }
} catch (Throwable $exception) {
    $avisoDashboard = 'No fue posible cargar tu seguimiento en este momento.';
}

$porcentajeHoras = 0;
if ((int) $dashboard['horas_requeridas'] > 0) {
    $porcentajeHoras = min(100, round(((int) $dashboard['horas_acumuladas'] / (int) $dashboard['horas_requeridas']) * 100));
}

$tituloPagina = 'Inicio | Alumno';
$paginaActiva = 'dashboard';
include __DIR__ . '/../../template/cabeceraAlu.php';
?>

<div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center mb-4">
    <div>
        <p class="text-secondary mb-1">Panel del alumno</p>
        <h1 class="h3 mb-0">Seguimiento de servicio social</h1>
    </div>
    <a class="btn btn-success" href="preregistro.php">Ir a mi preregistro</a>
</div>

<?php if (!empty($avisoDashboard)) : ?>
    <div class="alert alert-warning"><?php echo app_e($avisoDashboard); ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-secondary mb-1">Estado del preregistro</p>
                <h2 class="h4 mb-0"><?php echo app_e($dashboard['estatus_preregistro']); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-secondary mb-1">Servicio social</p>
                <h2 class="h4 mb-0"><?php echo app_e($dashboard['estatus_servicio'] ?? 'sin registro'); ?></h2>
                <?php if (!empty($dashboard['folio'])) : ?>
                    <p class="text-secondary mb-0">Folio: <?php echo app_e($dashboard['folio']); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between gap-3 mb-2">
            <h2 class="h5 mb-0">Horas registradas</h2>
            <span class="text-secondary"><?php echo app_e($dashboard['horas_acumuladas']); ?> / <?php echo app_e($dashboard['horas_requeridas']); ?></span>
        </div>
        <div class="progress" role="progressbar" aria-valuenow="<?php echo app_e($porcentajeHoras); ?>" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar bg-success" style="width: <?php echo app_e($porcentajeHoras); ?>%"><?php echo app_e($porcentajeHoras); ?>%</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <a class="card border-0 shadow-sm h-100 text-decoration-none text-dark" href="servicio_social.php">
            <div class="card-body">
                <h2 class="h5">Registrar servicio</h2>
                <p class="text-secondary mb-0">Captura dependencia, programa, fechas y responsable externo.</p>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a class="card border-0 shadow-sm h-100 text-decoration-none text-dark" href="actividades.php">
            <div class="card-body">
                <h2 class="h5">Actividades</h2>
                <p class="text-secondary mb-0">Registra actividades y horas acumuladas.</p>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a class="card border-0 shadow-sm h-100 text-decoration-none text-dark" href="seguimiento.php">
            <div class="card-body">
                <h2 class="h5">Seguimiento</h2>
                <p class="text-secondary mb-0">Consulta observaciones, validaciones y estado actual.</p>
            </div>
        </a>
    </div>
</div>

<?php include __DIR__ . '/../../template/pie.php'; ?>
