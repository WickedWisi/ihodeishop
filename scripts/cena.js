document.addEventListener("DOMContentLoaded", async () => {
  const modal = new bootstrap.Modal(document.getElementById("modalReserva"));
  const inputCantidad = document.getElementById("cantidadEntradas");
  const precioUnidad = document.getElementById("precioUnidad");
  const totalPrecio = document.getElementById("totalPrecio");
  const btnConfirmar = document.getElementById("confirmarReserva");
  const selectCena = document.getElementById("selectCena");
  const contenedor = document.querySelector("#infoCena");
  let datosGlobal = [];

  function actualizarPrecio(precio) {
    const cantidad = parseInt(inputCantidad.value) || 1;
    totalPrecio.textContent = (cantidad * precio).toFixed(2) + " €";
  }

  function formatearFecha(fechaISO) {
    const fecha = new Date(fechaISO);
    return fecha.toLocaleDateString("es-ES", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  }

  try {
    const res = await fetch("../php/getCena.php");
    const cenas = await res.json();
    const disponibles = cenas.filter(
      (cena) =>
        new Date(`${cena.fecha}T${cena.hora}`) > new Date() &&
        parseInt(cena.stock) > 0
    );

    disponibles.sort((a, b) => new Date(a.fecha) - new Date(b.fecha));

    const grid = document.querySelector("#contenedorCenas");
    if (!grid) return;
    grid.innerHTML = "";
    if (disponibles.length === 0) {
      contenedor.innerHTML =
        "<h2 class='text-warning text-center mt-3'>Próximamente nuevas fechas</h2>" +
        "<p class='mensaje-info'>🕵️‍♂️ No hay cenas disponibles ahora mismo.<br>Pero ya estamos organizando la siguiente.<br><br>Mientras tanto, échale un vistazo al carrusel y mira cómo fueron las últimas cenas... ¡pura risa, buen rollo y gente maja por todas partes!<br><br>Muy pronto anunciaremos nuevas fechas para que vuelvas a brindar, comer rico y soltar unas buenas carcajadas.<br><br>📣 ¡Nos vemos en la próxima!</p>";

      const mapa = document.getElementById("mapaGeneral");
      if (mapa) mapa.style.display = "none";
      return;
    }

    disponibles.forEach((cena, index) => {
      const div = document.createElement("div");
      div.className = "tarjeta-cena";
      div.innerHTML = `
        <h5>📅 ${formatearFecha(cena.fecha)}</h5>
        <ul>
          <li><b>Hora:</b> ${cena.hora}</li>
          <li><b>Lugar:</b> ${cena.lugar}</li>
        </ul>
        <div class="precio-stock d-flex justify-content-between w-100">
          <span><b>Precio:</b> ${parseFloat(cena.precio).toFixed(2)} €</span>
          <span><b>Stock:</b> ${cena.stock}</span>
        </div>
      `;
      grid.appendChild(div);
      datosGlobal.push(cena);
    });

    const boton = document.createElement("div");
    boton.innerHTML = `
      <div class="text-center mt-4 w-100">
        <button class="btn btn-primary btn-lg fw-bold abrir-modal" data-id="0">Quiero mi entrada</button>
      </div>
    `;
    contenedor.appendChild(boton);

    document.querySelectorAll(".abrir-modal").forEach((btn) => {
      btn.addEventListener("click", () => {
        selectCena.innerHTML = "";
        datosGlobal.forEach((cena, i) => {
          const option = document.createElement("option");
          option.value = i;
          option.textContent = `${formatearFecha(cena.fecha)} - ${cena.hora}`;
          selectCena.appendChild(option);
        });

        if (datosGlobal.length >= 2) {
          const optionAmbas = document.createElement("option");
          optionAmbas.value = "ambas";
          optionAmbas.textContent = "Ambas cenas";
          selectCena.appendChild(optionAmbas);
        }

        const cena = datosGlobal[0];
        selectCena.value = 0;
        inputCantidad.disabled = false;
        inputCantidad.value = "1";
        inputCantidad.max = cena.stock;
        precioUnidad.textContent = parseFloat(cena.precio).toFixed(2);
        actualizarPrecio(cena.precio);
        modal.show();
      });
    });

    selectCena.addEventListener("change", () => {
      const value = selectCena.value;
      if (value === "ambas") {
        const total = datosGlobal.reduce(
          (sum, c) => sum + parseFloat(c.precio),
          0
        );
        inputCantidad.disabled = true;
        inputCantidad.value = 1;
        precioUnidad.textContent = total.toFixed(2);
        actualizarPrecio(total);
      } else {
        inputCantidad.disabled = false;
        const cena = datosGlobal[parseInt(value)];
        inputCantidad.value = "1";
        inputCantidad.max = cena.stock;
        precioUnidad.textContent = parseFloat(cena.precio).toFixed(2);
        actualizarPrecio(cena.precio);
      }
    });

    btnConfirmar.onclick = () => {
      const value = selectCena.value;
      const cantidad = parseInt(inputCantidad.value);
      let items = [];

      if (value === "ambas") {
        items = datosGlobal.map((cena) => ({
          nombre: `Reserva Cena - ${cena.fecha}`,
          precio: parseFloat(cena.precio),
          cantidad: 1,
          fecha: cena.fecha,
        }));
      } else {
        const cena = datosGlobal[parseInt(value)];
        items = [
          {
            nombre: `Reserva Cena - ${cena.fecha}`,
            precio: parseFloat(cena.precio),
            cantidad: cantidad,
            fecha: cena.fecha,
          },
        ];
      }

      fetch("../php/checkout.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(items),
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.id) {
            const stripe = Stripe(
              "pk_live_51QJlGgRo1VjWcWKpCKKiO329qVF1nPnOOXEGvWzdyAC97pFueYvTca2BOBz475eprKzdN92y8khQWCBZPZUJDkXB00zhLzRCEt"
            ); // TU PUBLIC KEY
            stripe.redirectToCheckout({ sessionId: data.id });
          } else {
            alert("Error al generar la sesión de pago.");
          }
        })
        .catch((err) => {
          console.error("Error con Stripe:", err);
          alert("No se pudo generar la sesión de pago.");
        });
    };

    inputCantidad.addEventListener("input", () => {
      const precio = parseFloat(precioUnidad.textContent) || 0;
      let valor = parseInt(inputCantidad.value) || 1;
      if (valor < 1) valor = 1;
      inputCantidad.value = valor;
      actualizarPrecio(precio);
    });
  } catch (err) {
    console.error("Error al cargar las cenas:", err);
    contenedor.innerHTML =
      "<p class='text-warning text-center mt-3'>No se pudo cargar la información de las cenas.</p>";
  }
});
// ESTA FUNCION TIENE QUE ESTAR FUERA DEL document.addEventListener
function initMap() {
  fetch("../php/getCena.php")
    .then((res) => res.json())
    .then((cenas) => {
      const disponibles = cenas
        .filter(
          (cena) =>
            new Date(`${cena.fecha}T${cena.hora}`) > new Date() &&
            parseInt(cena.stock) > 0
        )
        .sort((a, b) => new Date(a.fecha) - new Date(b.fecha));

      if (disponibles.length > 0) {
        const direccion = disponibles[0].lugar;
        const mapaDiv = document.getElementById("mapaPrincipal");

        if (!mapaDiv) return;

        const mapa = new google.maps.Map(mapaDiv, {
          zoom: 15,
          center: { lat: 43.263, lng: -2.935 },
          mapTypeId: "roadmap",
        });

        const geocoder = new google.maps.Geocoder();
        geocoder.geocode({ address: direccion }, (results, status) => {
          if (status === "OK" && results[0]) {
            mapa.setCenter(results[0].geometry.location);
            new google.maps.Marker({
              map: mapa,
              position: results[0].geometry.location,
            });
          } else {
            console.error("No se pudo geolocalizar la dirección.");
          }
        });
      }
    })
    .catch((err) => console.error("Error al cargar el mapa:", err));
}
