document.addEventListener("DOMContentLoaded", () => {
  window.carrito = JSON.parse(localStorage.getItem("carrito")) || [];

  const cartButton = document.getElementById("cart-button");
  const cartBadge = cartButton?.querySelector(".badge");
  const cartDropdown = document.getElementById("cart-dropdown");
  const cartOverlay = document.getElementById("cart-overlay");
  const cartList = document.getElementById("cart-list");
  const cartTotal = document.getElementById("cart-total");
  const btnCerrar = document.querySelector(".cerrar-carrito");

  window.actualizarCarrito = function () {
    if (!cartList || !cartBadge || !cartTotal) return;

    cartList.innerHTML = "";
    let total = 0;

    carrito.forEach((item, index) => {
      const subtotal = item.precio * item.cantidad;
      total += subtotal;

      const li = document.createElement("li");
      li.className =
        "list-group-item d-flex justify-content-between align-items-center";
      li.innerHTML = `
        <div>
          <strong>${item.nombre}</strong><br>
          <input type="number" min="1" value="${item.cantidad}" 
            onchange="cambiarCantidad(${index}, this.value)" 
            class="form-control form-control-sm" style="width: 60px; display: inline-block;" />
        </div>
        <div>
          <span>€${subtotal.toFixed(2)}</span><br>
          <button class="btn btn-sm btn-danger mt-1" onclick="eliminarProducto(${index})">🗑️</button>
        </div>
      `;
      cartList.appendChild(li);
    });

    localStorage.setItem("carrito", JSON.stringify(carrito));
    cartTotal.textContent = `€${total.toFixed(2)}`;
    cartBadge.textContent = carrito.length;
    cartBadge.classList.toggle("bg-dark", carrito.length === 0);
    cartBadge.classList.toggle("bg-danger", carrito.length > 0);
  };

  window.cambiarCantidad = function (index, nuevaCantidad) {
    carrito[index].cantidad = parseInt(nuevaCantidad);
    localStorage.setItem("carrito", JSON.stringify(carrito));
    actualizarCarrito();
  };

  window.eliminarProducto = function (index) {
    carrito.splice(index, 1);
    localStorage.setItem("carrito", JSON.stringify(carrito));
    actualizarCarrito();
  };

  window.mostrarCarrito = function () {
    document.body.classList.add("cart-open");
  };

  window.ocultarCarrito = function () {
    document.body.classList.remove("cart-open");
  };

  document.querySelectorAll(".add-to-cart").forEach((boton) => {
    boton.addEventListener("click", (e) => {
      e.stopPropagation();
      const nombre = boton.dataset.nombre;
      const precio = parseFloat(boton.dataset.precio);
      const existente = carrito.find((p) => p.nombre === nombre);

      if (existente) {
        existente.cantidad += 1;
      } else {
        carrito.push({ nombre, precio, cantidad: 1 });
      }

      actualizarCarrito();
      mostrarCarrito();
    });
  });

  cartButton?.addEventListener("click", () => {
    document.body.classList.toggle("cart-open");
  });

  cartOverlay?.addEventListener("click", ocultarCarrito);
  btnCerrar?.addEventListener("click", ocultarCarrito);

  actualizarCarrito();
});
