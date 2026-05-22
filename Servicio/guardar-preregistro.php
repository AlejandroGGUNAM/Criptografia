<?php
require_once __DIR__ . '/config/bootstrap.php';

app_start_session();
app_security_headers();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_redirect('preregistro.php');
}

$old = [
    'nombres' => trim($_POST['nombres'] ?? ''),
    'apellido_paterno' => trim($_POST['apellido_paterno'] ?? ''),
    'apellido_materno' => trim($_POST['apellido_materno'] ?? ''),
    'correo' => trim($_POST['correo'] ?? ''),
    'telefono' => trim($_POST['telefono'] ?? ''),
    'matricula' => trim($_POST['matricula'] ?? ''),
    'carrera_id' => trim($_POST['carrera_id'] ?? ''),
    'semestre_actual' => trim($_POST['semestre_actual'] ?? ''),
    'creditos_aprobados' => trim($_POST['creditos_aprobados'] ?? ''),
    'acepta_aviso_privacidad' => $_POST['acepta_aviso_privacidad'] ?? '',
];

function preregistro_error($message, $old)
{
    app_flash('preregistro_error', $message);
    $_SESSION['preregistro_old'] = $old;
    app_redirect('preregistro.php');
}

if (
    $old['nombres'] === '' ||
    $old['apellido_paterno'] === '' ||
    $old['correo'] === '' ||
    $old['matricula'] === ''
) {
    preregistro_error('Completa los campos obligatorios.', $old);
}

if (!filter_var($old['correo'], FILTER_VALIDATE_EMAIL)) {
    preregistro_error('Ingresa un correo institucional valido.', $old);
}

$password = (string) ($_POST['password'] ?? '');
$passwordConfirmacion = (string) ($_POST['password_confirmacion'] ?? '');

if (strlen($password) < 10) {
    preregistro_error('La contrasena debe tener al menos 10 caracteres.', $old);
}

if (!hash_equals($password, $passwordConfirmacion)) {
    preregistro_error('La confirmacion de contrasena no coincide.', $old);
}

if (empty($old['acepta_aviso_privacidad'])) {
    preregistro_error('Debes aceptar el aviso de privacidad para continuar.', $old);
}

$semestre = $old['semestre_actual'] === '' ? null : (int) $old['semestre_actual'];
$creditos = $old['creditos_aprobados'] === '' ? null : (int) $old['creditos_aprobados'];
$carreraId = $old['carrera_id'] === '' ? null : (int) $old['carrera_id'];

if ($semestre !== null && ($semestre < 1 || $semestre > 20)) {
    preregistro_error('El semestre debe estar entre 1 y 20.', $old);
}

if ($creditos !== null && ($creditos < 0 || $creditos > 999)) {
    preregistro_error('Los creditos aprobados deben estar entre 0 y 999.', $old);
}

try {
    $pdo = app_db();
    $pdo->beginTransaction();

    $roleStmt = $pdo->prepare('SELECT id FROM roles WHERE codigo = :codigo LIMIT 1');
    $roleStmt->execute(['codigo' => 'alumno']);
    $rolAlumnoId = $roleStmt->fetchColumn();

    if (!$rolAlumnoId) {
        throw new RuntimeException('No existe el rol de alumno.');
    }

    $duplicateStmt = $pdo->prepare(
        'SELECT
            (SELECT COUNT(*) FROM usuarios WHERE correo = :correo) AS correos,
            (SELECT COUNT(*) FROM alumnos WHERE matricula = :matricula) AS matriculas'
    );
    $duplicateStmt->execute([
        'correo' => $old['correo'],
        'matricula' => $old['matricula'],
    ]);
    $duplicados = $duplicateStmt->fetch();

    if ((int) $duplicados['correos'] > 0) {
        $pdo->rollBack();
        preregistro_error('Ya existe una cuenta registrada con ese correo.', $old);
    }

    if ((int) $duplicados['matriculas'] > 0) {
        $pdo->rollBack();
        preregistro_error('Ya existe un alumno registrado con esa matricula.', $old);
    }

    $passwordHash = password_hash($password, PASSWORD_ARGON2ID);

    $usuarioStmt = $pdo->prepare(
        'INSERT INTO usuarios (
            rol_id,
            correo,
            password_hash,
            nombres,
            apellido_paterno,
            apellido_materno,
            telefono,
            estatus
         ) VALUES (
            :rol_id,
            :correo,
            :password_hash,
            :nombres,
            :apellido_paterno,
            :apellido_materno,
            :telefono,
            "pendiente_verificacion"
         )'
    );
    $usuarioStmt->execute([
        'rol_id' => $rolAlumnoId,
        'correo' => $old['correo'],
        'password_hash' => $passwordHash,
        'nombres' => $old['nombres'],
        'apellido_paterno' => $old['apellido_paterno'],
        'apellido_materno' => $old['apellido_materno'] ?: null,
        'telefono' => $old['telefono'] ?: null,
    ]);
    $usuarioId = (int) $pdo->lastInsertId();

    $alumnoStmt = $pdo->prepare(
        'INSERT INTO alumnos (
            usuario_id,
            carrera_id,
            matricula,
            semestre_actual,
            creditos_aprobados
         ) VALUES (
            :usuario_id,
            :carrera_id,
            :matricula,
            :semestre_actual,
            :creditos_aprobados
         )'
    );
    $alumnoStmt->execute([
        'usuario_id' => $usuarioId,
        'carrera_id' => $carreraId,
        'matricula' => $old['matricula'],
        'semestre_actual' => $semestre,
        'creditos_aprobados' => $creditos,
    ]);
    $alumnoId = (int) $pdo->lastInsertId();

    $pdo->prepare(
        'INSERT INTO preregistros (
            alumno_id,
            estatus,
            acepta_aviso_privacidad,
            enviado_en
         ) VALUES (
            :alumno_id,
            "pendiente_revision",
            1,
            NOW()
         )'
    )->execute(['alumno_id' => $alumnoId]);

    $pdo->prepare(
        'INSERT INTO historial_passwords (usuario_id, password_hash)
         VALUES (:usuario_id, :password_hash)'
    )->execute([
        'usuario_id' => $usuarioId,
        'password_hash' => $passwordHash,
    ]);

    $token = random_bytes(32);
    $ip = inet_pton($_SERVER['REMOTE_ADDR'] ?? '') ?: null;

    $pdo->prepare(
        'INSERT INTO tokens_seguridad (
            usuario_id,
            proposito,
            token_hash,
            expira_en,
            ip_creacion,
            user_agent_hash
         ) VALUES (
            :usuario_id,
            "verificacion_correo",
            :token_hash,
            DATE_ADD(NOW(), INTERVAL 24 HOUR),
            :ip_creacion,
            :user_agent_hash
         )'
    )->execute([
        'usuario_id' => $usuarioId,
        'token_hash' => hash('sha256', $token, true),
        'ip_creacion' => $ip,
        'user_agent_hash' => hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '', true),
    ]);

    $pdo->commit();

    app_flash('login_message', 'Preregistro enviado. Tu cuenta queda pendiente de verificacion y revision administrativa.');
    app_redirect('index.php');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    preregistro_error('No fue posible guardar el preregistro. Intenta nuevamente.', $old);
}
