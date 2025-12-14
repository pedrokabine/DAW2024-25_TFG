<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Eliminamos todos los datos de la sesión
session_unset();
session_destroy();

// Redirigimos a la página de inicio
header('Location: index.php');
exit;
