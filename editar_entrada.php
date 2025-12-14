<?php
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';

// Solo usuarios logueados
requerirLogin();

$pdo = obtenerConexion();
$idUsuario = $_SESSION['id_usuario'];

$errores = [];
$exito   = "";

// 1. Obtenemos el id de la entrada
$idEntrada = $_GET['id'] ?? null;

if ($idEntrada === null || !ctype_digit($idEntrada)) {
    $errores[] = "Entrada no válida.";
}

// 2. Cargar emociones para el select
try {
    $stmt = $pdo->query("SELECT id_emocion, nombre FROM emocion ORDER BY valor_numerico ASC");
    $emociones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errores[] = "No se han podido cargar las emociones.";
    $emociones = [];
}

// Valores del formulario
$fecha        = '';
$id_emocion   = '';
$actividades  = '';
$reflexion    = '';
$gratitud_raw = '';

// 3. Si es POST, procesamos actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $idEntrada = $_POST['id_entrada'] ?? '';

    if ($idEntrada === '' || !ctype_digit($idEntrada)) {
        $errores[] = "Entrada no válida.";
    }

    $fecha        = $_POST['fecha'] ?? '';
    $id_emocion   = $_POST['id_emocion'] ?? '';
    $actividades  = trim($_POST['actividades'] ?? '');
    $reflexion    = trim($_POST['reflexion'] ?? '');
    $gratitud_raw = trim($_POST['gratitud'] ?? '');

    // Validacion básica
    if ($fecha === '') {
        $errores[] = "La fecha es obligatoria.";
    }

    if ($id_emocion === '') {
        $errores[] = "Debes seleccionar una emoción.";
    }

    if ($reflexion === '') {
        $errores[] = "La reflexión no puede estar vacía.";
    }

    if ($gratitud_raw === '') {
        $errores[] = "Escribe al menos un motivo de gratitud.";
    }

    if (empty($errores)) {
        try {
            $pdo->beginTransaction();

            // Aseguramos de que la entrada pertenece al usuario
            $stmt = $pdo->prepare(
                "SELECT id_entrada
                 FROM entrada
                 WHERE id_entrada = ?
                   AND id_usuario = ?"
            );
            $stmt->execute([$idEntrada, $idUsuario]);
            $existe = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existe) {
                $errores[] = "No se ha encontrado la entrada o no tienes permiso para editarla.";
                $pdo->rollBack();
            } else {
                // Actualizar la entrada
                $stmtUp = $pdo->prepare(
                    "UPDATE entrada
                     SET id_emocion = ?, fecha = ?, actividades = ?, reflexion = ?
                     WHERE id_entrada = ?
                       AND id_usuario = ?"
                );
                $stmtUp->execute([
                    $id_emocion,
                    $fecha,
                    $actividades,
                    $reflexion,
                    $idEntrada,
                    $idUsuario
                ]);

                // Eliminar gratitudes anteriores
                $stmtDelG = $pdo->prepare(
                    "DELETE FROM gratitud
                     WHERE id_entrada = ?"
                );
                $stmtDelG->execute([$idEntrada]);

                // Insertar de nuevo las gratitudes
                $lineas = preg_split('/\r\n|\r|\n/', $gratitud_raw);
                $stmtGratitud = $pdo->prepare(
                    "INSERT INTO gratitud (id_entrada, texto)
                     VALUES (?, ?)"
                );

                foreach ($lineas as $linea) {
                    $texto = trim($linea);
                    if ($texto !== '') {
                        $stmtGratitud->execute([$idEntrada, $texto]);
                    }
                }

                $pdo->commit();
                $exito = "Entrada actualizada correctamente.";
            }

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores[] = "Se ha producido un error al actualizar la entrada.";
        }
    }

} elseif ($idEntrada !== null && ctype_digit($idEntrada)) {
    // 4. Si es GET (primera vez), cargamos los datos actuales de la entrada
    try {
        // Entrada
        $stmt = $pdo->prepare(
            "SELECT fecha, id_emocion, actividades, reflexion
             FROM entrada
             WHERE id_entrada = ?
               AND id_usuario = ?"
        );
        $stmt->execute([$idEntrada, $idUsuario]);
        $entrada = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($entrada) {
            $fecha       = $entrada['fecha'];
            $id_emocion  = $entrada['id_emocion'];
            $actividades = $entrada['actividades'];
            $reflexion   = $entrada['reflexion'];

            // Gratitudes
            $stmtG = $pdo->prepare(
                "SELECT texto
                 FROM gratitud
                 WHERE id_entrada = ?
                 ORDER BY id_gratitud ASC"
            );
            $stmtG->execute([$idEntrada]);
            $grats = $stmtG->fetchAll(PDO::FETCH_ASSOC);

            $lineas = [];
            foreach ($grats as $g) {
                $lineas[] = $g['texto'];
            }
            $gratitud_raw = implode("\n", $lineas);

        } else {
            $errores[] = "No se ha encontrado la entrada solicitada.";
        }

    } catch (PDOException $e) {
        $errores[] = "Se ha producido un error al cargar la entrada.";
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h3 mb-3">Editar entrada</h1>
        <p class="text-muted mb-4">
            Modifica la información de esta entrada de tu diario. Los cambios se guardarán al actualizar.
        </p>

        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errores as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($exito !== ""): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($exito); ?>
                <div class="mt-2">
                    <a href="entrada_detalle.php?id=<?php echo htmlspecialchars($idEntrada); ?>"
                       class="btn btn-sm btn-outline-primary">
                        Ver entrada
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($idEntrada !== null && empty($errores) || $exito !== ""): ?>
            <form method="post" novalidate>
                <input type="hidden" name="id_entrada" value="<?php echo htmlspecialchars($idEntrada); ?>">

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="fecha" class="form-label">Fecha</label>
                        <input
                            type="date"
                            class="form-control"
                            id="fecha"
                            name="fecha"
                            value="<?php echo htmlspecialchars($fecha); ?>"
                            required
                        >
                    </div>

                    <div class="col-md-8">
                        <label for="id_emocion" class="form-label">Emoción principal del día</label>
                        <select
                            class="form-select"
                            id="id_emocion"
                            name="id_emocion"
                            required
                        >
                            <option value="">Selecciona una emoción...</option>
                            <?php foreach ($emociones as $emocion): ?>
                                <option
                                    value="<?php echo $emocion['id_emocion']; ?>"
                                    <?php echo ($id_emocion == $emocion['id_emocion']) ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars($emocion['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mt-3">
                    <label for="actividades" class="form-label">Actividades relevantes</label>
                    <textarea
                        class="form-control"
                        id="actividades"
                        name="actividades"
                        rows="3"
                    ><?php echo htmlspecialchars($actividades); ?></textarea>
                </div>

                <div class="mt-3">
                    <label for="reflexion" class="form-label">Reflexión del día <span class="text-danger">*</span></label>
                    <textarea
                        class="form-control"
                        id="reflexion"
                        name="reflexion"
                        rows="4"
                        required
                    ><?php echo htmlspecialchars($reflexion); ?></textarea>
                </div>

                <div class="mt-3">
                    <label for="gratitud" class="form-label">Motivos de gratitud <span class="text-danger">*</span></label>
                    <textarea
                        class="form-control"
                        id="gratitud"
                        name="gratitud"
                        rows="3"
                        required
                    ><?php echo htmlspecialchars($gratitud_raw); ?></textarea>
                    <div class="form-text">
                        Escribe cada motivo de gratitud en una línea diferente.
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-between">
                    <a href="entrada_detalle.php?id=<?php echo htmlspecialchars($idEntrada); ?>"
                       class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
