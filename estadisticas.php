<?php
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';

// Solo usuarios logueados
requerirLogin();

$pdo = obtenerConexion();
$idUsuario = $_SESSION['id_usuario'];

$errores = [];

// Valores por defecto
$totalEntradas = 0;
$totalGratitudes = 0;
$fechaPrimera = null;
$fechaUltima = null;

$diasConEntrada = 0;
$diasConGratitud = 0;
$porcentajeDiasConGratitud = 0;

$porEmocion = [];
$porMes  = [];
$porSemana = [];
try {
    // Total de entradas del usuario
    $stmt = $pdo->prepare(
        "SELECT 
             COUNT(*) AS total,
             MIN(fecha) AS primera,
             MAX(fecha) AS ultima
         FROM entrada
         WHERE id_usuario = ?"
    );
    $stmt->execute([$idUsuario]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $totalEntradas = (int)$row['total'];
        $fechaPrimera  = $row['primera'];
        $fechaUltima   = $row['ultima'];
    }

    // Total de motivos de gratitud
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS total_gratitud
         FROM gratitud g
         INNER JOIN entrada e ON g.id_entrada = e.id_entrada
         WHERE e.id_usuario = ?"
    );
    $stmt->execute([$idUsuario]);
    $rowG = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($rowG) {
        $totalGratitudes = (int)$rowG['total_gratitud'];
    }

    // Entradas por emoción
    $stmt = $pdo->prepare(
        "SELECT em.nombre AS emocion,
                COUNT(*) AS total
         FROM entrada e
         INNER JOIN emocion em ON e.id_emocion = em.id_emocion
         WHERE e.id_usuario = ?
         GROUP BY em.id_emocion, em.nombre
         ORDER BY em.valor_numerico ASC"
    );
    $stmt->execute([$idUsuario]);
    $porEmocion = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Entradas por mes
    $stmt = $pdo->prepare(
        "SELECT DATE_FORMAT(fecha, '%Y-%m') AS anio_mes,
                COUNT(*) AS total
         FROM entrada
         WHERE id_usuario = ?
         GROUP BY anio_mes
         ORDER BY anio_mes ASC"
    );
    $stmt->execute([$idUsuario]);
    $porMes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Días con al menos una entrada
    $stmt = $pdo->prepare(
        "SELECT COUNT(DISTINCT fecha) AS dias
         FROM entrada
         WHERE id_usuario = ?"
    );
    $stmt->execute([$idUsuario]);
    $rowDias = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($rowDias) {
        $diasConEntrada = (int)$rowDias['dias'];
    }

    // Días con al menos un motivo de gratitud
    $stmt = $pdo->prepare(
        "SELECT COUNT(DISTINCT e.fecha) AS dias
         FROM entrada e
         INNER JOIN gratitud g ON e.id_entrada = g.id_entrada
         WHERE e.id_usuario = ?"
    );
    $stmt->execute([$idUsuario]);
    $rowDiasG = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($rowDiasG) {
        $diasConGratitud = (int)$rowDiasG['dias'];
    }

    if ($diasConEntrada > 0) {
        $porcentajeDiasConGratitud = round(
            ($diasConGratitud * 100) / $diasConEntrada,
            2
        );
    }

    // Evolución media de la emoción por semana
    $stmt = $pdo->prepare(
        "SELECT 
             YEARWEEK(e.fecha, 1) AS anio_semana,
             MIN(e.fecha) AS fecha_inicio,
             AVG(em.valor_numerico) AS emocion_media
         FROM entrada e
         INNER JOIN emocion em ON e.id_emocion = em.id_emocion
         WHERE e.id_usuario = ?
         GROUP BY anio_semana
         ORDER BY anio_semana ASC"
    );
    $stmt->execute([$idUsuario]);
    $porSemana = $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {
    $errores[] = "No se han podido cargar las estadísticas.";
}

// Preparar datos para Chart.js
$labelsEmocion = [];
$valoresEmocion = [];

foreach ($porEmocion as $fila) {
    $labelsEmocion[]  = $fila['emocion'];
    $valoresEmocion[] = (int)$fila['total'];
}

$labelsMes = [];
$valoresMes = [];

foreach ($porMes as $fila) {
    // anio_mes viene como  por defecto "2025-01" y lo convertimos a "01/2025"
    $partes = explode('-', $fila['anio_mes']); //esto es ->> [anio, mes]
    if (count($partes) === 2) {
        $labelsMes[] = $partes[1] . '/' . $partes[0];
    } else {
        $labelsMes[] = $fila['anio_mes'];
    }
    $valoresMes[] = (int)$fila['total'];
}

//datos por semana
$labelsSemana = [];
$valoresSemana = [];

foreach ($porSemana as $fila) {
    // YEARWEEK devuelve algo tipo 202501 >> usamos solo el número de semana
    $anioSemana = $fila['anio_semana']; // ej: 202501
    $numSemana = substr($anioSemana, 4); // "01"
    $labelsSemana[] = 'Semana ' . $numSemana;
    $valoresSemana[] = round((float)$fila['emocion_media'], 2);
}



include 'includes/header.php';
?>

<div class="mb-4">
    <h1 class="h3">Estadísticas</h1>
    <p class="text-muted mb-0">
        Aquí puedes ver una visión general de tu actividad en el diario y de tus emociones a lo largo del tiempo.
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

<?php if ($totalEntradas === 0 && empty($errores)): ?>
    <div class="alert alert-info">
        Todavía no hay datos suficientes para generar estadísticas.
        Empieza creando tu primera entrada desde el menú "Nueva entrada".
    </div>
<?php else: ?>
    <!-- Tarjetas de  resumen -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card text-center shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-muted">Entradas totales</h2>
                    <p class="display-6 mb-0"><?php echo $totalEntradas; ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card text-center shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-muted">Motivos de gratitud</h2>
                    <p class="display-6 mb-0"><?php echo $totalGratitudes; ?></p>
                        <?php if ($diasConEntrada > 0): ?>
                        <p class="mb-0 mt-2 text-muted small">
                            En el <?php echo $porcentajeDiasConGratitud; ?>% de tus días con entrada
                            has registrado al menos un motivo de gratitud.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card text-center shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-muted">Periodo registrado</h2>
                    <p class="mb-0">
                        <?php if ($fechaPrimera && $fechaUltima): ?>
                            <?php echo formatearFecha($fechaPrimera); ?>
                            &mdash;
                            <?php echo formatearFecha($fechaUltima); ?>
                        <?php else: ?>
                            Datos no disponibles
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico 1: Entradas por emoción -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h2 class="h5 card-title mb-3">Frecuencia de emociones</h2>
            <p class="text-muted">
                Número de entradas en las que cada emoción aparece como emoción principal del día.
            </p>
            <canvas id="graficoEmociones" height="140"></canvas>
        </div>
    </div>

    <!-- Gráfico 2: Entradas por mes -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h2 class="h5 card-title mb-3">Entradas por mes</h2>
            <p class="text-muted">
                Actividad general en el diario a lo largo del tiempo.
            </p>
            <canvas id="graficoMeses" height="140"></canvas>
        </div>
    </div>

    <!-- Gráfico 3: Evolución media de la emoción por semana -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h2 class="h5 card-title mb-3">Evolución emocional por semana</h2>
            <p class="text-muted">
                Muestra la media de tu emoción principal cada semana. Valores más altos indican semanas
                globalmente más positivas.
            </p>
            <canvas id="graficoSemana" height="140"></canvas>
        </div>
    </div>

<?php endif; ?>

<div class="mt-3">
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">Volver al inicio</a>
</div>

<!-- Chart.js solo en esta página -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Pasamos los datos de PHP a JS en un objeto global sencillo -->
<script>
window.datosGraficos = {
    labelsEmocion: <?php echo json_encode($labelsEmocion, JSON_UNESCAPED_UNICODE); ?>,
    valoresEmocion: <?php echo json_encode($valoresEmocion); ?>,
    labelsMes: <?php echo json_encode($labelsMes, JSON_UNESCAPED_UNICODE); ?>,
    valoresMes: <?php echo json_encode($valoresMes); ?>,
    labelsSemana: <?php echo json_encode($labelsSemana, JSON_UNESCAPED_UNICODE); ?>,
    valoresSemana: <?php echo json_encode($valoresSemana); ?>
};
</script>


<?php include 'includes/footer.php'; ?>
