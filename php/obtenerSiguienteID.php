<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Conexión a la base de datos
$mysqli = new mysqli("localhost", "root", "", "Tienda");

if ($mysqli->connect_errno) {
  echo json_encode(["status" => "error", "message" => "Fallo al conectar a la BD"]);
  exit;
}

// Consulta el último ID numérico
$result = $mysqli->query("SELECT MAX(CAST(SUBSTRING(id, 4) AS UNSIGNED)) AS maxId FROM bono WHERE id LIKE 'kit%'");

if ($result) {
  $row = $result->fetch_assoc();
  $nuevoIdNum = intval($row["maxId"]) + 1;
  $nuevoId = "kit" . $nuevoIdNum;
  echo json_encode(["id" => $nuevoId]);
} else {
  echo json_encode(["status" => "error", "message" => "No se pudo obtener el ID"]);
}

$mysqli->close();
?>
