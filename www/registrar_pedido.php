<?php
session_start(); // Inicia la sesión

include('db.php');

if (!isset($_SESSION['nombre_usuario'])) {
    header("Location: login.php");
    exit();
}

// Inicializar mensaje
$mensaje = "";

// Procesar el pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_proveedor = $_POST['id_proveedor'];
    $id_sucursal = $_POST['id_sucursal'];
    $productos = $_POST['productos'];

    // Validar cantidades y stock
    $errores = [];
    $numero_pedido = uniqid('PED-');

    // Verificar duplicidad de número de pedido
    $sql_check_pedido = "SELECT COUNT(*) AS total FROM pedidos WHERE numero_pedido = ?";
    $stmt = $conn->prepare($sql_check_pedido);
    $stmt->bind_param("s", $numero_pedido);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['total'] > 0) {
        $mensaje = "Error: El número de pedido ya existe. Intente nuevamente.";
    } else {
        foreach ($productos as $id_producto => $cantidad) {
            if ($cantidad > 0) {
                $sql_stock = "SELECT stock, nombre FROM productos WHERE id_producto = ?";
                $stmt = $conn->prepare($sql_stock);
                $stmt->bind_param("i", $id_producto);
                $stmt->execute();
                $result = $stmt->get_result();
                $producto = $result->fetch_assoc();

                if ($producto['stock'] < $cantidad) {
                    $errores[] = "El producto '{$producto['nombre']}' no tiene suficiente stock.";
                }
            }
        }

        if (!empty($errores)) {
            $mensaje = implode("<br>", $errores);
        } else {
            // Registrar el pedido
            $sql_pedido = "INSERT INTO pedidos (numero_pedido, id_proveedor, id_sucursal, fecha_pedido, estado) 
                           VALUES (?, ?, ?, NOW(), 'Pendiente')";
            $stmt = $conn->prepare($sql_pedido);
            $stmt->bind_param("sii", $numero_pedido, $id_proveedor, $id_sucursal);
            $stmt->execute();
            $id_pedido = $conn->insert_id;

            // Registrar detalles del pedido
            foreach ($productos as $id_producto => $cantidad) {
                if ($cantidad > 0) {
                    $sql_detalle = "INSERT INTO detalles_pedido (id_pedido, id_producto, cantidad_solicitada, cantidad_recibida) 
                                    VALUES (?, ?, ?, 0)";
                    $stmt = $conn->prepare($sql_detalle);
                    $stmt->bind_param("iii", $id_pedido, $id_producto, $cantidad);
                    $stmt->execute();

                    // Actualizar stock
                    $sql_stock_update = "UPDATE productos SET stock = stock - ? WHERE id_producto = ?";
                    $stmt = $conn->prepare($sql_stock_update);
                    $stmt->bind_param("ii", $cantidad, $id_producto);
                    $stmt->execute();
                }
            }

            $mensaje = "¡Pedido registrado exitosamente!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Pedido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <a href="index.php" class="btn btn-secondary mb-4"><i class="fas fa-arrow-left"></i> Regresar al Menú</a>
        <h2 class="text-center mb-4">Registrar Pedido</h2>

        <!-- Mensaje de éxito o error -->
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo strpos($mensaje, 'Error') === false ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Formulario para registrar un pedido -->
        <form action="registrar_pedido.php" method="POST">
            <div class="mb-3">
                <label for="proveedor" class="form-label">Proveedor</label>
                <select name="id_proveedor" id="proveedor" class="form-select" required>
                    <option value="">Seleccione un proveedor</option>
                    <?php
                    $sql = "SELECT id_proveedor, nombre FROM proveedores";
                    $result = $conn->query($sql);
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='{$row['id_proveedor']}'>{$row['nombre']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="sucursal" class="form-label">Sucursal</label>
                <select name="id_sucursal" id="sucursal" class="form-select" required>
                    <option value="">Seleccione una sucursal</option>
                    <?php
                    $sql = "SELECT id_sucursal, nombre FROM sucursales";
                    $result = $conn->query($sql);
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='{$row['id_sucursal']}'>{$row['nombre']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="productos" class="form-label">Productos</label>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Stock</th>
                            <th>Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT id_producto, nombre, stock FROM productos";
                        $result = $conn->query($sql);
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>{$row['nombre']}</td>";
                            echo "<td>{$row['stock']}</td>";
                            echo "<td><input type='number' name='productos[{$row['id_producto']}]' class='form-control' min='0'></td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-3"><i class="fas fa-check-circle"></i> Registrar Pedido</button>
        </form>

        <a href="recepcion.php" class="btn btn-info w-100"><i class="fas fa-shopping-cart"></i> Validar Pedido</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>