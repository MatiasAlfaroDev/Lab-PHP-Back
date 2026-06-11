<?php

// Evita que Symfony interprete "/api" como base path y recorte
// el prefijo de las rutas /api/* (el front controller vive en /api/index.php).
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/../public/index.php';

require __DIR__.'/../public/index.php';
