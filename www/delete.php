<?php
include ("db.php");

if(isset($_GET['id'])){
    $id = $_GET['id'];
    $query = "DELETE FROM Productos WHERE idProducto=$id";
    $result = mysqli_query($conn,$query);

    if(!$result){
        die("No se Elimino el registro");
    }
    $_SESSION['message']='Producto Eliminado';
    $_SESSION['message_type']='danger';
    header("Location: index.php");
}
?>