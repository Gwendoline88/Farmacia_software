<?php
session_start(); // Inicia la sesión

include('db.php'); // Conexión a la base de datos

// Manejar acciones: agregar, editar y eliminar producto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'];

    if ($accion === 'agregar') {
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        $precio = $_POST['precio'];
        $stock = $_POST['stock'];
        $clasificacion = $_POST['clasificacion'];
        $id_proveedor = $_POST['id_proveedor'];

        $sql = "INSERT INTO productos (nombre, descripcion, precio, stock, clasificacion, id_proveedor) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdisi", $nombre, $descripcion, $precio, $stock, $clasificacion, $id_proveedor);
        $stmt->execute();
    }

    if ($accion === 'editar') {
        $id_producto = $_POST['id_producto'];
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        $precio = $_POST['precio'];
        $stock = $_POST['stock'];
        $clasificacion = $_POST['clasificacion'];
        $id_proveedor = $_POST['id_proveedor'];

        $sql = "UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, stock = ?, clasificacion = ?, id_proveedor = ? 
                WHERE id_producto = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdisii", $nombre, $descripcion, $precio, $stock, $clasificacion, $id_proveedor, $id_producto);
        $stmt->execute();
    }

    if ($accion === 'eliminar') {
        $id_producto = $_POST['id_producto'];
        $sql = "DELETE FROM productos WHERE id_producto = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();
    }
}

// Obtener clasificación seleccionada
$clasificacion_seleccionada = isset($_GET['clasificacion']) ? $_GET['clasificacion'] : '';

// Consultar productos según clasificación
if ($clasificacion_seleccionada) {
    $sql_productos = "SELECT p.*, pr.nombre AS proveedor FROM productos p 
                      JOIN proveedores pr ON p.id_proveedor = pr.id_proveedor
                      WHERE p.clasificacion = ?";
    $stmt = $conn->prepare($sql_productos);
    $stmt->bind_param("s", $clasificacion_seleccionada);
    $stmt->execute();
    $result_productos = $stmt->get_result();
} else {
    $sql_productos = "SELECT p.*, pr.nombre AS proveedor FROM productos p 
                      JOIN proveedores pr ON p.id_proveedor = pr.id_proveedor";
    $result_productos = $conn->query($sql_productos);
}

// Consultar proveedores para el formulario
$sql_proveedores = "SELECT id_proveedor, nombre FROM proveedores";
$result_proveedores = $conn->query($sql_proveedores);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<div class="container mt-5">
    <!-- Botón de Regresar al Menú Principal -->
    <a href="index.php" class="btn btn-secondary mb-4"><i class="fas fa-arrow-left"></i> Regresar al Menú Principal</a>

    <h2 class="text-center mb-4">Gestión de Productos</h2>

    <!-- Formulario de Filtro -->
    <form method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-8">
                <select name="clasificacion" class="form-select">
                    <option value="">-- Seleccionar Clasificación --</option>
                    <option value="Antibiótico" <?php echo $clasificacion_seleccionada == 'Antibiótico' ? 'selected' : ''; ?>>Antibiótico</option>
                    <option value="Medicamento en refrigeración" <?php echo $clasificacion_seleccionada == 'Medicamento en refrigeración' ? 'selected' : ''; ?>>Medicamento en refrigeración</option>
                    <option value="Vitaminas/Suplementos" <?php echo $clasificacion_seleccionada == 'Vitaminas/Suplementos' ? 'selected' : ''; ?>>Vitaminas/Suplementos</option>
                    <option value="Medicamento controlado" <?php echo $clasificacion_seleccionada == 'Medicamento controlado' ? 'selected' : ''; ?>>Medicamento controlado</option>
                    <option value="Medicamento de libre venta" <?php echo $clasificacion_seleccionada == 'Medicamento de libre venta' ? 'selected' : ''; ?>>Medicamento de libre venta</option>
                    <option value="Artículos no medicamento – insumos" <?php echo $clasificacion_seleccionada == 'Artículos no medicamento – insumos' ? 'selected' : ''; ?>>Artículos no medicamento – insumos</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </div>
    </form>

    <!-- Botón de Agregar Producto -->
    <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#modalAgregarProducto">
        <i class="fas fa-plus"></i> Agregar Producto
    </button>

    <!-- Tabla de productos -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Clasificación</th>
                <th>Proveedor</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($producto = $result_productos->fetch_assoc()): ?>
                <tr <?php echo $producto['stock'] < 10 ? 'style="background-color: #ffcccc;"' : ''; ?>>
                    <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($producto['descripcion']); ?></td>
                    <td><?php echo htmlspecialchars($producto['precio']); ?> MXN</td>
                    <td><?php echo htmlspecialchars($producto['stock']); ?></td>
                    <td><?php echo htmlspecialchars($producto['clasificacion']); ?></td>
                    <td><?php echo htmlspecialchars($producto['proveedor']); ?></td>
                    <td>
                        <!-- Botón para editar -->
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditarProducto"
                                data-id="<?php echo $producto['id_producto']; ?>"
                                data-nombre="<?php echo $producto['nombre']; ?>"
                                data-descripcion="<?php echo $producto['descripcion']; ?>"
                                data-precio="<?php echo $producto['precio']; ?>"
                                data-stock="<?php echo $producto['stock']; ?>"
                                data-clasificacion="<?php echo $producto['clasificacion']; ?>"
                                data-proveedor="<?php echo $producto['id_proveedor']; ?>">Editar</button>

                        <!-- Botón para eliminar -->
                        <form action="productos.php" method="POST" style="display:inline;">
                            <input type="hidden" name="id_producto" value="<?php echo $producto['id_producto']; ?>">
                            <input type="hidden" name="accion" value="eliminar">
                            <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal para agregar producto -->
<div class="modal fade" id="modalAgregarProducto" tabindex="-1" aria-labelledby="modalAgregarProductoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="productos.php" method="POST">
                <input type="hidden" name="accion" value="agregar">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAgregarProductoLabel">Agregar Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <input type="text" name="descripcion" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="precio" class="form-label">Precio</label>
                        <input type="number" name="precio" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="stock" class="form-label">Stock</label>
                        <input type="number" name="stock" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="clasificacion" class="form-label">Clasificación</label>
                        <select name="clasificacion" class="form-select" required>
                            <option value="">-- Seleccione --</option>
                            <option value="Antibiótico">Antibiótico</option>
                            <option value="Medicamento en refrigeración">Medicamento en refrigeración</option>
                            <option value="Vitaminas/Suplementos">Vitaminas/Suplementos</option>
                            <option value="Medicamento controlado">Medicamento controlado</option>
                            <option value="Medicamento de libre venta">Medicamento de libre venta</option>
                            <option value="Artículos no medicamento – insumos">Artículos no medicamento – insumos</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="id_proveedor" class="form-label">Proveedor</label>
                        <select name="id_proveedor" class="form-select" required>
                            <?php while ($proveedor = $result_proveedores->fetch_assoc()): ?>
                                <option value="<?php echo $proveedor['id_proveedor']; ?>"><?php echo $proveedor['nombre']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para editar producto -->
<div class="modal fade" id="modalEditarProducto" tabindex="-1" aria-labelledby="modalEditarProductoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="productos.php" method="POST">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id_producto" id="editar-id_producto">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarProductoLabel">Editar Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" name="nombre" id="editar-nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <input type="text" name="descripcion" id="editar-descripcion" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="precio" class="form-label">Precio</label>
                        <input type="number" name="precio" id="editar-precio" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="stock" class="form-label">Stock</label>
                        <input type="number" name="stock" id="editar-stock" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="clasificacion" class="form-label">Clasificación</label>
                        <select name="clasificacion" id="editar-clasificacion" class="form-select" required>
                            <option value="">-- Seleccione --</option>
                            <option value="Antibiótico">Antibiótico</option>
                            <option value="Medicamento en refrigeración">Medicamento en refrigeración</option>
                            <option value="Vitaminas/Suplementos">Vitaminas/Suplementos</option>
                            <option value="Medicamento controlado">Medicamento controlado</option>
                            <option value="Medicamento de libre venta">Medicamento de libre venta</option>
                            <option value="Artículos no medicamento – insumos">Artículos no medicamento – insumos</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="id_proveedor" class="form-label">Proveedor</label>
                        <select name="id_proveedor" id="editar-id_proveedor" class="form-select" required>
                            <?php
                            $result_proveedores->data_seek(0); // Reiniciar resultado para volver a usar
                            while ($proveedor = $result_proveedores->fetch_assoc()): ?>
                                <option value="<?php echo $proveedor['id_proveedor']; ?>"><?php echo $proveedor['nombre']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Pasar datos al modal de edición
    const modalEditarProducto = document.getElementById('modalEditarProducto');
    modalEditarProducto.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget;

        const id_producto = button.getAttribute('data-id');
        const nombre = button.getAttribute('data-nombre');
        const descripcion = button.getAttribute('data-descripcion');
        const precio = button.getAttribute('data-precio');
        const stock = button.getAttribute('data-stock');
        const clasificacion = button.getAttribute('data-clasificacion');
        const id_proveedor = button.getAttribute('data-proveedor');

        document.getElementById('editar-id_producto').value = id_producto;
        document.getElementById('editar-nombre').value = nombre;
        document.getElementById('editar-descripcion').value = descripcion;
        document.getElementById('editar-precio').value = precio;
        document.getElementById('editar-stock').value = stock;
        document.getElementById('editar-clasificacion').value = clasificacion;
        document.getElementById('editar-id_proveedor').value = id_proveedor;
    });
</script>
</body>
</html>