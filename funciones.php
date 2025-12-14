<?php

//funciones de ayuda (lo del mail se intento pero no funcionó, no llega ningún correo)

function enviarCorreoBienvenida($email, $nombre)
{
    $asunto = "Bienvenido/a a tu Diario Personal Digital";

    $mensaje = "Hola " . $nombre . ",\r\n\r\n"
             . "Gracias por registrarte en el Diario Personal Digital.\r\n"
             . "A partir de ahora podrás anotar cómo te sientes, qué has vivido "
             . "y por qué te sientes agradecido/a cada día.\r\n\r\n"
             . "Te recomendamos iniciar sesión y crear tu primera entrada hoy mismo.\r\n\r\n"
             . "Un saludo,\r\n"
             . "El equipo de Diario Personal Digital";

    $cabeceras = "From: Diario Personal Digital <noreply@diariopersonaldigital.local>\r\n"
               . "Reply-To: noreply@diariopersonaldigital.local\r\n"
               . "Content-Type: text/plain; charset=utf-8\r\n";

    @mail($email, $asunto, $mensaje, $cabeceras);
}


 // Comprobamos si hay un usuario autenticado.

function usuarioAutenticado(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['id_usuario']);
}


 //Redirigimos a login si no hay usuario en sesión.

function requerirLogin(): void
{
    if (!usuarioAutenticado()) {
        header('Location: login.php');
        exit;
    }
}


//Devolvemos una fecha en formato dd/mm/aaaa para mostrar al usuario.

function formatearFecha($fecha)
{
    if (!$fecha) {
        return '';
    }

    $timestamp = strtotime($fecha);
    if ($timestamp === false) {
        return $fecha; // por si viene algo raro, devolvemos tal cual
    }

    return date('d/m/Y', $timestamp);
}

