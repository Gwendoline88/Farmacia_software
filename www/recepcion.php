<?php
session_start(); // Inicia la sesión

include('db.php');

if (!isset($_SESSION['nombre_usuario'])) {
    header("Location: login.php");
    exit();
}

// Inicializar mensaje
$mensaje = "";

// Validar pedido seleccionado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['validar_pedido'])) {
    $id_pedido = $_POST['id_pedido'];
    $productos_recibidos = $_POST['productos_recibidos'];

    foreach ($productos_recibidos as $id_producto => $cantidad_recibida) {
        // Actualizar cantidad recibida en detalles del pedido
        $sql_update_detalle = "UPDATE detalles_pedido 
                               SET cantidad_recibida = ? 
                               WHERE id_pedido = ? AND id_producto = ?";
        $stmt = $conn->prepare($sql_update_detalle);
        $stmt->bind_param("iii", $cantidad_recibida, $id_pedido, $id_producto);
        $stmt->execute();

        // Actualizar stock en productos
        $sql_update_stock = "UPDATE productos 
                             SET stock = stock + ? 
                             WHERE id_producto = ?";
        $stmt = $conn->prepare($sql_update_stock);
        $stmt->bind_param("ii", $cantidad_recibida, $id_producto);
        $stmt->execute();
    }

    // Marcar el pedido como completado
    $sql_update_estado = "UPDATE pedidos SET estado = 'Completado' WHERE id_pedido = ?";
    $stmt = $conn->prepare($sql_update_estado);
    $stmt->bind_param("i", $id_pedido);
    $stmt->execute();

    $mensaje = "Pedido validado y stock actualizado correctamente.";

    // Generar PDF automáticamente
    header("Location: generar_pdf.php?id_pedido=$id_pedido");
    exit();
}

// Obtener pedidos registrados con estado 'Pendiente'
$sql_pedidos = "SELECT p.id_pedido, p.numero_pedido, pr.nombre AS proveedor, s.nombre AS sucursal, p.fecha_pedido 
                FROM pedidos p
                JOIN proveedores pr ON p.id_proveedor = pr.id_proveedor
                JOIN sucursales s ON p.id_sucursal = s.id_sucursal
                WHERE p.estado = 'Pendiente'";
$result_pedidos = $conn->query($sql_pedidos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recepción de Pedidos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<div class="container mt-5">
    <a href="index.php" class="btn btn-secondary mb-4"><i class="fas fa-arrow-left"></i> Regresar al Menú</a>
    <h2 class="text-center">Recepción de Pedidos</h2>
    <p class="text-center">Valida la recepción de los productos entregados por los proveedores.</p>

    <!-- Mensaje -->
    <?php if ($mensaje): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $mensaje; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Formulario para seleccionar un pedido -->
    <form method="POST" class="mb-4">
        <div class="mb-3">
            <label for="id_pedido" class="form-label">Seleccionar Pedido</label>
            <select name="id_pedido" id="id_pedido" class="form-select" onchange="this.form.submit()" required>
                <option value="">Seleccione un pedido</option>
                <?php while ($pedido = $result_pedidos->fetch_assoc()): ?>
                    <option value="<?php echo $pedido['id_pedido']; ?>" <?php echo (isset($_POST['id_pedido']) && $_POST['id_pedido'] == $pedido['id_pedido']) ? 'selected' : ''; ?>>
                        <?php echo $pedido['numero_pedido'] . " - " . $pedido['proveedor'] . " - " . $pedido['sucursal']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
    </form>

    <?php if (isset($_POST['id_pedido'])): 
        $id_pedido = $_POST['id_pedido'];
        $sql_detalles = "SELECT dp.id_producto, pr.nombre AS producto, dp.cantidad_solicitada, dp.cantidad_recibida 
                         FROM detalles_pedido dp
                         JOIN productos pr ON dp.id_producto = pr.id_producto
                         WHERE dp.id_pedido = ?";
        $stmt = $conn->prepare($sql_detalles);
        $stmt->bind_param("i", $id_pedido);
        $stmt->execute();
        $result_detalles = $stmt->get_result();
    ?>
        <!-- Tabla de productos del pedido -->
        <form method="POST">
            <input type="hidden" name="id_pedido" value="<?php echo $id_pedido; ?>">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad Solicitada</th>
                        <th>Cantidad Recibida</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($detalle = $result_detalles->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $detalle['producto']; ?></td>
                            <td><?php echo $detalle['cantidad_solicitada']; ?></td>
                            <td>
                                <input type="number" class="form-control" name="productos_recibidos[<?php echo $detalle['id_producto']; ?>]" 
                                       value="<?php echo $detalle['cantidad_recibida']; ?>" min="0" max="<?php echo $detalle['cantidad_solicitada']; ?>" required>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <button type="submit" name="validar_pedido" class="btn btn-primary w-100"><i class="fas fa-check"></i> Validar Pedido</button>
        </form>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>