<?php
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';

requerirLogin();

$pdo       = obtenerConexion();
$idUsuario = $_SESSION['id_usuario'];

$erroresDatos = [];
$exitoDatos   = '';

$erroresPass = [];
$exitoPass   = '';
//enumero pasos para que sea más entendible
// 1. Cargar datos actuales del usuario
try {
    $stmt = $pdo->prepare(
        "SELECT nombre, email, fecha_registro
         FROM usuario
         WHERE id_usuario = ?"
    );
    $stmt->execute([$idUsuario]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        $erroresDatos[] = "No se han podido cargar los datos del usuario.";
    }
} catch (PDOException $e) {
    $erroresDatos[] = "Se ha producido un error al cargar los datos del usuario.";
}

// 2. Procesar actualización de nombre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar_datos') {
    $nuevoNombre = trim($_POST['nombre'] ?? '');

    if ($nuevoNombre === '') {
        $erroresDatos[] = "El nombre no puede estar vacío.";
    }

    if (empty($erroresDatos)) {
        try {
            $stmtUp = $pdo->prepare(
                "UPDATE usuario
                 SET nombre = ?
                 WHERE id_usuario = ?"
            );
            $stmtUp->execute([$nuevoNombre, $idUsuario]);

            $exitoDatos = "Los datos se han actualizado correctamente.";
            $usuario['nombre'] = $nuevoNombre;
            $_SESSION['nombre_usuario'] = $nuevoNombre;

        } catch (PDOException $e) {
            $erroresDatos[] = "Se ha producido un error al actualizar los datos.";
        }
    }
}

// 3. Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'cambiar_password') {
    $passwordActual = $_POST['password_actual'] ?? '';
    $passwordNueva  = $_POST['password_nueva'] ?? '';
    $passwordNueva2 = $_POST['password_nueva2'] ?? '';

    if ($passwordActual === '' || $passwordNueva === '' || $passwordNueva2 === '') {
        $erroresPass[] = "Todos los campos de contraseña son obligatorios.";
    }

    if (strlen($passwordNueva) < 6) {
        $erroresPass[] = "La nueva contraseña debe tener al menos 6 caracteres.";
    }

    if ($passwordNueva !== $passwordNueva2) {
        $erroresPass[] = "Las nuevas contraseñas no coinciden.";
    }

    if (empty($erroresPass)) {
        try {
            // Obtener hash actual
            $stmt = $pdo->prepare(
                "SELECT password_hash
                 FROM usuario
                 WHERE id_usuario = ?"
            );
            $stmt->execute([$idUsuario]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row || !password_verify($passwordActual, $row['password_hash'])) {
                $erroresPass[] = "La contraseña actual no es correcta.";
            } else {
                // Actualizar hash
                $nuevoHash = password_hash($passwordNueva, PASSWORD_DEFAULT);

                $stmtUp = $pdo->prepare(
                    "UPDATE usuario
                     SET password_hash = ?
                     WHERE id_usuario = ?"
                );
                $stmtUp->execute([$nuevoHash, $idUsuario]);

                $exitoPass = "La contraseña se ha actualizado correctamente.";
            }

        } catch (PDOException $e) {
            $erroresPass[] = "Se ha producido un error al actualizar la contraseña.";
        }
    }
}

include 'includes/header.php';
?>

<div class="mb-4">
    <h1 class="h3">Perfil de usuario</h1>
    <p class="text-muted mb-0">
        Desde aquí puedes consultar y actualizar tus datos básicos de acceso al diario.
    </p>
</div>

<div class="row g-4">
    <!-- Datos básicos -->
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Datos personales</h2>

                <?php if (!empty($erroresDatos)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($erroresDatos as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($exitoDatos !== ''): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($exitoDatos); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($usuario)): ?>
                    <form method="post" novalidate>
                        <input type="hidden" name="accion" value="actualizar_datos">

                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input
                                type="text"
                                class="form-control"
                                id="nombre"
                                name="nombre"
                                value="<?php echo htmlspecialchars($usuario['nombre']); ?>"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Correo electrónico</label>
                            <input
                                type="email"
                                class="form-control"
                                value="<?php echo htmlspecialchars($usuario['email']); ?>"
                                disabled
                            >
                            <div class="form-text">
                                El correo se usa como identificador de inicio de sesión.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Fecha de registro</label>
                            <input
                                type="text"
                                class="form-control"
                                value="<?php echo formatearFecha($usuario['fecha_registro']); ?>"
                                disabled
                            >
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary btn-sm">
                                Guardar cambios
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Aquí el Cambio de contraseña -->
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Cambiar contraseña</h2>
                <p class="text-muted">
                    Por seguridad, te pedimos que introduzcas tu contraseña actual antes de establecer una nueva.
                </p>

                <?php if (!empty($erroresPass)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($erroresPass as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($exitoPass !== ''): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($exitoPass); ?>
                    </div>
                <?php endif; ?>

                <form method="post" novalidate>
                    <input type="hidden" name="accion" value="cambiar_password">

                    <div class="mb-3">
                        <label for="password_actual" class="form-label">Contraseña actual</label>
                        <input
                            type="password"
                            class="form-control"
                            id="password_actual"
                            name="password_actual"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label for="password_nueva" class="form-label">Nueva contraseña</label>
                        <input
                            type="password"
                            class="form-control"
                            id="password_nueva"
                            name="password_nueva"
                            required
                        >
                        <div class="form-text">
                            Mínimo 6 caracteres.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password_nueva2" class="form-label">Repetir nueva contraseña</label>
                        <input
                            type="password"
                            class="form-control"
                            id="password_nueva2"
                            name="password_nueva2"
                            required
                        >
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary btn-sm">
                            Actualizar contraseña
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="mt-4">
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">Volver al inicio</a>
</div>

<?php include 'includes/footer.php'; ?>
