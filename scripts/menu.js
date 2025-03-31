/**
 * menu-nuevo.js - Versión simplificada
 * Se enfoca en mostrar correctamente las marcas de cada categoría
 */

document.addEventListener('DOMContentLoaded', function() {
    // Variables de configuración
    const CONFIG = {
        API_URL: 'scripts/menu-data.php',
        TIMEOUT: 10000,
        LOADING_DELAY: 300,
        MOBILE_BREAKPOINT: 992
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
            console.log("Configurando menú móvil");
            
            // Eliminar eventos previos para evitar duplicación
            const nuevoBoton = mobileMenuButton.cloneNode(true);
            mobileMenuButton.parentNode.replaceChild(nuevoBoton, mobileMenuButton);
            
            nuevoBoton.addEventListener('click', function(e) {
                console.log("Click en botón móvil");
                e.preventDefault();
                e.stopPropagation();
                mainMenu.classList.toggle('active');
                
                // Asegurarse que el botón de CATALOGO permanezca visible
                const productosDropdown = document.getElementById('productosDropdown');
                if (productosDropdown) {
                    productosDropdown.style.display = 'inline-flex';
                }
            });
            
            // Cerrar el menú al hacer clic fuera
            document.addEventListener('click', function(event) {
                if (!mainMenu.contains(event.target) && event.target !== nuevoBoton) {
                    mainMenu.classList.remove('active');
                }
            });
        } else {
            console.error("No se encontró el botón de menú móvil o el menú principal");
        }
    };
    
    // Configurar el menú desplegable de productos
    const setupProductosMenu = () => {
        const productosButton = document.querySelector('#productosDropdown');
        const megaMenu = document.querySelector('.mega-menu');
        
        if (productosButton && megaMenu) {
            console.log("Configurando menú de productos");
            
            let menuVisible = false;
            
            // Función para mostrar/ocultar el menú
            function toggleMenu(show) {
                megaMenu.style.display = show ? 'block' : 'none';
                menuVisible = show;
            }
            
            // Mostrar menú al hacer clic
            productosButton.addEventListener('click', function(e) {
                console.log("Click en botón de productos");
                e.preventDefault();
                e.stopPropagation();
                
                toggleMenu(!menuVisible);
            });
            
            // En escritorio: También mostrar al pasar el mouse
            if (window.innerWidth >= CONFIG.MOBILE_BREAKPOINT) {
                let timeoutId;
                
                productosButton.addEventListener('mouseenter', function() {
                    clearTimeout(timeoutId);
                    toggleMenu(true);
                });
                
                megaMenu.addEventListener('mouseenter', function() {
                    clearTimeout(timeoutId);
                    toggleMenu(true);
                });
                
                productosButton.addEventListener('mouseleave', function() {
                    timeoutId = setTimeout(() => {
                        if (!megaMenu.matches(':hover')) {
                            toggleMenu(false);
                        }
                    }, 200);
                });
                
                megaMenu.addEventListener('mouseleave', function() {
                    timeoutId = setTimeout(() => toggleMenu(false), 200);
                });
            }
            
            // Cerrar al hacer clic en cualquier parte fuera del menú
            document.addEventListener('click', function(e) {
                if (!megaMenu.contains(e.target) && e.target !== productosButton) {
                    toggleMenu(false);
                }
            });
        } else {
            console.error("No se encontró el botón de productos o el mega menú");
        }
    };

    // Cargar el menú desde la API
    const cargarMenu = () => {
        console.log("Cargando menú desde API:", CONFIG.API_URL);
        
        // Usar datos de ejemplo directamente para depuración
        // Comentar esta sección cuando la API esté funcionando correctamente
        /*
        console.log("Usando datos de ejemplo para depuración");
        const datos = construirDatosEjemplo();
        construirMenuTabular(datos);
        configurarEventos();
        return;
        */
        
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
            console.log("Datos recibidos:", data);
            
            // Construir el menú visual directamente con los datos de la API
            construirMenuTabular(data);
            // Configurar eventos para los enlaces del menú
            configurarEventos();
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
            
            // Mostrar un menú de ejemplo en caso de error
            construirMenuEjemplo();
        });
    };

    // Construir menú con formato tabular (según la imagen de referencia)
    function construirMenuTabular(datos) {
        const megaMenu = document.querySelector('.mega-menu');
        if (!megaMenu) {
            console.error("No se encontró el contenedor del mega menú");
            return;
        }
        
        console.log("Construyendo menú con datos:", datos);
        
        // Limpiar el contenedor
        megaMenu.innerHTML = '';
        
        // Crear elemento de tabla para el menú
        const table = document.createElement('table');
        table.className = 'menu-table';
        table.style.width = '100%';
        table.style.borderCollapse = 'collapse';
        
        // Procesar cada subcategoría
        datos.forEach(subcategoria => {
            // Crear fila para la categoría
            const row = document.createElement('tr');
            
            // Celda con el título de la categoría 
            const categoryCell = document.createElement('td');
            categoryCell.style.padding = '10px 15px';
            categoryCell.style.verticalAlign = 'top';
            categoryCell.style.fontWeight = 'bold';
            categoryCell.style.color = '#333';
            categoryCell.style.minWidth = '200px';
            categoryCell.textContent = subcategoria.nombre + ' →';
            categoryCell.style.whiteSpace = 'nowrap';
            
            // Celda con las marcas
            const brandsCell = document.createElement('td');
            brandsCell.style.padding = '10px 15px';
            
            // IMPORTANTE: Verificar y mostrar el contenido de marcas para depuración
            console.log(`Marcas para ${subcategoria.nombre}:`, subcategoria.marcas);
            
            // Si tiene marcas, mostrarlas
            if (subcategoria.marcas && subcategoria.marcas.length > 0) {
                // Crear tabla interna para marcas
                const brandsTable = document.createElement('table');
                brandsTable.style.width = '100%';
                
                // Determinar el número de columnas (2 columnas por defecto)
                const numColumns = 2;
                const marcasPorColumna = Math.ceil(subcategoria.marcas.length / numColumns);
                
                // Crear filas para las marcas
                for (let i = 0; i < marcasPorColumna; i++) {
                    const brandsRow = document.createElement('tr');
                    
                    // Crear celdas para cada columna
                    for (let j = 0; j < numColumns; j++) {
                        const index = i + j * marcasPorColumna;
                        
                        // Si hay una marca para esta posición
                        if (index < subcategoria.marcas.length) {
                            const marca = subcategoria.marcas[index];
                            const brandCell = document.createElement('td');
                            brandCell.style.padding = '5px 10px';
                            
                            const brandLink = document.createElement('a');
                            brandLink.href = '#';
                            brandLink.textContent = marca.nombre;
                            brandLink.style.textDecoration = 'none';
                            brandLink.style.color = '#333';
                            brandLink.setAttribute('data-categoria', subcategoria.id);
                            brandLink.setAttribute('data-marca', marca.id);
                            
                            // Si tiene PDF, mostrar ícono
                            if (marca.pdf_ruta) {
                                brandLink.setAttribute('data-pdf', marca.pdf_ruta);
                                
                                const pdfIcon = document.createElement('i');
                                pdfIcon.className = 'fas fa-file-pdf ms-2';
                                pdfIcon.style.color = '#ff0000';
                                brandLink.appendChild(pdfIcon);
                            }
                            
                            brandCell.appendChild(brandLink);
                            brandsRow.appendChild(brandCell);
                        } else {
                            // Celda vacía para completar la tabla
                            const emptyCell = document.createElement('td');
                            brandsRow.appendChild(emptyCell);
                        }
                    }
                    
                    brandsTable.appendChild(brandsRow);
                }
                
                brandsCell.appendChild(brandsTable);
            } 
            // Si tiene items (para MISCELÁNEOS)
            else if (subcategoria.items && subcategoria.items.length > 0) {
                // Depuración: Verificar que hay items
                console.log(`Items para ${subcategoria.nombre}:`, subcategoria.items);
                
                // Crear tabla interna para items
                const itemsTable = document.createElement('table');
                itemsTable.style.width = '100%';
                
                // Determinar el número de columnas (2 columnas por defecto)
                const numColumns = 2;
                const itemsPorColumna = Math.ceil(subcategoria.items.length / numColumns);
                
                // Crear filas para los items
                for (let i = 0; i < itemsPorColumna; i++) {
                    const itemsRow = document.createElement('tr');
                    
                    // Crear celdas para cada columna
                    for (let j = 0; j < numColumns; j++) {
                        const index = i + j * itemsPorColumna;
                        
                        // Si hay un item para esta posición
                        if (index < subcategoria.items.length) {
                            const item = subcategoria.items[index];
                            const itemCell = document.createElement('td');
                            itemCell.style.padding = '5px 10px';
                            
                            const itemLink = document.createElement('a');
                            itemLink.href = '#';
                            itemLink.textContent = item.nombre;
                            itemLink.style.textDecoration = 'none';
                            itemLink.style.color = '#333';
                            itemLink.setAttribute('data-categoria', subcategoria.id);
                            itemLink.setAttribute('data-item', item.id);
                            
                            // Si tiene PDF, mostrar ícono
                            if (item.pdf_ruta) {
                                itemLink.setAttribute('data-pdf', item.pdf_ruta);
                                
                                const pdfIcon = document.createElement('i');
                                pdfIcon.className = 'fas fa-file-pdf ms-2';
                                pdfIcon.style.color = '#ff0000';
                                itemLink.appendChild(pdfIcon);
                            }
                            
                            itemCell.appendChild(itemLink);
                            itemsRow.appendChild(itemCell);
                        } else {
                            // Celda vacía para completar la tabla
                            const emptyCell = document.createElement('td');
                            itemsRow.appendChild(emptyCell);
                        }
                    }
                    
                    itemsTable.appendChild(itemsRow);
                }
                
                brandsCell.appendChild(itemsTable);
            } else {
                // Si no tiene marcas ni items, mostrar mensaje
                brandsCell.textContent = 'No hay elementos disponibles';
            }
            
            // Agregar celdas a la fila
            row.appendChild(categoryCell);
            row.appendChild(brandsCell);
            
            // Agregar fila a la tabla
            table.appendChild(row);
            
            // Agregar separador excepto para la última categoría
            if (datos.indexOf(subcategoria) < datos.length - 1) {
                const separatorRow = document.createElement('tr');
                const separatorCell = document.createElement('td');
                separatorCell.colSpan = 2;
                separatorCell.style.borderBottom = '1px solid #eee';
                separatorCell.style.padding = '5px 0';
                separatorRow.appendChild(separatorCell);
                table.appendChild(separatorRow);
            }
        });
        
        // Agregar tabla al mega menú
        megaMenu.appendChild(table);
        
        // Establecer estilos para el mega menú
        megaMenu.style.display = 'none';
        megaMenu.style.position = 'absolute';
        megaMenu.style.backgroundColor = 'white';
        megaMenu.style.boxShadow = '0 5px 15px rgba(0,0,0,0.2)';
        megaMenu.style.borderRadius = '8px';
        megaMenu.style.padding = '15px';
        megaMenu.style.zIndex = '1000';
        
        // En móvil, ajustar el ancho
        if (window.innerWidth < CONFIG.MOBILE_BREAKPOINT) {
            megaMenu.style.width = '100%';
        } else {
            megaMenu.style.minWidth = '600px';
            megaMenu.style.maxWidth = '800px';
        }
    }
    
    // Construir menú de ejemplo en caso de error
    function construirMenuEjemplo() {
        const datos = [
            {
                id: 1,
                nombre: 'PUNTAS',
                tipo: 'subcategoria',
                marcas: [
                    { id: 1, nombre: 'CAT', tipo: 'marca' },
                    { id: 2, nombre: 'KOMATSU', tipo: 'marca' },
                    { id: 3, nombre: 'VOLVO', tipo: 'marca' },
                    { id: 4, nombre: 'JCB', tipo: 'marca' }
                ]
            },
            {
                id: 2,
                nombre: 'CUCHILLAS',
                tipo: 'subcategoria',
                marcas: [
                    { id: 5, nombre: 'CAT', tipo: 'marca' },
                    { id: 6, nombre: 'KOMATSU', tipo: 'marca' },
                    { id: 7, nombre: 'CASE', tipo: 'marca' },
                    { id: 8, nombre: 'JOHN DEERE', tipo: 'marca' },
                    { id: 9, nombre: 'MERLO', tipo: 'marca' },
                    { id: 10, nombre: 'MANITOU', tipo: 'marca' },
                    { id: 11, nombre: 'JCB', tipo: 'marca' }
                ]
            },
            {
                id: 3,
                nombre: 'TREN DE RODAMIENTO',
                tipo: 'subcategoria',
                marcas: [
                    { id: 12, nombre: 'CAT', tipo: 'marca' },
                    { id: 13, nombre: 'KOMATSU', tipo: 'marca' },
                    { id: 14, nombre: 'VOLVO', tipo: 'marca' },
                    { id: 15, nombre: 'DOOSAN', tipo: 'marca' },
                    { id: 16, nombre: 'JOHN DEERE', tipo: 'marca' }
                ]
            },
            {
                id: 4,
                nombre: 'MISCELÁNEOS',
                tipo: 'subcategoria',
                items: [
                    { id: 17, nombre: 'SELLOS DE CADENAS', tipo: 'item' },
                    { id: 18, nombre: 'PERNERÍA', tipo: 'item' },
                    { id: 19, nombre: 'BARRAS DE RECALCE', tipo: 'item' },
                    { id: 20, nombre: 'RESORTE TEMPLADOR', tipo: 'item' },
                    { id: 21, nombre: 'GUIADORES DE CADENA', tipo: 'item' },
                    { id: 22, nombre: 'PINES Y BOCINAS DE CUCHARA', tipo: 'item' },
                    { id: 23, nombre: 'GUIADORES DE MOTONIVELADORAS', tipo: 'item' }
                ]
            }
        ];
        
        construirMenuTabular(datos);
        configurarEventos();
    }

    // Configurar eventos para los elementos del menú
    function configurarEventos() {
        // Configurar eventos para links de marcas e items
        document.querySelectorAll('.mega-menu a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Si tiene PDF, abrirlo
                if (this.hasAttribute('data-pdf')) {
                    const pdfRuta = this.getAttribute('data-pdf');
                    if (pdfRuta) {
                        window.open(pdfRuta, '_blank');
                    }
                    return;
                }
                
                // Obtener datos del elemento seleccionado
                const categoriaId = this.getAttribute('data-categoria');
                const marcaId = this.getAttribute('data-marca');
                const itemId = this.getAttribute('data-item');
                
                console.log('Seleccionado:', {
                    categoriaId: categoriaId,
                    marcaId: marcaId,
                    itemId: itemId,
                    texto: this.textContent.trim()
                });
                
                // Implementar lógica para mostrar productos de la marca seleccionada
                // ...
                
                // Cerrar el menú después de seleccionar
                const megaMenu = document.querySelector('.mega-menu');
                if (megaMenu) {
                    megaMenu.style.display = 'none';
                }
            });
        });
    }

    // Inicializar todas las funciones
    function inicializar() {
        // Configurar menú móvil
        setupMobileMenu();
        
        // Configurar menú desplegable de productos
        setupProductosMenu();
        
        // Cargar datos del menú desde la API
        cargarMenu();
    }
    
    // Llamar a la función inicializar para arrancar todo el proceso
    inicializar();
    
    // Manejar cambios de tamaño de ventana
    window.addEventListener('resize', function() {
        // Volver a configurar el menú si cambia el tamaño (especialmente entre móvil y escritorio)
        setupProductosMenu();
    });
});