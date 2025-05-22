<?php
require __DIR__ . '/vendor/autoload.php';
$cred = require __DIR__ . '/credenciales.php';
\Stripe\Stripe::setApiKey($cred['STRIPE_SECRET']);


header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data["id"])) {
  echo json_encode(["status" => "error", "message" => "Faltan datos o el ID"]);
  exit;
}

$mysqli = new mysqli("localhost", "root", "", "Tienda");

if ($mysqli->connect_errno) {
  echo json_encode(["status" => "error", "message" => "Fallo al conectar a la BD"]);
  exit;
}

// Recogemos datos
$id = $mysqli->real_escape_string($data["id"]);
$nombre = $mysqli->real_escape_string($data["nombre"]);
$categoria = $mysqli->real_escape_string($data["categoria"]);
$desCorta = $mysqli->real_escape_string($data["desCorta"]);
$precio = floatval($data["precio"]);
$descripcionRaw = $data["descripcion"];
$descripcionNormalizada = preg_replace('/\\\\n/', "\n", $descripcionRaw);
$descripcion = $mysqli->real_escape_string($descripcionNormalizada);

$imagen = $mysqli->real_escape_string($data["imagen"]);
$caracteristicas = json_encode($data["caracteristicas"], JSON_UNESCAPED_UNICODE);

// ✅ Creamos un nuevo Payment Link con precio actualizado
try {
  $product = \Stripe\Product::create(['name' => $nombre]);

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

// ✅ Actualizar en BD con el nuevo enlace
$query = "UPDATE bono 
          SET nombre = ?, categoria = ?, desCorta = ?, precio = ?, descripcion = ?, caracteristicas = ?, imagen = ?, linkCompra = ?
          WHERE id = ?";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("sssdssssi", $nombre, $categoria, $desCorta, $precio, $descripcion, $caracteristicas, $imagen, $linkCompra, $id);

if ($stmt->execute()) {
  echo json_encode(["status" => "ok", "message" => "Producto y enlace actualizados correctamente."]);
} else {
  echo json_encode(["status" => "error", "message" => "Error al actualizar: " . $stmt->error]);
}

$stmt->close();
$mysqli->close();
?>
