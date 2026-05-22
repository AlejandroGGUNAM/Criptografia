<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('profesor');

$profesor = app_current_profesor();
$dashboard = [
    'alumnos_asignados' => 0,
    'actividades_pendientes' => 0,
    'actividades_observadas' => 0,
    'actividades_validadas' => 0,
    'concluidos' => 0,
];

try {
    if ($profesor) {
        $stmt = app_db()->prepare(
            'SELECT
                COUNT(DISTINCT ss.id) AS alumnos_asignados,
                COALESCE(SUM(CASE WHEN act.estatus = "registrada" THEN 1 ELSE 0 END), 0) AS actividades_pendientes,
                COALESCE(SUM(CASE WHEN act.estatus = "observada" THEN 1 ELSE 0 END), 0) AS actividades_observadas,
                COALESCE(SUM(CASE WHEN act.estatus = "validada" THEN 1 ELSE 0 END), 0) AS actividades_validadas,
                COUNT(DISTINCT CASE WHEN ss.estatus = "concluido" THEN ss.id END) AS concluidos
             FROM servicios_sociales ss
             LEFT JOIN actividades_servicio_social act ON act.servicio_social_id = ss.id
             WHERE ss.profesor_id = :profesor_id'
        );
        $stmt->execute(['profesor_id' => $profesor['profesor_id']]);
        $row = $stmt->fetch();
        if ($row) {
            $dashboard = array_merge($dashboard, $row);
        }
    }
} catch (Throwable $exception) {
    $avisoDashboard = 'No fue posible cargar tus indicadores en este momento.';
}

$tituloPagina = 'Dashboard | Profesor';
$paginaActiva = 'dashboard';
include __DIR__ . '/../../template/cabeceraProf.php';
?>

<div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center mb-4">
    <div>
        <p class="text-secondary mb-1">Panel del profesor</p>
        <h1 class="h3 mb-0">Seguimiento academico</h1>
    </div>
    <a class="btn btn-primary" href="actividades.php">Validar actividades</a>
</div>

<?php if (!empty($avisoDashboard)) : ?>
    <div class="alert alert-warning"><?php echo app_e($avisoDashboard); ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-secondary mb-1">Alumnos asignados</p>
                <p class="display-6 fw-semibold mb-0"><?php echo app_e($dashboard['alumnos_asignados']); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-secondary mb-1">Actividades pendientes</p>
                <p class="display-6 fw-semibold mb-0"><?php echo app_e($dashboard['actividades_pendientes']); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-secondary mb-1">Actividades observadas</p>
                <p class="display-6 fw-semibold mb-0"><?php echo app_e($dashboard['actividades_observadas']); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-secondary mb-1">Concluidos</p>
                <p class="display-6 fw-semibold mb-0"><?php echo app_e($dashboard['concluidos']); ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Trabajo pendiente</h2>
                <div class="list-group list-group-flush">
                    <a class="list-group-item list-group-item-action px-0" href="alumnos_asignados.php">Consultar alumnos asignados</a>
                    <a class="list-group-item list-group-item-action px-0" href="actividades.php">Validar actividades y horas</a>
                    <a class="list-group-item list-group-item-action px-0" href="firmas.php">Consultar mis firmas digitales</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Validacion segura</h2>
                <p class="text-secondary mb-0">El profesor conserva seguimiento de alumnos y actividades; preregistros y solicitudes quedan bajo control administrativo.</p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../template/pie.php'; ?>
