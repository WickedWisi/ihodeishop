<?php
require __DIR__ . '/vendor/autoload.php';
$cred = require __DIR__ . '/credenciales.php';
\Stripe\Stripe::setApiKey($cred['STRIPE_SECRET']);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$data = json_decode(file_get_contents("php://input"), true);

if (
  !$data || 
  !isset($data["id"]) || 
  !isset($data["horas"]) || 
  !isset($data["precio"])
) {
  echo json_encode(["status" => "error", "message" => "Faltan datos obligatorios"]);
  exit;
}

$mysqli = new mysqli("localhost", "root", "", "Tienda");

if ($mysqli->connect_errno) {
  echo json_encode(["status" => "error", "message" => "Error al conectar a la BD"]);
  exit;
}

$id = intval($data["id"]);
$horas = intval($data["horas"]);
$precio = floatval($data["precio"]);
$nombrePaquete = $horas . " horas de asesoramiento";

try {
  $product = \Stripe\Product::create(['name' => $nombrePaquete]);

  $price = \Stripe\Price::create([
    'unit_amount' => intval($precio * 100),
    'currency' => 'eur',
    'product' => $product->id,
  ]);

  $payment_link = \Stripe\PaymentLink::create([
    'line_items' => [[
      'price' => $price->id,
      'quantity' => 1,
    ]],
    'phone_number_collection' => ['enabled' => true],
    'after_completion' => [
      'type' => 'redirect',
      'redirect' => [
        'url' => 'http://localhost/iHodeiShop/windows/success.html',
      ],
    ],
  ]);

  $linkCompra = $payment_link->url;
} catch (Exception $e) {
  echo json_encode(["status" => "error", "message" => "Error con Stripe: " . $e->getMessage()]);
  exit;
}

$query = "UPDATE paquete SET horas = ?, precio = ?, linkCompra = ? WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("idsi", $horas, $precio, $linkCompra, $id);

if ($stmt->execute()) {
  echo json_encode(["success" => true, "message" => "Paquete y enlace actualizados correctamente."]);
} else {
  echo json_encode(["success" => false, "message" => "Error al actualizar: " . $stmt->error]);
}

$stmt->close();
$mysqli->close();
