/**
 * Sistema de Administración de PDFs
 * Este script gestiona todas las operaciones relacionadas con PDFs:
 * - Carga la lista de PDFs desde el servidor
 * - Permite filtrar y buscar PDFs
 * - Maneja la subida de nuevos PDFs
 * - Permite eliminar PDFs existentes
 */

document.addEventListener('DOMContentLoaded', function() {
    // Comprobar autenticación
    checkAuth();
    
    // Cargar datos iniciales
    loadPDFs();
    loadSelectors();
    
    // Configurar eventos
    setupEventListeners();
});

/**
 * Verifica si el usuario está autenticado y actualiza la interfaz
 */
function checkAuth() {
    const username = localStorage.getItem('admin_username');
    
    if (!username) {
        // Redireccionar al login si no hay sesión
        window.location.href = 'login.html';
        return;
    }
    
    // Mostrar nombre de usuario
    document.getElementById('username-display').textContent = username;
    
    // Configurar botón de logout
    document.getElementById('logout-btn').addEventListener('click', function(e) {
        e.preventDefault();
        localStorage.removeItem('admin_username');
        localStorage.removeItem('admin_token');
        window.location.href = 'login.html';
    });
}

/**
 * Carga la lista de PDFs desde el servidor
 */
function loadPDFs() {
    fetch('api/pdfs-list.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Error al cargar los PDFs: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            renderPDFs(data);
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('No se pudieron cargar los PDFs. Intente nuevamente.', 'danger');
            
            // Mostrar mensaje de error en la lista
            document.getElementById('pdfList').innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${error.message || 'Error al cargar los PDFs. Por favor, intente nuevamente.'}
                        <button class="btn btn-sm btn-outline-danger ms-3" onclick="loadPDFs()">
                            <i class="fas fa-sync me-1"></i> Reintentar
                        </button>
                    </div>
                </div>
            `;
        });
}

/**
 * Renderiza la lista de PDFs en la interfaz
 * @param {Array} pdfs - Lista de PDFs
 */
function renderPDFs(pdfs) {
    const pdfList = document.getElementById('pdfList');
    
    if (!pdfs || pdfs.length === 0) {
        pdfList.innerHTML = `
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No hay PDFs disponibles. Haga clic en "Subir nuevo PDF" para agregar uno.
                </div>
            </div>
        `;
        return;
    }
    
    let html = '';
    
    pdfs.forEach(pdf => {
        // Determinar el badge según el tipo de referencia
        let typeBadge = '';
        switch(pdf.tipo_referencia) {
            case 'tipo_maquinaria':
                typeBadge = '<span class="badge bg-primary">Tipo de Maquinaria</span>';
                break;
            case 'marca':
                typeBadge = '<span class="badge bg-info text-dark">Marca</span>';
                break;
            case 'producto':
                typeBadge = '<span class="badge bg-success">Producto</span>';
                break;
            default:
                typeBadge = '<span class="badge bg-secondary">Otro</span>';
        }
        
        // Formatear el tamaño del archivo
        let fileSize = '';
        if (pdf.tamano < 1024 * 1024) {
            fileSize = (pdf.tamano / 1024).toFixed(2) + ' KB';
        } else {
            fileSize = (pdf.tamano / (1024 * 1024)).toFixed(2) + ' MB';
        }
        
        // Crear card para el PDF
        html += `
            <div class="col-md-4 mb-4 pdf-item" 
                 data-nombre="${pdf.nombre_archivo.toLowerCase()}" 
                 data-tipo="${pdf.tipo_referencia}">
                <div class="card card-pdf h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-truncate" title="${pdf.nombre_archivo}">
                            ${pdf.nombre_archivo}
                        </h6>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item preview-pdf" href="#" data-id="${pdf.id_archivo}" 
                                       data-ruta="${pdf.ruta_archivo}" data-nombre="${pdf.nombre_archivo}">
                                        <i class="fas fa-eye me-2"></i> Ver PDF
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="${pdf.ruta_archivo}" download>
                                        <i class="fas fa-download me-2"></i> Descargar
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger delete-pdf" href="#" 
                                       data-id="${pdf.id_archivo}" 
                                       data-nombre="${pdf.nombre_archivo}">
                                        <i class="fas fa-trash me-2"></i> Eliminar
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-center align-items-center pdf-preview-container mb-3">
                            <div class="text-center">
                                <i class="fas fa-file-pdf pdf-icon"></i>
                                <p class="mt-2 mb-0">
                                    <span class="badge bg-secondary">${fileSize}</span>
                                </p>
                            </div>
                        </div>
                        <p class="mb-1">
                            <strong>Tipo:</strong> ${typeBadge}
                        </p>
                        <p class="mb-1">
                            <strong>Referencia:</strong> ${pdf.item_nombre}
                        </p>
                        ${pdf.descripcion ? `
                            <p class="mb-0 text-muted small">
                                ${pdf.descripcion}
                            </p>
                        ` : ''}
                    </div>
                    <div class="card-footer text-muted small">
                        <i class="fas fa-calendar-alt me-1"></i> ${formatDate(pdf.fecha_creacion)}
                    </div>
                </div>
            </div>
        `;
    });
    
    pdfList.innerHTML = html;
    
    // Asignar eventos después de renderizar
    assignPDFEvents();
}

/**
 * Asigna eventos a los elementos de la lista de PDFs
 */
function assignPDFEvents() {
    // Eventos para eliminar PDFs
    document.querySelectorAll('.delete-pdf').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const nombre = this.getAttribute('data-nombre');
            
            document.getElementById('pdfNameToDelete').textContent = nombre;
            
            const deleteBtn = document.getElementById('confirmDeleteBtn');
            deleteBtn.setAttribute('data-id', id);
            
            const deleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
            deleteModal.show();
        });
    });
    
    // Eventos para previsualizar PDFs
    document.querySelectorAll('.preview-pdf').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const ruta = this.getAttribute('data-ruta');
            const nombre = this.getAttribute('data-nombre');
            
            document.getElementById('pdfPreviewTitle').textContent = nombre;
            document.getElementById('pdfPreviewFrame').src = ruta;
            document.getElementById('pdfDownloadLink').href = ruta;
            
            const previewModal = new bootstrap.Modal(document.getElementById('pdfPreviewModal'));
            previewModal.show();
        });
    });
}

/**
 * Carga los selectores dinámicos para el formulario de subida
 */
function loadSelectors() {
    // Cargar tipos de maquinaria
    fetch('api/get-selectors.php?type=tipo_maquinaria')
        .then(response => response.json())
        .then(data => {
            window.tiposMaquinaria = data;
        })
        .catch(error => console.error('Error cargando tipos de maquinaria:', error));
    
    // Cargar marcas
    fetch('api/get-selectors.php?type=marcas')
        .then(response => response.json())
        .then(data => {
            window.marcas = data;
        })
        .catch(error => console.error('Error cargando marcas:', error));
    
    // Cargar productos
    fetch('api/get-selectors.php?type=productos')
        .then(response => response.json())
        .then(data => {
            window.productos = data;
        })
        .catch(error => console.error('Error cargando productos:', error));
}

/**
 * Configura los escuchadores de eventos para la interfaz
 */
function setupEventListeners() {
    // Cambio en tipo de referencia
    document.getElementById('tipoReferencia').addEventListener('change', function() {
        const tipo = this.value;
        let html = '';
        
        if (tipo === 'tipo_maquinaria' && window.tiposMaquinaria) {
            html = '<label for="idReferencia" class="form-label">Tipo de Maquinaria</label>';
            html += '<select class="form-select" id="idReferencia" name="id_referencia" required>';
            html += '<option value="">Seleccione tipo de maquinaria</option>';
            
            window.tiposMaquinaria.forEach(item => {
                html += `<option value="${item.id}">${item.nombre}</option>`;
            });
            
            html += '</select>';
        } else if (tipo === 'marca' && window.marcas) {
            html = '<label for="idReferencia" class="form-label">Marca</label>';
            html += '<select class="form-select" id="idReferencia" name="id_referencia" required>';
            html += '<option value="">Seleccione marca</option>';
            
            window.marcas.forEach(item => {
                html += `<option value="${item.id}">${item.nombre}</option>`;
            });
            
            html += '</select>';
        } else if (tipo === 'producto' && window.productos) {
            html = '<label for="idReferencia" class="form-label">Producto</label>';
            html += '<select class="form-select" id="idReferencia" name="id_referencia" required>';
            html += '<option value="">Seleccione producto</option>';
            
            window.productos.forEach(item => {
                html += `<option value="${item.id}">${item.nombre}</option>`;
            });
            
            html += '</select>';
        } else {
            html = '<div class="alert alert-info">Seleccione primero un tipo de referencia</div>';
        }
        
        document.getElementById('referenciaContainer').innerHTML = html;
    });
    
    // Filtrado de PDFs
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const searchText = this.value.toLowerCase();
        const filterType = document.getElementById('filterType').value;
        
        document.querySelectorAll('.pdf-item').forEach(item => {
            const nombre = item.getAttribute('data-nombre');
            const tipo = item.getAttribute('data-tipo');
            
            const matchesSearch = nombre.includes(searchText);
            const matchesType = filterType === '' || tipo === filterType;
            
            if (matchesSearch && matchesType) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    document.getElementById('filterType').addEventListener('change', function() {
        document.getElementById('searchInput').dispatchEvent(new Event('keyup'));
    });
    
    // Formulario de subida de PDF
    document.getElementById('pdfUploadForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const uploadBtn = document.getElementById('uploadPdfBtn');
        
        // Añadir spinner e inhabilitar botón
        uploadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Subiendo...';
        uploadBtn.disabled = true;
        
        fetch('api/upload-pdf.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message || 'PDF subido correctamente', 'success');
                
                // Cerrar modal y recargar lista
                bootstrap.Modal.getInstance(document.getElementById('uploadPdfModal')).hide();
                this.reset();
                loadPDFs();
            } else {
                showMessage(data.message || 'Error al subir el PDF', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error en el servidor. Intente nuevamente.', 'danger');
        })
        .finally(() => {
            // Restaurar botón
            uploadBtn.innerHTML = '<i class="fas fa-upload me-2"></i> Subir PDF';
            uploadBtn.disabled = false;
        });
    });
    
    // Confirmar eliminación de PDF
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        
        // Añadir spinner e inhabilitar botón
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Eliminando...';
        this.disabled = true;
        
        fetch(`api/delete-pdf.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message || 'PDF eliminado correctamente', 'success');
                    
                    // Cerrar modal y recargar lista
                    bootstrap.Modal.getInstance(document.getElementById('confirmDeleteModal')).hide();
                    loadPDFs();
                } else {
                    showMessage(data.message || 'Error al eliminar el PDF', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Error en el servidor. Intente nuevamente.', 'danger');
            })
            .finally(() => {
                // Restaurar botón
                this.innerHTML = '<i class="fas fa-trash me-2"></i> Eliminar';
                this.disabled = false;
            });
    });
}

/**
 * Muestra un mensaje en la interfaz
 * @param {string} message - Mensaje a mostrar
 * @param {string} type - Tipo de mensaje (success, danger, warning, info)
 */
function showMessage(message, type = 'info') {
    const msgElement = document.getElementById('mensaje-sistema');
    const msgText = document.getElementById('mensaje-texto');
    
    msgElement.className = `alert alert-${type} alert-dismissible fade show`;
    msgText.textContent = message;
    
    msgElement.style.display = 'block';
    
    // Ocultar después de 5 segundos
    setTimeout(() => {
        msgElement.style.display = 'none';
    }, 5000);
}

/**
 * Formatea una fecha para mostrarla en la interfaz
 * @param {string} dateString - Fecha en formato string
 * @returns {string} - Fecha formateada
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}