<?php
require_once __DIR__ . '/layout.php';

$tituloPagina = $tituloPagina ?? 'Profesor | Servicio Social';
$paginaActiva = $paginaActiva ?? null;
$basePath = $basePath ?? '../..';

ss_render_header([
    'base_path' => $basePath,
    'role_code' => 'profesor',
    'role_name' => 'Profesor',
    'title' => $tituloPagina,
    'active' => $paginaActiva,
    'theme' => 'bg-primary',
    'home' => 'usuarios/profesor/menu.php',
    'profile' => 'usuarios/profesor/perfil.php',
    'security' => 'usuarios/profesor/seguridad.php',
    'logout' => 'logout.php',
    'nav' => [
        [
            'key' => 'dashboard',
            'label' => 'Dashboard',
            'href' => 'usuarios/profesor/menu.php',
        ],
        [
            'key' => 'alumnos_asignados',
            'label' => 'Alumnos asignados',
            'href' => 'usuarios/profesor/alumnos_asignados.php',
        ],
        [
            'key' => 'actividades',
            'label' => 'Actividades',
            'href' => 'usuarios/profesor/actividades.php',
        ],
        [
            'key' => 'firmas',
            'label' => 'Firmas',
            'href' => 'usuarios/profesor/firmas.php',
        ],
    ],
]);
