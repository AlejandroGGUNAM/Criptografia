<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acceso denegado');
}

require_once __DIR__ . '/../config/bootstrap.php';

$pdo = app_db();
$pdo->beginTransaction();

try {
    $roles = [];
    foreach ($pdo->query('SELECT id, codigo FROM roles') as $rol) {
        $roles[$rol['codigo']] = (int) $rol['id'];
    }

    foreach (['alumno', 'profesor', 'administrador'] as $codigo) {
        if (empty($roles[$codigo])) {
            throw new RuntimeException('Falta el rol requerido: ' . $codigo);
        }
    }

    $pdo->prepare(
        'INSERT INTO departamentos (nombre, codigo, activo)
         VALUES ("Administracion de Servicio Social", "SS", 1)
         ON DUPLICATE KEY UPDATE activo = 1'
    )->execute();
    $departamentoId = (int) $pdo->query('SELECT id FROM departamentos WHERE codigo = "SS" LIMIT 1')->fetchColumn();

    $pdo->prepare(
        'INSERT INTO carreras (departamento_id, nombre, codigo, creditos_totales, activo)
         VALUES (:departamento_id, "Carrera general", "GEN", 480, 1)
         ON DUPLICATE KEY UPDATE departamento_id = VALUES(departamento_id), activo = 1'
    )->execute(['departamento_id' => $departamentoId]);
    $carreraId = (int) $pdo->query('SELECT id FROM carreras WHERE codigo = "GEN" LIMIT 1')->fetchColumn();

    $pdo->prepare(
        'INSERT INTO ciclos_escolares (nombre, fecha_inicio, fecha_fin, activo)
         VALUES ("2026", "2026-01-01", "2026-12-31", 1)
         ON DUPLICATE KEY UPDATE activo = 1'
    )->execute();

    $pdo->prepare(
        'INSERT INTO dependencias (
            nombre,
            tipo,
            correo_contacto,
            responsable_nombre,
            responsable_cargo,
            activo
         ) VALUES (
            "Universidad - Servicio Social Interno",
            "universitaria",
            "serviciosocial@universidad.local",
            "Coordinacion de Servicio Social",
            "Coordinacion",
            1
         )
         ON DUPLICATE KEY UPDATE activo = 1'
    )->execute();
    $dependenciaId = (int) $pdo->query('SELECT id FROM dependencias WHERE nombre = "Universidad - Servicio Social Interno" LIMIT 1')->fetchColumn();

    $pdo->prepare(
        'INSERT INTO programas_servicio_social (
            dependencia_id,
            nombre,
            descripcion,
            cupo_total,
            cupo_disponible,
            horas_requeridas,
            activo
         ) VALUES (
            :dependencia_id,
            "Programa general de servicio social",
            "Programa base para pruebas y operacion inicial del sistema.",
            100,
            100,
            480,
            1
         )
         ON DUPLICATE KEY UPDATE activo = 1'
    )->execute(['dependencia_id' => $dependenciaId]);

    $adminCorreo = 'admin@universidad.local';
    $adminPassword = 'AdminServicio2026!';
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE correo = :correo LIMIT 1');
    $stmt->execute(['correo' => $adminCorreo]);
    $adminId = (int) $stmt->fetchColumn();

    if ($adminId === 0) {
        $pdo->prepare(
            'INSERT INTO usuarios (
                rol_id,
                correo,
                password_hash,
                nombres,
                apellido_paterno,
                telefono,
                estatus,
                correo_verificado_en
             ) VALUES (
                :rol_id,
                :correo,
                :password_hash,
                "Administrador",
                "Inicial",
                NULL,
                "activo",
                NOW()
             )'
        )->execute([
            'rol_id' => $roles['administrador'],
            'correo' => $adminCorreo,
            'password_hash' => password_hash($adminPassword, PASSWORD_ARGON2ID),
        ]);
        $adminId = (int) $pdo->lastInsertId();
    }

    $pdo->prepare(
        'INSERT INTO administradores (usuario_id, area)
         VALUES (:usuario_id, "Coordinacion de Servicio Social")
         ON DUPLICATE KEY UPDATE area = VALUES(area)'
    )->execute(['usuario_id' => $adminId]);

    $profCorreo = 'profesor@universidad.local';
    $profPassword = 'ProfesorServicio2026!';
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE correo = :correo LIMIT 1');
    $stmt->execute(['correo' => $profCorreo]);
    $profId = (int) $stmt->fetchColumn();

    if ($profId === 0) {
        $pdo->prepare(
            'INSERT INTO usuarios (
                rol_id,
                correo,
                password_hash,
                nombres,
                apellido_paterno,
                telefono,
                estatus,
                correo_verificado_en
             ) VALUES (
                :rol_id,
                :correo,
                :password_hash,
                "Profesor",
                "Asesor",
                NULL,
                "activo",
                NOW()
             )'
        )->execute([
            'rol_id' => $roles['profesor'],
            'correo' => $profCorreo,
            'password_hash' => password_hash($profPassword, PASSWORD_ARGON2ID),
        ]);
        $profId = (int) $pdo->lastInsertId();
    }

    $pdo->prepare(
        'INSERT INTO profesores (usuario_id, departamento_id, numero_empleado, grado_academico)
         VALUES (:usuario_id, :departamento_id, "PROF-001", "Licenciatura")
         ON DUPLICATE KEY UPDATE
            departamento_id = VALUES(departamento_id),
            numero_empleado = VALUES(numero_empleado),
            grado_academico = VALUES(grado_academico)'
    )->execute([
        'usuario_id' => $profId,
        'departamento_id' => $departamentoId,
    ]);

    $pdo->commit();

    app_ensure_signature_key($adminId);
    app_ensure_signature_key($profId);

    echo "Seed inicial aplicado.\n";
    echo "Administrador: {$adminCorreo} / {$adminPassword}\n";
    echo "Profesor: {$profCorreo} / {$profPassword}\n";
    echo "Carrera inicial ID: {$carreraId}\n";
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, 'Error en seed inicial: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
