<?php
session_start(); // Inicia la sesión

require('fpdf/fpdf.php');
include('db.php');

if (!isset($_GET['id_pedido'])) {
    die("No se especificó un pedido.");
}

$id_pedido = $_GET['id_pedido'];

// Obtener información del pedido
$sql = "SELECT p.numero_pedido, pr.nombre AS proveedor, s.nombre AS sucursal, p.fecha_pedido 
        FROM pedidos p
        JOIN proveedores pr ON p.id_proveedor = pr.id_proveedor
        JOIN sucursales s ON p.id_sucursal = s.id_sucursal
        WHERE p.id_pedido = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_pedido);
$stmt->execute();
$result = $stmt->get_result();
$pedido = $result->fetch_assoc();

if (!$pedido) {
    die("El pedido especificado no existe.");
}

// Obtener detalles del pedido
$sql_detalles = "SELECT pr.nombre AS producto, dp.cantidad_solicitada, dp.cantidad_recibida 
                 FROM detalles_pedido dp
                 JOIN productos pr ON dp.id_producto = pr.id_producto
                 WHERE dp.id_pedido = ?";
$stmt = $conn->prepare($sql_detalles);
$stmt->bind_param("i", $id_pedido);
$stmt->execute();
$result_detalles = $stmt->get_result();

// Crear PDF
class PDF extends FPDF
{
    function Header()
    {
        // Logo
        $this->Image('images/logo.png', 10, 10, 25);
        // Título
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, utf8_decode('Farmacia La Píldora'), 0, 1, 'C');
        $this->SetFont('Arial', 'I', 12);
        $this->Cell(0, 10, 'Reporte de Pedido', 0, 1, 'C');
        $this->Ln(10);
    }

    function Footer()
    {
        // Posición: 1.5 cm del final
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Página ' . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);

// Información del pedido con estilo organizado
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(40, 10, 'Pedido: ', 0, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, $pedido['numero_pedido'], 0, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(40, 10, 'Proveedor: ', 0, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, utf8_decode($pedido['proveedor']), 0, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(40, 10, 'Sucursal: ', 0, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, utf8_decode($pedido['sucursal']), 0, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(40, 10, 'Fecha: ', 0, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, $pedido['fecha_pedido'], 0, 1);
$pdf->Ln(10);

// Tabla de productos con encabezados claros
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(200, 220, 255); // Fondo para encabezados
$pdf->Cell(100, 10, 'Producto', 1, 0, 'C', true);
$pdf->Cell(40, 10, 'Cantidad Solicitada', 1, 0, 'C', true);
$pdf->Cell(40, 10, 'Cantidad Recibida', 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 12);
while ($detalle = $result_detalles->fetch_assoc()) {
    $pdf->Cell(100, 10, utf8_decode($detalle['producto']), 1);
    $pdf->Cell(40, 10, $detalle['cantidad_solicitada'], 1, 0, 'C');
    $pdf->Cell(40, 10, $detalle['cantidad_recibida'], 1, 1, 'C');
}

// Forzar descarga del PDF directamente en el navegador para que se guarde en "Descargas"
$pdf->Output('D', 'Pedido_' . $pedido['numero_pedido'] . '.pdf');