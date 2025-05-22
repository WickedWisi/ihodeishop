<?php
header('Content-Type: application/json');

// Conexión a MySQL
$mysqli = new mysqli('localhost', 'root', '', 'Tienda');

if ($mysqli->connect_error) {
  http_response_code(500);
  echo json_encode(['error' => 'Error de conexión']);
  exit;
}

// AÑADE activo en el SELECT
$query = "SELECT * FROM paquete WHERE activo = 1 ORDER BY horas ASC";
$result = $mysqli->query($query);

$paquetes = [];

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $paquetes[] = $row;
  }
}

echo json_encode($paquetes, JSON_UNESCAPED_UNICODE);
$mysqli->close();
