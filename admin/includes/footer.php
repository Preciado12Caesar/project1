</div>
            <!-- End Page Content -->
            
            <!-- Footer -->
            <footer class="footer mt-auto py-3 bg-white shadow">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col text-center">
                            <span>Copyright &copy; <a href="https://cesarpreciado.com"> Cesar Preciado </a> <?php echo date('Y'); ?></span>
                        </div>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->
    
    <!-- Scripts -->
    <script src="../scripts/jquery-3.7.1.min.js"></script>
    <script src="../scripts/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Función para inicializar DataTables si está presente
        if (typeof $.fn.DataTable !== 'undefined' && $('#dataTable').length > 0) {
            $('#dataTable').DataTable({
                "language": {
                    "search": "Buscar:",
                    "lengthMenu": "Mostrar _MENU_ registros por página",
                    "zeroRecords": "No se encontraron registros",
                    "info": "Mostrando página _PAGE_ de _PAGES_",
                    "infoEmpty": "No hay registros disponibles",
                    "infoFiltered": "(filtrado de _MAX_ registros totales)",
                    "paginate": {
                        "first": "Primero",
                        "last": "Último",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    }
                }
            });
        }
        
        // Tooltips de Bootstrap
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    });
    </script>
</body>
</html>