const stripe = Stripe(
  "pk_live_51QJlGgRo1VjWcWKpCKKiO329qVF1nPnOOXEGvWzdyAC97pFueYvTca2BOBz475eprKzdN92y8khQWCBZPZUJDkXB00zhLzRCEt"
); // Reemplaza con tu clave pública real

document
  .getElementById("comprar-basico")
  ?.addEventListener("click", function () {
    fetch("php/checkout.php", {
      method: "POST",
    })
      .then((res) => res.json())
      .then((data) => {
        return stripe.redirectToCheckout({ sessionId: data.id });
      })
      .catch((error) => console.error("Error:", error));
  });
