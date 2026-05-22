# Base de datos del Sistema de Servicio Social

Archivo principal:

```text
database/servicio_social_mysql8.sql
```

Base de datos creada:

```sql
servicio_social_universidad
```

## Importar desde phpMyAdmin

1. Abre XAMPP.
2. Inicia MySQL.
3. Entra a `http://localhost/phpmyadmin`.
4. Ve a **Importar**.
5. Selecciona `database/servicio_social_mysql8.sql`.
6. Ejecuta la importacion.

## Importar desde terminal en XAMPP

Con MySQL iniciado:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root --execute="source C:/xampp/htdocs/Servicio/database/servicio_social_mysql8.sql"
```

## Crear datos iniciales

Despues de importar la base, ejecuta:

```powershell
php database\seed_inicial.php
```

Esto crea:

- Un administrador inicial.
- Un profesor inicial.
- Un departamento, carrera, dependencia y programa base.
- Llaves publicas para firma digital ECDSA P-256.

Credenciales iniciales:

```text
Administrador: admin@universidad.local / AdminServicio2026!
Profesor: profesor@universidad.local / ProfesorServicio2026!
```

Cambia estas contrasenas desde la seccion **Seguridad** al entrar.

## Contenido

- Usuarios con roles: alumno, profesor y administrador.
- Preregistro de alumnos.
- Dashboard por rol mediante vistas SQL.
- Registro de servicio social.
- Actividades y horas sin archivos adjuntos.
- Tokens de seguridad.
- Sesiones.
- Historial de passwords.
- Llaves publicas.
- Firmas digitales.
- Validaciones firmables.
- Bitacora de auditoria con hash encadenado.

## Nota

El archivo esta preparado para MySQL 8.0+. Tu XAMPP actual detectado usa MariaDB 10.4 como motor local, por eso conviene probar la importacion en el motor definitivo antes de entregar el proyecto.
