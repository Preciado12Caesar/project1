document.addEventListener('DOMContentLoaded', function() {
    // Variables de configuración
    const CONFIG = {
        API_URL: window.location.hostname === 'localhost' 
            ? 'http://localhost/menu-data.php' 
            : '/menu-data.php',
        TIMEOUT: 10000,
        LOADING_DELAY: 300
    };
    
    // Elemento para mostrar mensajes de error
    const crearMensajeError = (mensaje) => {
        console.error(mensaje);
    };

    // Configurar eventos para el menú móvil
    const setupMobileMenu = () => {
        const mobileMenuButton = document.querySelector('.mobile-menu-button');
        const mainMenu = document.querySelector('.main-menu');
        
        if (mobileMenuButton && mainMenu) {
            mobileMenuButton.addEventListener('click', function(e) {
                e.stopPropagation();
                mainMenu.classList.toggle('active');
            });
            
            // Cerrar el menú al hacer clic fuera
            document.addEventListener('click', function(event) {
                if (!mainMenu.contains(event.target) && event.target !== mobileMenuButton) {
                    mainMenu.classList.remove('active');
                }
            });
        }
    };

    // Configuración de navegación suave
    const setupSmoothScrolling = () => {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                // Si es un enlace del menú desplegable, no hacer nada
                if (this.classList.contains('dropdown-toggle') || 
                    this.closest('.mega-menu') !== null) {
                    return;
                }
                
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth'
                    });
                    
                    // Si estamos en móvil, cerrar el menú después de hacer clic
                    if (window.innerWidth < 992) {
                        document.querySelector('.main-menu').classList.remove('active');
                    }
                }
            });
        });
    };

    // Cargar el menú desde la API
    const cargarMenu = () => {
        const startTime = Date.now();
        
        // Crear controller para poder abortar la petición si tarda demasiado
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), CONFIG.TIMEOUT);
        
        fetch(CONFIG.API_URL, { 
            signal: controller.signal,
            headers: {
                'Accept': 'application/json',
                'Cache-Control': 'no-cache'
            } 
        })
        .then(response => {
            clearTimeout(timeoutId);
            if (!response.ok) {
                throw new Error(`Error en el servidor: ${response.status} ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            const elapsed = Date.now() - startTime;
            // Garantizamos un tiempo mínimo de carga para evitar parpadeos
            const remainingDelay = Math.max(0, CONFIG.LOADING_DELAY - elapsed);
            
            setTimeout(() => {
                construirMenu(data);
                configurarEventos();
            }, remainingDelay);
        })
        .catch(error => {
            clearTimeout(timeoutId);
            console.error('Error al cargar el menú:', error);
            
            let mensaje = 'Ha ocurrido un error al cargar el catálogo.';
            if (error.name === 'AbortError') {
                mensaje = 'La conexión ha tardado demasiado. Por favor, verifique su conexión a internet e intente nuevamente.';
            } else if (error.message.includes('NetworkError')) {
                mensaje = 'No se pudo conectar con el servidor. Por favor, compruebe su conexión a internet.';
            }
            
            crearMensajeError(mensaje);
        });
    };

    // Función para construir el menú dinámicamente
    function construirMenu(menuData) {
        if (!menuData || !Array.isArray(menuData) || menuData.length === 0) {
            console.error('Datos del menú inválidos:', menuData);
            crearMensajeError('Los datos del catálogo no son válidos. Contacte al administrador.');
            return;
        }
        
        const menuContainer = document.getElementById('listaCategoriasDinamicas');
        if (!menuContainer) {
            console.error('Elemento contenedor del menú no encontrado');
            return;
        }
        
        menuContainer.innerHTML = ''; // Limpiar el contenedor
        
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
                                pdfIcon.style.color = '#ff0000';
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
                                        pdfIcon.style.color = '#ff0000';
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
                                                pdfIcon.style.color = '#ff0000';
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
                                // Si no tiene marcas ni series, pero tiene PDF, hacerlo clickeable directamente
                                if (tipo.pdf_ruta || tipo.productos) {
                                    aTipo.className = 'item-link';
                                    aTipo.setAttribute('data-item', tipo.id);
                                    aTipo.setAttribute('data-type', 'tipo');
                                    
                                    // Si tiene productos directamente asociados (sin marca)
                                    if (tipo.productos && tipo.productos.length > 0) {
                                        const ulProductos = document.createElement('ul');
                                        ulProductos.className = `menu-nivel4 ${subcategoriaCSS}`;
                                        
                                        tipo.productos.forEach(producto => {
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
                                                pdfIcon.style.color = '#ff0000';
                                                aProducto.appendChild(pdfIcon);
                                            }
                                            
                                            liProducto.appendChild(aProducto);
                                            ulProductos.appendChild(liProducto);
                                        });
                                        
                                        liTipo.appendChild(ulProductos);
                                    }
                                } else {
                                    // Si no tiene nada, solo hacerlo clickeable
                                    aTipo.className = 'item-link';
                                    aTipo.setAttribute('data-item', tipo.id);
                                    aTipo.setAttribute('data-type', 'tipo');
                                }
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

    // Configurar eventos para elementos del menú
    function configurarEventos() {
        // Agregar event listeners a todos los elementos con clase 'item-link'
        const allLinks = document.querySelectorAll('.item-link');
        
        allLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                const itemId = this.getAttribute('data-item');
                const itemType = this.getAttribute('data-type');
                const itemText = this.textContent.trim();
                const pdfRuta = this.getAttribute('data-pdf');
                
                // Construir la ruta de navegación
                let breadcrumb = '';
                let currentElement = this;
                let menuItems = [];
                
                // Navegar hacia arriba en el DOM para construir la ruta
                while (currentElement && !currentElement.classList.contains('menu-nivel1')) {
                    if (currentElement.tagName === 'A') {
                        let texto = currentElement.textContent.trim();
                        menuItems.unshift(texto);
                    }
                    currentElement = currentElement.parentNode;
                }
                
                breadcrumb = menuItems.join(' > ');
                
                // Actualizar el contenido
                mostrarDetalle(itemId, itemType, itemText, breadcrumb, pdfRuta);
            });
        });
        
        // Manejar clics en TODOS los elementos que tienen PDF
        const elementsWithPDF = document.querySelectorAll('a[data-pdf]');
        
        elementsWithPDF.forEach(link => {
            // Asegurarnos de que el ícono de PDF sea reconocible y clickeable
            if (!link.querySelector('.fa-file-pdf')) {
                const pdfRuta = link.getAttribute('data-pdf');
                if (pdfRuta) {
                    // Añadir ícono de PDF si no existe
                    const pdfIcon = document.createElement('i');
                    pdfIcon.className = 'fas fa-file-pdf ms-2';
                    pdfIcon.style.color = '#ff0000';
                    link.appendChild(pdfIcon);
                }
            }
            
            // Evento para click en el elemento o en el ícono
            link.addEventListener('click', function(e) {
                // Si se hace clic en el enlace o el ícono PDF
                const target = e.target;
                // Si se hizo clic en el ícono o si el elemento tiene una ruta de PDF
                if ((target.tagName === 'I' && target.classList.contains('fa-file-pdf')) || 
                    this.hasAttribute('data-pdf')) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const pdfRuta = this.getAttribute('data-pdf');
                    if (pdfRuta) {
                        // Descargar el PDF
                        const downloadLink = document.createElement('a');
                        downloadLink.href = pdfRuta;
                        downloadLink.download = this.textContent.trim() + '.pdf';
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                    }
                }
            });
        });
    }

    // Función para descargar PDF silenciosamente
    function mostrarDetalle(itemId, itemType, itemText, breadcrumb, pdfRuta) {
        // Si existe un PDF, descargarlo
        if (pdfRuta) {
            // Crear un enlace invisible y hacer clic en él para descargar
            const downloadLink = document.createElement('a');
            downloadLink.href = pdfRuta;
            downloadLink.download = itemText + '.pdf';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
            return;
        }
    }

    // Inicializar funciones
    setupMobileMenu();
    setupSmoothScrolling();
    cargarMenu();
});