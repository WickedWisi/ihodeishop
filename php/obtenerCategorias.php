<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$mysqli = new mysqli("localhost", "root", "", "Tienda");

if ($mysqli->connect_errno) {
  echo json_encode([]);
  exit;
}

$query = "SELECT DISTINCT categoria FROM bono WHERE activo = 1 ORDER BY categoria ASC";

$result = $mysqli->query($query);

$categorias = [];
while ($row = $result->fetch_assoc()) {
  $categorias[] = $row["categoria"];
}

echo json_encode($categorias);

$mysqli->close();
?>
