document.addEventListener("DOMContentLoaded", () => {
  // Inicializa carrito si no existe
  if (!window.carrito) {
    window.carrito = JSON.parse(localStorage.getItem("carrito")) || [];
  }

  const params = new URLSearchParams(window.location.search);
  const id = params.get("id");

  fetch("../php/obtenerBonos.php")
    .then((res) => res.json())
    .then((productos) => {
      console.log("Buscando producto con ID:", id);
      console.log(
        "IDs disponibles:",
        productos.map((p) => p.id)
      );
      const producto = productos.find((p) => String(p.id) === String(id));

      const contenedor = document.getElementById("detalle-producto");

      if (!producto) {
        contenedor.innerHTML = `<div class="alert alert-danger">Producto no encontrado.</div>`;
        return;
      }

      // Parseamos características (vienen como array)
      const caracteristicas = Array.isArray(producto.caracteristicas)
        ? producto.caracteristicas
        : [];

      const descripcionFormateada = producto.descripcion
        .replace(/\\n/g, "<br>")
        .replace(/\n/g, "<br>")
        .replace(/\*(.*?)\*/g, "<strong>$1</strong>");

      contenedor.innerHTML = `
        <div class="row align-items-start g-5">
          <div class="col-md-6 text-center">
            <img src="../imagen/${producto.imagen}" alt="${
        producto.nombre
      }" class="img-fluid">
          </div>
          <div class="col-md-6">
            <h2 class="producto-nombre">${producto.nombre}</h2>
            <p class="producto-descripcion">${descripcionFormateada}</p>
            <div class="producto-caracteristicas mb-4">
              ${caracteristicas.map((c) => `<p>- ${c}</p>`).join("")}
            </div>
            <p class="producto-precio">${parseFloat(producto.precio).toFixed(
              2
            )} € <b>con IVA<b></p>
            <div class="d-flex gap-3">
              <a href="${
                producto.linkCompra
              }" class="btn btn-primary flex-fill">Quiero mi bono</a>
              <button class="btn btn-outline-success flex-fill" id="btn-añadir-carrito">
                Añadir al carrito
              </button>
            </div>
          </div>
        </div>
      `;

      setTimeout(() => {
        const boton = document.getElementById("btn-añadir-carrito");
        if (boton) {
          boton.addEventListener("click", () => {
            const existe = window.carrito.find(
              (p) => p.nombre === producto.nombre
            );
            if (existe) {
              existe.cantidad += 1;
            } else {
              window.carrito.push({
                nombre: producto.nombre,
                precio: parseFloat(producto.precio),
                cantidad: 1,
              });
            }
            localStorage.setItem("carrito", JSON.stringify(window.carrito));
            if (typeof actualizarCarrito === "function") actualizarCarrito();
            if (typeof mostrarCarrito === "function") mostrarCarrito();
          });
        }
      }, 0);
    })
    .catch((err) => {
      console.error("Error cargando el producto:", err);
      document.getElementById("detalle-producto").innerHTML =
        "<div class='alert alert-danger'>Error al cargar el producto.</div>";
    });
});
