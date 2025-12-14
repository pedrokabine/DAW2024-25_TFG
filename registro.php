<?php
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';
include 'includes/header.php';

$nombre   = '';
$email    = '';
$errores  = [];
$exito    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    // Validaciones sencillas
    if ($nombre === '') {
        $errores[] = "El nombre es obligatorio.";
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Debes introducir un correo electrónico válido.";
    }

    if ($password === '') {
        $errores[] = "La contraseña es obligatoria.";
    } elseif (strlen($password) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres.";
    }

    if ($password !== $password2) {
        $errores[] = "Las contraseñas no coinciden.";
    }

    // Si no hay errores, intentamos guardar en la base de datos
    if (empty($errores)) {
        try {
            $pdo = obtenerConexion();

            // Comprobamos si ya existe un usuario con ese email
            $stmt = $pdo->prepare("SELECT id_usuario FROM usuario WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $errores[] = "Ya existe un usuario registrado con ese correo electrónico.";
            } else {
                // Guardar usuario nuevo
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare(
                    "INSERT INTO usuario (nombre, email, password_hash)
                     VALUES (?, ?, ?)"
                );
                $stmt->execute([$nombre, $email, $passwordHash]);
                //Probamos a enviar correo de bienvenida si ha ido todo bien
                enviarCorreoBienvenida($email, $nombre);

                $exito = "Cuenta creada correctamente. Ya puedes iniciar sesión.";

           // Limpiamos campos del formulario
                $nombre = '';
                $email  = '';
            }
        } catch (PDOException $e) {
            $errores[] = "Se ha producido un error al registrar el usuario.";
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <h1 class="h3 mb-3 text-center">Crear una cuenta</h1>
        <p class="text-muted text-center mb-4">
            Regístrate para empezar tu diario personal digital.
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

        <?php if ($exito !== ''): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($exito); ?>
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre</label>
                <input
                    type="text"
                    class="form-control"
                    id="nombre"
                    name="nombre"
                    value="<?php echo htmlspecialchars($nombre); ?>"
                    required
                >
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <input
                    type="email"
                    class="form-control"
                    id="email"
                    name="email"
                    value="<?php echo htmlspecialchars($email); ?>"
                    required
                >
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input
                    type="password"
                    class="form-control"
                    id="password"
                    name="password"
                    required
                >
                <div class="form-text">
                    Mínimo 6 caracteres.
                </div>
            </div>

            <div class="mb-3">
                <label for="password2" class="form-label">Repetir contraseña</label>
                <input
                    type="password"
                    class="form-control"
                    id="password2"
                    name="password2"
                    required
                >
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Crear cuenta</button>
                <a href="login.php" class="btn btn-outline-secondary">Ya tengo cuenta</a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
