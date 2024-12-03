<?php
session_start(); // Inicia la sesión al principio del archivo
include('db.php'); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $contraseña = $_POST['contraseña'];

    $sql = "SELECT * FROM usuarios WHERE email = ? AND contraseña = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $contraseña);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $_SESSION['id_usuario'] = $user['id_usuario']; // Asegúrate de guardar el id del usuario
        $_SESSION['nombre_usuario'] = $user['nombre'];
        header("Location: index.php");
        exit();
    } else {
        echo "<div class='alert alert-danger text-center'>Correo o contraseña incorrectos.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Farmacia La Píldora</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container d-flex align-items-center vh-100">
        <div class="row w-100">
            <!-- Imagen de la izquierda -->
            <div class="col-md-6 d-none d-md-flex justify-content-center align-items-center">
                <img src="images/Login.webp" alt="Farmacia" class="img-fluid">
            </div>
            <!-- Formulario de Login -->
            <div class="col-md-6 d-flex flex-column justify-content-center align-items-center">
                <h2 class="mb-4">FARMACIA LA PÍLDORA</h2>
                <i class="fas fa-user-circle fa-4x mb-3"></i>
                <h3 class="text-center mb-4">Login</h3>
                <form action="login.php" method="POST" class="w-75">
                    <div class="mb-3">
                        <input type="text" class="form-control" name="email" placeholder="Correo Electrónico" required>
                    </div>
                    <div class="mb-3">
                        <input type="password" class="form-control" name="contraseña" placeholder="Contraseña" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mb-3">Iniciar Sesión</button>
                    <p class="text-center">¿No tienes cuenta? <a href="registro.php">Regístrate</a></p>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>