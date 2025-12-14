<?php
require_once __DIR__ . '/config.php';

function obtenerConexion()
{
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        // En un proyecto real no se mostraría el mensaje completo,
        // pero aquí ayuda para depurar mientras aprendo.
        die('Error de conexión a la base de datos: ' . $e->getMessage());
    }
}
