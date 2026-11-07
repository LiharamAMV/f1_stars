/* ========================================== */
/* assets/js/admin.js */
/* ========================================== */

jQuery(document).ready(function($) {
    
    // Cargar carreras
    $('#load-races').on('click', function() {
        const button = $(this);
        const result = $('#load-races-result');
        
        button.prop('disabled', true).html('Cargando... <span class="f1-loading"></span>');
        result.removeClass('success error').hide();
        
        $.ajax({
            url: f1StarsAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'f1_load_races',
                nonce: f1StarsAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    result.addClass('success').html(response.data.message).fadeIn();
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    result.addClass('error').html('Error: ' + response.data).fadeIn();
                }
            },
            error: function() {
                result.addClass('error').html('Error de conexión. Intenta de nuevo.').fadeIn();
            },
            complete: function() {
                button.prop('disabled', false).html('Cargar Carreras y Sesiones');
            }
        });
    });
    
    // Cargar resultados (con procesamiento por lotes para evitar timeout)
    $('#load-results').on('click', function() {
        const button = $(this);
        const result = $('#load-results-result');
        
        button.prop('disabled', true).html('Cargando... <span class="f1-loading"></span>');
        result.removeClass('success error').hide();
        
        let totalLoaded = 0;
        let allErrors   = [];
        
        function runBatch(batch) {
            $.ajax({
                url: f1StarsAdmin.ajax_url,
                type: 'POST',
                timeout: 120000, // 2 min por lote
                data: {
                    action: 'f1_load_results',
                    nonce:  f1StarsAdmin.nonce,
                    batch:  batch
                },
                success: function(response) {
                    if (!response.success) {
                        result.addClass('error').html('✗ Error: ' + response.data).fadeIn();
                        button.prop('disabled', false).html('Cargar Resultados');
                        return;
                    }
                    totalLoaded += response.data.loaded || 0;
                    if (response.data.errors && response.data.errors.length) {
                        allErrors = allErrors.concat(response.data.errors);
                    }
                    
                    if (response.data.has_more) {
                        // Quedan sesiones — mostrar progreso y continuar
                        result.removeClass('error').addClass('success')
                              .html('⏳ Procesando... ' + totalLoaded + ' sesiones cargadas, ' + response.data.remaining + ' pendientes.')
                              .fadeIn();
                        setTimeout(function() { runBatch(response.data.next_batch); }, 500);
                    } else {
                        // Todo listo
                        let msg = '✓ Resultados cargados: ' + totalLoaded + ' sesiones.';
                        if (allErrors.length) {
                            msg += '<br><small>' + allErrors.slice(0, 5).join('<br>') + '</small>';
                        }
                        result.removeClass('error').addClass('success').html(msg).fadeIn();
                        button.prop('disabled', false).html('Cargar Resultados');
                        setTimeout(function() { location.reload(); }, 2500);
                    }
                },
                error: function(xhr) {
                    result.addClass('error').html('✗ Error de conexión (lote ' + batch + '). Estado HTTP: ' + xhr.status + '. Intenta de nuevo.').fadeIn();
                    button.prop('disabled', false).html('Cargar Resultados');
                }
            });
        }
        
        runBatch(0);
    });
    
});