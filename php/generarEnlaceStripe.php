<?php
require __DIR__ . '/vendor/autoload.php';
$cred = require __DIR__ . '/credenciales.php';

\Stripe\Stripe::setApiKey($cred['STRIPE_SECRET']);

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['nombre']) || !isset($data['precio'])) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos']);
    exit;
}

try {
    $product = \Stripe\Product::create(['name' => $data['nombre']]);
    $price = \Stripe\Price::create([
        'unit_amount' => intval($data['precio'] * 100),
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
        'metadata' => [ 
        'productos' => $data['nombre']
    ]
    ]);

    echo json_encode(['link' => $payment_link->url]);  // ✅ Devuelve "link"
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Stripe: ' . $e->getMessage()]);
}
