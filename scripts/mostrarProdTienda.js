document.addEventListener("DOMContentLoaded", () => {
  fetch("../php/obtenerBonos.php") // ← desde BBDD, no JSON local
    .then((res) => res.json())
    .then((productos) => {
      const contenedor = document.getElementById("contenedor-productos");
      const contenedorFiltros = document.getElementById("contenedor-filtros");
      const categoriasUnicas = new Set();

      // Mostrar productos
      productos.forEach((p) => {
        const categoriaLimpia = p.categoria.trim().toLowerCase();
        if (
          ![...categoriasUnicas]
            .map((c) => c.trim().toLowerCase())
            .includes(categoriaLimpia)
        ) {
          categoriasUnicas.add(p.categoria.trim());
        }

        const div = document.createElement("div");
        div.className = "col-md-4 mb-4 producto-tarjeta";
        div.id = p.id;

        div.setAttribute("data-nombre", p.nombre || "");
        div.setAttribute("data-categoria", p.categoria || "");

        div.innerHTML = `
          <div class="card text-center">
            <div class="card-img-placeholder" onclick="window.location.href='producto.html?id=${
              p.id
            }'" style="cursor: pointer;">
              <img src="../imagen/${p.imagen}" alt="${
          p.nombre
        }" class="img-fluid rounded">

            </div>
            <div class="card-body" onclick="window.location.href='producto.html?id=${
              p.id
            }'" style="cursor: pointer;">
              <h5 class="card-title">${p.nombre}</h5>
              <p class="card-text">${p.desCorta}</p>
              <p class="precio">${parseFloat(p.precio).toLocaleString("es-ES", {
                style: "currency",
                currency: "EUR",
              })} <b>con IVA<b></p>
            </div>
            <div class="card-footer bg-white border-0 d-flex gap-2 px-3 pb-3">
              <a href="${
                p.linkCompra
              }" class="btn btn-primary flex-fill">Comprar</a>
              <button class="btn btn-outline-success flex-fill add-to-cart" 
                data-nombre="${p.nombre}" 
                data-precio="${p.precio}">
                Añadir
              </button>
            </div>
          </div>
        `;
        contenedor.appendChild(div);
      });

      // Filtros dinámicos
      contenedorFiltros.innerHTML += `
        <input type="text" id="busqueda" placeholder="🔍 Buscar por nombre" class="form-control mb-3">
        <h5>Categorías</h5>
      `;

      let i = 0;
      [...categoriasUnicas]
        .map((c) => c.trim().toLowerCase())
        .filter((c, i, self) => self.indexOf(c) === i)
        .sort()
        .forEach((cat) => {
          const id = `cat-${i++}`;
          contenedorFiltros.innerHTML += `
          <div class="form-check">
            <input class="form-check-input filtro-categoria" type="checkbox" value="${cat}" id="${id}">
            <label class="form-check-label" for="${id}">${formatearNombreCategoria(
            cat
          )}</label>
          </div>
        `;
        });

      // Lógica de filtrado
      const aplicarFiltros = () => {
        const texto = document.getElementById("busqueda").value.toLowerCase();
        const categoriasActivas = Array.from(
          document.querySelectorAll(".filtro-categoria:checked")
        ).map((c) => c.value.toLowerCase());

        document.querySelectorAll(".producto-tarjeta").forEach((card) => {
          const nombre = card.dataset.nombre.toLowerCase();
          const categoria = card.dataset.categoria.toLowerCase();

          const coincideNombre = nombre.includes(texto);
          const coincideCategoria =
            categoriasActivas.length === 0 ||
            categoriasActivas.includes(categoria);

          card.style.display =
            coincideNombre && coincideCategoria ? "block" : "none";
        });
      };

      contenedorFiltros.addEventListener("input", (e) => {
        if (e.target.id === "busqueda") aplicarFiltros();
      });

      contenedorFiltros.addEventListener("change", (e) => {
        if (e.target.classList.contains("filtro-categoria")) aplicarFiltros();
      });

      // Añadir al carrito
      if (
        typeof actualizarCarrito === "function" &&
        typeof mostrarCarrito === "function"
      ) {
        window.carrito = JSON.parse(localStorage.getItem("carrito")) || [];

        document.querySelectorAll(".add-to-cart").forEach((boton) => {
          boton.addEventListener("click", (e) => {
            e.stopPropagation();
            const nombre = boton.dataset.nombre;
            const precio = parseFloat(boton.dataset.precio);
            const existente = window.carrito.find((p) => p.nombre === nombre);

            if (existente) {
              existente.cantidad += 1;
            } else {
              window.carrito.push({ nombre, precio, cantidad: 1 });
            }

            localStorage.setItem("carrito", JSON.stringify(window.carrito));
            actualizarCarrito();
            mostrarCarrito();
          });
        });
      }
    })
    .catch((err) => {
      console.error("Error al cargar productos:", err);
    });
});

// Formatear categoría: "kitDigital" → "Kit Digital"
function formatearNombreCategoria(cat) {
  return cat.replace(/([A-Z])/g, " $1").replace(/^./, (c) => c.toUpperCase());
}
