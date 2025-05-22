<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
  echo json_encode(["success" => false, "message" => "Datos inválidos"]);
  exit;
}

$mysqli = new mysqli("localhost", "root", "", "Tienda");
if ($mysqli->connect_errno) {
  echo json_encode(["success" => false, "message" => "Error de conexión"]);
  exit;
}

$errores = 0;

foreach ($data as $p) {
  $id = intval($p["id"]);
  $activo = intval($p["activo"]);

  $stmt = $mysqli->prepare("UPDATE paquete SET activo = ? WHERE id = ?");
  $stmt->bind_param("ii", $activo, $id);
  if (!$stmt->execute()) {
    $errores++;
  }
  $stmt->close();
}

$mysqli->close();

if ($errores === 0) {
  echo json_encode(["success" => true, "message" => "Estados actualizados"]);
} else {
  echo json_encode(["success" => false, "message" => "Algunos cambios no se aplicaron"]);
}
