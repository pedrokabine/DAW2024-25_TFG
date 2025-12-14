<?php
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';

// Solo usuarios logueados
requerirLogin();

$pdo = obtenerConexion();
$idUsuario = $_SESSION['id_usuario'];

$errores   = [];
$entradas  = [];
$emociones = [];

$mensajeExito = '';

if (isset($_GET['msg']) && $_GET['msg'] === 'eliminada') {
    $mensajeExito = "La entrada se ha eliminado correctamente.";
}


// Valores de filtro (GET)
$fechaDesde = $_GET['fecha_desde'] ?? '';
$fechaHasta = $_GET['fecha_hasta'] ?? '';
$idEmocionFiltro = $_GET['id_emocion'] ?? '';

// 1. Cargar emociones para el select de filtro
try {
    $stmt = $pdo->query(
        "SELECT id_emocion, nombre
         FROM emocion
         ORDER BY valor_numerico ASC"
    );
    $emociones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errores[] = "No se han podido cargar las emociones.";
}

try {
    // Consulta base
    $sql = "
        SELECT e.id_entrada,
               e.fecha,
               e.reflexion,
               em.nombre AS emocion
        FROM entrada e
        INNER JOIN emocion em ON e.id_emocion = em.id_emocion
        WHERE e.id_usuario = ?
    ";

    $parametros = [$idUsuario];

    // Filtro por fecha desde
    if ($fechaDesde !== '') {
        $sql .= " AND e.fecha >= ? ";
        $parametros[] = $fechaDesde;
    }

    // Filtro por fecha hasta
    if ($fechaHasta !== '') {
        $sql .= " AND e.fecha <= ? ";
        $parametros[] = $fechaHasta;
    }

    // Filtro por emoción
    if ($idEmocionFiltro !== '') {
        $sql .= " AND e.id_emocion = ? ";
        $parametros[] = $idEmocionFiltro;
    }

    $sql .= " ORDER BY e.fecha DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);
    $entradas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errores[] = "No se ha podido cargar el historial de entradas.";
}


include 'includes/header.php';
?>

<div class="mb-4">
    <h1 class="h3">Historial de entradas</h1>
    <p class="text-muted mb-0">
        Puedes filtrar tus entradas por fecha y por emoción principal del día.
    </p>
</div>

<!-- Filtros -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form class="row g-3" method="get" action="historial.php">
            <div class="col-md-3">
                <label for="fecha_desde" class="form-label">Desde</label>
                <input
                    type="date"
                    class="form-control"
                    id="fecha_desde"
                    name="fecha_desde"
                    value="<?php echo htmlspecialchars($fechaDesde); ?>"
                >
            </div>

            <div class="col-md-3">
                <label for="fecha_hasta" class="form-label">Hasta</label>
                <input
                    type="date"
                    class="form-control"
                    id="fecha_hasta"
                    name="fecha_hasta"
                    value="<?php echo htmlspecialchars($fechaHasta); ?>"
                >
            </div>

            <div class="col-md-3">
                <label for="id_emocion" class="form-label">Emoción</label>
                <select
                    class="form-select"
                    id="id_emocion"
                    name="id_emocion"
                >
                    <option value="">Todas</option>
                    <?php foreach ($emociones as $emocion): ?>
                        <option
                            value="<?php echo $emocion['id_emocion']; ?>"
                            <?php echo ($idEmocionFiltro == $emocion['id_emocion']) ? 'selected' : ''; ?>
                        >
                            <?php echo htmlspecialchars($emocion['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100">Aplicar filtros</button>
            </div>

            <div class="col-12 d-flex justify-content-between">
                <small class="text-muted">
                    Si dejas los campos vacíos, se mostrarán todas las entradas.
                </small>
                <a href="historial.php" class="btn btn-link btn-sm">Quitar filtros</a>
            </div>
        </form>
    </div>
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

<!--Mensaje de exito si se borra-->

<?php if ($mensajeExito !== ''): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($mensajeExito); ?>
    </div>
<?php endif; ?>


<?php if (empty($entradas) && empty($errores)): ?>
    <div class="alert alert-info">
        No se han encontrado entradas con los filtros seleccionados.
        Prueba a cambiar el rango de fechas o la emoción.
    </div>
<?php else: ?>
    <div class="list-group">
        <?php foreach ($entradas as $entrada): ?>
            <?php
                $fechaBonita = formatearFecha($entrada['fecha']);
                $texto = $entrada['reflexion'] ?? '';
                if (mb_strlen($texto) > 120) {
                    $texto = mb_substr($texto, 0, 120) . '...';
                }
            ?>
            <a href="entrada_detalle.php?id=<?php echo $entrada['id_entrada']; ?>"
               class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between">
                    <h2 class="h6 mb-1">
                        <?php echo htmlspecialchars($fechaBonita); ?>
                    </h2>
                    <span class="badge bg-primary">
                        <?php echo htmlspecialchars($entrada['emocion']); ?>
                    </span>
                </div>
                <p class="mb-1 text-muted">
                    <?php echo nl2br(htmlspecialchars($texto)); ?>
                </p>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="mt-4">
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">Volver al inicio</a>
</div>

<?php include 'includes/footer.php'; ?>
