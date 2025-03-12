document.addEventListener("DOMContentLoaded", function() {
    const apiUrl = "http://localhost/productos.php";

    fetch(apiUrl)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error("Error desde la API:", data.error);
                return;
            }

            let contenedor = document.getElementById("productosContainer");
            if (!contenedor) {
                console.error("No se encontró el contenedor de productos.");
                return;
            }

            contenedor.innerHTML = "";

            data.forEach(producto => {
                let card = document.createElement("div");
                card.classList.add("col-md-4", "mb-4");

                card.innerHTML = `
                    <div class="card">
                        <img src="${producto.imagen || 'recursos/no-image.png'}" class="card-img-top" alt="${producto.producto}">
                        <div class="card-body">
                            <h5 class="card-title">${producto.producto}</h5>
                            <p class="card-text"><strong>Marca:</strong> ${producto.marca}</p>
                            <p class="card-text"><strong>Categoría:</strong> ${producto.categoria}</p>
                        </div>
                    </div>
                `;

                contenedor.appendChild(card);
            });
        })
        .catch(error => console.error("Error al obtener productos:", error));
});
