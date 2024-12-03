<?php
session_start();
include('db.php');

// Inicializar carrito
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Procesar acciones (Agregar al carrito, Confirmar venta)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Agregar producto al carrito
    if (isset($_POST['agregar'])) {
        $id_producto = $_POST['id_producto'];
        $cantidad = (int)$_POST['cantidad'];
        $id_promocion = $_POST['id_promocion'];

        // Validar selección de producto
        if ($id_producto && $cantidad > 0) {
            // Consultar datos del producto
            $sql = "SELECT id_producto, nombre, precio, stock FROM productos WHERE id_producto = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id_producto);
            $stmt->execute();
            $producto = $stmt->get_result()->fetch_assoc();

            if ($producto && $producto['stock'] >= $cantidad) {
                // Calcular descuento si hay promoción
                $descuento_aplicado = 0;
                if (!empty($id_promocion)) {
                    $sql_promocion = "SELECT descuento, tipo FROM promociones WHERE id_promocion = ?";
                    $stmt_promocion = $conn->prepare($sql_promocion);
                    $stmt_promocion->bind_param("i", $id_promocion);
                    $stmt_promocion->execute();
                    $promocion = $stmt_promocion->get_result()->fetch_assoc();

                    if ($promocion) {
                        if ($promocion['tipo'] === 'porcentaje') {
                            $descuento_aplicado = ($producto['precio'] * $cantidad) * ($promocion['descuento'] / 100);
                        } elseif ($promocion['tipo'] === 'fijo') {
                            $descuento_aplicado = min($promocion['descuento'], $producto['precio'] * $cantidad);
                        }
                    }
                }

                // Agregar producto al carrito
                $_SESSION['carrito'][] = [
                    'id_producto' => $producto['id_producto'],
                    'nombre' => $producto['nombre'],
                    'precio' => $producto['precio'],
                    'cantidad' => $cantidad,
                    'subtotal' => ($producto['precio'] * $cantidad) - $descuento_aplicado,
                    'id_promocion' => $id_promocion,
                    'descuento_aplicado' => $descuento_aplicado
                ];
            } else {
                $mensaje_error = "Stock insuficiente o producto no encontrado.";
            }
        } else {
            $mensaje_error = "Seleccione un producto válido y cantidad mayor a 0.";
        }
    }

    // Confirmar venta
    if (isset($_POST['confirmar_venta'])) {
        if (!empty($_SESSION['carrito'])) {
            $total = 0;
            $total_descuentos = 0;

            foreach ($_SESSION['carrito'] as $item) {
                $total += $item['subtotal'];
                $total_descuentos += $item['descuento_aplicado'];
            }

            $total_final = $total;

            // Registrar venta en la base de datos
            $sql_venta = "INSERT INTO Ventas (fecha_venta, id_cliente, total, descuento, total_final) VALUES (NOW(), NULL, ?, ?, ?)";
            $stmt_venta = $conn->prepare($sql_venta);
            $stmt_venta->bind_param("ddd", $total, $total_descuentos, $total_final);
            $stmt_venta->execute();
            $id_venta = $stmt_venta->insert_id;

            // Registrar detalles de la venta
            $sql_detalle = "INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario, subtotal, id_promocion, descuento_aplicado) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_detalle = $conn->prepare($sql_detalle);

            foreach ($_SESSION['carrito'] as $item) {
                $stmt_detalle->bind_param(
                    "iiiddid",
                    $id_venta,
                    $item['id_producto'],
                    $item['cantidad'],
                    $item['precio'],
                    $item['subtotal'],
                    $item['id_promocion'],
                    $item['descuento_aplicado']
                );
                $stmt_detalle->execute();

                // Actualizar stock
                $sql_stock = "UPDATE productos SET stock = stock - ? WHERE id_producto = ?";
                $stmt_stock = $conn->prepare($sql_stock);
                $stmt_stock->bind_param("ii", $item['cantidad'], $item['id_producto']);
                $stmt_stock->execute();
            }

            $_SESSION['carrito'] = [];
            $mensaje_exito = "Venta registrada correctamente.";
        } else {
            $mensaje_error = "No hay productos en el carrito.";
        }
    }
}

// Calcular totales
$total = 0;
$total_descuentos = 0;
foreach ($_SESSION['carrito'] as $item) {
    $total += $item['subtotal'];
    $total_descuentos += $item['descuento_aplicado'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Punto de Venta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <a href="index.php" class="btn btn-secondary mb-4">Regresar al Menú</a>
    <h2 class="text-center mb-4">Punto de Venta</h2>

    <?php if (isset($mensaje_error)): ?>
        <div class="alert alert-danger"><?php echo $mensaje_error; ?></div>
    <?php endif; ?>

    <?php if (isset($mensaje_exito)): ?>
        <div class="alert alert-success"><?php echo $mensaje_exito; ?></div>
    <?php endif; ?>

    <form method="POST" class="row mb-4">
        <div class="col-md-4">
            <select name="id_producto" class="form-select" required>
                <option value="">Seleccione un producto</option>
                <?php
                $result = $conn->query("SELECT id_producto, nombre FROM productos WHERE stock > 0");
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='{$row['id_producto']}'>{$row['nombre']}</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-3">
            <input type="number" name="cantidad" class="form-control" placeholder="Cantidad" min="1" required>
        </div>
        <div class="col-md-3">
            <select name="id_promocion" class="form-select">
                <option value="">Sin descuento</option>
                <?php
                $result = $conn->query("SELECT id_promocion, descripcion FROM promociones");
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='{$row['id_promocion']}'>{$row['descripcion']}</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" name="agregar" class="btn btn-primary w-100">Agregar al Carrito</button>
        </div>
    </form>

    <table class="table">
        <thead>
        <tr>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Precio Unitario</th>
            <th>Descuento</th>
            <th>Subtotal</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($_SESSION['carrito'] as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                <td><?php echo $item['cantidad']; ?></td>
                <td><?php echo number_format($item['precio'], 2); ?> MXN</td>
                <td><?php echo number_format($item['descuento_aplicado'], 2); ?> MXN</td>
                <td><?php echo number_format($item['subtotal'], 2); ?> MXN</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h4>Total: <?php echo number_format($total, 2); ?> MXN</h4>
    <h4>Descuentos Totales: <?php echo number_format($total_descuentos, 2); ?> MXN</h4>
    <h4>Total Final: <?php echo number_format($total - $total_descuentos, 2); ?> MXN</h4>

    <form method="POST">
        <button type="submit" name="confirmar_venta" class="btn btn-success w-100">Confirmar Venta</button>
    </form>
</div>
</body>
</html>