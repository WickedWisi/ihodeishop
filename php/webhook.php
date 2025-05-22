<?php
require __DIR__ . '/vendor/autoload.php';
$cred = require __DIR__ . '/credenciales.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

\Stripe\Stripe::setApiKey($cred['STRIPE_SECRET']);

$payload = @file_get_contents("php://input");
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$endpoint_secret = $cred['STRIPE_WEBHOOK_SECRET'];

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
} catch (\UnexpectedValueException $e) {
    http_response_code(400);
    exit("Payload invalido");
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    exit("Firma invalida");
}

if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;

    if (!empty($session->payment_intent)) {
        $intentId = $session->payment_intent;
        $intent = \Stripe\PaymentIntent::retrieve($intentId);

        $lineItems = \Stripe\Checkout\Session::allLineItems($session->id);
        $cenasCompradas = [];

        $nombreProducto = $lineItems->data[0]->description ?? 'Producto';
        $cantidadEntradas = $lineItems->data[0]->quantity ?? 1;
        $mysqli = new mysqli("localhost", "root", "", "Tienda");
        if ($mysqli->connect_error) {
            error_log("Error de conexión MySQL: " . $mysqli->connect_error);
            http_response_code(500);
            exit("Error de conexión a la base de datos.");
        }

        foreach ($lineItems->data as $item) {
            $nombre = $item->description ?? '';
            $cantidad = $item->quantity ?? 1;

            if (stripos($nombre, 'cena') !== false && preg_match('/(\d{4}-\d{2}-\d{2})/', $nombre, $match)) {
                $fecha = $match[1];
                $stmt = $mysqli->prepare("SELECT imagenEntrada, id, lugar, hora, stock FROM cena WHERE fecha = ?");
                $stmt->bind_param("s", $fecha);
                $stmt->execute();
                $resultado = $stmt->get_result();
                if ($fila = $resultado->fetch_assoc()) {
                    $imagePath = $_SERVER['DOCUMENT_ROOT'] . '/iHodeiShop/' . $fila['imagenEntrada'];
                    $cenasCompradas[] = [
                        'fecha' => $fecha,
                        'hora' => substr($fila['hora'], 0, 5),
                        'lugar' => $fila['lugar'],
                        'imagen' => $imagePath,
                        'cantidad' => $cantidad
                    ];
                    $stmtUpdate = $mysqli->prepare("UPDATE cena SET stock = GREATEST(stock - ?, 0) WHERE id = ?");
                    $stmtUpdate->bind_param("ii", $cantidad, $fila['id']);
                    $stmtUpdate->execute();
                    $stmtUpdate->close();
                }
                $stmt->close();
            }
        }

        $mysqli->close();


        if (empty($intent->metadata['productos'])) {
            \Stripe\PaymentIntent::update($intentId, [
                'metadata' => [
                    'productos' => $nombreProducto
                ]
            ]);
        }

            // Email
            $emailCliente = null;
            if (!empty($session->customer_details->email)) {
                $emailCliente = $session->customer_details->email;
            } elseif (!empty($session->customer)) {
                $cliente = \Stripe\Customer::retrieve($session->customer);
                $emailCliente = $cliente->email ?? null;
            }

            if ($emailCliente) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp-relay.brevo.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = $cred['SMTP_USER'];
                    $mail->Password = $cred['SMTP_PASS'];

                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;
                    $mail->CharSet = 'UTF-8';
                    $mail->setFrom($cred['SMTP_USER'], 'iHodei Shop');
                    $mail->addAddress($emailCliente);
                    
                    // Discriminacion por tipo de producto
                if (count($cenasCompradas) >= 2) {
                    $mail->Subject = "Entradas para tus cenas del " . implode(" y ", array_map(fn($c) => date("d/m", strtotime($c['fecha'])), $cenasCompradas)) . " 🍝😄";
                    $mensaje = "Holaaa! 👋\n\nYa formas parte de nuestras próximas **Cenas de Chistes**, y no sabes la ilusión que nos hace tenerte con nosotros 😍.\n\n";
                    $mensaje .= "🪪 Entradas compradas:\n\n";

                foreach ($cenasCompradas as $index => $cena) {
                    $n = $index + 1;
                    $mensaje .= "🍽️ Cena $n:\n";
                    $mensaje .= "📅 Fecha: {$cena['fecha']}\n";
                    $mensaje .= "⏰ Hora: {$cena['hora']}\n";
                    $mensaje .= "📍 Lugar: {$cena['lugar']}\n";
                    $mensaje .= "🎟️ Entradas: {$cena['cantidad']}\n\n";

                    if ($cena['imagen'] && file_exists($cena['imagen'])) {
                        $mail->addAttachment($cena['imagen'], "entrada-{$cena['fecha']}.png");
                    } else {
                        error_log("Imagen no encontrada para la fecha: {$cena['fecha']}");
                    }
                }

                        $mensaje .= "🧾 Tus entradas van adjuntas en este mismo correo, una por cada cena que hayas reservado (puedes enseñarlas desde el móvil sin problema).\n\n";
                        $mensaje .= "🧠 Recuerda traer:\n";
                        $mensaje .= "- Buen humor (¡o al menos ganas de reír!)\n";
                        $mensaje .= "- Hambre (la cena va en serio 🍽️)\n";
                        $mensaje .= "- Y lo más importante... un chiste preparado sí o sí! 😎\n\n";
                        $mensaje .= "Nos hace mucha ilusión compartir estas noches especiales contigo. ¡Cualquier duda, estamos aquí para ayudarte!\n\n";
                        $mensaje .= "Un abrazo del equipo de iHodei 💙";
                    }
                    elseif (stripos($nombreProducto, 'cena') !== false) {

                    $mail->Subject = "¡Ya tienes mesa en la Cena de Chistes! 🍝😄";
                    $mensaje = "Holaaa!👋\n\nYa formas parte de nuestra proxima Cena de Chistes, y no sabes la ilusion que nos hace tenerte en la mesa (aunque aun no sabemos si contaras chistes... o solo los sufriras 😅)\n\n";
                    $mensaje .= "📍 Lugar: $lugarEvento\n";
                    $mensaje .= "🗓️ Fecha y hora: $fechaEvento a las $horaEvento\n";
                    $mensaje .= "🎟️ Entrada adjunta: en este correo encontraras tu entrada en formato imagen.\n";
                    $mensaje .= "Solo tienes que ensenarla al llegar (desde el movil vale perfectamente 📱)\n\n";
                    $mensaje .= "Que necesitas traer?\n";
                    $mensaje .= "- Buen humor (o al menos ganas de reir)\n";
                    $mensaje .= "- Hambre (la cena va en serio)\n";
                    $mensaje .= "- Y lo mas importante... un chiste preparado si o si! 😎\n\n";
                    $mensaje .= "Sera una noche especial, y nos alegra mucho que estes dentro. Cualquier duda que tengas, aqui estamos para ayudarte.\n\n";
                    $mensaje .= "Un abrazo del equipo de iHodei 💙";

                    if ($imagePath && file_exists($imagePath)) {
                        $mail->addAttachment($imagePath, 'entrada-cena.png');
                    } else {
                        error_log("Imagen no encontrada para la fecha: $fechaEvento");
                    }
                } elseif (preg_match('/\bhoras\b|\bhora\b/i', $nombreProducto)) {
                    $mail->Subject = "Tu tiempo con iHodei empieza ahora ⏳";
                    $mensaje = "Holaaa! 👋\n\nHemos recibido correctamente tu compra del paquete de horas de iHodei.\n\n";
                    $mensaje .= "Muchas gracias por confiar en nosotros!\n\n";
                    $mensaje .= "Muy pronto nos pondremos en contacto contigo para organizar todo y que puedas empezar a aprovechar tus horas al maximo.\n\n";
                    $mensaje .= "Este mensaje es automatico, no hace falta que respondas 😊:)\n\n";
                    $mensaje .= "Un saludo del equipo de iHodei 💙";
                } elseif (stripos($nombreProducto, 'kit') !== false || stripos($nombreProducto, 'bono') !== false) {
                    $mail->Subject = "Empezamos con tu Kit Digital 🚀";
                    $mensaje = "Holaaa!\n\nHemos recibido tu solicitud para la tramitacion del Kit Digital, y queremos darte las gracias por confiar en iHodei para acompanarte en este proceso.\n\n";
                    $mensaje .= "Nuestro equipo ya esta trabajando en tu expediente. En breve nos pondremos en contacto contigo para iniciar los primeros pasos y asegurarnos de que todo vaya como la seda.\n\n";
                    $mensaje .= "Este mensaje es automatico, no hace falta que respondas.\n\n";
                    $mensaje .= "Seguimos en contacto muy pronto 😉:)\n\n";
                    $mensaje .= "Un saludo del equipo de iHodei 💙";
                } else {
                    $mail->Subject = "Gracias por tu compra en iHodei!";
                    $mensaje = "Hola!\n\nGracias por tu compra en iHodei Shop. Producto: $nombreProducto. Cantidad: $cantidadEntradas.\n\nUn saludo!";
                }

                $mail->Body = $mensaje;
                $mail->send();
            } catch (Exception $e) {
                error_log("Error al enviar email: " . $mail->ErrorInfo);
            }
        }
    }
}

http_response_code(200);
echo "OK";
