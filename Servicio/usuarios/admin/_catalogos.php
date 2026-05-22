<?php
require_once __DIR__ . '/../../config/bootstrap.php';

function admin_catalogos_definiciones()
{
    return [
        'departamentos' => [
            'titulo' => 'Departamentos',
            'singular' => 'departamento',
            'tabla' => 'departamentos',
            'activo' => 'departamentos',
            'archivo' => 'departamentos.php',
            'orden' => 'nombre',
            'lista_sql' => 'SELECT * FROM departamentos ORDER BY activo DESC, nombre',
            'campos' => [
                ['nombre' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'text', 'requerido' => true, 'max' => 160],
                ['nombre' => 'codigo', 'etiqueta' => 'Codigo', 'tipo' => 'text', 'max' => 40, 'nullable' => true],
                ['nombre' => 'activo', 'etiqueta' => 'Activo', 'tipo' => 'checkbox', 'default' => 1],
            ],
            'columnas' => [
                ['campo' => 'nombre', 'etiqueta' => 'Nombre'],
                ['campo' => 'codigo', 'etiqueta' => 'Codigo'],
                ['campo' => 'activo', 'etiqueta' => 'Estado', 'booleano' => true],
            ],
        ],
        'carreras' => [
            'titulo' => 'Carreras',
            'singular' => 'carrera',
            'tabla' => 'carreras',
            'activo' => 'carreras',
            'archivo' => 'carreras.php',
            'orden' => 'nombre',
            'lista_sql' => 'SELECT c.*, d.nombre AS departamento FROM carreras c LEFT JOIN departamentos d ON d.id = c.departamento_id ORDER BY c.activo DESC, c.nombre',
            'campos' => [
                ['nombre' => 'departamento_id', 'etiqueta' => 'Departamento', 'tipo' => 'select', 'nullable' => true, 'opciones_sql' => 'SELECT id, nombre FROM departamentos WHERE activo = 1 ORDER BY nombre'],
                ['nombre' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'text', 'requerido' => true, 'max' => 180],
                ['nombre' => 'codigo', 'etiqueta' => 'Codigo', 'tipo' => 'text', 'max' => 40, 'nullable' => true],
                ['nombre' => 'creditos_totales', 'etiqueta' => 'Creditos totales', 'tipo' => 'number', 'nullable' => true, 'min' => 1, 'max' => 999],
                ['nombre' => 'activo', 'etiqueta' => 'Activo', 'tipo' => 'checkbox', 'default' => 1],
            ],
            'columnas' => [
                ['campo' => 'nombre', 'etiqueta' => 'Nombre'],
                ['campo' => 'departamento', 'etiqueta' => 'Departamento'],
                ['campo' => 'codigo', 'etiqueta' => 'Codigo'],
                ['campo' => 'creditos_totales', 'etiqueta' => 'Creditos'],
                ['campo' => 'activo', 'etiqueta' => 'Estado', 'booleano' => true],
            ],
        ],
        'ciclos_escolares' => [
            'titulo' => 'Ciclos escolares',
            'singular' => 'ciclo escolar',
            'tabla' => 'ciclos_escolares',
            'activo' => 'ciclos_escolares',
            'archivo' => 'ciclos_escolares.php',
            'orden' => 'fecha_inicio DESC',
            'lista_sql' => 'SELECT * FROM ciclos_escolares ORDER BY activo DESC, fecha_inicio DESC',
            'campos' => [
                ['nombre' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'text', 'requerido' => true, 'max' => 60],
                ['nombre' => 'fecha_inicio', 'etiqueta' => 'Fecha inicio', 'tipo' => 'date', 'requerido' => true],
                ['nombre' => 'fecha_fin', 'etiqueta' => 'Fecha fin', 'tipo' => 'date', 'requerido' => true],
                ['nombre' => 'activo', 'etiqueta' => 'Activo', 'tipo' => 'checkbox', 'default' => 1],
            ],
            'columnas' => [
                ['campo' => 'nombre', 'etiqueta' => 'Nombre'],
                ['campo' => 'fecha_inicio', 'etiqueta' => 'Inicio'],
                ['campo' => 'fecha_fin', 'etiqueta' => 'Fin'],
                ['campo' => 'activo', 'etiqueta' => 'Estado', 'booleano' => true],
            ],
        ],
        'dependencias' => [
            'titulo' => 'Dependencias',
            'singular' => 'dependencia',
            'tabla' => 'dependencias',
            'activo' => 'dependencias',
            'archivo' => 'dependencias.php',
            'orden' => 'nombre',
            'lista_sql' => 'SELECT * FROM dependencias ORDER BY activo DESC, nombre',
            'campos' => [
                ['nombre' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'text', 'requerido' => true, 'max' => 200],
                ['nombre' => 'tipo', 'etiqueta' => 'Tipo', 'tipo' => 'select_static', 'requerido' => true, 'opciones' => ['publica' => 'Publica', 'privada' => 'Privada', 'social' => 'Social', 'universitaria' => 'Universitaria', 'otra' => 'Otra']],
                ['nombre' => 'rfc', 'etiqueta' => 'RFC', 'tipo' => 'text', 'max' => 13, 'nullable' => true],
                ['nombre' => 'direccion', 'etiqueta' => 'Direccion', 'tipo' => 'text', 'max' => 255, 'nullable' => true],
                ['nombre' => 'telefono', 'etiqueta' => 'Telefono', 'tipo' => 'text', 'max' => 25, 'nullable' => true],
                ['nombre' => 'correo_contacto', 'etiqueta' => 'Correo contacto', 'tipo' => 'email', 'max' => 190, 'nullable' => true],
                ['nombre' => 'responsable_nombre', 'etiqueta' => 'Responsable', 'tipo' => 'text', 'max' => 160, 'nullable' => true],
                ['nombre' => 'responsable_cargo', 'etiqueta' => 'Cargo', 'tipo' => 'text', 'max' => 120, 'nullable' => true],
                ['nombre' => 'activo', 'etiqueta' => 'Activo', 'tipo' => 'checkbox', 'default' => 1],
            ],
            'columnas' => [
                ['campo' => 'nombre', 'etiqueta' => 'Nombre'],
                ['campo' => 'tipo', 'etiqueta' => 'Tipo'],
                ['campo' => 'responsable_nombre', 'etiqueta' => 'Responsable'],
                ['campo' => 'correo_contacto', 'etiqueta' => 'Correo'],
                ['campo' => 'activo', 'etiqueta' => 'Estado', 'booleano' => true],
            ],
        ],
        'programas' => [
            'titulo' => 'Programas',
            'singular' => 'programa',
            'tabla' => 'programas_servicio_social',
            'activo' => 'programas',
            'archivo' => 'programas.php',
            'orden' => 'nombre',
            'lista_sql' => 'SELECT p.*, d.nombre AS dependencia FROM programas_servicio_social p INNER JOIN dependencias d ON d.id = p.dependencia_id ORDER BY p.activo DESC, p.nombre',
            'campos' => [
                ['nombre' => 'dependencia_id', 'etiqueta' => 'Dependencia', 'tipo' => 'select', 'requerido' => true, 'opciones_sql' => 'SELECT id, nombre FROM dependencias WHERE activo = 1 ORDER BY nombre'],
                ['nombre' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'text', 'requerido' => true, 'max' => 200],
                ['nombre' => 'descripcion', 'etiqueta' => 'Descripcion', 'tipo' => 'textarea', 'nullable' => true],
                ['nombre' => 'cupo_total', 'etiqueta' => 'Cupo total', 'tipo' => 'number', 'nullable' => true, 'min' => 1, 'max' => 999],
                ['nombre' => 'cupo_disponible', 'etiqueta' => 'Cupo disponible', 'tipo' => 'number', 'nullable' => true, 'min' => 0, 'max' => 999],
                ['nombre' => 'horas_requeridas', 'etiqueta' => 'Horas requeridas', 'tipo' => 'number', 'requerido' => true, 'min' => 1, 'max' => 999, 'default' => 480],
                ['nombre' => 'activo', 'etiqueta' => 'Activo', 'tipo' => 'checkbox', 'default' => 1],
            ],
            'columnas' => [
                ['campo' => 'nombre', 'etiqueta' => 'Nombre'],
                ['campo' => 'dependencia', 'etiqueta' => 'Dependencia'],
                ['campo' => 'cupo_disponible', 'etiqueta' => 'Cupo disponible'],
                ['campo' => 'horas_requeridas', 'etiqueta' => 'Horas'],
                ['campo' => 'activo', 'etiqueta' => 'Estado', 'booleano' => true],
            ],
        ],
    ];
}

function admin_catalogo_procesar($clave, array $def)
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }

    if (!app_verify_csrf('admin_catalogo_' . $clave, $_POST['csrf_token'] ?? '')) {
        app_flash('admin_error', 'La sesion expiro. Intenta nuevamente.');
        app_redirect($def['archivo']);
    }

    $accion = $_POST['accion'] ?? 'guardar';
    $id = (int) ($_POST['id'] ?? 0);

    try {
        $pdo = app_db();

        if ($accion === 'toggle' && $id > 0) {
            $pdo->prepare('UPDATE ' . $def['tabla'] . ' SET activo = 1 - activo WHERE id = :id')->execute(['id' => $id]);
            app_audit('admin.catalogo.toggle', $def['tabla'], $id);
            app_flash('admin_message', 'Estado actualizado correctamente.');
            app_redirect($def['archivo']);
        }

        $valores = [];
        foreach ($def['campos'] as $campo) {
            $nombre = $campo['nombre'];
            if (($campo['tipo'] ?? 'text') === 'checkbox') {
                $valores[$nombre] = isset($_POST[$nombre]) ? 1 : 0;
                continue;
            }

            $valor = trim((string) ($_POST[$nombre] ?? ''));

            if (!empty($campo['requerido']) && $valor === '') {
                app_flash('admin_error', 'Completa el campo obligatorio: ' . $campo['etiqueta'] . '.');
                app_redirect($def['archivo'] . ($id > 0 ? '?editar=' . $id : ''));
            }

            if (($campo['tipo'] ?? 'text') === 'number') {
                $valores[$nombre] = $valor === '' ? null : (int) $valor;
            } elseif ($valor === '' && !empty($campo['nullable'])) {
                $valores[$nombre] = null;
            } else {
                $valores[$nombre] = $valor;
            }
        }

        if ($clave === 'ciclos_escolares' && $valores['fecha_fin'] < $valores['fecha_inicio']) {
            app_flash('admin_error', 'La fecha fin no puede ser anterior a la fecha de inicio.');
            app_redirect($def['archivo'] . ($id > 0 ? '?editar=' . $id : ''));
        }

        if ($clave === 'programas' && $valores['cupo_total'] !== null && $valores['cupo_disponible'] !== null && $valores['cupo_disponible'] > $valores['cupo_total']) {
            app_flash('admin_error', 'El cupo disponible no puede superar el cupo total.');
            app_redirect($def['archivo'] . ($id > 0 ? '?editar=' . $id : ''));
        }

        if ($id > 0) {
            $sets = [];
            foreach (array_keys($valores) as $nombre) {
                $sets[] = $nombre . ' = :' . $nombre;
            }
            $valores['id'] = $id;
            $pdo->prepare('UPDATE ' . $def['tabla'] . ' SET ' . implode(', ', $sets) . ' WHERE id = :id')->execute($valores);
            app_audit('admin.catalogo.actualizar', $def['tabla'], $id);
            app_flash('admin_message', ucfirst($def['singular']) . ' actualizado correctamente.');
        } else {
            $campos = array_keys($valores);
            $placeholders = array_map(function ($campo) {
                return ':' . $campo;
            }, $campos);
            $pdo->prepare(
                'INSERT INTO ' . $def['tabla'] . ' (' . implode(', ', $campos) . ')
                 VALUES (' . implode(', ', $placeholders) . ')'
            )->execute($valores);
            app_audit('admin.catalogo.crear', $def['tabla'], (int) $pdo->lastInsertId());
            app_flash('admin_message', ucfirst($def['singular']) . ' registrado correctamente.');
        }

        app_redirect($def['archivo']);
    } catch (Throwable $exception) {
        app_flash('admin_error', 'No fue posible guardar el catalogo. Revisa que no exista un registro duplicado.');
        app_redirect($def['archivo'] . ($id > 0 ? '?editar=' . $id : ''));
    }
}

function admin_catalogo_opciones(array $campo)
{
    if (($campo['tipo'] ?? '') === 'select_static') {
        $opciones = [];
        foreach ($campo['opciones'] as $valor => $texto) {
            $opciones[] = ['id' => $valor, 'nombre' => $texto];
        }

        return $opciones;
    }

    if (!empty($campo['opciones_sql'])) {
        return app_db()->query($campo['opciones_sql'])->fetchAll();
    }

    return [];
}

function admin_catalogo_render_campo(array $campo, array $registro)
{
    $nombre = $campo['nombre'];
    $tipo = $campo['tipo'] ?? 'text';
    $valor = $registro[$nombre] ?? ($campo['default'] ?? '');
    $requerido = !empty($campo['requerido']) ? 'required' : '';
    $max = isset($campo['max']) ? 'maxlength="' . app_e($campo['max']) . '"' : '';

    if ($tipo === 'textarea') {
        ?>
        <textarea class="form-control" id="<?php echo app_e($nombre); ?>" name="<?php echo app_e($nombre); ?>" rows="3" <?php echo $requerido; ?>><?php echo app_e($valor); ?></textarea>
        <?php
        return;
    }

    if ($tipo === 'select' || $tipo === 'select_static') {
        $opciones = admin_catalogo_opciones($campo);
        ?>
        <select class="form-select" id="<?php echo app_e($nombre); ?>" name="<?php echo app_e($nombre); ?>" <?php echo $requerido; ?>>
            <?php if (!empty($campo['nullable'])) : ?>
                <option value="">Sin asignar</option>
            <?php endif; ?>
            <?php foreach ($opciones as $opcion) : ?>
                <option value="<?php echo app_e($opcion['id']); ?>" <?php echo ((string) $valor === (string) $opcion['id']) ? 'selected' : ''; ?>>
                    <?php echo app_e($opcion['nombre']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
        return;
    }

    if ($tipo === 'checkbox') {
        ?>
        <div class="form-check form-switch pt-2">
            <input class="form-check-input" type="checkbox" id="<?php echo app_e($nombre); ?>" name="<?php echo app_e($nombre); ?>" value="1" <?php echo (int) $valor === 1 ? 'checked' : ''; ?>>
            <label class="form-check-label" for="<?php echo app_e($nombre); ?>">Registro activo</label>
        </div>
        <?php
        return;
    }

    $inputType = in_array($tipo, ['email', 'number', 'date'], true) ? $tipo : 'text';
    $min = isset($campo['min']) ? 'min="' . app_e($campo['min']) . '"' : '';
    ?>
    <input type="<?php echo app_e($inputType); ?>" class="form-control" id="<?php echo app_e($nombre); ?>" name="<?php echo app_e($nombre); ?>" value="<?php echo app_e($valor); ?>" <?php echo $requerido; ?> <?php echo $max; ?> <?php echo $min; ?>>
    <?php
}

function admin_render_catalogo($clave)
{
    app_require_role('administrador');

    $definiciones = admin_catalogos_definiciones();
    if (empty($definiciones[$clave])) {
        app_redirect('menu.php');
    }

    $def = $definiciones[$clave];
    admin_catalogo_procesar($clave, $def);

    $error = app_take_flash('admin_error');
    $mensaje = app_take_flash('admin_message');
    $registro = [];
    $filas = [];
    $aviso = '';
    $editarId = (int) ($_GET['editar'] ?? 0);

    try {
        if ($editarId > 0) {
            $stmt = app_db()->prepare('SELECT * FROM ' . $def['tabla'] . ' WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $editarId]);
            $registro = $stmt->fetch() ?: [];
        }

        $filas = app_db()->query($def['lista_sql'])->fetchAll();
    } catch (Throwable $exception) {
        $aviso = 'No fue posible cargar el catalogo.';
    }

    $tituloPagina = $def['titulo'] . ' | Administrador';
    $paginaActiva = $def['activo'];
    include __DIR__ . '/../../template/cabeceraAdmin.php';
    ?>

    <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center mb-4">
        <div>
            <p class="text-secondary mb-1">Catalogos administrativos</p>
            <h1 class="h3 mb-0"><?php echo app_e($def['titulo']); ?></h1>
        </div>
        <?php if ($editarId > 0) : ?>
            <a class="btn btn-outline-dark" href="<?php echo app_e($def['archivo']); ?>">Nuevo registro</a>
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
                    <h2 class="h5"><?php echo $editarId > 0 ? 'Editar' : 'Registrar'; ?> <?php echo app_e($def['singular']); ?></h2>
                    <input type="hidden" name="csrf_token" value="<?php echo app_e(app_csrf_token('admin_catalogo_' . $clave)); ?>">
                    <input type="hidden" name="id" value="<?php echo app_e($registro['id'] ?? 0); ?>">
                    <input type="hidden" name="accion" value="guardar">

                    <div class="vstack gap-3">
                        <?php foreach ($def['campos'] as $campo) : ?>
                            <div>
                                <label for="<?php echo app_e($campo['nombre']); ?>" class="form-label"><?php echo app_e($campo['etiqueta']); ?></label>
                                <?php admin_catalogo_render_campo($campo, $registro); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card-footer bg-white d-flex justify-content-end">
                    <button class="btn btn-dark" type="submit">Guardar</button>
                </div>
            </form>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h5">Registros</h2>
                    <?php if (!$filas) : ?>
                        <p class="text-secondary mb-0">No hay registros en este catalogo.</p>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <?php foreach ($def['columnas'] as $columna) : ?>
                                            <th><?php echo app_e($columna['etiqueta']); ?></th>
                                        <?php endforeach; ?>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($filas as $fila) : ?>
                                        <tr>
                                            <?php foreach ($def['columnas'] as $columna) : ?>
                                                <td>
                                                    <?php if (!empty($columna['booleano'])) : ?>
                                                        <span class="badge <?php echo (int) $fila[$columna['campo']] === 1 ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                                                            <?php echo (int) $fila[$columna['campo']] === 1 ? 'Activo' : 'Inactivo'; ?>
                                                        </span>
                                                    <?php else : ?>
                                                        <?php echo app_e($fila[$columna['campo']] ?? ''); ?>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                            <td>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <a class="btn btn-sm btn-outline-dark" href="<?php echo app_e($def['archivo']); ?>?editar=<?php echo app_e($fila['id']); ?>">Editar</a>
                                                    <form method="post">
                                                        <input type="hidden" name="csrf_token" value="<?php echo app_e(app_csrf_token('admin_catalogo_' . $clave)); ?>">
                                                        <input type="hidden" name="id" value="<?php echo app_e($fila['id']); ?>">
                                                        <button class="btn btn-sm btn-outline-secondary" name="accion" value="toggle" type="submit">
                                                            <?php echo (int) $fila['activo'] === 1 ? 'Desactivar' : 'Activar'; ?>
                                                        </button>
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
        </div>
    </div>

    <?php
    include __DIR__ . '/../../template/pie.php';
}
