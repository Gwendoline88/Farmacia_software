<?php
session_start(); // Inicia la sesión

include('db.php'); // Conexión a la base de datos

if (!isset($_SESSION['nombre_usuario'])) {
    header("Location: login.php");
    exit();
}

$mensaje = "";

// Acción: Agregar Cliente
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == "agregar") {
    $nombre = $_POST['nombre'];
    $apellido_paterno = $_POST['apellido_paterno'];
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];

    // Verificar si el número telefónico ya existe
    $sql_check = "SELECT * FROM clientes WHERE telefono = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $telefono);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $mensaje = "Error: El número telefónico ya está registrado.";
    } else {
        $sql = "INSERT INTO clientes (nombre, apellido_paterno, telefono, direccion) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $nombre, $apellido_paterno, $telefono, $direccion);

        if ($stmt->execute()) {
            $mensaje = "Cliente registrado con éxito!";
        } else {
            $mensaje = "Error en el registro.";
        }
    }
}

// Acción: Actualizar Cliente
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == "actualizar") {
    $id_cliente = $_POST['id_cliente'];
    $nombre = $_POST['nombre'];
    $apellido_paterno = $_POST['apellido_paterno'];
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];

    $sql = "UPDATE clientes SET nombre = ?, apellido_paterno = ?, telefono = ?, direccion = ? WHERE id_cliente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $nombre, $apellido_paterno, $telefono, $direccion, $id_cliente);

    if ($stmt->execute()) {
        $mensaje = "Cliente actualizado con éxito!";
    } else {
        $mensaje = "Error al actualizar el cliente.";
    }
}

// Acción: Eliminar Cliente
if (isset($_GET['eliminar'])) {
    $id_cliente = $_GET['eliminar'];

    $sql = "DELETE FROM clientes WHERE id_cliente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_cliente);

    if ($stmt->execute()) {
        $mensaje = "Cliente eliminado con éxito!";
    } else {
        $mensaje = "Error al eliminar el cliente.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes - Farmacia La Píldora</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Gestión de Clientes</h2>

        <!-- Botón para regresar al menú principal -->
        <div class="mb-4 text-end">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Regresar al Menú
            </a>
        </div>

        <!-- Mensaje de éxito o error -->
        <?php if ($mensaje): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Formulario de Búsqueda -->
        <div class="mb-4">
            <form action="clientes.php" method="GET" class="d-flex">
                <input type="text" name="busqueda" class="form-control me-2" placeholder="Buscar cliente por nombre o teléfono" value="<?php echo isset($_GET['busqueda']) ? htmlspecialchars($_GET['busqueda']) : ''; ?>">
                <button type="submit" class="btn btn-primary">Buscar Cliente</button>
            </form>
        </div>

        <!-- Formulario de Agregar Cliente -->
        <h4 class="text-center mb-3">Añadir Cliente</h4>
        <form action="clientes.php" method="POST">
            <input type="hidden" name="accion" value="agregar">
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre</label>
                <input type="text" class="form-control" name="nombre" required>
            </div>
            <div class="mb-3">
                <label for="apellido_paterno" class="form-label">Apellido Paterno</label>
                <input type="text" class="form-control" name="apellido_paterno" required>
            </div>
            <div class="mb-3">
                <label for="telefono" class="form-label">Número Telefónico</label>
                <input type="text" class="form-control" name="telefono" required>
            </div>
            <div class="mb-3">
                <label for="direccion" class="form-label">Dirección</label>
                <input type="text" class="form-control" name="direccion">
            </div>
            <button type="submit" class="btn btn-primary w-100">Guardar</button>
        </form>

        <!-- Resultados de Búsqueda -->
        <?php
        if (isset($_GET['busqueda']) && $_GET['busqueda'] !== '') {
            $busqueda = $_GET['busqueda'];

            $sql = "SELECT * FROM clientes WHERE nombre LIKE ? OR telefono LIKE ?";
            $stmt = $conn->prepare($sql);
            $param = "%" . $busqueda . "%";
            $stmt->bind_param("ss", $param, $param);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo '<table class="table table-striped mt-4">';
                echo '<thead><tr><th>Nombre</th><th>Apellido</th><th>Teléfono</th><th>Dirección</th><th>Acciones</th></tr></thead>';
                echo '<tbody>';

                while ($row = $result->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['nombre']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['apellido_paterno']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['telefono']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['direccion']) . '</td>';
                    echo '<td>';
                    echo '<a href="clientes.php?editar=' . $row['id_cliente'] . '" class="btn btn-sm btn-warning me-2">Editar</a>';
                    echo '<a href="clientes.php?eliminar=' . $row['id_cliente'] . '" class="btn btn-sm btn-danger">Eliminar</a>';
                    echo '</td>';
                    echo '</tr>';
                }

                echo '</tbody></table>';
            } else {
                echo '<p class="text-danger">No se encontraron resultados para la búsqueda: ' . htmlspecialchars($busqueda) . '</p>';
            }
        }
        ?>

        <!-- Formulario de Edición -->
        <?php
        if (isset($_GET['editar'])) {
            $id_cliente = $_GET['editar'];

            $sql = "SELECT * FROM clientes WHERE id_cliente = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id_cliente);
            $stmt->execute();
            $result = $stmt->get_result();
            $cliente = $result->fetch_assoc();

            if ($cliente) {
                echo '<h4 class="mt-4">Editar Cliente</h4>';
                echo '<form action="clientes.php" method="POST">';
                echo '<input type="hidden" name="id_cliente" value="' . $cliente['id_cliente'] . '">';
                echo '<div class="mb-3"><label class="form-label">Nombre</label>';
                echo '<input type="text" name="nombre" class="form-control" value="' . htmlspecialchars($cliente['nombre']) . '" required></div>';
                echo '<div class="mb-3"><label class="form-label">Apellido Paterno</label>';
                echo '<input type="text" name="apellido_paterno" class="form-control" value="' . htmlspecialchars($cliente['apellido_paterno']) . '" required></div>';
                echo '<div class="mb-3"><label class="form-label">Teléfono</label>';
                echo '<input type="text" name="telefono" class="form-control" value="' . htmlspecialchars($cliente['telefono']) . '" required></div>';
                echo '<div class="mb-3"><label class="form-label">Dirección</label>';
                echo '<input type="text" name="direccion" class="form-control" value="' . htmlspecialchars($cliente['direccion']) . '"></div>';
                echo '<button type="submit" name="accion" value="actualizar" class="btn btn-success">Guardar Cambios</button>';
                echo '</form>';
            }
        }
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>