<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Recibir y decodificar los datos JSON
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data["horas"]) || !isset($data["precio"]) || !isset($data["linkCompra"])) {
  echo json_encode(["status" => "error", "message" => "❌ Faltan datos obligatorios"]);
  exit;
}

// Conexión a la base de datos
$mysqli = new mysqli("localhost", "root", "", "Tienda");

if ($mysqli->connect_errno) {
  echo json_encode(["status" => "error", "message" => "❌ Fallo al conectar a la base de datos"]);
  exit;
}

// Preparar datos
$horas = intval($data["horas"]);
$precio = floatval($data["precio"]);
$linkCompra = $mysqli->real_escape_string($data["linkCompra"]);
$activo = 1; // Activo por defecto

// Insertar en la base de datos
$query = "INSERT INTO paquete (horas, precio, linkCompra, activo) VALUES (?, ?, ?, ?)";
$stmt = $mysqli->prepare($query);

if (!$stmt) {
  echo json_encode(["status" => "error", "message" => "❌ Error preparando la consulta: " . $mysqli->error]);
  exit;
}

$stmt->bind_param("idsi", $horas, $precio, $linkCompra, $activo);

if ($stmt->execute()) {
  echo json_encode(["status" => "ok", "message" => "✅ Paquete guardado correctamente"]);
} else {
  echo json_encode(["status" => "error", "message" => "❌ Error al guardar: " . $stmt->error]);
}

$stmt->close();
$mysqli->close();
