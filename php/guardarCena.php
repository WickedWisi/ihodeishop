<?php
header('Content-Type: application/json');

// Comprobar que se recibe una imagen y datos por POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'error' => 'Método no permitido']);
  exit;
}

// Conexión a la base de datos
$mysqli = new mysqli('localhost', 'root', '', 'Tienda');
if ($mysqli->connect_error) {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Conexión fallida']);
  exit;
}

// Obtener campos
$fecha = $mysqli->real_escape_string($_POST['fecha'] ?? '');
$hora = $mysqli->real_escape_string($_POST['hora'] ?? '');
$lugar = $mysqli->real_escape_string($_POST['lugar'] ?? '');
$stock = intval($_POST['stock'] ?? 0);
$precio = floatval($_POST['precio'] ?? 0);
$activado = 1;

// Procesar imagen
if (!isset($_FILES['imagenEntrada']) || $_FILES['imagenEntrada']['error'] !== UPLOAD_ERR_OK) {
  echo json_encode(['success' => false, 'error' => 'Imagen no recibida correctamente']);
  exit;
}

$nombreLimpio = date('Y-m-d', strtotime($fecha)); // entrada_2025-07-25
$extension = pathinfo($_FILES['imagenEntrada']['name'], PATHINFO_EXTENSION);
$nombreArchivo = 'entrada_' . $nombreLimpio . '.' . $extension;
$rutaDestino = __DIR__ . '/../entradas/' . $nombreArchivo;
$rutaBD = 'entradas/' . $nombreArchivo; // Esta es la que se guarda en BD

if (!move_uploaded_file($_FILES['imagenEntrada']['tmp_name'], $rutaDestino)) {
  echo json_encode(['success' => false, 'error' => 'No se pudo mover la imagen']);
  exit;
}

// Insertar en la base de datos incluyendo la ruta de la imagen
$sql = "INSERT INTO cena (fecha, hora, lugar, stock, precio, activado, imagenEntrada)
        VALUES ('$fecha', '$hora', '$lugar', $stock, $precio, $activado, '$rutaBD')";

if ($mysqli->query($sql)) {
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false, 'error' => $mysqli->error]);
}

$mysqli->close();
