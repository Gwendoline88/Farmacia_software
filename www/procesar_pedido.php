<?php
session_start(); // Inicia la sesión

include('db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_proveedor = $_POST['id_proveedor'];
    $id_sucursal = $_POST['id_sucursal'];
    $productos = $_POST['productos'];

    $errores = [];
    $numero_pedido = uniqid('PED-');

    foreach ($productos as $id_producto => $cantidad) {
        if ($cantidad > 0) {
            $sql = "SELECT stock FROM productos WHERE id_producto = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id_producto);
            $stmt->execute();
            $result = $stmt->get_result();
            $producto = $result->fetch_assoc();

            if ($producto['stock'] < $cantidad) {
                $errores[] = "El producto con ID $id_producto no tiene suficiente stock.";
            }
        }
    }

    if (!empty($errores)) {
        $_SESSION['errores'] = $errores;
        header("Location: registrar_pedido.php");
        exit();
    }

    $sql_pedido = "INSERT INTO pedidos (numero_pedido, id_proveedor, id_sucursal, fecha_pedido) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql_pedido);
    $stmt->bind_param("sii", $numero_pedido, $id_proveedor, $id_sucursal);
    $stmt->execute();
    $id_pedido = $conn->insert_id;

    foreach ($productos as $id_producto => $cantidad) {
        if ($cantidad > 0) {
            $sql_detalle = "INSERT INTO detalles_pedido (id_pedido, id_producto, cantidad_solicitada, cantidad_recibida) VALUES (?, ?, ?, 0)";
            $stmt = $conn->prepare($sql_detalle);
            $stmt->bind_param("iii", $id_pedido, $id_producto, $cantidad);
            $stmt->execute();

            $sql_stock = "UPDATE productos SET stock = stock - ? WHERE id_producto = ?";
            $stmt = $conn->prepare($sql_stock);
            $stmt->bind_param("ii", $cantidad, $id_producto);
            $stmt->execute();
        }
    }

    header("Location: generar_pdf.php?id_pedido=$id_pedido");
    exit();
}
?>