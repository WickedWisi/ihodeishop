<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Recibir y decodificar los datos JSON
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
  echo json_encode(["status" => "error", "message" => "No se recibieron datos"]);
  exit;
}

// Conexión a la base de datos
$mysqli = new mysqli("localhost", "root", "", "Tienda");

if ($mysqli->connect_errno) {
  echo json_encode(["status" => "error", "message" => "Fallo al conectar a la BD"]);
  exit;
}

// Preparar datos
$nombre = $mysqli->real_escape_string($data["nombre"]);
$categoria = $mysqli->real_escape_string($data["categoria"]);
$desCorta = $mysqli->real_escape_string($data["desCorta"]);
$precio = floatval($data["precio"]);
$descripcionRaw = $data["descripcion"];
$descripcionNormalizada = preg_replace('/\\\\n/', "\n", $descripcionRaw);
$descripcion = $mysqli->real_escape_string($descripcionNormalizada);
$imagen = $mysqli->real_escape_string($data["imagen"]);
$linkCompra = $mysqli->real_escape_string($data["linkCompra"]);
$caracteristicas = json_encode($data["caracteristicas"], JSON_UNESCAPED_UNICODE);

// Insertar en la base de datos (sin ID manual)
$query = "INSERT INTO bono (nombre, categoria, desCorta, precio, descripcion, caracteristicas, imagen, linkCompra)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("sssdssss", $nombre, $categoria, $desCorta, $precio, $descripcion, $caracteristicas, $imagen, $linkCompra);

if ($stmt->execute()) {
  echo json_encode(["status" => "ok", "message" => "Producto guardado correctamente."]);
} else {
  echo json_encode(["status" => "error", "message" => "Error al guardar: " . $stmt->error]);
}

$stmt->close();
$mysqli->close();
?>
