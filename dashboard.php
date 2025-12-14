<?php
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';

requerirLogin();

$pdo          = obtenerConexion();
$idUsuario    = $_SESSION['id_usuario'];
$nombreUsuario = $_SESSION['nombre_usuario'] ?? 'Usuario';

$totalEntradas = 0;
$ultimaEntrada = null;
$errores       = [];

// el Total de entradas y fecha de la última
try {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS total,
                MAX(fecha) AS ultima_fecha
         FROM entrada
         WHERE id_usuario = ?"
    );
    $stmt->execute([$idUsuario]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $totalEntradas = (int)$row['total'];
        $fechaUltima   = $row['ultima_fecha'];
    }

    // Detalles de la última entrada (fecha + emoción)
    if ($totalEntradas > 0 && $fechaUltima) {
        $stmt2 = $pdo->prepare(
            "SELECT e.fecha, em.nombre AS emocion
             FROM entrada e
             INNER JOIN emocion em ON e.id_emocion = em.id_emocion
             WHERE e.id_usuario = ?
             ORDER BY e.fecha DESC
             LIMIT 1"
        );
        $stmt2->execute([$idUsuario]);
        $ultimaEntrada = $stmt2->fetch(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $errores[] = "No se han podido cargar los datos de resumen.";
}

include 'includes/header.php';
?>

<div class="mb-4">
    <h1 class="h3">Hola, <?php echo htmlspecialchars($nombreUsuario); ?></h1>
    <p class="text-muted mb-0">
        Bienvenido/a a tu Diario Personal Digital. Desde aquí puedes crear nuevas entradas,
        revisar tu historial o consultar tus estadísticas emocionales.
    </p>
</div>

<?php if (!empty($errores)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errores as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Resumen rápido de tu estado segun como entres en la aplicacion -->
<div class="card mb-4 shadow-sm">
    <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
        <div>
            <h2 class="h6 text-muted mb-2">Resumen de tu diario</h2>
            <?php if ($totalEntradas === 0): ?>
                <p class="mb-0">
                    Todavía no has escrito ninguna entrada. Empieza hoy con tu primera reflexión.
                </p>
            <?php else: ?>
                <p class="mb-1">
                    Has registrado <strong><?php echo $totalEntradas; ?></strong> entrada<?php echo $totalEntradas > 1 ? 's' : ''; ?> en tu diario.
                </p>
                <?php if ($ultimaEntrada): ?>
                    <p class="mb-0 text-muted">
                        Tu última entrada fue el <strong><?php echo formatearFecha($ultimaEntrada['fecha']); ?></strong>
                        con la emoción <strong><?php echo htmlspecialchars($ultimaEntrada['emocion']); ?></strong>.
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="mt-3 mt-md-0">
            <a href="estadisticas.php" class="btn btn-outline-primary btn-sm">
                Ver estadísticas
            </a>
        </div>
    </div>
</div>

<!-- Tarjetas principales  que muestran las opciones disponibles de la aplicacion-->
<div class="row g-3">
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h2 class="h5 card-title">Nueva entrada</h2>
                <p class="card-text text-muted">
                    Anota cómo te sientes hoy, qué has vivido y qué agradeces.
                </p>
                <a href="nueva_entrada.php" class="btn btn-primary btn-sm">Escribir ahora</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h2 class="h5 card-title">Historial</h2>
                <p class="card-text text-muted">
                    Revisa tus entradas anteriores y observa tu evolución con filtros por fecha y emoción.
                </p>
                <a href="historial.php" class="btn btn-outline-primary btn-sm">Ver historial</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h2 class="h5 card-title">Perfil</h2>
                <p class="card-text text-muted">
                    Actualiza tus datos básicos de usuario y cambia tu contraseña cuando lo necesites.
                </p>
                <a href="perfil.php" class="btn btn-outline-primary btn-sm">Ir al perfil</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
