document.addEventListener("DOMContentLoaded", () => {
  const modal = new bootstrap.Modal(document.getElementById("modalPaquete"));
  const selectHoras = document.getElementById("selectHoras");
  const precioMostrar = document.getElementById("precioMostrar");
  const btnGenerarPago = document.getElementById("btnGenerarPago");

  let listaHoras = [];

  document.querySelectorAll(".abrir-modal").forEach((btn) => {
    btn.addEventListener("click", () => {
      fetch("../php/getHoras.php")
        .then((res) => {
          console.log("RESPUESTA:", res);
          return res.json();
        })
        .then((data) => {
          console.log("DATOS:", data);
          listaHoras = data;

          if (!selectHoras) {
            console.error("El selectHoras no existe en el DOM.");
            return;
          }

          selectHoras.innerHTML = "";

          data.forEach((opcion) => {
            const option = document.createElement("option");
            option.value = opcion.id;
            option.textContent = `${opcion.horas} horas`; // Solo mostramos las horas en el select
            option.dataset.precio = opcion.precio; // Guardamos el precio en el dataset
            selectHoras.appendChild(option);
          });

          // Mostrar el primer precio por defecto (con IVA incluido)
          if (data.length > 0) {
            precioMostrar.innerHTML = `${parseFloat(data[0].precio).toFixed(
              2
            )} € <b>con IVA</b>`;
          } else {
            precioMostrar.textContent = "-";
          }

          modal.show();
        })
        .catch((err) => {
          console.error("Error al cargar paquetes:", err);
          alert("No se pudo cargar la información.");
        });
    });
  });

  // Actualizar precio al cambiar selección
  selectHoras?.addEventListener("change", () => {
    const selectedOption = selectHoras.selectedOptions[0];
    const precio = selectedOption?.dataset?.precio || "-";
    precioMostrar.innerHTML =
      precio !== "-"
        ? `${parseFloat(precio).toFixed(2)} € <b>IVA incluido</b>`
        : "-";
  });

  // Redirigir a Stripe al pulsar Contratar
  btnGenerarPago?.addEventListener("click", () => {
    const selectedOption = selectHoras.selectedOptions[0];
    if (!selectedOption) return alert("Selecciona una opción válida");

    const horas = selectedOption.textContent;
    const precio = parseFloat(selectedOption.dataset.precio);

    fetch("../php/generarEnlaceStripe.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        nombre: `Paquete de ${horas}`,
        precio: precio,
      }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.link) {
          window.location.href = data.link;
        } else {
          alert("Error al generar enlace de pago.");
        }
      })
      .catch((err) => {
        console.error("Error al conectar con Stripe:", err);
        alert("No se pudo generar el enlace de pago.");
      });
  });
});
