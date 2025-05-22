<?php
header('Content-Type: application/json');

// Conexión a MySQL
$mysqli = new mysqli('localhost', 'root', '', 'Tienda');

if ($mysqli->connect_error) {
  http_response_code(500);
  echo json_encode(['error' => 'Error de conexión']);
  exit;
}

// Traer todas las cenas (activadas o no)
$query = "SELECT * FROM cena ORDER BY fecha DESC, hora DESC";
$result = $mysqli->query($query);

$cenas = [];

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    // Formatear fecha y hora
    $row['fecha'] = date('Y-m-d', strtotime($row['fecha']));
    $row['hora'] = date('H:i', strtotime($row['hora']));
    $cenas[] = $row;
  }
}

echo json_encode($cenas, JSON_UNESCAPED_UNICODE);

$mysqli->close();
