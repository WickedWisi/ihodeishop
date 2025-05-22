<?php
// Conexión a la base de datos local
$host = 'localhost';
$user = 'root';
$password = '';
$db = 'Tienda';

$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) {
  die("Conexión fallida: " . $conn->connect_error);
}

$sql = "SELECT * FROM bono WHERE activo = 1";
$result = $conn->query($sql);

$bonos = [];

if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    // Si caracteristicas es texto JSON, lo decodificamos para el frontend
    $row['caracteristicas'] = json_decode($row['caracteristicas']);
    $bonos[] = $row;
  }
}

header('Content-Type: application/json');
echo json_encode($bonos, JSON_UNESCAPED_UNICODE);

$conn->close();
?>
