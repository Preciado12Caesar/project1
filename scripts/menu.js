document.addEventListener('DOMContentLoaded', function() {
    // Configuración
    const API_URL = '/api/menu-data.php';
    
    // Elementos del DOM
    const catalogoButton = document.querySelector('#productosDropdown');
    const megaMenu = document.querySelector('.mega-menu');
    
    // 1. Configuración inicial para móvil (crear overlay y botón cerrar)
    function setupMobileElements() {
        // Crear overlay para fondo
        if (!document.querySelector('.overlay')) {
            const overlay = document.createElement('div');
            overlay.className = 'overlay';
            document.body.appendChild(overlay);
            
            // Cerrar el menú al hacer clic en el overlay
            overlay.addEventListener('click', function() {
                closeMobileMenu();
            });
        }
        
        // Crear botón de cerrar para móvil
        if (window.innerWidth < 992 && !document.querySelector('.close-mobile-menu')) {
            const closeButton = document.createElement('button');
            closeButton.className = 'close-mobile-menu';
            closeButton.innerHTML = '×';
            closeButton.addEventListener('click', function() {
                closeMobileMenu();
            });
            
            if (megaMenu) {
                megaMenu.appendChild(closeButton);
            }
        }
    }
    
    // Función para cerrar menú en móvil
    function closeMobileMenu() {
        if (megaMenu) megaMenu.style.display = 'none';
        const overlay = document.querySelector('.overlay');
        if (overlay) overlay.classList.remove('active');
    }
    
    // 2. Manejo del botón de catálogo
    if (catalogoButton && megaMenu) {
        // Ocultar el menú inicialmente
        megaMenu.style.display = 'none';
        
        // Toggle del menú al hacer clic
        catalogoButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (megaMenu.style.display === 'block') {
                closeMobileMenu();
            } else {
                megaMenu.style.display = 'block';
                
                // En móvil, activar overlay
                if (window.innerWidth < 992) {
                    const overlay = document.querySelector('.overlay');
                    if (overlay) overlay.classList.add('active');
                }
            }
        });
        
        // Si no es móvil, añadir comportamiento hover
        if (window.innerWidth >= 992) {
            const productosWrapper = document.querySelector('.productos-wrapper');
            
            if (productosWrapper) {
                // Retrasar el cierre para dar tiempo a navegar
                let timeoutId;
                
                productosWrapper.addEventListener('mouseenter', function() {
                    clearTimeout(timeoutId);
                    megaMenu.style.display = 'block';
                });
                
                productosWrapper.addEventListener('mouseleave', function() {
                    // Retrasar el cierre para permitir mover el mouse al submenú
                    timeoutId = setTimeout(function() {
                        if (!document.querySelector('.mega-menu:hover')) {
                            megaMenu.style.display = 'none';
                        }
                    }, 300); // 300ms de retraso
                });
                
                // Si el mouse vuelve al menú, cancelar el cierre
                megaMenu.addEventListener('mouseenter', function() {
                    clearTimeout(timeoutId);
                });
                
                megaMenu.addEventListener('mouseleave', function() {
                    timeoutId = setTimeout(function() {
                        megaMenu.style.display = 'none';
                    }, 300);
                });
            }
        }
        
        // Cerrar el menú al hacer clic fuera (solo en desktop)
        document.addEventListener('click', function(e) {
            if (window.innerWidth >= 992 && megaMenu.style.display === 'block' && 
                !megaMenu.contains(e.target) && e.target !== catalogoButton) {
                megaMenu.style.display = 'none';
            }
        });
    }
    
    // 3. Cargar datos del menú
    function cargarMenu() {
        fetch(API_URL)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                construirMenu(data);
                configurarEventosMenu();
                setupMobileElements(); // Configurar elementos para móvil
            })
            .catch(error => {
                console.error('Error al cargar el menú:', error);
                // Mostrar mensaje de error al usuario
                const menuContainer = document.getElementById('listaCategoriasDinamicas');
                if (menuContainer) {
                    menuContainer.innerHTML = '<li><a href="#">Error al cargar el menú</a></li>';
                }
            });
    }
    
    // 4. Construir el menú con los datos recibidos
    function construirMenu(menuData) {
        if (!menuData || !Array.isArray(menuData) || menuData.length === 0) {
            console.error('Datos del menú inválidos:', menuData);
            return;
        }
        
        const menuContainer = document.getElementById('listaCategoriasDinamicas');
        if (!menuContainer) {
            console.error('Elemento contenedor del menú no encontrado');
            return;
        }
        
        menuContainer.innerHTML = '';
        
        menuData.forEach(categoria => {
            // Crear elemento de categoría (Nivel 1)
            const cssClass = (categoria.nombre.toLowerCase() === 'productos') ? 'bg-productos' : 'bg-servicios';
            const liCategoria = document.createElement('li');
            liCategoria.className = 'has-children';
            
            const aCategoria = document.createElement('a');
            aCategoria.href = '#';
            aCategoria.setAttribute('data-categoria', categoria.id);
            aCategoria.textContent = categoria.nombre || 'Sin nombre';
            
            liCategoria.appendChild(aCategoria);
            
            // Si tiene subcategorías, crear el submenu (Nivel 2)
            if (categoria.subcategorias && categoria.subcategorias.length > 0) {
                const ulSubcategorias = document.createElement('ul');
                ulSubcategorias.className = `menu-nivel2 ${cssClass}`;
                
                categoria.subcategorias.forEach(subcategoria => {
                    // Determinar clase CSS para subcategoría
                    let subcategoriaCSS = '';
                    const subcategoriaLower = (subcategoria.nombre || '').toLowerCase();
                    
                    if (subcategoriaLower === 'puntas') {
                        subcategoriaCSS = 'bg-productos';
                    } else if (subcategoriaLower === 'cuchillas') {
                        subcategoriaCSS = 'bg-cuchillas';
                    } else if (subcategoriaLower === 'tren de rodamiento') {
                        subcategoriaCSS = 'bg-tren-rodamiento';
                    }
                    
                    const liSubcategoria = document.createElement('li');
                    liSubcategoria.className = 'has-children';
                    
                    const aSubcategoria = document.createElement('a');
                    aSubcategoria.href = '#';
                    aSubcategoria.setAttribute('data-subcategoria', subcategoria.id);
                    aSubcategoria.textContent = subcategoria.nombre || 'Sin nombre';
                    
                    liSubcategoria.appendChild(aSubcategoria);
                    
                    // Si tiene tipos de maquinaria, crear el submenu (Nivel 3)
                    if (subcategoria.tipos_maquinaria && subcategoria.tipos_maquinaria.length > 0) {
                        const ulTipos = document.createElement('ul');
                        ulTipos.className = `menu-nivel3 ${subcategoriaCSS}`;
                        
                        subcategoria.tipos_maquinaria.forEach(tipo => {
                            const liTipo = document.createElement('li');
                            liTipo.className = 'has-children';
                            
                            const aTipo = document.createElement('a');
                            aTipo.href = '#';
                            aTipo.setAttribute('data-tipo', tipo.id);
                            aTipo.textContent = tipo.nombre || 'Sin nombre';
                            
                            // Agregar indicador de PDF si existe
                            if (tipo.pdf_ruta) {
                                aTipo.setAttribute('data-pdf', tipo.pdf_ruta);
                                // Añadir ícono de PDF
                                const pdfIcon = document.createElement('i');
                                pdfIcon.className = 'fas fa-file-pdf ms-2';
                                aTipo.appendChild(pdfIcon);
                            }
                            
                            liTipo.appendChild(aTipo);
                            
                            // Si tiene marcas, crear el submenu (Nivel 4)
                            if (tipo.marcas && tipo.marcas.length > 0) {
                                const ulMarcas = document.createElement('ul');
                                ulMarcas.className = `menu-nivel4 ${subcategoriaCSS}`;
                                
                                tipo.marcas.forEach(marca => {
                                    const liMarca = document.createElement('li');
                                    liMarca.className = 'has-children';
                                    
                                    const aMarca = document.createElement('a');
                                    aMarca.href = '#';
                                    aMarca.setAttribute('data-marca', marca.id);
                                    aMarca.textContent = marca.nombre || 'Sin nombre';
                                    
                                    // Agregar indicador de PDF si existe
                                    if (marca.pdf_ruta) {
                                        aMarca.setAttribute('data-pdf', marca.pdf_ruta);
                                        // Añadir ícono de PDF
                                        const pdfIcon = document.createElement('i');
                                        pdfIcon.className = 'fas fa-file-pdf ms-2';
                                        aMarca.appendChild(pdfIcon);
                                    }
                                    
                                    liMarca.appendChild(aMarca);
                                    
                                    // Si tiene productos, crear el submenu (Nivel 5)
                                    if (marca.productos && marca.productos.length > 0) {
                                        const ulProductos = document.createElement('ul');
                                        ulProductos.className = `menu-nivel5 ${subcategoriaCSS}`;
                                        
                                        marca.productos.forEach(producto => {
                                            const liProducto = document.createElement('li');
                                            
                                            const aProducto = document.createElement('a');
                                            aProducto.href = '#';
                                            aProducto.className = 'item-link';
                                            aProducto.setAttribute('data-item', producto.id);
                                            aProducto.setAttribute('data-type', 'producto');
                                            aProducto.textContent = producto.nombre || 'Sin nombre';
                                            
                                            // Agregar indicador de PDF si existe
                                            if (producto.pdf_ruta) {
                                                aProducto.setAttribute('data-pdf', producto.pdf_ruta);
                                                // Añadir ícono de PDF
                                                const pdfIcon = document.createElement('i');
                                                pdfIcon.className = 'fas fa-file-pdf ms-2';
                                                aProducto.appendChild(pdfIcon);
                                            }
                                            
                                            liProducto.appendChild(aProducto);
                                            ulProductos.appendChild(liProducto);
                                        });
                                        
                                        liMarca.appendChild(ulProductos);
                                    } else {
                                        // Si no tiene productos, hacer que la marca sea clickeable directamente
                                        aMarca.className = 'item-link';
                                        aMarca.setAttribute('data-item', marca.id);
                                        aMarca.setAttribute('data-type', 'marca');
                                    }
                                    
                                    ulMarcas.appendChild(liMarca);
                                });
                                
                                liTipo.appendChild(ulMarcas);
                            } else {
                                // Si no tiene marcas, hacer que el tipo sea clickeable directamente
                                aTipo.className = 'item-link';
                                aTipo.setAttribute('data-item', tipo.id);
                                aTipo.setAttribute('data-type', 'tipo');
                            }
                            
                            ulTipos.appendChild(liTipo);
                        });
                        
                        liSubcategoria.appendChild(ulTipos);
                    }
                    
                    ulSubcategorias.appendChild(liSubcategoria);
                });
                
                liCategoria.appendChild(ulSubcategorias);
            }
            
            menuContainer.appendChild(liCategoria);
        });
    }
    
    // 5. Configurar eventos para elementos del menú
    function configurarEventosMenu() {
        // En móvil, manejar la expansión/contracción de submenús
        const menuItemsWithChildren = document.querySelectorAll('.has-children > a:not(.item-link)');
        
        menuItemsWithChildren.forEach(item => {
            item.addEventListener('click', function(e) {
                // En móvil, alternar visibilidad
                if (window.innerWidth < 992) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const parent = this.parentElement;
                    
                    // Cerrar otros elementos al mismo nivel
                    const siblings = parent.parentElement.children;
                    for (let i = 0; i < siblings.length; i++) {
                        if (siblings[i] !== parent && siblings[i].classList.contains('active')) {
                            siblings[i].classList.remove('active');
                        }
                    }
                    
                    parent.classList.toggle('active');
                } else {
                    // En desktop, dejar que el hover maneje esto
                    if (!this.classList.contains('item-link')) {
                        e.preventDefault();
                    }
                }
            });
        });
        
        // Manejar clics en elementos con PDF
        const elementsWithPDF = document.querySelectorAll('a[data-pdf]');
        
        elementsWithPDF.forEach(link => {
            link.addEventListener('click', function(e) {
                if (e.target.classList && e.target.classList.contains('fa-file-pdf')) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const pdfRuta = this.getAttribute('data-pdf');
                    if (pdfRuta) {
                        window.open(pdfRuta, '_blank');
                    }
                }
            });
        });
        
        // Manejar clics en elementos finales (item-link)
        const itemLinks = document.querySelectorAll('.item-link');
        
        itemLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                const pdfRuta = this.getAttribute('data-pdf');
                if (pdfRuta) {
                    // Descargar PDF
                    const downloadLink = document.createElement('a');
                    downloadLink.href = pdfRuta;
                    downloadLink.download = this.textContent.trim() + '.pdf';
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                }
                
                // Cerrar el menú
                closeMobileMenu();
            });
        });
    }
    
    // Iniciar la carga del menú
    cargarMenu();
    
    // Detectar cambios en el tamaño de la ventana
    window.addEventListener('resize', function() {
        setupMobileElements();
    });
});