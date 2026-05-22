<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('administrador');

$usuarios = [];
$roles = [];
$carreras = [];
$departamentos = [];
$registro = null;
$error = app_take_flash('admin_error');
$mensaje = app_take_flash('admin_message');
$aviso = '';
$editarId = (int) ($_GET['editar'] ?? 0);

try {
    $pdo = app_db();
    $roles = $pdo->query('SELECT id, codigo, nombre FROM roles WHERE activo = 1 ORDER BY id')->fetchAll();
    $carreras = $pdo->query('SELECT id, nombre FROM carreras WHERE activo = 1 ORDER BY nombre')->fetchAll();
    $departamentos = $pdo->query('SELECT id, nombre FROM departamentos WHERE activo = 1 ORDER BY nombre')->fetchAll();

    if ($editarId > 0) {
        $stmt = $pdo->prepare(
            'SELECT
                u.*,
                r.codigo AS rol_codigo,
                al.matricula,
                al.carrera_id,
                al.semestre_actual,
                al.creditos_aprobados,
                al.promedio,
                pr.departamento_id,
                pr.numero_empleado,
                pr.grado_academico,
                ad.area
             FROM usuarios u
             INNER JOIN roles r ON r.id = u.rol_id
             LEFT JOIN alumnos al ON al.usuario_id = u.id
             LEFT JOIN profesores pr ON pr.usuario_id = u.id
             LEFT JOIN administradores ad ON ad.usuario_id = u.id
             WHERE u.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $editarId]);
        $registro = $stmt->fetch() ?: null;
    }

    $usuarios = $pdo->query(
        'SELECT
            u.*,
            r.nombre AS rol,
            r.codigo AS rol_codigo,
            al.matricula,
            ca.nombre AS carrera,
            pr.numero_empleado,
            dp.nombre AS departamento,
            ad.area
         FROM usuarios u
         INNER JOIN roles r ON r.id = u.rol_id
         LEFT JOIN alumnos al ON al.usuario_id = u.id
         LEFT JOIN carreras ca ON ca.id = al.carrera_id
         LEFT JOIN profesores pr ON pr.usuario_id = u.id
         LEFT JOIN departamentos dp ON dp.id = pr.departamento_id
         LEFT JOIN administradores ad ON ad.usuario_id = u.id
         ORDER BY u.creado_en DESC'
    )->fetchAll();
} catch (Throwable $exception) {
    $aviso = 'No fue posible cargar los usuarios.';
}

$tituloPagina = 'Usuarios | Administrador';
$paginaActiva = 'usuarios';
include __DIR__ . '/../../template/cabeceraAdmin.php';
?>

<div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center mb-4">
    <div>
        <p class="text-secondary mb-1">Acceso y perfiles</p>
        <h1 class="h3 mb-0">Usuarios</h1>
    </div>
    <?php if ($editarId > 0) : ?>
        <a class="btn btn-outline-dark" href="usuarios.php">Nuevo usuario</a>
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
    <div class="col-xl-4">
        <form action="procesar-usuario.php" method="post" class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5"><?php echo $registro ? 'Editar usuario' : 'Registrar usuario'; ?></h2>
                <input type="hidden" name="csrf_token" value="<?php echo app_e(app_csrf_token('admin_usuario_accion')); ?>">
                <input type="hidden" name="accion" value="guardar">
                <input type="hidden" name="usuario_id" value="<?php echo app_e($registro['id'] ?? 0); ?>">

                <div class="row g-3">
                    <div class="col-12">
                        <label for="rol_id" class="form-label">Rol</label>
                        <select class="form-select" id="rol_id" name="rol_id" required>
                            <option value="">Selecciona un rol</option>
                            <?php foreach ($roles as $rol) : ?>
                                <option value="<?php echo app_e($rol['id']); ?>" <?php echo ((string) ($registro['rol_id'] ?? '') === (string) $rol['id']) ? 'selected' : ''; ?>>
                                    <?php echo app_e($rol['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="correo" class="form-label">Correo</label>
                        <input type="email" class="form-control" id="correo" name="correo" maxlength="190" required value="<?php echo app_e($registro['correo'] ?? ''); ?>">
                    </div>

                    <div class="col-12">
                        <label for="password" class="form-label"><?php echo $registro ? 'Nueva contrasena opcional' : 'Contrasena inicial'; ?></label>
                        <input type="password" class="form-control" id="password" name="password" minlength="10" <?php echo $registro ? '' : 'required'; ?>>
                    </div>

                    <div class="col-md-6">
                        <label for="nombres" class="form-label">Nombre(s)</label>
                        <input type="text" class="form-control" id="nombres" name="nombres" maxlength="120" required value="<?php echo app_e($registro['nombres'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="apellido_paterno" class="form-label">Apellido paterno</label>
                        <input type="text" class="form-control" id="apellido_paterno" name="apellido_paterno" maxlength="120" required value="<?php echo app_e($registro['apellido_paterno'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="apellido_materno" class="form-label">Apellido materno</label>
                        <input type="text" class="form-control" id="apellido_materno" name="apellido_materno" maxlength="120" value="<?php echo app_e($registro['apellido_materno'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="telefono" class="form-label">Telefono</label>
                        <input type="text" class="form-control" id="telefono" name="telefono" maxlength="25" value="<?php echo app_e($registro['telefono'] ?? ''); ?>">
                    </div>

                    <div class="col-12">
                        <label for="estatus" class="form-label">Estatus</label>
                        <select class="form-select" id="estatus" name="estatus" required>
                            <?php foreach (['pendiente_verificacion', 'activo', 'bloqueado', 'inactivo'] as $estatus) : ?>
                                <option value="<?php echo app_e($estatus); ?>" <?php echo (($registro['estatus'] ?? 'activo') === $estatus) ? 'selected' : ''; ?>>
                                    <?php echo app_e(app_status_text($estatus)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <hr>

                <h3 class="h6">Datos de alumno</h3>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="matricula" class="form-label">Matricula</label>
                        <input type="text" class="form-control" id="matricula" name="matricula" maxlength="40" value="<?php echo app_e($registro['matricula'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="carrera_id" class="form-label">Carrera</label>
                        <select class="form-select" id="carrera_id" name="carrera_id">
                            <option value="">Sin asignar</option>
                            <?php foreach ($carreras as $carrera) : ?>
                                <option value="<?php echo app_e($carrera['id']); ?>" <?php echo ((string) ($registro['carrera_id'] ?? '') === (string) $carrera['id']) ? 'selected' : ''; ?>>
                                    <?php echo app_e($carrera['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="semestre_actual" class="form-label">Semestre</label>
                        <input type="number" class="form-control" id="semestre_actual" name="semestre_actual" min="1" max="20" value="<?php echo app_e($registro['semestre_actual'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="creditos_aprobados" class="form-label">Creditos</label>
                        <input type="number" class="form-control" id="creditos_aprobados" name="creditos_aprobados" min="0" max="999" value="<?php echo app_e($registro['creditos_aprobados'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="promedio" class="form-label">Promedio</label>
                        <input type="number" step="0.01" class="form-control" id="promedio" name="promedio" min="0" max="100" value="<?php echo app_e($registro['promedio'] ?? ''); ?>">
                    </div>
                </div>

                <h3 class="h6">Datos de profesor</h3>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="numero_empleado" class="form-label">Numero empleado</label>
                        <input type="text" class="form-control" id="numero_empleado" name="numero_empleado" maxlength="40" value="<?php echo app_e($registro['numero_empleado'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="departamento_id" class="form-label">Departamento</label>
                        <select class="form-select" id="departamento_id" name="departamento_id">
                            <option value="">Sin asignar</option>
                            <?php foreach ($departamentos as $departamento) : ?>
                                <option value="<?php echo app_e($departamento['id']); ?>" <?php echo ((string) ($registro['departamento_id'] ?? '') === (string) $departamento['id']) ? 'selected' : ''; ?>>
                                    <?php echo app_e($departamento['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="grado_academico" class="form-label">Grado academico</label>
                        <input type="text" class="form-control" id="grado_academico" name="grado_academico" maxlength="80" value="<?php echo app_e($registro['grado_academico'] ?? ''); ?>">
                    </div>
                </div>

                <h3 class="h6">Datos de administrador</h3>
                <div>
                    <label for="area" class="form-label">Area</label>
                    <input type="text" class="form-control" id="area" name="area" maxlength="120" value="<?php echo app_e($registro['area'] ?? ''); ?>">
                </div>
            </div>

            <div class="card-footer bg-white d-flex justify-content-end">
                <button class="btn btn-dark" type="submit">Guardar usuario</button>
            </div>
        </form>
    </div>

    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5">Usuarios registrados</h2>
                <?php if (!$usuarios) : ?>
                    <p class="text-secondary mb-0">No hay usuarios registrados.</p>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Rol</th>
                                    <th>Referencia</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $usuario) : ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo app_e(trim($usuario['nombres'] . ' ' . $usuario['apellido_paterno'] . ' ' . ($usuario['apellido_materno'] ?? ''))); ?></div>
                                            <div class="small text-secondary"><?php echo app_e($usuario['correo']); ?></div>
                                        </td>
                                        <td><?php echo app_e($usuario['rol']); ?></td>
                                        <td class="small text-secondary">
                                            <?php if ($usuario['rol_codigo'] === 'alumno') : ?>
                                                <?php echo app_e($usuario['matricula'] ?? 'Sin matricula'); ?> - <?php echo app_e($usuario['carrera'] ?? 'Sin carrera'); ?>
                                            <?php elseif ($usuario['rol_codigo'] === 'profesor') : ?>
                                                <?php echo app_e($usuario['numero_empleado'] ?? 'Sin numero'); ?> - <?php echo app_e($usuario['departamento'] ?? 'Sin departamento'); ?>
                                            <?php else : ?>
                                                <?php echo app_e($usuario['area'] ?? 'Sin area'); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo app_e(app_badge_class($usuario['estatus'])); ?>">
                                                <?php echo app_e(app_status_text($usuario['estatus'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-2">
                                                <a class="btn btn-sm btn-outline-dark" href="usuarios.php?editar=<?php echo app_e($usuario['id']); ?>">Editar</a>
                                                <?php foreach (['activo' => 'Activar', 'bloqueado' => 'Bloquear', 'inactivo' => 'Inactivar'] as $estado => $texto) : ?>
                                                    <?php if ($usuario['estatus'] !== $estado) : ?>
                                                        <form action="procesar-usuario.php" method="post">
                                                            <input type="hidden" name="csrf_token" value="<?php echo app_e(app_csrf_token('admin_usuario_accion')); ?>">
                                                            <input type="hidden" name="accion" value="estatus">
                                                            <input type="hidden" name="usuario_id" value="<?php echo app_e($usuario['id']); ?>">
                                                            <input type="hidden" name="estatus" value="<?php echo app_e($estado); ?>">
                                                            <button class="btn btn-sm btn-outline-secondary" type="submit"><?php echo app_e($texto); ?></button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
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
    </div>
</div>

<?php include __DIR__ . '/../../template/pie.php'; ?>
