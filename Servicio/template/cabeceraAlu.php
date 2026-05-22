<?php
require_once __DIR__ . '/layout.php';

$tituloPagina = $tituloPagina ?? 'Alumno | Servicio Social';
$paginaActiva = $paginaActiva ?? null;
$basePath = $basePath ?? '../..';

ss_render_header([
    'base_path' => $basePath,
    'role_code' => 'alumno',
    'role_name' => 'Alumno',
    'title' => $tituloPagina,
    'active' => $paginaActiva,
    'theme' => 'bg-success',
    'home' => 'usuarios/alumno/menu.php',
    'profile' => 'usuarios/alumno/perfil.php',
    'security' => 'usuarios/alumno/seguridad.php',
    'logout' => 'logout.php',
    'nav' => [
        [
            'key' => 'dashboard',
            'label' => 'Inicio',
            'href' => 'usuarios/alumno/menu.php',
        ],
        [
            'key' => 'preregistro',
            'label' => 'Preregistro',
            'href' => 'usuarios/alumno/preregistro.php',
        ],
        [
            'key' => 'servicio_social',
            'label' => 'Mi servicio social',
            'href' => 'usuarios/alumno/servicio_social.php',
        ],
        [
            'key' => 'actividades',
            'label' => 'Actividades y horas',
            'href' => 'usuarios/alumno/actividades.php',
        ],
        [
            'key' => 'seguimiento',
            'label' => 'Seguimiento',
            'href' => 'usuarios/alumno/seguimiento.php',
        ],
        [
            'key' => 'constancias',
            'label' => 'Constancias',
            'href' => 'usuarios/alumno/constancias.php',
        ],
    ],
]);
