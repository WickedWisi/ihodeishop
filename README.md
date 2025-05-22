# 🛒 iHodei Shop

Tienda web desarrollada con HTML, CSS, JavaScript y PHP para la venta de productos, paquetes de horas y entradas para eventos como la Cena de Chistes. Incluye integración con Stripe para pagos y sistema de gestión de stock.

## 🚀 Características principales

- Visualización dinámica de productos y paquetes.
- Compra de entradas para eventos con control de stock.
- Generación de enlaces de pago con Stripe Checkout.
- Envío automático de emails con PHPMailer y archivos adjuntos (entradas).
- Módulo de carrito persistente con `localStorage`.
- Panel de administración desde extensión de Chrome (fuera del repo).

## 🗂️ Estructura del proyecto

```
├── json/                 # Archivos JSON (ej: productos)
├── php/                  # Scripts del servidor (PHP, Stripe, correo)
├── scripts/              # JavaScript del frontend
├── video/                # Videos promocionales
├── windows/              # Páginas HTML individuales
├── .gitignore            # Ignora archivos sensibles
├── composer.json         # Dependencias PHP (PHPMailer, Stripe)
├── composer.lock
└── index.html            # Página principal
```

## ⚙️ Requisitos

Para poder ejecutar este proyecto en local necesitas tener instalado:

- **PHP 8.x o superior** (puedes usar XAMPP, MAMP o similar)
- **Composer** (para instalar dependencias de PHP como PHPMailer y Stripe)
- **Cuenta de Stripe** (para generar claves de API públicas y secretas)
- **Servidor local Apache + MySQL** (como XAMPP)

### Instalación de dependencias (solo una vez):

Dentro del directorio `/php`, ejecuta:

```bash
composer install
```

Esto instalará automáticamente las librerías necesarias (PHPMailer, Stripe PHP SDK, etc.) según lo definido en composer.json.

## 🔐 Variables sensibles

Guarda tus claves privadas en `php/credenciales.php`, que está ignorado por Git:

```php
return [
  'STRIPE_SECRET' => 'sk_live_...',
  'STRIPE_WEBHOOK_SECRET' => 'whsec_...',
  'SMTP_USER' => '...',
  'SMTP_PASS' => '...'
];
```

## 💳 Integración con Stripe

Los pagos se procesan mediante Stripe Checkout. La clave pública está en el archivo:

```
scripts/stripe-init.js
```

La generación de sesiones de pago se realiza en:

```
php/checkout.php
```

## 📧 Envío de emails

- Usamos PHPMailer con SMTP de Brevo.
- Adjuntamos entradas en formato imagen.
- Los correos se personalizan según el producto comprado.

## 📦 Paquetes y eventos

- Los eventos como la Cena de Chistes tienen stock limitado y temporizador.
- Se generan entradas personalizadas que se envían por email.
