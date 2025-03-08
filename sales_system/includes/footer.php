</main>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Custom JS -->
<script src="<?php echo getBaseUrl(); ?>assets/js/notifications.js"></script>

<script>
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Handle session timeout
    let sessionTimeout;
    function resetSessionTimeout() {
        clearTimeout(sessionTimeout);
        sessionTimeout = setTimeout(function() {
            Swal.fire({
                title: 'Sessão expirando',
                text: 'Sua sessão irá expirar em breve. Deseja continuar?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, continuar',
                cancelButtonText: 'Sair'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Refresh session
                    fetch('<?php echo getBaseUrl(); ?>refresh_session.php')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                resetSessionTimeout();
                            } else {
                                window.location.href = '<?php echo getBaseUrl(); ?>login.php';
                            }
                        })
                        .catch(() => {
                            window.location.href = '<?php echo getBaseUrl(); ?>login.php';
                        });
                } else {
                    window.location.href = '<?php echo getBaseUrl(); ?>logout.php';
                }
            });
        }, <?php echo (SESSION_LIFETIME - 300) * 1000; ?>); // 5 minutes before session expires
    }

    // Initialize session timeout
    resetSessionTimeout();
    document.addEventListener('click', resetSessionTimeout);
    document.addEventListener('keypress', resetSessionTimeout);

    // Handle AJAX errors globally
    $(document).ajaxError(function(event, jqXHR, settings, error) {
        let errorMessage = 'Erro ao processar a requisição';
        
        if (jqXHR.responseJSON && jqXHR.responseJSON.error) {
            errorMessage = jqXHR.responseJSON.error;
        } else if (jqXHR.status === 401) {
            errorMessage = 'Sessão expirada. Por favor, faça login novamente.';
            setTimeout(() => {
                window.location.href = '<?php echo getBaseUrl(); ?>login.php';
            }, 2000);
        } else if (jqXHR.status === 403) {
            errorMessage = 'Você não tem permissão para realizar esta ação.';
        }
        
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: errorMessage
        });
    });

    // Add CSRF token to all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Format currency function
    function formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    }

    // Format date function
    function formatDate(dateString, showTime = false) {
        const options = {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        };
        
        if (showTime) {
            options.hour = '2-digit';
            options.minute = '2-digit';
        }
        
        return new Date(dateString).toLocaleDateString('pt-BR', options);
    }

    // Show loading spinner
    function showLoading(message = 'Carregando...') {
        Swal.fire({
            title: message,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }

    // Hide loading spinner
    function hideLoading() {
        Swal.close();
    }

    // Show success message
    function showSuccess(message) {
        Swal.fire({
            icon: 'success',
            title: 'Sucesso',
            text: message,
            timer: 2000,
            showConfirmButton: false
        });
    }

    // Show error message
    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: message
        });
    }

    // Show confirmation dialog
    function showConfirmation(title, text, callback) {
        Swal.fire({
            icon: 'warning',
            title: title,
            text: text,
            showCancelButton: true,
            confirmButtonText: 'Sim',
            cancelButtonText: 'Não'
        }).then((result) => {
            if (result.isConfirmed) {
                callback();
            }
        });
    }
</script>

</body>
</html>
