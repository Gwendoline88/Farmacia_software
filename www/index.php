<?php
session_start();
if (!isset($_SESSION['nombre_usuario'])) {
    header("Location: login.php");
    exit();
}

// Función para cerrar sesión
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú de Opciones - Farmacia La Píldora</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa; /* Fondo suave */
        }
        .card:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Efecto hover en las tarjetas */
        }
        .btn-logout {
            position: fixed;
            top: 20px;
            right: 20px;
        }
        .welcome-text {
            margin-top: 30px;
        }
    </style>
</head>
<body>

    <!-- Botón para cerrar sesión -->
    <a href="index.php?logout=true" class="btn btn-danger btn-logout">
        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
    </a>

    <div class="container">
        <div class="text-center welcome-text">
            <h2 class="mb-4">Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?></h2>
            <h3 class="mb-4">Menú de Opciones</h3>
        </div>

        <div class="row justify-content-center">
            <!-- Tarjeta para Gestión de Clientes -->
            <div class="col-md-4 mb-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <i class="fas fa-users fa-3x mb-3"></i>
                        <h5 class="card-title">Gestión de Clientes</h5>
                        <p class="card-text">Accede al módulo para gestionar la información de los clientes.</p>
                        <a href="clientes.php" class="btn btn-primary w-100">
                            <i class="fas fa-arrow-right"></i> Ir a Clientes
                        </a>
                    </div>
                </div>
            </div>

            <!-- Tarjeta para Recepción de Pedidos -->
            <div class="col-md-4 mb-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <i class="fas fa-pills fa-3x mb-3"></i>
                        <h5 class="card-title">Recepción de Pedidos</h5>
                        <p class="card-text">Genera y gestiona nuevos pedidos para la farmacia.</p>
                        <a href="registrar_pedido.php" class="btn btn-primary w-100">
                            <i class="fas fa-arrow-right"></i> Ir a Registrar Pedido
                        </a>
                    </div>
                </div>
            </div>

            <!-- Tarjeta para Gestión de Productos -->
            <div class="col-md-4 mb-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <i class="fas fa-boxes fa-3x mb-3"></i>
                        <h5 class="card-title">Gestión de Productos</h5>
                        <p class="card-text">Consulta y administra los productos disponibles.</p>
                        <a href="productos.php" class="btn btn-primary w-100">
                            <i class="fas fa-arrow-right"></i> Ir a Productos
                        </a>
                    </div>
                </div>
            </div>

            <!-- Tarjeta para Ventas -->
            <div class="col-md-4 mb-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                        <h5 class="card-title">Ventas</h5>
                        <p class="card-text">Accede al módulo de ventas para registrar transacciones.</p>
                        <a href="ventas.php" class="btn btn-primary w-100">
                            <i class="fas fa-arrow-right"></i> Ir a Ventas
                        </a>
                    </div>
                </div>
            </div>

            <!-- Tarjeta para Medicamentos Controlados -->
            <div class="col-md-4 mb-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <i class="fas fa-prescription-bottle fa-3x mb-3"></i>
                        <h5 class="card-title">Medicamentos Controlados</h5>
                        <p class="card-text">Gestiona las ventas de medicamentos controlados con receta médica.</p>
                        <a href="medicamento_controlado.php" class="btn btn-primary w-100">
                            <i class="fas fa-arrow-right"></i> Ir a Medicamentos Controlados
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>