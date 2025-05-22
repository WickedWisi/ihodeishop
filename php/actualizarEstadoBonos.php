<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Recibir y decodificar datos JSON
$datos = json_decode(file_get_contents("php://input"), true);

if (!$datos || !is_array($datos)) {
  echo json_encode(["status" => "error", "message" => "No se recibieron datos válidos."]);
  exit;
}

// Conexión a la base de datos
$mysqli = new mysqli("localhost", "root", "", "Tienda");

if ($mysqli->connect_errno) {
  echo json_encode(["status" => "error", "message" => "Error de conexión a la base de datos."]);
  exit;
}

$query = "UPDATE bono SET activo = ? WHERE id = ?";
$stmt = $mysqli->prepare($query);

$errores = 0;

foreach ($datos as $item) {
  $id = intval($item["id"]);
  $activo = intval($item["activo"]);

  $stmt->bind_param("ii", $activo, $id);

  if (!$stmt->execute()) {
    $errores++;
  }
}

$stmt->close();
$mysqli->close();

if ($errores === 0) {
  echo json_encode(["status" => "ok", "message" => "Estados actualizados correctamente."]);
} else {
  echo json_encode(["status" => "warning", "message" => "Algunos productos no se actualizaron."]);
}
?>
