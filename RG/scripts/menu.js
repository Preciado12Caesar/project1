document.addEventListener("DOMContentLoaded", function () {
    const listaCategorias = document.getElementById("listaCategoriasDinamicas");
    const apiURL = "http://localhost/categorias.php";

    fetch(apiURL)
        .then(response => response.json())
        .then(data => {
            listaCategorias.innerHTML = "";

            data.forEach(categoria => {
                let liCategoria = document.createElement("li");
                liCategoria.className = "dropdown-submenu position-relative";

                let aCategoria = document.createElement("a");
                aCategoria.className = "dropdown-item dropdown-toggle";
                aCategoria.href = "#";
                aCategoria.textContent = categoria.nombre;

                let ulProductos = document.createElement("ul");
                ulProductos.className = "dropdown-menu submenu";

                categoria.productos.forEach(producto => {
                    let liProducto = document.createElement("li");
                    liProducto.className = "dropdown-submenu position-relative";

                    let aProducto = document.createElement("a");
                    aProducto.className = "dropdown-item dropdown-toggle";
                    aProducto.href = "#";
                    aProducto.textContent = producto.nombre;

                    let ulMarcas = document.createElement("ul");
                    ulMarcas.className = "dropdown-menu submenu";

                    producto.marcas.forEach(marca => {
                        let liMarca = document.createElement("li");
                        let aMarca = document.createElement("a");
                        aMarca.className = "dropdown-item";
                        aMarca.href = "#";
                        aMarca.textContent = marca.nombre;

                        liMarca.appendChild(aMarca);
                        ulMarcas.appendChild(liMarca);
                    });

                    liProducto.appendChild(aProducto);
                    liProducto.appendChild(ulMarcas);
                    ulProductos.appendChild(liProducto);
                });

                liCategoria.appendChild(aCategoria);
                liCategoria.appendChild(ulProductos);
                listaCategorias.appendChild(liCategoria);
            });

            // Agregar eventos para mostrar los submenús
            document.querySelectorAll(".dropdown-submenu").forEach(submenu => {
                submenu.addEventListener("mouseenter", function () {
                    let submenuElement = this.querySelector(".submenu");
                    if (submenuElement) submenuElement.style.display = "block";
                });
                submenu.addEventListener("mouseleave", function () {
                    let submenuElement = this.querySelector(".submenu");
                    if (submenuElement) submenuElement.style.display = "none";
                });
            });
        })
        .catch(error => console.error("Error cargando categorías:", error));
});
