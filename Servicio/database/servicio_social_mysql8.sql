-- Sistema Seguro de Servicio Social Universitario
-- Motor objetivo: MySQL 8.0+
-- Nota: no incluye modulo de subida de documentos en esta etapa.

CREATE DATABASE IF NOT EXISTS servicio_social_universidad
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE servicio_social_universidad;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
-- Catalogos de seguridad y acceso
-- =========================================================

CREATE TABLE IF NOT EXISTS roles (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo VARCHAR(40) NOT NULL,
  nombre VARCHAR(80) NOT NULL,
  descripcion VARCHAR(255) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_roles_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permisos (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo VARCHAR(80) NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  descripcion VARCHAR(255) NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_permisos_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS roles_permisos (
  rol_id BIGINT UNSIGNED NOT NULL,
  permiso_id BIGINT UNSIGNED NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (rol_id, permiso_id),
  CONSTRAINT fk_roles_permisos_rol
    FOREIGN KEY (rol_id) REFERENCES roles(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_roles_permisos_permiso
    FOREIGN KEY (permiso_id) REFERENCES permisos(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- Catalogos academicos
-- =========================================================

CREATE TABLE IF NOT EXISTS departamentos (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(160) NOT NULL,
  codigo VARCHAR(40) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_departamentos_codigo (codigo),
  UNIQUE KEY uq_departamentos_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS carreras (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  departamento_id BIGINT UNSIGNED NULL,
  nombre VARCHAR(180) NOT NULL,
  codigo VARCHAR(40) NULL,
  creditos_totales SMALLINT UNSIGNED NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_carreras_codigo (codigo),
  KEY idx_carreras_departamento (departamento_id),
  CONSTRAINT fk_carreras_departamento
    FOREIGN KEY (departamento_id) REFERENCES departamentos(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT chk_carreras_creditos_totales
    CHECK (creditos_totales IS NULL OR creditos_totales > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ciclos_escolares (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(60) NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ciclos_nombre (nombre),
  CONSTRAINT chk_ciclos_fechas
    CHECK (fecha_fin >= fecha_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- Usuarios
-- =========================================================

CREATE TABLE IF NOT EXISTS usuarios (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  rol_id BIGINT UNSIGNED NOT NULL,
  correo VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  nombres VARCHAR(120) NOT NULL,
  apellido_paterno VARCHAR(120) NOT NULL,
  apellido_materno VARCHAR(120) NULL,
  telefono VARCHAR(25) NULL,
  estatus ENUM('pendiente_verificacion','activo','bloqueado','inactivo') NOT NULL DEFAULT 'pendiente_verificacion',
  correo_verificado_en TIMESTAMP NULL,
  ultimo_acceso_en TIMESTAMP NULL,
  intentos_fallidos SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  bloqueado_hasta TIMESTAMP NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_correo (correo),
  KEY idx_usuarios_rol (rol_id),
  KEY idx_usuarios_estatus (estatus),
  CONSTRAINT fk_usuarios_rol
    FOREIGN KEY (rol_id) REFERENCES roles(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_usuarios_intentos
    CHECK (intentos_fallidos <= 20)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS alumnos (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id BIGINT UNSIGNED NOT NULL,
  carrera_id BIGINT UNSIGNED NULL,
  matricula VARCHAR(40) NOT NULL,
  semestre_actual TINYINT UNSIGNED NULL,
  creditos_aprobados SMALLINT UNSIGNED NULL,
  promedio DECIMAL(5,2) NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_alumnos_usuario (usuario_id),
  UNIQUE KEY uq_alumnos_matricula (matricula),
  KEY idx_alumnos_carrera (carrera_id),
  CONSTRAINT fk_alumnos_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_alumnos_carrera
    FOREIGN KEY (carrera_id) REFERENCES carreras(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT chk_alumnos_semestre
    CHECK (semestre_actual IS NULL OR semestre_actual BETWEEN 1 AND 20),
  CONSTRAINT chk_alumnos_creditos
    CHECK (creditos_aprobados IS NULL OR creditos_aprobados <= 999),
  CONSTRAINT chk_alumnos_promedio
    CHECK (promedio IS NULL OR promedio BETWEEN 0 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS profesores (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id BIGINT UNSIGNED NOT NULL,
  departamento_id BIGINT UNSIGNED NULL,
  numero_empleado VARCHAR(40) NOT NULL,
  grado_academico VARCHAR(80) NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_profesores_usuario (usuario_id),
  UNIQUE KEY uq_profesores_numero_empleado (numero_empleado),
  KEY idx_profesores_departamento (departamento_id),
  CONSTRAINT fk_profesores_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_profesores_departamento
    FOREIGN KEY (departamento_id) REFERENCES departamentos(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS administradores (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id BIGINT UNSIGNED NOT NULL,
  area VARCHAR(120) NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_administradores_usuario (usuario_id),
  CONSTRAINT fk_administradores_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- Preregistro
-- =========================================================

CREATE TABLE IF NOT EXISTS preregistros (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  alumno_id BIGINT UNSIGNED NOT NULL,
  ciclo_escolar_id BIGINT UNSIGNED NULL,
  estatus ENUM('borrador','pendiente_revision','observado','validado','rechazado','cancelado') NOT NULL DEFAULT 'borrador',
  porcentaje_creditos DECIMAL(5,2) NULL,
  acepta_aviso_privacidad TINYINT(1) NOT NULL DEFAULT 0,
  enviado_en TIMESTAMP NULL,
  revisado_por BIGINT UNSIGNED NULL,
  revisado_en TIMESTAMP NULL,
  validado_por BIGINT UNSIGNED NULL,
  validado_en TIMESTAMP NULL,
  comentario_general TEXT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_preregistros_alumno (alumno_id),
  KEY idx_preregistros_ciclo (ciclo_escolar_id),
  KEY idx_preregistros_estatus (estatus),
  KEY idx_preregistros_revisado_por (revisado_por),
  KEY idx_preregistros_validado_por (validado_por),
  CONSTRAINT fk_preregistros_alumno
    FOREIGN KEY (alumno_id) REFERENCES alumnos(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_preregistros_ciclo
    FOREIGN KEY (ciclo_escolar_id) REFERENCES ciclos_escolares(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_preregistros_revisado_por
    FOREIGN KEY (revisado_por) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_preregistros_validado_por
    FOREIGN KEY (validado_por) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT chk_preregistros_porcentaje_creditos
    CHECK (porcentaje_creditos IS NULL OR porcentaje_creditos BETWEEN 0 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS preregistro_observaciones (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  preregistro_id BIGINT UNSIGNED NOT NULL,
  usuario_id BIGINT UNSIGNED NOT NULL,
  observacion TEXT NOT NULL,
  visible_para_alumno TINYINT(1) NOT NULL DEFAULT 1,
  creada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_observaciones_preregistro (preregistro_id),
  KEY idx_observaciones_usuario (usuario_id),
  CONSTRAINT fk_observaciones_preregistro
    FOREIGN KEY (preregistro_id) REFERENCES preregistros(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_observaciones_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- Servicio social
-- =========================================================

CREATE TABLE IF NOT EXISTS dependencias (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(200) NOT NULL,
  tipo ENUM('publica','privada','social','universitaria','otra') NOT NULL DEFAULT 'publica',
  rfc VARCHAR(13) NULL,
  direccion VARCHAR(255) NULL,
  telefono VARCHAR(25) NULL,
  correo_contacto VARCHAR(190) NULL,
  responsable_nombre VARCHAR(160) NULL,
  responsable_cargo VARCHAR(120) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_dependencias_nombre (nombre),
  KEY idx_dependencias_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS programas_servicio_social (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  dependencia_id BIGINT UNSIGNED NOT NULL,
  nombre VARCHAR(200) NOT NULL,
  descripcion TEXT NULL,
  cupo_total SMALLINT UNSIGNED NULL,
  cupo_disponible SMALLINT UNSIGNED NULL,
  horas_requeridas SMALLINT UNSIGNED NOT NULL DEFAULT 480,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_programas_dependencia_nombre (dependencia_id, nombre),
  KEY idx_programas_dependencia (dependencia_id),
  CONSTRAINT fk_programas_dependencia
    FOREIGN KEY (dependencia_id) REFERENCES dependencias(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_programas_cupos
    CHECK (
      cupo_total IS NULL
      OR cupo_disponible IS NULL
      OR cupo_disponible <= cupo_total
    ),
  CONSTRAINT chk_programas_horas
    CHECK (horas_requeridas > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS servicios_sociales (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  alumno_id BIGINT UNSIGNED NOT NULL,
  profesor_id BIGINT UNSIGNED NULL,
  dependencia_id BIGINT UNSIGNED NOT NULL,
  programa_id BIGINT UNSIGNED NULL,
  ciclo_escolar_id BIGINT UNSIGNED NULL,
  folio VARCHAR(40) NOT NULL,
  estatus ENUM(
    'borrador',
    'enviado',
    'en_revision_profesor',
    'observado_profesor',
    'aprobado_profesor',
    'en_validacion_admin',
    'aprobado',
    'rechazado',
    'activo',
    'concluido',
    'cancelado'
  ) NOT NULL DEFAULT 'borrador',
  fecha_inicio DATE NULL,
  fecha_fin_programada DATE NULL,
  fecha_fin_real DATE NULL,
  horario TEXT NULL,
  actividades TEXT NULL,
  responsable_externo_nombre VARCHAR(160) NULL,
  responsable_externo_cargo VARCHAR(120) NULL,
  responsable_externo_correo VARCHAR(190) NULL,
  horas_requeridas SMALLINT UNSIGNED NOT NULL DEFAULT 480,
  horas_acumuladas SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  enviado_en TIMESTAMP NULL,
  aprobado_profesor_en TIMESTAMP NULL,
  aprobado_admin_en TIMESTAMP NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_servicios_folio (folio),
  KEY idx_servicios_alumno (alumno_id),
  KEY idx_servicios_profesor (profesor_id),
  KEY idx_servicios_dependencia (dependencia_id),
  KEY idx_servicios_programa (programa_id),
  KEY idx_servicios_ciclo (ciclo_escolar_id),
  KEY idx_servicios_estatus (estatus),
  CONSTRAINT fk_servicios_alumno
    FOREIGN KEY (alumno_id) REFERENCES alumnos(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_servicios_profesor
    FOREIGN KEY (profesor_id) REFERENCES profesores(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_servicios_dependencia
    FOREIGN KEY (dependencia_id) REFERENCES dependencias(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_servicios_programa
    FOREIGN KEY (programa_id) REFERENCES programas_servicio_social(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_servicios_ciclo
    FOREIGN KEY (ciclo_escolar_id) REFERENCES ciclos_escolares(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT chk_servicios_fechas_programadas
    CHECK (
      fecha_inicio IS NULL
      OR fecha_fin_programada IS NULL
      OR fecha_fin_programada >= fecha_inicio
    ),
  CONSTRAINT chk_servicios_fecha_real
    CHECK (
      fecha_inicio IS NULL
      OR fecha_fin_real IS NULL
      OR fecha_fin_real >= fecha_inicio
    ),
  CONSTRAINT chk_servicios_horas
    CHECK (horas_requeridas > 0 AND horas_acumuladas <= horas_requeridas)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS servicio_estado_historial (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  servicio_social_id BIGINT UNSIGNED NOT NULL,
  usuario_id BIGINT UNSIGNED NOT NULL,
  estatus_anterior VARCHAR(40) NULL,
  estatus_nuevo VARCHAR(40) NOT NULL,
  comentario TEXT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_historial_servicio (servicio_social_id),
  KEY idx_historial_usuario (usuario_id),
  CONSTRAINT fk_historial_servicio
    FOREIGN KEY (servicio_social_id) REFERENCES servicios_sociales(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_historial_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS actividades_servicio_social (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  servicio_social_id BIGINT UNSIGNED NOT NULL,
  alumno_id BIGINT UNSIGNED NOT NULL,
  fecha_actividad DATE NOT NULL,
  horas SMALLINT UNSIGNED NOT NULL,
  descripcion TEXT NOT NULL,
  estatus ENUM('registrada','observada','validada','rechazada') NOT NULL DEFAULT 'registrada',
  revisada_por BIGINT UNSIGNED NULL,
  revisada_en TIMESTAMP NULL,
  comentario_revision TEXT NULL,
  creada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_actividades_servicio (servicio_social_id),
  KEY idx_actividades_alumno (alumno_id),
  KEY idx_actividades_estatus (estatus),
  CONSTRAINT fk_actividades_servicio
    FOREIGN KEY (servicio_social_id) REFERENCES servicios_sociales(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_actividades_alumno
    FOREIGN KEY (alumno_id) REFERENCES alumnos(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_actividades_revisada_por
    FOREIGN KEY (revisada_por) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT chk_actividades_horas
    CHECK (horas BETWEEN 1 AND 24)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- Criptografia, tokens y auditoria
-- =========================================================

CREATE TABLE IF NOT EXISTS tokens_seguridad (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id BIGINT UNSIGNED NULL,
  proposito ENUM(
    'verificacion_correo',
    'recuperacion_password',
    'csrf',
    'refresh_sesion',
    'api',
    'firma_operacion'
  ) NOT NULL,
  token_hash BINARY(32) NOT NULL,
  algoritmo_hash VARCHAR(40) NOT NULL DEFAULT 'SHA-256',
  expira_en TIMESTAMP NOT NULL,
  usado_en TIMESTAMP NULL,
  revocado_en TIMESTAMP NULL,
  ip_creacion VARBINARY(16) NULL,
  user_agent_hash BINARY(32) NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tokens_hash (token_hash),
  KEY idx_tokens_usuario (usuario_id),
  KEY idx_tokens_proposito (proposito),
  KEY idx_tokens_expira (expira_en),
  CONSTRAINT fk_tokens_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sesiones_usuario (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id BIGINT UNSIGNED NOT NULL,
  session_id_hash BINARY(32) NOT NULL,
  csrf_secret_hash BINARY(32) NULL,
  ip_hash BINARY(32) NULL,
  user_agent_hash BINARY(32) NULL,
  creada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ultima_actividad_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expira_en TIMESTAMP NOT NULL,
  revocada_en TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_sesiones_hash (session_id_hash),
  KEY idx_sesiones_usuario (usuario_id),
  KEY idx_sesiones_expira (expira_en),
  CONSTRAINT fk_sesiones_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS historial_passwords (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id BIGINT UNSIGNED NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  algoritmo VARCHAR(40) NOT NULL DEFAULT 'Argon2id',
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_historial_passwords_usuario (usuario_id),
  CONSTRAINT fk_historial_passwords_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS llaves_publicas_usuario (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id BIGINT UNSIGNED NOT NULL,
  algoritmo ENUM('Ed25519','ECDSA_P256','RSA_PSS_2048') NOT NULL DEFAULT 'ECDSA_P256',
  llave_publica TEXT NOT NULL,
  fingerprint_sha256 BINARY(32) NOT NULL,
  activa TINYINT(1) NOT NULL DEFAULT 1,
  creada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  revocada_en TIMESTAMP NULL,
  motivo_revocacion VARCHAR(255) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_llaves_fingerprint (fingerprint_sha256),
  KEY idx_llaves_usuario (usuario_id),
  KEY idx_llaves_algoritmo (algoritmo),
  CONSTRAINT fk_llaves_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS firmas_digitales (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id BIGINT UNSIGNED NOT NULL,
  llave_publica_id BIGINT UNSIGNED NOT NULL,
  entidad_tipo ENUM('preregistro','servicio_social','actividad','validacion_preregistro','validacion_servicio') NOT NULL,
  entidad_id BIGINT UNSIGNED NOT NULL,
  algoritmo ENUM('Ed25519','ECDSA_P256','RSA_PSS_2048') NOT NULL DEFAULT 'ECDSA_P256',
  payload_json JSON NOT NULL,
  payload_hash BINARY(32) NOT NULL,
  firma BLOB NOT NULL,
  firma_base64 TEXT NULL,
  firmado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  verificado_en TIMESTAMP NULL,
  valido TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_firmas_usuario (usuario_id),
  KEY idx_firmas_llave (llave_publica_id),
  KEY idx_firmas_entidad (entidad_tipo, entidad_id),
  KEY idx_firmas_payload_hash (payload_hash),
  CONSTRAINT fk_firmas_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_firmas_llave
    FOREIGN KEY (llave_publica_id) REFERENCES llaves_publicas_usuario(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS validaciones_preregistro (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  preregistro_id BIGINT UNSIGNED NOT NULL,
  usuario_validador_id BIGINT UNSIGNED NOT NULL,
  firma_digital_id BIGINT UNSIGNED NULL,
  resultado ENUM('aprobado','rechazado','observado') NOT NULL,
  comentario TEXT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_validaciones_preregistro (preregistro_id),
  KEY idx_validaciones_preregistro_usuario (usuario_validador_id),
  KEY idx_validaciones_preregistro_firma (firma_digital_id),
  CONSTRAINT fk_validaciones_preregistro_preregistro
    FOREIGN KEY (preregistro_id) REFERENCES preregistros(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_validaciones_preregistro_usuario
    FOREIGN KEY (usuario_validador_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_validaciones_preregistro_firma
    FOREIGN KEY (firma_digital_id) REFERENCES firmas_digitales(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS validaciones_servicio_social (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  servicio_social_id BIGINT UNSIGNED NOT NULL,
  usuario_validador_id BIGINT UNSIGNED NOT NULL,
  firma_digital_id BIGINT UNSIGNED NULL,
  etapa ENUM('revision_profesor','validacion_admin','cierre') NOT NULL,
  resultado ENUM('aprobado','rechazado','observado') NOT NULL,
  comentario TEXT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_validaciones_servicio (servicio_social_id),
  KEY idx_validaciones_servicio_usuario (usuario_validador_id),
  KEY idx_validaciones_servicio_firma (firma_digital_id),
  CONSTRAINT fk_validaciones_servicio_servicio
    FOREIGN KEY (servicio_social_id) REFERENCES servicios_sociales(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_validaciones_servicio_usuario
    FOREIGN KEY (usuario_validador_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_validaciones_servicio_firma
    FOREIGN KEY (firma_digital_id) REFERENCES firmas_digitales(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bitacora_auditoria (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id BIGINT UNSIGNED NULL,
  accion VARCHAR(120) NOT NULL,
  entidad_tipo VARCHAR(80) NULL,
  entidad_id BIGINT UNSIGNED NULL,
  ip_hash BINARY(32) NULL,
  user_agent_hash BINARY(32) NULL,
  datos_evento JSON NULL,
  hash_anterior BINARY(32) NULL,
  hash_evento BINARY(32) NOT NULL,
  hmac_evento BINARY(32) NULL,
  algoritmo_hash VARCHAR(40) NOT NULL DEFAULT 'SHA-256',
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_bitacora_hash_evento (hash_evento),
  KEY idx_bitacora_usuario (usuario_id),
  KEY idx_bitacora_entidad (entidad_tipo, entidad_id),
  KEY idx_bitacora_accion (accion),
  KEY idx_bitacora_creado (creado_en),
  CONSTRAINT fk_bitacora_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configuracion_seguridad (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  clave VARCHAR(120) NOT NULL,
  valor VARCHAR(255) NOT NULL,
  descripcion VARCHAR(255) NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_configuracion_seguridad_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- Vistas para dashboards
-- =========================================================

DROP VIEW IF EXISTS vw_alumnos_preregistro;
CREATE VIEW vw_alumnos_preregistro AS
SELECT
  a.id AS alumno_id,
  u.id AS usuario_id,
  a.matricula,
  CONCAT_WS(' ', u.nombres, u.apellido_paterno, u.apellido_materno) AS alumno,
  u.correo,
  c.nombre AS carrera,
  a.semestre_actual,
  p.estatus AS estatus_preregistro,
  p.enviado_en,
  p.validado_en,
  p.actualizado_en
FROM alumnos a
INNER JOIN usuarios u ON u.id = a.usuario_id
LEFT JOIN carreras c ON c.id = a.carrera_id
LEFT JOIN preregistros p ON p.alumno_id = a.id;

DROP VIEW IF EXISTS vw_servicios_sociales_detalle;
CREATE VIEW vw_servicios_sociales_detalle AS
SELECT
  ss.id AS servicio_social_id,
  ss.folio,
  ss.estatus,
  ss.fecha_inicio,
  ss.fecha_fin_programada,
  ss.fecha_fin_real,
  ss.horas_requeridas,
  ss.horas_acumuladas,
  a.matricula,
  CONCAT_WS(' ', ua.nombres, ua.apellido_paterno, ua.apellido_materno) AS alumno,
  ua.correo AS correo_alumno,
  c.nombre AS carrera,
  CONCAT_WS(' ', up.nombres, up.apellido_paterno, up.apellido_materno) AS profesor,
  d.nombre AS dependencia,
  ps.nombre AS programa,
  ce.nombre AS ciclo_escolar,
  ss.actualizado_en
FROM servicios_sociales ss
INNER JOIN alumnos a ON a.id = ss.alumno_id
INNER JOIN usuarios ua ON ua.id = a.usuario_id
LEFT JOIN carreras c ON c.id = a.carrera_id
LEFT JOIN profesores pr ON pr.id = ss.profesor_id
LEFT JOIN usuarios up ON up.id = pr.usuario_id
INNER JOIN dependencias d ON d.id = ss.dependencia_id
LEFT JOIN programas_servicio_social ps ON ps.id = ss.programa_id
LEFT JOIN ciclos_escolares ce ON ce.id = ss.ciclo_escolar_id;

DROP VIEW IF EXISTS vw_dashboard_administrador;
CREATE VIEW vw_dashboard_administrador AS
SELECT
  (SELECT COUNT(*) FROM usuarios WHERE estatus = 'activo') AS usuarios_activos,
  (SELECT COUNT(*) FROM alumnos) AS total_alumnos,
  (SELECT COUNT(*) FROM profesores) AS total_profesores,
  (SELECT COUNT(*) FROM preregistros WHERE estatus = 'pendiente_revision') AS preregistros_pendientes,
  (SELECT COUNT(*) FROM preregistros WHERE estatus = 'observado') AS preregistros_observados,
  (SELECT COUNT(*) FROM preregistros WHERE estatus = 'validado') AS preregistros_validados,
  (SELECT COUNT(*) FROM servicios_sociales WHERE estatus IN ('enviado','en_revision_profesor','en_validacion_admin')) AS servicios_pendientes,
  (SELECT COUNT(*) FROM servicios_sociales WHERE estatus = 'activo') AS servicios_activos,
  (SELECT COUNT(*) FROM servicios_sociales WHERE estatus = 'concluido') AS servicios_concluidos,
  (SELECT COUNT(*) FROM firmas_digitales WHERE valido = 1) AS firmas_validas;

DROP VIEW IF EXISTS vw_dashboard_profesor;
CREATE VIEW vw_dashboard_profesor AS
SELECT
  pr.id AS profesor_id,
  u.id AS usuario_id,
  CONCAT_WS(' ', u.nombres, u.apellido_paterno, u.apellido_materno) AS profesor,
  COUNT(ss.id) AS alumnos_asignados,
  SUM(CASE WHEN ss.estatus IN ('enviado','en_revision_profesor') THEN 1 ELSE 0 END) AS pendientes_revision,
  SUM(CASE WHEN ss.estatus = 'observado_profesor' THEN 1 ELSE 0 END) AS observados,
  SUM(CASE WHEN ss.estatus IN ('aprobado_profesor','en_validacion_admin','aprobado','activo') THEN 1 ELSE 0 END) AS aprobados_o_activos,
  SUM(CASE WHEN ss.estatus = 'concluido' THEN 1 ELSE 0 END) AS concluidos
FROM profesores pr
INNER JOIN usuarios u ON u.id = pr.usuario_id
LEFT JOIN servicios_sociales ss ON ss.profesor_id = pr.id
GROUP BY pr.id, u.id, profesor;

DROP VIEW IF EXISTS vw_dashboard_alumno;
CREATE VIEW vw_dashboard_alumno AS
SELECT
  a.id AS alumno_id,
  u.id AS usuario_id,
  a.matricula,
  CONCAT_WS(' ', u.nombres, u.apellido_paterno, u.apellido_materno) AS alumno,
  p.estatus AS estatus_preregistro,
  ss.id AS servicio_social_id,
  ss.folio,
  ss.estatus AS estatus_servicio,
  ss.horas_requeridas,
  ss.horas_acumuladas,
  ss.actualizado_en AS servicio_actualizado_en
FROM alumnos a
INNER JOIN usuarios u ON u.id = a.usuario_id
LEFT JOIN preregistros p ON p.alumno_id = a.id
LEFT JOIN servicios_sociales ss ON ss.alumno_id = a.id;

-- =========================================================
-- Datos iniciales
-- =========================================================

INSERT INTO roles (id, codigo, nombre, descripcion) VALUES
  (1, 'alumno', 'Alumno', 'Usuario que realiza preregistro y registro de servicio social'),
  (2, 'profesor', 'Profesor', 'Usuario que da seguimiento academico y valida actividades asignadas'),
  (3, 'administrador', 'Administrador', 'Usuario con control operativo del sistema')
ON DUPLICATE KEY UPDATE
  nombre = VALUES(nombre),
  descripcion = VALUES(descripcion),
  activo = 1;

INSERT INTO permisos (codigo, nombre, descripcion) VALUES
  ('dashboard.ver', 'Ver dashboard', 'Acceso al panel principal del rol'),
  ('preregistro.crear', 'Crear preregistro', 'Captura inicial del alumno'),
  ('preregistro.revisar', 'Revisar preregistro', 'Revision administrativa del preregistro'),
  ('preregistro.validar', 'Validar preregistro', 'Validacion final del preregistro'),
  ('servicio.crear', 'Crear servicio social', 'Registro de solicitud de servicio social'),
  ('servicio.revisar', 'Revisar servicio social', 'Revision administrativa del servicio social'),
  ('servicio.validar', 'Validar servicio social', 'Validacion administrativa del servicio social'),
  ('actividad.crear', 'Registrar actividad', 'Registro de actividades y horas sin archivos adjuntos'),
  ('actividad.validar', 'Validar actividad', 'Validacion de actividades y horas'),
  ('firma.crear', 'Crear firma digital', 'Firmar aprobaciones y validaciones'),
  ('auditoria.ver', 'Ver auditoria', 'Consulta de bitacora criptografica'),
  ('usuarios.gestionar', 'Gestionar usuarios', 'Alta, baja y cambios de usuarios'),
  ('catalogos.gestionar', 'Gestionar catalogos', 'Carreras, departamentos, dependencias y programas')
ON DUPLICATE KEY UPDATE
  nombre = VALUES(nombre),
  descripcion = VALUES(descripcion);

INSERT IGNORE INTO roles_permisos (rol_id, permiso_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permisos p ON p.codigo IN (
  'dashboard.ver',
  'preregistro.crear',
  'servicio.crear',
  'actividad.crear',
  'firma.crear'
)
WHERE r.codigo = 'alumno';

INSERT IGNORE INTO roles_permisos (rol_id, permiso_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permisos p ON p.codigo IN (
  'dashboard.ver',
  'actividad.validar'
)
WHERE r.codigo = 'profesor';

DELETE rp
FROM roles_permisos rp
INNER JOIN roles r ON r.id = rp.rol_id
INNER JOIN permisos p ON p.id = rp.permiso_id
WHERE r.codigo = 'profesor'
  AND p.codigo IN ('preregistro.revisar', 'servicio.revisar', 'firma.crear');

INSERT IGNORE INTO roles_permisos (rol_id, permiso_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permisos p ON p.codigo IN (
  'dashboard.ver',
  'preregistro.revisar',
  'preregistro.validar',
  'servicio.revisar',
  'servicio.validar',
  'actividad.validar',
  'firma.crear',
  'auditoria.ver',
  'usuarios.gestionar',
  'catalogos.gestionar'
)
WHERE r.codigo = 'administrador';

INSERT INTO configuracion_seguridad (clave, valor, descripcion) VALUES
  ('password_algoritmo', 'Argon2id', 'Algoritmo recomendado para password_hash en PHP'),
  ('token_algoritmo_hash', 'SHA-256', 'Los tokens se guardan como hash, nunca en texto plano'),
  ('firma_algoritmo_preferido', 'ECDSA_P256', 'Algoritmo elegido para firma digital con OpenSSL y curva P-256'),
  ('cifrado_simetrico_preferido', 'AES-256-GCM', 'Algoritmo elegido para cifrado simetrico de datos sensibles'),
  ('csrf_ttl_minutos', '30', 'Tiempo sugerido para tokens CSRF'),
  ('verificacion_correo_ttl_horas', '24', 'Expiracion de tokens de verificacion de correo'),
  ('recuperacion_password_ttl_minutos', '30', 'Expiracion de tokens para restablecer contrasena'),
  ('sesion_ttl_minutos', '120', 'Tiempo maximo sugerido de sesion inactiva'),
  ('auditoria_hash_chain', 'HMAC-SHA-256', 'Bitacora encadenada con hash anterior y HMAC')
ON DUPLICATE KEY UPDATE
  valor = VALUES(valor),
  descripcion = VALUES(descripcion);
