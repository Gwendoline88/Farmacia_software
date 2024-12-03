<?php
session_start();
require('db.php');

if (!isset($_SESSION['medicamentos_controlados'])) {
    $_SESSION['medicamentos_controlados'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Agregar producto al carrito
    if (isset($_POST['agregar'])) {
        $id_producto = $_POST['id_producto'];
        $cantidad = (int)$_POST['cantidad'];
        $paciente = $_POST['paciente'];
        $doctor = $_POST['doctor'];
        $telefono_doctor = $_POST['telefono_doctor'];
        $cedula_doctor = $_POST['cedula_doctor'];
        $fecha_receta = $_POST['fecha_receta'];

        $sql = "SELECT * FROM productos WHERE id_producto = ? AND clasificacion = 'Medicamento controlado'";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("Error en la consulta SQL: " . $conn->error);
        }

        $stmt->bind_param("i", $id_producto);
        $stmt->execute();
        $producto = $stmt->get_result()->fetch_assoc();

        if ($producto) {
            // Verificar stock
            if ($producto['stock'] >= $cantidad) {
                $_SESSION['medicamentos_controlados'][] = [
                    'id_producto' => $producto['id_producto'],
                    'nombre' => $producto['nombre'],
                    'precio' => $producto['precio'],
                    'cantidad' => $cantidad,
                    'subtotal' => $producto['precio'] * $cantidad,
                    'paciente' => $paciente,
                    'doctor' => $doctor,
                    'telefono_doctor' => $telefono_doctor,
                    'cedula_doctor' => $cedula_doctor,
                    'fecha_receta' => $fecha_receta
                ];
            } else {
                $mensaje_error = "Stock insuficiente para el producto seleccionado.";
            }
        } else {
            $mensaje_error = "El producto seleccionado no es un medicamento controlado.";
        }
    }

    // Confirmar venta
    if (isset($_POST['confirmar_venta'])) {
        if (!empty($_SESSION['medicamentos_controlados'])) {
            foreach ($_SESSION['medicamentos_controlados'] as $item) {
                $sql = "INSERT INTO medicamentos_controlados_ventas (
                            id_producto, cantidad, precio_unitario, subtotal, paciente, doctor, telefono_doctor, cedula_doctor, fecha_receta
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);

                if (!$stmt) {
                    die("Error en la consulta SQL: " . $conn->error);
                }

                $stmt->bind_param(
                    "iiddsssss",
                    $item['id_producto'],
                    $item['cantidad'],
                    $item['precio'],
                    $item['subtotal'],
                    $item['paciente'],
                    $item['doctor'],
                    $item['telefono_doctor'],
                    $item['cedula_doctor'],
                    $item['fecha_receta']
                );

                $stmt->execute();

                // Reducir el stock del producto
                $sql_update_stock = "UPDATE productos SET stock = stock - ? WHERE id_producto = ?";
                $stmt_stock = $conn->prepare($sql_update_stock);
                $stmt_stock->bind_param("ii", $item['cantidad'], $item['id_producto']);
                $stmt_stock->execute();
            }

            // Vaciar el carrito
            $_SESSION['medicamentos_controlados'] = [];
            $mensaje_exito = "Venta confirmada exitosamente.";
        } else {
            $mensaje_error = "No hay productos en el carrito.";
        }
    }
}

// Calcular totales
$total = 0;
foreach ($_SESSION['medicamentos_controlados'] as $item) {
    $total += $item['subtotal'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicamentos Controlados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <a href="index.php" class="btn btn-secondary mb-4">Regresar al Menú</a>
    <h2 class="text-center mb-4">Medicamentos Controlados</h2>

    <?php if (isset($mensaje_error)): ?>
        <div class="alert alert-danger"><?php echo $mensaje_error; ?></div>
    <?php endif; ?>

    <?php if (isset($mensaje_exito)): ?>
        <div class="alert alert-success"><?php echo $mensaje_exito; ?></div>
    <?php endif; ?>

    <form method="POST" class="row mb-4">
        <div class="col-md-2">
            <select name="id_producto" class="form-select" required>
                <option value="">Seleccione un producto</option>
                <?php
                $result = $conn->query("SELECT id_producto, nombre FROM productos WHERE clasificacion = 'Medicamento controlado'");
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='{$row['id_producto']}'>{$row['nombre']}</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-1">
            <input type="number" name="cantidad" class="form-control" placeholder="Cantidad" min="1" required>
        </div>
        <div class="col-md-2">
            <input type="text" name="paciente" class="form-control" placeholder="Paciente" required>
        </div>
        <div class="col-md-2">
            <input type="text" name="doctor" class="form-control" placeholder="Doctor" required>
        </div>
        <div class="col-md-2">
            <input type="text" name="telefono_doctor" class="form-control" placeholder="Teléfono Doctor" required>
        </div>
        <div class="col-md-2">
            <input type="text" name="cedula_doctor" class="form-control" placeholder="Cédula Doctor" required>
        </div>
        <div class="col-md-1">
            <input type="date" name="fecha_receta" class="form-control" required>
        </div>
        <div class="col-md-12 mt-2 text-center">
            <button type="submit" name="agregar" class="btn btn-primary">Agregar</button>
        </div>
    </form>

    <table class="table">
        <thead>
        <tr>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Precio Unitario</th>
            <th>Subtotal</th>
            <th>Paciente</th>
            <th>Doctor</th>
            <th>Teléfono Doctor</th>
            <th>Cédula Doctor</th>
            <th>Fecha Receta</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($_SESSION['medicamentos_controlados'] as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                <td><?php echo $item['cantidad']; ?></td>
                <td><?php echo number_format($item['precio'], 2); ?> MXN</td>
                <td><?php echo number_format($item['subtotal'], 2); ?> MXN</td>
                <td><?php echo htmlspecialchars($item['paciente']); ?></td>
                <td><?php echo htmlspecialchars($item['doctor']); ?></td>
                <td><?php echo htmlspecialchars($item['telefono_doctor']); ?></td>
                <td><?php echo htmlspecialchars($item['cedula_doctor']); ?></td>
                <td><?php echo htmlspecialchars($item['fecha_receta']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Total: <?php echo number_format($total, 2); ?> MXN</h3>

    <form method="POST">
        <button type="submit" name="confirmar_venta" class="btn btn-success w-100">Confirmar Venta</button>
    </form>
</div>
</body>
</html>