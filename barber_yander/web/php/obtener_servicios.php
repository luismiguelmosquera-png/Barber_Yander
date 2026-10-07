<?php
header('Content-Type: application/json');
include 'conexion.php';

$sql = "SELECT id, nombre, precio, emoji, imagen FROM servicios";
$result = $conn->query($sql);

$servicios = array();
if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $servicios[] = $row;
    }
}

echo json_encode($servicios);
$conn->close();
?>
