/* ========================================== */
/* assets/js/frontend.js */
/* ========================================== */

jQuery(document).ready(function($) {
    
    let drivers = [];
    
    // Cargar pilotos al cargar la página
    function loadDrivers() {
        $.ajax({
            url: f1Stars.ajax_url,
            type: 'POST',
            data: {
                action: 'f1_get_drivers',
                nonce: f1Stars.nonce
            },
            success: function(response) {
                if (response.success) {
                    drivers = response.data;
                    populateDriverSelects();
                }
            }
        });
    }
    
    // Poblar los selectores de pilotos
    function populateDriverSelects() {
        const selects = ['#driver1', '#driver2', '#driver3'];
        
        // Extraer apellido y ordenar alfabéticamente
        const sortedDrivers = drivers.slice().sort(function(a, b) {
            const nameA = a.full_name || '';
            const nameB = b.full_name || '';
            const lastA = nameA.split(' ').pop();
            const lastB = nameB.split(' ').pop();
            return lastA.localeCompare(lastB);
        });
        
        selects.forEach(function(selectId, index) {
            const $select = $(selectId);
            if (!$select.length) return;
            const currentVal = $select.val();
            const placeholder = $select.find('option:first').text() || ((index + 1) + 'º Posición');
            
            $select.empty().append($('<option>', {
                value: '',
                text: placeholder
            }));
            
            sortedDrivers.forEach(function(driver) {
                const name = driver.full_name || '';
                const lastName = name.split(' ').pop();
                $select.append($('<option>', {
                    value: driver.driver_number,
                    text: lastName,
                    'data-headshot': driver.headshot_url || '',
                    'data-colour': driver.team_colour || '',
                    'data-team': driver.team || '',
                    'data-fullname': name
                }));
            });

            if (currentVal) {
                $select.val(currentVal);
                updateDriverPreview(selectId);
            }
        });
    }
    
    // Actualizar la preview del piloto seleccionado
    function updateDriverPreview(selectId) {
        const $select = $(selectId);
        const $preview = $('#preview-' + selectId.replace('#', ''));
        const $option = $select.find('option:selected');
        
        if (!$preview.length) return;
        
        const val = $select.val();
        if (!val) {
            $preview.removeClass('active').empty();
            $preview.css('border-left-color', '#ccc');
            return;
        }
        
        const headshot = $option.data('headshot') || '';
        const colour = $option.data('colour') || '999';
        const team = $option.data('team') || '';
        const fullname = $option.data('fullname') || $option.text();
        
        let html = '';
        if (headshot) {
            html += '<img src="' + headshot + '" alt="' + fullname + '">';
        }
        html += '<div>';
        html += '<div class="f1-preview-name">' + fullname + '</div>';
        if (team) {
            html += '<div class="f1-preview-team">' + team + '</div>';
        }
        html += '</div>';
        
        $preview.html(html).addClass('active');
        $preview.css('border-left-color', '#' + colour.replace('#', ''));
    }
    
    // Validar que no se repitan pilotos
    function validateDrivers() {
        const driver1 = $('#driver1').val();
        const driver2 = $('#driver2').val();
        const driver3 = $('#driver3').val();
        
        if (driver1 && driver2 && driver1 === driver2) {
            return false;
        }
        if (driver1 && driver3 && driver1 === driver3) {
            return false;
        }
        if (driver2 && driver3 && driver2 === driver3) {
            return false;
        }
        
        return true;
    }
    
    // Evento de cambio en los selectores de pilotos
    $('#driver1, #driver2, #driver3').on('change', function() {
        const id = '#' + $(this).attr('id');
        updateDriverPreview(id);
        
        if (!validateDrivers()) {
            alert('No puedes seleccionar el mismo piloto dos veces');
            $(this).val('');
            updateDriverPreview(id);
        }
    });
    
    // Enviar apuesta
    $('#f1-bet-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const button = form.find('button[type="submit"]');
        const result = $('#bet-result');
        
        // Validar
        if (!validateDrivers()) {
            result.removeClass('success').addClass('error')
                  .html('Debes seleccionar 3 pilotos diferentes').fadeIn();
            return;
        }
        
        const nick = $('#nick').val().trim();
        const session = $('#session').val();
        const driver1 = $('#driver1').val();
        const driver2 = $('#driver2').val();
        const driver3 = $('#driver3').val();
        
        if (!nick || !session || !driver1 || !driver2 || !driver3) {
            result.removeClass('success').addClass('error')
                  .html('Todos los campos son obligatorios').fadeIn();
            return;
        }
        
        button.prop('disabled', true).html('Enviando... <span class="f1-loading"></span>');
        result.removeClass('success error').hide();
        
        $.ajax({
            url: f1Stars.ajax_url,
            type: 'POST',
            data: {
                action: 'f1_submit_bet',
                nonce: f1Stars.nonce,
                nick: nick,
                session_id: session,
                driver1: driver1,
                driver2: driver2,
                driver3: driver3
            },
            success: function(response) {
                if (response.success) {
                    result.removeClass('error').addClass('success')
                          .html('✓ ' + response.data).fadeIn();
                    
                    // Limpiar formulario (excepto el nick)
                    $('#session, #driver1, #driver2, #driver3').val('');
                    // Limpiar previews
                    $('.f1-driver-preview').removeClass('active').empty().css('border-left-color', '#ccc');
                } else {
                    result.removeClass('success').addClass('error')
                          .html('✗ ' + response.data).fadeIn();
                }
            },
            error: function() {
                result.removeClass('success').addClass('error')
                      .html('✗ Error de conexión. Intenta de nuevo.').fadeIn();
            },
            complete: function() {
                button.prop('disabled', false).html('Enviar Apuesta');
            }
        });
    });
    
    // Inicializar
    if ($('#f1-bet-form').length > 0) {
        loadDrivers();
        ['#driver1', '#driver2', '#driver3'].forEach(function(id) {
            if ($(id).val()) {
                updateDriverPreview(id);
            }
        });
    }
    
    // Botón Cargar Resultados (visible para todos los usuarios)
    $('#f1-load-results-btn').on('click', function() {
        const $btn = $(this);
        const $msg = $('#f1-load-results-msg');
        
        $btn.prop('disabled', true).text('⏳ Cargando...');
        $msg.hide();
        
        $.ajax({
            url: f1Stars.ajax_url,
            type: 'POST',
            data: {
                action: 'f1_load_results',
                nonce: f1Stars.nonce
            },
            success: function(response) {
                if (response.success) {
                    $msg.css({'color': '#00c853', 'padding': '8px 12px', 'background': 'rgba(0,200,83,0.1)', 'border-radius': '6px'})
                        .html('✓ ' + response.data.message).fadeIn();
                    if (response.data.loaded > 0) {
                        setTimeout(function() { location.reload(); }, 1500);
                    }
                } else {
                    $msg.css({'color': '#ff1744', 'padding': '8px 12px', 'background': 'rgba(255,23,68,0.1)', 'border-radius': '6px'})
                        .html('✗ ' + response.data).fadeIn();
                }
            },
            error: function() {
                $msg.css({'color': '#ff1744'}).html('✗ Error de conexión').fadeIn();
            },
            complete: function() {
                $btn.prop('disabled', false).text('📊 Cargar Resultados');
            }
        });
    });
    
    // Auto-refresh de clasificación cada 60 segundos
    if ($('.f1-stars-standings').length > 0) {
        setInterval(function() {
            location.reload();
        }, 60000);
    }
    
});