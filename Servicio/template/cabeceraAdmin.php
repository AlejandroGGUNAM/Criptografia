<?php
require_once __DIR__ . '/layout.php';

$tituloPagina = $tituloPagina ?? 'Administrador | Servicio Social';
$paginaActiva = $paginaActiva ?? null;
$basePath = $basePath ?? '../..';

ss_render_header([
    'base_path' => $basePath,
    'role_code' => 'admin',
    'role_name' => 'Administrador',
    'title' => $tituloPagina,
    'active' => $paginaActiva,
    'theme' => 'bg-dark',
    'home' => 'usuarios/admin/menu.php',
    'profile' => 'usuarios/admin/perfil.php',
    'security' => 'usuarios/admin/seguridad.php',
    'logout' => 'logout.php',
    'nav' => [
        [
            'key' => 'dashboard',
            'label' => 'Dashboard',
            'href' => 'usuarios/admin/menu.php',
        ],
        [
            'key' => 'usuarios',
            'label' => 'Usuarios',
            'href' => 'usuarios/admin/usuarios.php',
        ],
        [
            'key' => 'preregistros',
            'label' => 'Preregistros',
            'href' => 'usuarios/admin/preregistros.php',
        ],
        [
            'key' => 'servicios_sociales',
            'label' => 'Servicios sociales',
            'href' => 'usuarios/admin/servicios_sociales.php',
        ],
        [
            'key' => 'catalogos',
            'label' => 'Catalogos',
            'items' => [
                [
                    'key' => 'carreras',
                    'label' => 'Carreras',
                    'href' => 'usuarios/admin/carreras.php',
                ],
                [
                    'key' => 'departamentos',
                    'label' => 'Departamentos',
                    'href' => 'usuarios/admin/departamentos.php',
                ],
                [
                    'key' => 'ciclos_escolares',
                    'label' => 'Ciclos escolares',
                    'href' => 'usuarios/admin/ciclos_escolares.php',
                ],
                [
                    'key' => 'dependencias',
                    'label' => 'Dependencias',
                    'href' => 'usuarios/admin/dependencias.php',
                ],
                [
                    'key' => 'programas',
                    'label' => 'Programas',
                    'href' => 'usuarios/admin/programas.php',
                ],
            ],
        ],
        [
            'key' => 'seguridad',
            'label' => 'Seguridad',
            'items' => [
                [
                    'key' => 'tokens',
                    'label' => 'Tokens',
                    'href' => 'usuarios/admin/tokens.php',
                ],
                [
                    'key' => 'llaves_publicas',
                    'label' => 'Llaves publicas',
                    'href' => 'usuarios/admin/llaves_publicas.php',
                ],
                [
                    'key' => 'firmas_digitales',
                    'label' => 'Firmas digitales',
                    'href' => 'usuarios/admin/firmas_digitales.php',
                ],
                [
                    'key' => 'configuracion_seguridad',
                    'label' => 'Configuracion',
                    'href' => 'usuarios/admin/configuracion_seguridad.php',
                ],
            ],
        ],
        [
            'key' => 'auditoria',
            'label' => 'Auditoria',
            'href' => 'usuarios/admin/auditoria.php',
        ],
    ],
]);
