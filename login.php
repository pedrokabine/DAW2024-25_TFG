<?php
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si ya está logueado, lo mandamos al dashboard
if (isset($_SESSION['id_usuario'])) {
    header('Location: dashboard.php');
    exit;
}

$email   = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Introduce un correo electrónico válido.";
    }

    if ($password === '') {
        $errores[] = "Debes introducir la contraseña.";
    }

    if (empty($errores)) {
        try {
            $pdo = obtenerConexion();

            $stmt = $pdo->prepare(
                "SELECT id_usuario, nombre, email, password_hash
                 FROM usuario
                 WHERE email = ?"
            );
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario && password_verify($password, $usuario['password_hash'])) {
            // Login correcto
                session_regenerate_id(true);// para evitar fijación de sesión

                $_SESSION['id_usuario']      = $usuario['id_usuario'];
                $_SESSION['nombre_usuario']  = $usuario['nombre'];
                $_SESSION['email_usuario']   = $usuario['email'];

                header('Location: dashboard.php');
                exit;
            } else {
                $errores[] = "Correo o contraseña incorrectos.";
            }

        } catch (PDOException $e) {
            $errores[] = "Se ha producido un error al intentar iniciar sesión.";
        }
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <h1 class="h3 mb-3 text-center">Iniciar sesión</h1>
        <p class="text-muted text-center mb-4">
            Accede a tu diario personal digital.
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

        <form method="post" novalidate>
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
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Entrar</button>
                <a href="registro.php" class="btn btn-outline-secondary">Crear una cuenta</a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
