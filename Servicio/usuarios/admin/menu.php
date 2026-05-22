<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('administrador');

$dashboard = [
    'usuarios_activos' => 0,
    'total_alumnos' => 0,
    'total_profesores' => 0,
    'preregistros_pendientes' => 0,
    'servicios_pendientes' => 0,
    'servicios_activos' => 0,
    'firmas_validas' => 0,
];

try {
    $row = app_db()->query('SELECT * FROM vw_dashboard_administrador LIMIT 1')->fetch();
    if ($row) {
        $dashboard = array_merge($dashboard, $row);
    }
} catch (Throwable $exception) {
    $avisoDashboard = 'No fue posible cargar los indicadores en este momento.';
}

$tituloPagina = 'Dashboard | Administrador';
$paginaActiva = 'dashboard';
include __DIR__ . '/../../template/cabeceraAdmin.php';
?>

<div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center mb-4">
    <div>
        <p class="text-secondary mb-1">Panel administrativo</p>
        <h1 class="h3 mb-0">Control general del servicio social</h1>
    </div>
    <a class="btn btn-outline-dark" href="auditoria.php">Revisar auditoria</a>
</div>

<?php if (!empty($avisoDashboard)) : ?>
    <div class="alert alert-warning"><?php echo app_e($avisoDashboard); ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-secondary mb-1">Alumnos registrados</p>
                <p class="display-6 fw-semibold mb-0"><?php echo app_e($dashboard['total_alumnos']); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-secondary mb-1">Profesores</p>
                <p class="display-6 fw-semibold mb-0"><?php echo app_e($dashboard['total_profesores']); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-secondary mb-1">Preregistros pendientes</p>
                <p class="display-6 fw-semibold mb-0"><?php echo app_e($dashboard['preregistros_pendientes']); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-secondary mb-1">Firmas validas</p>
                <p class="display-6 fw-semibold mb-0"><?php echo app_e($dashboard['firmas_validas']); ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Acciones principales</h2>
                <div class="list-group list-group-flush">
                    <a class="list-group-item list-group-item-action px-0" href="preregistros.php">Revisar preregistros pendientes</a>
                    <a class="list-group-item list-group-item-action px-0" href="servicios_sociales.php">Validar solicitudes de servicio social</a>
                    <a class="list-group-item list-group-item-action px-0" href="usuarios.php">Gestionar usuarios del sistema</a>
                    <a class="list-group-item list-group-item-action px-0" href="firmas_digitales.php">Consultar firmas digitales</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Estado operativo</h2>
                <p class="text-secondary mb-2">Servicios pendientes: <?php echo app_e($dashboard['servicios_pendientes']); ?></p>
                <p class="text-secondary mb-2">Servicios activos: <?php echo app_e($dashboard['servicios_activos']); ?></p>
                <p class="text-secondary mb-0">Usuarios activos: <?php echo app_e($dashboard['usuarios_activos']); ?></p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../template/pie.php'; ?>
