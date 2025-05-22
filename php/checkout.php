<?php
require __DIR__ . '/vendor/autoload.php';
$cred = require __DIR__ . '/credenciales.php';
\Stripe\Stripe::setApiKey($cred['STRIPE_SECRET']);

header('Content-Type: application/json');

// Recogemos los datos del carrito enviados por fetch desde JS
$input = file_get_contents("php://input");
$data = json_decode($input, true);

$line_items = [];

foreach ($data as $item) {
    $line_items[] = [
        'price_data' => [
            'currency' => 'eur',
            'product_data' => [
                'name' => $item['nombre'],
            ],
            'unit_amount' => intval($item['precio'] * 100), // en céntimos
        ],
        'quantity' => intval($item['cantidad']),
    ];
}

$YOUR_DOMAIN = 'http://localhost/iHodeiShop';

$checkout_session = \Stripe\Checkout\Session::create([
    'line_items' => $line_items,
    'mode' => 'payment',
    'success_url' => $YOUR_DOMAIN . '/windows/success.html',
    'cancel_url' => $YOUR_DOMAIN . '/windows/cancel.html',
    'customer_creation' => 'always',
    'phone_number_collection' => ['enabled' => true],
    'payment_intent_data' => [
      'metadata' => [
        'productos' => implode(", ", array_column($data, 'nombre')) // <-- Aquí
      ]
    ]
  ]);
  



echo json_encode(['id' => $checkout_session->id]);
