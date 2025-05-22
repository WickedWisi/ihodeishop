<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$esJSON = strpos($_SERVER["CONTENT_TYPE"] ?? "", "application/json") !== false;

if ($esJSON) {
    $input = json_decode(file_get_contents("php://input"), true);
} else {
    $input = $_POST;
}

// Validación de datos
if (
    !$input ||
    !isset($input["id"]) ||
    !isset($input["fecha"]) ||
    !isset($input["hora"]) ||
    !isset($input["lugar"]) ||
    !isset($input["stock"]) ||
    !isset($input["precio"])
) {
    echo json_encode(["success" => false, "error" => "Faltan datos obligatorios"]);
    exit;
}

$mysqli = new mysqli("localhost", "root", "", "Tienda");

if ($mysqli->connect_errno) {
    echo json_encode(["success" => false, "error" => "Error de conexión"]);
    exit;
}

// Escapar y preparar datos
$id = intval($input["id"]);
$fecha = $mysqli->real_escape_string($input["fecha"]);
$hora = $mysqli->real_escape_string($input["hora"]);
$lugar = $mysqli->real_escape_string($input["lugar"]);
$stock = intval($input["stock"]);
$precio = floatval($input["precio"]);

$imagenEntrada = null;

// Si se ha enviado una nueva imagen
if (isset($_FILES['imagenEntrada']) && $_FILES['imagenEntrada']['error'] === UPLOAD_ERR_OK) {
    $nombreLimpio = date('Y-m-d', strtotime($fecha)); // entrada_2025-07-25
    $extension = pathinfo($_FILES['imagenEntrada']['name'], PATHINFO_EXTENSION);
    $nombreArchivo = 'entrada_' . $nombreLimpio . '.' . $extension;
    $rutaDestino = __DIR__ . '/../entradas/' . $nombreArchivo;
    $rutaBD = 'entradas/' . $nombreArchivo;

    if (!move_uploaded_file($_FILES['imagenEntrada']['tmp_name'], $rutaDestino)) {
        echo json_encode(["success" => false, "error" => "No se pudo guardar la nueva imagen"]);
        exit;
    }

    $imagenEntrada = $rutaBD;
}

// Actualizar cena
if ($imagenEntrada) {
    $query = "UPDATE cena SET fecha = ?, hora = ?, lugar = ?, stock = ?, precio = ?, imagenEntrada = ? WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("sssidsi", $fecha, $hora, $lugar, $stock, $precio, $imagenEntrada, $id);
} else {
    $query = "UPDATE cena SET fecha = ?, hora = ?, lugar = ?, stock = ?, precio = ? WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("sssidi", $fecha, $hora, $lugar, $stock, $precio, $id);
}

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => "Error al actualizar: " . $stmt->error]);
}

$stmt->close();
$mysqli->close();
