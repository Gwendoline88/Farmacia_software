<?php
session_start(); // Inicia la sesión

include('db.php');
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $contraseña = $_POST['contraseña']; // Contraseña en texto plano

    $sql = "INSERT INTO usuarios (nombre, email, contraseña) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $nombre, $email, $contraseña);

    if ($stmt->execute()) {
        $mensaje = "Registro exitoso!";
    } else {
        $mensaje = "Error en el registro.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Farmacia La Píldora</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container d-flex align-items-center vh-100">
        <div class="col-md-6 mx-auto text-center">
            <h2 class="mb-4">FARMACIA LA PÍLDORA</h2>
            <i class="fas fa-user-plus fa-4x mb-3"></i>
            <h3 class="mb-4">Registro de Usuario</h3>

            <!-- Mensaje de Éxito -->
            <?php if ($mensaje): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <form action="registro.php" method="POST" class="w-100">
                <div class="mb-3">
                    <input type="text" class="form-control" name="nombre" placeholder="Nombre Completo" required>
                </div>
                <div class="mb-3">
                    <input type="email" class="form-control" name="email" placeholder="Correo Electrónico" required>
                </div>
                <div class="mb-3">
                    <input type="password" class="form-control" name="contraseña" placeholder="Contraseña" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 mb-3">Registrarse</button>
                <p class="text-center">¿Ya tienes cuenta? <a href="login.php">Inicia Sesión</a></p>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>