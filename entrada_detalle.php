<?php
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';

// Solo usuarios logueados
requerirLogin();

$pdo = obtenerConexion();
$idUsuario = $_SESSION['id_usuario'];

// id de la entrada desde GET
$idEntrada = $_GET['id'] ?? null;

$errores = [];
$entrada = null;
$motivosGratitud = [];

// 1. Si viene un POST para eliminar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {

    if ($idEntrada === null || !ctype_digit($idEntrada)) {
        $errores[] = "Entrada no válida.";
    } else {
        try {
            // Comprobar que la entrada pertenece al usuario
            $stmt = $pdo->prepare(
                "SELECT id_entrada
                 FROM entrada
                 WHERE id_entrada = ?
                   AND id_usuario = ?"
            );
            $stmt->execute([$idEntrada, $idUsuario]);
            $existe = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existe) {
                // Borramos la entrada (gratitud se borra en cascada por la Foreign key)
                $stmtDel = $pdo->prepare(
                    "DELETE FROM entrada
                     WHERE id_entrada = ?
                       AND id_usuario = ?"
                );
                $stmtDel->execute([$idEntrada, $idUsuario]);

                // Redirigimos al historial con un mensaje
                header('Location: historial.php?msg=eliminada');
                exit;

            } else {
                $errores[] = "No se ha encontrado la entrada o no tienes permiso para eliminarla.";
            }
        } catch (PDOException $e) {
            $errores[] = "Se ha producido un error al intentar eliminar la entrada.";
        }
    }
}

// 2. Si no estamos borrando, cargamos la entrada para mostrarla
if ($idEntrada === null || !ctype_digit($idEntrada)) {
    $errores[] = "Entrada no válida.";
} else {
    try {
        // Entrada y emoción
        $stmt = $pdo->prepare(
            "SELECT e.id_entrada,
                    e.fecha,
                    e.actividades,
                    e.reflexion,
                    em.nombre AS emocion
             FROM entrada e
             INNER JOIN emocion em ON e.id_emocion = em.id_emocion
             WHERE e.id_entrada = ?
               AND e.id_usuario = ?"
        );
        $stmt->execute([$idEntrada, $idUsuario]);
        $entrada = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($entrada) {
            // Motivos de gratitud
            $stmtG = $pdo->prepare(
                "SELECT texto
                 FROM gratitud
                 WHERE id_entrada = ?
                 ORDER BY id_gratitud ASC"
            );
            $stmtG->execute([$idEntrada]);
            $motivosGratitud = $stmtG->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $errores[] = "No se ha encontrado la entrada solicitada.";
        }

    } catch (PDOException $e) {
        $errores[] = "Se ha producido un error al cargar la entrada.";
    }
}

include 'includes/header.php';
?>


<div class="mb-4">
    <h1 class="h3">Detalle de la entrada</h1>
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

<?php if ($entrada): ?>
    <div class="card mb-3 shadow-sm">
        <div class="card-body">
            <h2 class="h5 card-title mb-3">
                <?php echo formatearFecha($entrada['fecha']); ?>
            </h2>

            <p class="mb-2">
                <span class="fw-semibold">Emoción principal:</span>
                <span class="badge bg-primary">
                    <?php echo htmlspecialchars($entrada['emocion']); ?>
                </span>
            </p>

            <?php if (!empty($entrada['actividades'])): ?>
                <div class="mb-3">
                    <h3 class="h6 mb-1">Actividades relevantes</h3>
                    <p class="mb-0">
                        <?php echo nl2br(htmlspecialchars($entrada['actividades'])); ?>
                    </p>
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <h3 class="h6 mb-1">Reflexión</h3>
                <p class="mb-0">
                    <?php echo nl2br(htmlspecialchars($entrada['reflexion'])); ?>
                </p>
            </div>

            <div>
                <h3 class="h6 mb-1">Motivos de gratitud</h3>
                <?php if (!empty($motivosGratitud)): ?>
                    <ul class="mb-0">
                        <?php foreach ($motivosGratitud as $g): ?>
                            <li><?php echo htmlspecialchars($g['texto']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">
                        No se registraron motivos de gratitud en esta entrada.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between">
    <a href="historial.php" class="btn btn-outline-secondary btn-sm">Volver al historial</a>
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">Ir al inicio</a>
</div>

<?php if ($entrada): ?>
        <div class="d-flex gap-2">
            <a href="editar_entrada.php?id=<?php echo $entrada['id_entrada']; ?>"
               class="btn btn-primary btn-sm">
                Editar entrada
            </a>

            <form method="post"
                  onsubmit="return confirm('¿Seguro que quieres eliminar esta entrada? Esta acción no se puede deshacer.');">
                <input type="hidden" name="accion" value="eliminar">
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    Eliminar
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
