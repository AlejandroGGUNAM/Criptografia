<?php
require_once __DIR__ . '/../../config/bootstrap.php';

app_require_role('administrador');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_redirect('usuarios.php');
}

if (!app_verify_csrf('admin_usuario_accion', $_POST['csrf_token'] ?? '')) {
    app_flash('admin_error', 'La sesion expiro. Intenta nuevamente.');
    app_redirect('usuarios.php');
}

$accion = $_POST['accion'] ?? 'guardar';
$usuarioId = (int) ($_POST['usuario_id'] ?? 0);
$estatusPermitidos = ['pendiente_verificacion', 'activo', 'bloqueado', 'inactivo'];

try {
    $pdo = app_db();

    if ($accion === 'estatus') {
        $estatus = $_POST['estatus'] ?? '';

        if ($usuarioId <= 0 || !in_array($estatus, $estatusPermitidos, true)) {
            app_flash('admin_error', 'El cambio de estado no es valido.');
            app_redirect('usuarios.php');
        }

        if ($usuarioId === (int) $_SESSION['usuario_id'] && $estatus !== 'activo') {
            app_flash('admin_error', 'No puedes desactivar o bloquear tu propia cuenta.');
            app_redirect('usuarios.php');
        }

        $pdo->prepare(
            'UPDATE usuarios
             SET estatus = :estatus,
                 correo_verificado_en = CASE WHEN :estatus_activo = "activo" THEN COALESCE(correo_verificado_en, NOW()) ELSE correo_verificado_en END,
                 bloqueado_hasta = NULL
             WHERE id = :id'
        )->execute([
            'estatus' => $estatus,
            'estatus_activo' => $estatus,
            'id' => $usuarioId,
        ]);

        app_audit('admin.usuario.estatus', 'usuario', $usuarioId, ['estatus' => $estatus]);
        app_flash('admin_message', 'Estado del usuario actualizado.');
        app_redirect('usuarios.php');
    }

    $rolId = (int) ($_POST['rol_id'] ?? 0);
    $correo = trim($_POST['correo'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $nombres = trim($_POST['nombres'] ?? '');
    $apellidoPaterno = trim($_POST['apellido_paterno'] ?? '');
    $apellidoMaterno = trim($_POST['apellido_materno'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $estatus = $_POST['estatus'] ?? 'activo';

    if ($rolId <= 0 || $correo === '' || $nombres === '' || $apellidoPaterno === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        app_flash('admin_error', 'Completa los datos generales del usuario.');
        app_redirect('usuarios.php' . ($usuarioId > 0 ? '?editar=' . $usuarioId : ''));
    }

    if (!in_array($estatus, $estatusPermitidos, true)) {
        app_flash('admin_error', 'El estatus seleccionado no es valido.');
        app_redirect('usuarios.php' . ($usuarioId > 0 ? '?editar=' . $usuarioId : ''));
    }

    if ($usuarioId === (int) $_SESSION['usuario_id'] && $estatus !== 'activo') {
        app_flash('admin_error', 'No puedes desactivar o bloquear tu propia cuenta.');
        app_redirect('usuarios.php?editar=' . $usuarioId);
    }

    if ($usuarioId === 0 && strlen($password) < 10) {
        app_flash('admin_error', 'La contrasena inicial debe tener al menos 10 caracteres.');
        app_redirect('usuarios.php');
    }

    if ($usuarioId > 0 && $password !== '' && strlen($password) < 10) {
        app_flash('admin_error', 'La nueva contrasena debe tener al menos 10 caracteres.');
        app_redirect('usuarios.php?editar=' . $usuarioId);
    }

    $stmt = $pdo->prepare('SELECT codigo FROM roles WHERE id = :id AND activo = 1 LIMIT 1');
    $stmt->execute(['id' => $rolId]);
    $rolCodigo = $stmt->fetchColumn();

    if (!$rolCodigo) {
        app_flash('admin_error', 'El rol seleccionado no existe.');
        app_redirect('usuarios.php' . ($usuarioId > 0 ? '?editar=' . $usuarioId : ''));
    }

    if ($usuarioId === (int) $_SESSION['usuario_id'] && $rolCodigo !== 'administrador') {
        app_flash('admin_error', 'No puedes quitarte el rol de administrador desde tu propia sesion.');
        app_redirect('usuarios.php?editar=' . $usuarioId);
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE correo = :correo AND id <> :id');
    $stmt->execute(['correo' => $correo, 'id' => $usuarioId]);
    if ((int) $stmt->fetchColumn() > 0) {
        app_flash('admin_error', 'Ya existe un usuario con ese correo.');
        app_redirect('usuarios.php' . ($usuarioId > 0 ? '?editar=' . $usuarioId : ''));
    }

    $matricula = trim($_POST['matricula'] ?? '');
    $carreraId = ($_POST['carrera_id'] ?? '') === '' ? null : (int) $_POST['carrera_id'];
    $semestre = ($_POST['semestre_actual'] ?? '') === '' ? null : (int) $_POST['semestre_actual'];
    $creditos = ($_POST['creditos_aprobados'] ?? '') === '' ? null : (int) $_POST['creditos_aprobados'];
    $promedio = ($_POST['promedio'] ?? '') === '' ? null : (float) $_POST['promedio'];
    $numeroEmpleado = trim($_POST['numero_empleado'] ?? '');
    $departamentoId = ($_POST['departamento_id'] ?? '') === '' ? null : (int) $_POST['departamento_id'];
    $gradoAcademico = trim($_POST['grado_academico'] ?? '');
    $area = trim($_POST['area'] ?? '');

    if ($rolCodigo === 'alumno' && $matricula === '') {
        app_flash('admin_error', 'Captura la matricula para el alumno.');
        app_redirect('usuarios.php' . ($usuarioId > 0 ? '?editar=' . $usuarioId : ''));
    }

    if ($rolCodigo === 'profesor' && $numeroEmpleado === '') {
        app_flash('admin_error', 'Captura el numero de empleado para el profesor.');
        app_redirect('usuarios.php' . ($usuarioId > 0 ? '?editar=' . $usuarioId : ''));
    }

    if ($semestre !== null && ($semestre < 1 || $semestre > 20)) {
        app_flash('admin_error', 'El semestre debe estar entre 1 y 20.');
        app_redirect('usuarios.php' . ($usuarioId > 0 ? '?editar=' . $usuarioId : ''));
    }

    if ($creditos !== null && ($creditos < 0 || $creditos > 999)) {
        app_flash('admin_error', 'Los creditos deben estar entre 0 y 999.');
        app_redirect('usuarios.php' . ($usuarioId > 0 ? '?editar=' . $usuarioId : ''));
    }

    if ($promedio !== null && ($promedio < 0 || $promedio > 100)) {
        app_flash('admin_error', 'El promedio debe estar entre 0 y 100.');
        app_redirect('usuarios.php' . ($usuarioId > 0 ? '?editar=' . $usuarioId : ''));
    }

    if ($rolCodigo === 'alumno') {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM alumnos
             WHERE matricula = :matricula
               AND usuario_id <> :usuario_id'
        );
        $stmt->execute(['matricula' => $matricula, 'usuario_id' => $usuarioId]);
        if ((int) $stmt->fetchColumn() > 0) {
            app_flash('admin_error', 'Ya existe un alumno con esa matricula.');
            app_redirect('usuarios.php' . ($usuarioId > 0 ? '?editar=' . $usuarioId : ''));
        }
    }

    if ($rolCodigo === 'profesor') {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM profesores
             WHERE numero_empleado = :numero_empleado
               AND usuario_id <> :usuario_id'
        );
        $stmt->execute(['numero_empleado' => $numeroEmpleado, 'usuario_id' => $usuarioId]);
        if ((int) $stmt->fetchColumn() > 0) {
            app_flash('admin_error', 'Ya existe un profesor con ese numero de empleado.');
            app_redirect('usuarios.php' . ($usuarioId > 0 ? '?editar=' . $usuarioId : ''));
        }
    }

    $pdo->beginTransaction();

    if ($usuarioId > 0) {
        $params = [
            'rol_id' => $rolId,
            'correo' => $correo,
            'nombres' => $nombres,
            'apellido_paterno' => $apellidoPaterno,
            'apellido_materno' => $apellidoMaterno ?: null,
            'telefono' => $telefono ?: null,
            'estatus' => $estatus,
            'estatus_verificado' => $estatus,
            'id' => $usuarioId,
        ];
        $passwordSql = '';

        if ($password !== '') {
            $params['password_hash'] = password_hash($password, PASSWORD_ARGON2ID);
            $passwordSql = ', password_hash = :password_hash';
        }

        $pdo->prepare(
            'UPDATE usuarios
             SET rol_id = :rol_id,
                 correo = :correo,
                 nombres = :nombres,
                 apellido_paterno = :apellido_paterno,
                 apellido_materno = :apellido_materno,
                 telefono = :telefono,
                 estatus = :estatus,
                 correo_verificado_en = CASE WHEN :estatus_verificado = "activo" THEN COALESCE(correo_verificado_en, NOW()) ELSE correo_verificado_en END
                 ' . $passwordSql . '
             WHERE id = :id'
        )->execute($params);

        if (!empty($params['password_hash'])) {
            $pdo->prepare(
                'INSERT INTO historial_passwords (usuario_id, password_hash)
                 VALUES (:usuario_id, :password_hash)'
            )->execute([
                'usuario_id' => $usuarioId,
                'password_hash' => $params['password_hash'],
            ]);
        }
    } else {
        $passwordHash = password_hash($password, PASSWORD_ARGON2ID);
        $pdo->prepare(
            'INSERT INTO usuarios (
                rol_id,
                correo,
                password_hash,
                nombres,
                apellido_paterno,
                apellido_materno,
                telefono,
                estatus,
                correo_verificado_en
             ) VALUES (
                :rol_id,
                :correo,
                :password_hash,
                :nombres,
                :apellido_paterno,
                :apellido_materno,
                :telefono,
                :estatus,
                CASE WHEN :estatus_verificado = "activo" THEN NOW() ELSE NULL END
             )'
        )->execute([
            'rol_id' => $rolId,
            'correo' => $correo,
            'password_hash' => $passwordHash,
            'nombres' => $nombres,
            'apellido_paterno' => $apellidoPaterno,
            'apellido_materno' => $apellidoMaterno ?: null,
            'telefono' => $telefono ?: null,
            'estatus' => $estatus,
            'estatus_verificado' => $estatus,
        ]);
        $usuarioId = (int) $pdo->lastInsertId();

        $pdo->prepare(
            'INSERT INTO historial_passwords (usuario_id, password_hash)
             VALUES (:usuario_id, :password_hash)'
        )->execute([
            'usuario_id' => $usuarioId,
            'password_hash' => $passwordHash,
        ]);
    }

    if ($rolCodigo === 'alumno') {
        $pdo->prepare(
            'INSERT INTO alumnos (
                usuario_id,
                carrera_id,
                matricula,
                semestre_actual,
                creditos_aprobados,
                promedio
             ) VALUES (
                :usuario_id,
                :carrera_id,
                :matricula,
                :semestre_actual,
                :creditos_aprobados,
                :promedio
             )
             ON DUPLICATE KEY UPDATE
                carrera_id = VALUES(carrera_id),
                matricula = VALUES(matricula),
                semestre_actual = VALUES(semestre_actual),
                creditos_aprobados = VALUES(creditos_aprobados),
                promedio = VALUES(promedio)'
        )->execute([
            'usuario_id' => $usuarioId,
            'carrera_id' => $carreraId,
            'matricula' => $matricula,
            'semestre_actual' => $semestre,
            'creditos_aprobados' => $creditos,
            'promedio' => $promedio,
        ]);
    } elseif ($rolCodigo === 'profesor') {
        $pdo->prepare(
            'INSERT INTO profesores (
                usuario_id,
                departamento_id,
                numero_empleado,
                grado_academico
             ) VALUES (
                :usuario_id,
                :departamento_id,
                :numero_empleado,
                :grado_academico
             )
             ON DUPLICATE KEY UPDATE
                departamento_id = VALUES(departamento_id),
                numero_empleado = VALUES(numero_empleado),
                grado_academico = VALUES(grado_academico)'
        )->execute([
            'usuario_id' => $usuarioId,
            'departamento_id' => $departamentoId,
            'numero_empleado' => $numeroEmpleado,
            'grado_academico' => $gradoAcademico ?: null,
        ]);
    } elseif ($rolCodigo === 'administrador') {
        $pdo->prepare(
            'INSERT INTO administradores (usuario_id, area)
             VALUES (:usuario_id, :area)
             ON DUPLICATE KEY UPDATE area = VALUES(area)'
        )->execute([
            'usuario_id' => $usuarioId,
            'area' => $area ?: null,
        ]);
    }

    app_audit('admin.usuario.guardar', 'usuario', $usuarioId, ['rol' => $rolCodigo, 'estatus' => $estatus]);
    $pdo->commit();

    app_flash('admin_message', 'Usuario guardado correctamente.');
    app_redirect('usuarios.php');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    app_flash('admin_error', 'No fue posible guardar el usuario. Revisa duplicados y datos obligatorios.');
    app_redirect('usuarios.php' . ($usuarioId > 0 ? '?editar=' . $usuarioId : ''));
}
