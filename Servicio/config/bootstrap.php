<?php

date_default_timezone_set('America/Mexico_City');

if (!defined('APP_BASE_PATH')) {
    define('APP_BASE_PATH', dirname(__DIR__));
}

require_once APP_BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'crypto.php';

if (!function_exists('app_config')) {
    function app_config($key = null)
    {
        $config = [
            'db' => [
                'host' => '127.0.0.1',
                'port' => 3307,
                'name' => 'servicio_social_universidad',
                'user' => 'root',
                'pass' => 'DBCale224-',
                'charset' => 'utf8mb4',
            ],
            'crypto' => [
                'symmetric_cipher' => 'AES-256-GCM',
                'signature_algorithm' => 'ECDSA_P256',
                'password_algorithm' => 'Argon2id',
            ],
        ];

        return $key === null ? $config : ($config[$key] ?? null);
    }
}

if (!function_exists('app_start_session')) {
    function app_start_session()
    {
        if (PHP_SAPI === 'cli' || session_status() !== PHP_SESSION_NONE) {
            return;
        }

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }
}

if (!function_exists('app_security_headers')) {
    function app_security_headers()
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}

if (!function_exists('app_db')) {
    function app_db()
    {
        static $pdo = null;

        if ($pdo instanceof PDO) {
            return $pdo;
        }

        $db = app_config('db');
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $db['host'],
            $db['port'],
            $db['name'],
            $db['charset']
        );

        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $pdo;
    }
}

if (!function_exists('app_e')) {
    function app_e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('app_redirect')) {
    function app_redirect($path)
    {
        header('Location: ' . $path);
        exit;
    }
}

if (!function_exists('app_flash')) {
    function app_flash($key, $message)
    {
        $_SESSION[$key] = $message;
    }
}

if (!function_exists('app_take_flash')) {
    function app_take_flash($key)
    {
        $message = $_SESSION[$key] ?? '';
        unset($_SESSION[$key]);

        return $message;
    }
}

if (!function_exists('app_csrf_token')) {
    function app_csrf_token($action)
    {
        app_start_session();

        if (empty($_SESSION['csrf_secret'])) {
            $_SESSION['csrf_secret'] = bin2hex(random_bytes(32));
        }

        return hash_hmac('sha256', $action, $_SESSION['csrf_secret']);
    }
}

if (!function_exists('app_verify_csrf')) {
    function app_verify_csrf($action, $token)
    {
        $expected = app_csrf_token($action);

        return is_string($token) && hash_equals($expected, $token);
    }
}

if (!function_exists('app_user_home')) {
    function app_user_home($roleCode)
    {
        $routes = [
            'administrador' => 'usuarios/admin/menu.php',
            'profesor' => 'usuarios/profesor/menu.php',
            'alumno' => 'usuarios/alumno/menu.php',
        ];

        return $routes[$roleCode] ?? 'index.php';
    }
}

if (!function_exists('app_require_role')) {
    function app_require_role($roles)
    {
        app_start_session();

        $roles = (array) $roles;

        if (empty($_SESSION['usuario_id'])) {
            app_flash('login_error', 'Inicia sesion para continuar.');
            app_redirect('../../index.php');
        }

        if (!in_array($_SESSION['rol_codigo'] ?? '', $roles, true)) {
            app_redirect('../../' . app_user_home($_SESSION['rol_codigo'] ?? ''));
        }
    }
}

if (!function_exists('app_current_alumno')) {
    function app_current_alumno()
    {
        $stmt = app_db()->prepare(
            'SELECT
                a.id AS alumno_id,
                a.usuario_id,
                a.carrera_id,
                a.matricula,
                a.semestre_actual,
                a.creditos_aprobados,
                a.promedio,
                u.correo,
                u.nombres,
                u.apellido_paterno,
                u.apellido_materno,
                u.telefono,
                u.estatus AS estatus_usuario,
                u.correo_verificado_en,
                c.nombre AS carrera
             FROM alumnos a
             INNER JOIN usuarios u ON u.id = a.usuario_id
             LEFT JOIN carreras c ON c.id = a.carrera_id
             WHERE a.usuario_id = :usuario_id
             LIMIT 1'
        );
        $stmt->execute(['usuario_id' => $_SESSION['usuario_id'] ?? 0]);

        return $stmt->fetch() ?: null;
    }
}

if (!function_exists('app_current_profesor')) {
    function app_current_profesor()
    {
        $stmt = app_db()->prepare(
            'SELECT
                p.id AS profesor_id,
                p.usuario_id,
                p.departamento_id,
                p.numero_empleado,
                p.grado_academico,
                u.correo,
                u.nombres,
                u.apellido_paterno,
                u.apellido_materno,
                u.telefono,
                u.estatus AS estatus_usuario,
                d.nombre AS departamento
             FROM profesores p
             INNER JOIN usuarios u ON u.id = p.usuario_id
             LEFT JOIN departamentos d ON d.id = p.departamento_id
             WHERE p.usuario_id = :usuario_id
             LIMIT 1'
        );
        $stmt->execute(['usuario_id' => $_SESSION['usuario_id'] ?? 0]);

        return $stmt->fetch() ?: null;
    }
}

if (!function_exists('app_current_administrador')) {
    function app_current_administrador()
    {
        $stmt = app_db()->prepare(
            'SELECT
                a.id AS administrador_id,
                a.usuario_id,
                a.area,
                u.correo,
                u.nombres,
                u.apellido_paterno,
                u.apellido_materno,
                u.telefono,
                u.estatus AS estatus_usuario
             FROM administradores a
             INNER JOIN usuarios u ON u.id = a.usuario_id
             WHERE a.usuario_id = :usuario_id
             LIMIT 1'
        );
        $stmt->execute(['usuario_id' => $_SESSION['usuario_id'] ?? 0]);

        return $stmt->fetch() ?: null;
    }
}

if (!function_exists('app_audit')) {
    function app_audit($accion, $entidadTipo = null, $entidadId = null, array $datos = [])
    {
        try {
            $pdo = app_db();
            $hashAnterior = $pdo->query(
                'SELECT hash_evento FROM bitacora_auditoria ORDER BY id DESC LIMIT 1'
            )->fetchColumn();

            $payload = [
                'accion' => $accion,
                'usuario_id' => $_SESSION['usuario_id'] ?? null,
                'entidad_tipo' => $entidadTipo,
                'entidad_id' => $entidadId,
                'datos' => $datos,
                'fecha' => date('c'),
                'nonce' => bin2hex(random_bytes(8)),
            ];
            $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);
            $hashEvento = hash('sha256', (string) $hashAnterior . $payloadJson, true);
            $hmacEvento = hash_hmac('sha256', (string) $hashAnterior . $payloadJson, app_crypto_key(), true);

            $pdo->prepare(
                'INSERT INTO bitacora_auditoria (
                    usuario_id,
                    accion,
                    entidad_tipo,
                    entidad_id,
                    ip_hash,
                    user_agent_hash,
                    datos_evento,
                    hash_anterior,
                    hash_evento,
                    hmac_evento
                 ) VALUES (
                    :usuario_id,
                    :accion,
                    :entidad_tipo,
                    :entidad_id,
                    :ip_hash,
                    :user_agent_hash,
                    :datos_evento,
                    :hash_anterior,
                    :hash_evento,
                    :hmac_evento
                 )'
            )->execute([
                'usuario_id' => $_SESSION['usuario_id'] ?? null,
                'accion' => $accion,
                'entidad_tipo' => $entidadTipo,
                'entidad_id' => $entidadId,
                'ip_hash' => hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '', true),
                'user_agent_hash' => hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '', true),
                'datos_evento' => $payloadJson,
                'hash_anterior' => $hashAnterior ?: null,
                'hash_evento' => $hashEvento,
                'hmac_evento' => $hmacEvento,
            ]);
        } catch (Throwable $exception) {
            // La auditoria no debe bloquear la operacion principal si aun no existe la tabla.
        }
    }
}

if (!function_exists('app_key_storage_dir')) {
    function app_key_storage_dir()
    {
        return APP_BASE_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'keys';
    }
}

if (!function_exists('app_openssl_binary')) {
    function app_openssl_binary()
    {
        $path = 'C:/xampp/apache/bin/openssl.exe';

        if (!is_file($path)) {
            throw new RuntimeException('No se encontro openssl.exe de XAMPP.');
        }

        return $path;
    }
}

if (!function_exists('app_shell_arg')) {
    function app_shell_arg($value)
    {
        return '"' . str_replace('"', '\"', (string) $value) . '"';
    }
}

if (!function_exists('app_private_key_path')) {
    function app_private_key_path($usuarioId)
    {
        return app_key_storage_dir() . DIRECTORY_SEPARATOR . 'usuario_' . (int) $usuarioId . '_ecdsa_p256.pem';
    }
}

if (!function_exists('app_private_key_encrypted_path')) {
    function app_private_key_encrypted_path($usuarioId)
    {
        return app_private_key_path($usuarioId) . '.enc';
    }
}

if (!function_exists('app_public_key_path')) {
    function app_public_key_path($usuarioId)
    {
        return app_private_key_path($usuarioId) . '.pub';
    }
}

if (!function_exists('app_prepare_key_storage_dir')) {
    function app_prepare_key_storage_dir()
    {
        $dir = app_key_storage_dir();

        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        $htaccess = $dir . DIRECTORY_SEPARATOR . '.htaccess';
        if (!is_file($htaccess)) {
            file_put_contents($htaccess, "Require all denied\n", LOCK_EX);
        }

        return $dir;
    }
}

if (!function_exists('app_private_key_aad')) {
    function app_private_key_aad($usuarioId)
    {
        return 'firma_privada_usuario:' . (int) $usuarioId;
    }
}

if (!function_exists('app_destroy_sensitive_file')) {
    function app_destroy_sensitive_file($path)
    {
        if (!is_string($path) || !is_file($path)) {
            return;
        }

        $size = filesize($path);
        if ($size !== false && $size > 0) {
            @file_put_contents($path, str_repeat("\0", $size), LOCK_EX);
        }

        @file_put_contents($path, '', LOCK_EX);
        @chmod($path, 0666);
        @unlink($path);
    }
}

if (!function_exists('app_store_private_key_pem')) {
    function app_store_private_key_pem($usuarioId, $privatePem)
    {
        app_prepare_key_storage_dir();

        $encryptedPath = app_private_key_encrypted_path($usuarioId);
        file_put_contents($encryptedPath, app_encrypt($privatePem, app_private_key_aad($usuarioId)), LOCK_EX);
        @chmod($encryptedPath, 0600);
        app_destroy_sensitive_file(app_private_key_path($usuarioId));
    }
}

if (!function_exists('app_load_private_key_pem')) {
    function app_load_private_key_pem($usuarioId)
    {
        $encryptedPath = app_private_key_encrypted_path($usuarioId);
        if (is_file($encryptedPath)) {
            $encrypted = trim((string) file_get_contents($encryptedPath));
            if ($encrypted === '') {
                throw new RuntimeException('La llave privada cifrada esta vacia.');
            }

            return app_decrypt($encrypted, app_private_key_aad($usuarioId));
        }

        $legacyPath = app_private_key_path($usuarioId);
        if (is_file($legacyPath)) {
            $privatePem = (string) file_get_contents($legacyPath);
            if (trim($privatePem) !== '') {
                app_store_private_key_pem($usuarioId, $privatePem);
                return $privatePem;
            }
        }

        return false;
    }
}

if (!function_exists('app_write_temp_private_key')) {
    function app_write_temp_private_key($privatePem)
    {
        $path = app_prepare_key_storage_dir()
            . DIRECTORY_SEPARATOR
            . 'firma_priv_' . bin2hex(random_bytes(8)) . '.pem';

        file_put_contents($path, $privatePem, LOCK_EX);
        @chmod($path, 0600);

        return $path;
    }
}

if (!function_exists('app_public_key_from_private_pem')) {
    function app_public_key_from_private_pem($usuarioId, $privatePem)
    {
        $privateTempPath = app_write_temp_private_key($privatePem);
        $publicPath = app_public_key_path($usuarioId);

        try {
            $command = app_shell_arg(app_openssl_binary())
                . ' ec -in ' . app_shell_arg($privateTempPath)
                . ' -pubout -out ' . app_shell_arg($publicPath)
                . ' 2>NUL';
            exec($command, $output, $code);

            if ($code !== 0 || !is_file($publicPath)) {
                throw new RuntimeException('No fue posible generar la llave publica de firma.');
            }

            return (string) file_get_contents($publicPath);
        } finally {
            app_destroy_sensitive_file($privateTempPath);
        }
    }
}

if (!function_exists('app_ensure_signature_key')) {
    function app_ensure_signature_key($usuarioId)
    {
        $usuarioId = (int) $usuarioId;
        $dir = app_prepare_key_storage_dir();
        $privatePem = app_load_private_key_pem($usuarioId);

        if ($privatePem === false || trim($privatePem) === '') {
            $generatedPrivatePath = $dir
                . DIRECTORY_SEPARATOR
                . 'usuario_' . $usuarioId . '_ecdsa_p256_' . bin2hex(random_bytes(4)) . '.pem';
            $command = app_shell_arg(app_openssl_binary())
                . ' ecparam -name prime256v1 -genkey -noout -out '
                . app_shell_arg($generatedPrivatePath);
            exec($command, $output, $code);

            if ($code !== 0 || !is_file($generatedPrivatePath)) {
                throw new RuntimeException('No fue posible generar la llave privada de firma.');
            }

            $privatePem = (string) file_get_contents($generatedPrivatePath);
            app_destroy_sensitive_file($generatedPrivatePath);
            app_store_private_key_pem($usuarioId, $privatePem);
        }

        $publicPath = app_public_key_path($usuarioId);
        $publicPem = is_file($publicPath) ? (string) file_get_contents($publicPath) : '';
        if (trim($publicPem) === '') {
            $publicPem = app_public_key_from_private_pem($usuarioId, $privatePem);
        }

        $fingerprint = hash('sha256', $publicPem, true);

        $pdo = app_db();
        $stmt = $pdo->prepare(
            'SELECT id
             FROM llaves_publicas_usuario
             WHERE usuario_id = :usuario_id
               AND fingerprint_sha256 = :fingerprint
             LIMIT 1'
        );
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'fingerprint' => $fingerprint,
        ]);
        $keyId = $stmt->fetchColumn();

        if (!$keyId) {
            $pdo->prepare(
                'INSERT INTO llaves_publicas_usuario (
                    usuario_id,
                    algoritmo,
                    llave_publica,
                    fingerprint_sha256,
                    activa
                 ) VALUES (
                    :usuario_id,
                    "ECDSA_P256",
                    :llave_publica,
                    :fingerprint,
                    1
                 )'
            )->execute([
                'usuario_id' => $usuarioId,
                'llave_publica' => $publicPem,
                'fingerprint' => $fingerprint,
            ]);
            $keyId = app_db()->lastInsertId();
        }

        return [
            'id' => (int) $keyId,
            'private_key_encrypted_path' => app_private_key_encrypted_path($usuarioId),
            'public_key' => $publicPem,
            'fingerprint' => $fingerprint,
            'algorithm' => 'ECDSA_P256',
        ];
    }
}

if (!function_exists('app_canonicalize_json_value')) {
    function app_canonicalize_json_value($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        $keys = array_keys($value);
        $isList = $keys === range(0, count($value) - 1);

        if (!$isList) {
            ksort($value, SORT_STRING);
        }

        foreach ($value as $key => $item) {
            $value[$key] = app_canonicalize_json_value($item);
        }

        return $value;
    }
}

if (!function_exists('app_canonical_json')) {
    function app_canonical_json(array $payload)
    {
        return json_encode(
            app_canonicalize_json_value($payload),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }
}

if (!function_exists('app_sign_payload')) {
    function app_sign_payload($entidadTipo, $entidadId, array $payload)
    {
        if (empty($_SESSION['usuario_id'])) {
            throw new RuntimeException('No hay usuario autenticado para firmar.');
        }

        $usuarioId = (int) $_SESSION['usuario_id'];
        $payload['firmante_usuario_id'] = (int) $_SESSION['usuario_id'];
        $payload['firmado_en'] = date('c');

        $payloadJson = app_canonical_json($payload);
        $payloadHash = hash('sha256', $payloadJson, true);
        $key = app_ensure_signature_key($usuarioId);
        $privatePem = app_load_private_key_pem($usuarioId);

        if ($privatePem === false || trim($privatePem) === '') {
            throw new RuntimeException('No hay llave privada de firma disponible.');
        }

        $tmpBase = app_key_storage_dir() . DIRECTORY_SEPARATOR . 'firma_' . bin2hex(random_bytes(8));
        $payloadPath = $tmpBase . '.json';
        $signaturePath = $tmpBase . '.sig';
        $privateTempPath = app_write_temp_private_key($privatePem);

        try {
            file_put_contents($payloadPath, $payloadJson, LOCK_EX);

            $command = app_shell_arg(app_openssl_binary())
                . ' dgst -sha256 -sign ' . app_shell_arg($privateTempPath)
                . ' -out ' . app_shell_arg($signaturePath)
                . ' ' . app_shell_arg($payloadPath)
                . ' 2>NUL';
            exec($command, $output, $code);

            if ($code !== 0 || !is_file($signaturePath)) {
                throw new RuntimeException('No fue posible firmar el payload.');
            }

            $signature = file_get_contents($signaturePath);
        } finally {
            app_destroy_sensitive_file($payloadPath);
            app_destroy_sensitive_file($signaturePath);
            app_destroy_sensitive_file($privateTempPath);
        }

        $pdo = app_db();
        $pdo->prepare(
            'INSERT INTO firmas_digitales (
                usuario_id,
                llave_publica_id,
                entidad_tipo,
                entidad_id,
                algoritmo,
                payload_json,
                payload_hash,
                firma,
                firma_base64,
                verificado_en,
                valido
             ) VALUES (
                :usuario_id,
                :llave_publica_id,
                :entidad_tipo,
                :entidad_id,
                "ECDSA_P256",
                :payload_json,
                :payload_hash,
                :firma,
                :firma_base64,
                NOW(),
                1
             )'
        )->execute([
            'usuario_id' => $_SESSION['usuario_id'],
            'llave_publica_id' => $key['id'],
            'entidad_tipo' => $entidadTipo,
            'entidad_id' => $entidadId,
            'payload_json' => $payloadJson,
            'payload_hash' => $payloadHash,
            'firma' => $signature,
            'firma_base64' => base64_encode($signature),
        ]);

        return (int) $pdo->lastInsertId();
    }
}

if (!function_exists('app_verify_signature')) {
    function app_verify_signature($firmaId)
    {
        $stmt = app_db()->prepare(
            'SELECT
                f.id,
                f.algoritmo,
                f.payload_json,
                f.payload_hash,
                f.firma,
                l.algoritmo AS llave_algoritmo,
                l.llave_publica
             FROM firmas_digitales f
             INNER JOIN llaves_publicas_usuario l ON l.id = f.llave_publica_id
             WHERE f.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => (int) $firmaId]);
        $firma = $stmt->fetch();

        if (!$firma || $firma['algoritmo'] !== 'ECDSA_P256' || $firma['llave_algoritmo'] !== 'ECDSA_P256') {
            return false;
        }

        $decodedPayload = json_decode((string) $firma['payload_json'], true);
        if (!is_array($decodedPayload)) {
            return false;
        }

        $payloadJson = app_canonical_json($decodedPayload);
        $payloadHash = hash('sha256', $payloadJson, true);
        if (!hash_equals((string) $firma['payload_hash'], $payloadHash)) {
            return false;
        }

        $tmpBase = app_prepare_key_storage_dir()
            . DIRECTORY_SEPARATOR
            . 'verifica_' . bin2hex(random_bytes(8));
        $payloadPath = $tmpBase . '.json';
        $signaturePath = $tmpBase . '.sig';
        $publicPath = $tmpBase . '.pub';

        try {
            file_put_contents($payloadPath, $payloadJson, LOCK_EX);
            file_put_contents($signaturePath, $firma['firma'], LOCK_EX);
            file_put_contents($publicPath, $firma['llave_publica'], LOCK_EX);

            $command = app_shell_arg(app_openssl_binary())
                . ' dgst -sha256 -verify ' . app_shell_arg($publicPath)
                . ' -signature ' . app_shell_arg($signaturePath)
                . ' ' . app_shell_arg($payloadPath)
                . ' 2>NUL';
            exec($command, $output, $code);

            return $code === 0;
        } finally {
            app_destroy_sensitive_file($payloadPath);
            app_destroy_sensitive_file($signaturePath);
            app_destroy_sensitive_file($publicPath);
        }
    }
}

if (!function_exists('app_status_text')) {
    function app_status_text($status)
    {
        $labels = [
            'borrador' => 'Borrador',
            'pendiente_revision' => 'Pendiente de revision',
            'observado' => 'Observado',
            'validado' => 'Validado',
            'rechazado' => 'Rechazado',
            'cancelado' => 'Cancelado',
            'enviado' => 'Enviado',
            'en_revision_profesor' => 'En revision del profesor',
            'observado_profesor' => 'Observado por profesor',
            'aprobado_profesor' => 'Aprobado por profesor',
            'en_validacion_admin' => 'En validacion administrativa',
            'aprobado' => 'Aprobado',
            'activo' => 'Activo',
            'concluido' => 'Concluido',
            'registrada' => 'Registrada',
            'validada' => 'Validada',
            'observada' => 'Observada',
            'rechazada' => 'Rechazada',
            'pendiente_verificacion' => 'Pendiente de verificacion',
            'bloqueado' => 'Bloqueado',
            'inactivo' => 'Inactivo',
        ];

        return $labels[$status] ?? (string) $status;
    }
}

if (!function_exists('app_badge_class')) {
    function app_badge_class($status)
    {
        $classes = [
            'validado' => 'text-bg-success',
            'aprobado' => 'text-bg-success',
            'aprobado_profesor' => 'text-bg-success',
            'activo' => 'text-bg-success',
            'concluido' => 'text-bg-success',
            'validada' => 'text-bg-success',
            'pendiente_verificacion' => 'text-bg-warning',
            'pendiente_revision' => 'text-bg-warning',
            'enviado' => 'text-bg-warning',
            'en_revision_profesor' => 'text-bg-warning',
            'en_validacion_admin' => 'text-bg-warning',
            'registrada' => 'text-bg-warning',
            'observado' => 'text-bg-info',
            'observado_profesor' => 'text-bg-info',
            'observada' => 'text-bg-info',
            'rechazado' => 'text-bg-danger',
            'rechazada' => 'text-bg-danger',
            'bloqueado' => 'text-bg-danger',
            'inactivo' => 'text-bg-secondary',
            'cancelado' => 'text-bg-secondary',
            'borrador' => 'text-bg-secondary',
        ];

        return $classes[$status] ?? 'text-bg-secondary';
    }
}
