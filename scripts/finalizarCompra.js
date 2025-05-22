document.addEventListener("DOMContentLoaded", () => {
  const stripe = Stripe(
    "pk_live_51QJlGgRo1VjWcWKpCKKiO329qVF1nPnOOXEGvWzdyAC97pFueYvTca2BOBz475eprKzdN92y8khQWCBZPZUJDkXB00zhLzRCEt"
  );

  const botonFinalizar = document.getElementById("finalizar-compra");
  if (!botonFinalizar) return;

  botonFinalizar.addEventListener("click", () => {
    // Calcular la cantidad de niveles a subir en el path
    const niveles =
      window.location.pathname
        .replace(/\/[^/]*$/, "")
        .split("/")
        .filter(Boolean).length - 1;

    const subida = Array(niveles).fill("..").join("/") || ".";
    const rutaCheckout = `${subida}/php/checkout.php`;

    fetch(rutaCheckout, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(window.carrito),
    })
      .then((res) => res.json())
      .then((data) => {
        // Vaciar carrito
        window.carrito.length = 0;
        localStorage.removeItem("carrito");
        window.actualizarCarrito();

        // Redirigir a Stripe
        return stripe.redirectToCheckout({ sessionId: data.id });
      })
      .catch((err) => {
        console.error("Error al crear sesión:", err);
        alert("Error al procesar el pago. Intenta de nuevo.");
      });
  });
});
