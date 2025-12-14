<?php
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';

// Solo usuarios logueados pueden entrar 
requerirLogin();

$pdo = obtenerConexion();

// Para mostrar mensajes tanto de éxito  como de los de fallo
$errores = [];
$exito   = "";

// Valores por defecto del formulario
$fecha        = date('Y-m-d');
$id_emocion   = '';
$actividades  = '';
$reflexion    = '';
$gratitud_raw = '';


// Comentarios para indicar el flujo y que hace cada sección:
// 1. Cargar emociones para el select
try {
    $stmt = $pdo->query("SELECT id_emocion, nombre FROM emocion ORDER BY valor_numerico ASC");
    $emociones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errores[] = "No se han podido cargar las emociones.";
    $emociones = [];
}

// 2. Procesar el formulario si se ha enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha        = $_POST['fecha'] ?? date('Y-m-d');
    $id_emocion   = $_POST['id_emocion'] ?? '';
    $actividades  = trim($_POST['actividades'] ?? '');
    $reflexion    = trim($_POST['reflexion'] ?? '');
    $gratitud_raw = trim($_POST['gratitud'] ?? '');

    // Validaciones muy básicas
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

    // Si no hay errores, guardamos
    if (empty($errores)) {
        try {
            $pdo->beginTransaction();

            // Insertar la entrada
            $stmt = $pdo->prepare(
                "INSERT INTO entrada (id_usuario, id_emocion, fecha, actividades, reflexion)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $_SESSION['id_usuario'],
                $id_emocion,
                $fecha,
                $actividades,
                $reflexion
            ]);

            $id_entrada = $pdo->lastInsertId();

            // Procesar la gratitud: una línea = un motivo
            $lineas = preg_split('/\r\n|\r|\n/', $gratitud_raw);
            $stmtGratitud = $pdo->prepare(
                "INSERT INTO gratitud (id_entrada, texto)
                 VALUES (?, ?)"
            );

            foreach ($lineas as $linea) {
                $texto = trim($linea);
                if ($texto !== '') {
                    $stmtGratitud->execute([$id_entrada, $texto]);
                }
            }

            $pdo->commit();

            $exito = "Entrada guardada correctamente.";
            //  fecha y emoción como estaban, pero limpiamos textos
            $actividades  = '';
            $reflexion    = '';
            $gratitud_raw = '';

        } catch (PDOException $e) {
            $pdo->rollBack();
            // Si hay un error que puede ser típico de entrada duplicada por día
            if ($e->getCode() === '23000') {
                $errores[] = "Ya tienes una entrada para esa fecha. Puedes editarla desde el historial (cuando lo tengamos).";
            } else {
                $errores[] = "Se ha producido un error al guardar la entrada.";
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h3 mb-3">Nueva entrada</h1>
        <p class="text-muted mb-4">
            Tómate unos minutos para escribir cómo ha ido tu día. Intenta ser sincero/a contigo mismo/a.
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
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
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
                    <div class="form-text">
                        Selecciona el día de la entrada. Se guardará como <?php echo formatearFecha($fecha); ?>.
                    </div>

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
                    placeholder="¿Qué has hecho hoy que quieras recordar?"
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
                    placeholder="¿Cómo te has sentido? ¿Qué has aprendido hoy sobre ti?"
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
                    placeholder="Escribe cada motivo de gratitud en una línea diferente."
                ><?php echo htmlspecialchars($gratitud_raw); ?></textarea>
                <div class="form-text">
                    Por ejemplo:<br>
                    - La conversación con un amigo<br>
                    - Haber podido entrenar<br>
                    - Un momento de calma por la tarde
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-between">
                <a href="dashboard.php" class="btn btn-outline-secondary">Volver</a>
                <button type="submit" class="btn btn-primary">Guardar entrada</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
