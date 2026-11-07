<!-- templates/admin/dashboard.php -->
<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap f1-stars-admin">
    <h1><?php _e('F1 Stars - Panel de Control', 'f1-stars'); ?></h1>
    
    <div class="f1-dashboard">
        <div class="f1-card">
            <h2>🔧 Test de Conexión API</h2>
            <p>Verifica que el servidor puede conectarse correctamente a las APIs de F1.</p>
            <button id="test-api" class="button">Probar Conexión</button>
            <div id="test-api-result"></div>
        </div>
        
        <div class="f1-card">
            <h2>🗄️ Crear Tablas de Base de Datos</h2>
            <p>Crea las tablas necesarias para que el plugin funcione correctamente.</p>
            <p style="color: #666; font-size: 12px;">Si las tablas ya existen, no serán modificadas.</p>
            <button id="create-tables" class="button button-primary">Crear Tablas</button>
            <div id="create-tables-result"></div>
        </div>
        
        <div class="f1-card">
            <h2>🏎️ Cargar Datos de F1</h2>
            <p>Carga las carreras, sesiones y pilotos de la temporada actual desde la API oficial de F1.</p>
            <p style="color: #666; font-size: 12px;">Este proceso puede tardar 20-30 segundos.</p>
            <button id="load-races" class="button button-primary">Cargar Carreras y Sesiones</button>
            <div id="load-races-result"></div>
        </div>
        
        <div class="f1-card">
            <h2>🔧 Reparar Sesiones Duplicadas</h2>
            <p>Si el formulario muestra la misma carrera dos veces, o en "Apuestas" falta gente, usa esto para limpiar duplicados.</p>
            <p style="color:#666;font-size:12px;">Fusiona sesiones duplicadas y reasigna todas las apuestas al ID correcto. Seguro de ejecutar.</p>
            <button id="dedup-sessions" class="button button-primary">🔧 Eliminar Duplicados</button>
            <div id="dedup-sessions-result"></div>
        </div>

        <div class="f1-card">
            <h2>🗑️ Eliminar Apuestas de Temporada Anterior</h2>
            <p>Elimina las apuestas de una temporada específica para limpiar datos del año pasado.</p>
            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:10px;">
                <select id="delete-season-select" style="padding:6px 10px; border-radius:4px; border:1px solid #ccc;">
                    <option value="2025">2025</option>
                    <option value="2024">2024</option>
                    <option value="2023">2023</option>
                </select>
                <button id="delete-bets" class="button" style="background:#e74c3c;color:white;border-color:#c0392b;">
                    🗑️ Solo borrar Apuestas
                </button>
                <button id="delete-season-data" class="button" style="background:#8e1c1c;color:white;border-color:#6b1414;">
                    ⚠️ Borrar TODO (sesiones + resultados + pilotos)
                </button>
            </div>
            <p style="color:#888;font-size:12px;">⚠️ Esta acción no se puede deshacer. "Solo borrar Apuestas" mantiene las sesiones y resultados.</p>
            <div id="delete-bets-result"></div>
        </div>
        
        <div class="f1-card">
            <h2>📊 Cargar Resultados</h2>
            <p>Actualiza los resultados de las sesiones completadas y calcula los puntos.</p>
            <button id="load-results" class="button button-primary">Cargar Resultados</button>
            <div id="load-results-result"></div>
        </div>
        
        <div class="f1-card">
            <h2>📈 Estadísticas</h2>
            <?php
            global $wpdb;
            $table_participants = $wpdb->prefix . 'f1_participants';
            $table_sessions     = $wpdb->prefix . 'f1_sessions';
            $table_bets         = $wpdb->prefix . 'f1_bets';
            $table_drivers      = $wpdb->prefix . 'f1_drivers';
            
            $tables_exist = $wpdb->get_var("SHOW TABLES LIKE '$table_participants'") === $table_participants;
            
            if ($tables_exist) {
                $participants = $wpdb->get_var("SELECT COUNT(*) FROM $table_participants");
                $sessions = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}f1_sessions WHERE season = %d",
                    get_option('f1_stars_season', 2026)
                ));
                $bets = $wpdb->get_var("SELECT COUNT(*) FROM $table_bets");
                $drivers = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}f1_drivers WHERE season = %d",
                    get_option('f1_stars_season', 2026)
                ));
                $completed = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}f1_sessions WHERE season = %d AND is_completed = 1",
                    get_option('f1_stars_season', 2026)
                ));
            } else {
                $participants = 0;
                $sessions = 0;
                $bets = 0;
                $drivers = 0;
                $completed = 0;
            }
            ?>
            <ul>
                <li><strong>Participantes:</strong> <?php echo intval($participants); ?></li>
                <li><strong>Pilotos cargados:</strong> <?php echo intval($drivers); ?></li>
                <li><strong>Sesiones totales:</strong> <?php echo intval($sessions); ?></li>
                <li><strong>Sesiones completadas:</strong> <?php echo intval($completed); ?></li>
                <li><strong>Apuestas totales:</strong> <?php echo intval($bets); ?></li>
            </ul>
            
            <?php if ($sessions == 0 && $tables_exist): ?>
                <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 10px; border-radius: 4px; margin-top: 10px;">
                    <strong>⚠️ Atención:</strong> No hay sesiones cargadas. Haz clic en "Cargar Carreras y Sesiones" arriba.
                </div>
            <?php endif; ?>
            
            <?php if ($drivers == 0 && $tables_exist): ?>
                <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 10px; border-radius: 4px; margin-top: 10px;">
                    <strong>⚠️ Atención:</strong> No hay pilotos cargados. Haz clic en "Cargar Carreras y Sesiones" arriba.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
#test-api-result, #create-tables-result, #load-races-result, #load-results-result, #delete-bets-result, #dedup-sessions-result {
    margin-top: 15px;
    padding: 12px;
    border-radius: 4px;
    display: none;
    white-space: pre-wrap;
    font-size: 13px;
}

#test-api-result.success, #create-tables-result.success, 
#load-races-result.success, #load-results-result.success, #delete-bets-result.success, #dedup-sessions-result.success {
    background: #d5f5e3;
    border: 1px solid #27ae60;
    color: #1e7e34;
    display: block;
}

#test-api-result.error, #create-tables-result.error, 
#load-races-result.error, #load-results-result.error, #delete-bets-result.error, #dedup-sessions-result.error {
    background: #fadbd8;
    border: 1px solid #e74c3c;
    color: #c0392b;
    display: block;
}

#test-api-result.info {
    background: #d1ecf1;
    border: 1px solid #0c5460;
    color: #0c5460;
    display: block;
}

.api-test-details {
    margin-top: 10px;
    font-family: monospace;
    font-size: 12px;
}

.api-test-item {
    padding: 5px 0;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.api-test-item:last-child {
    border-bottom: none;
}
</style>

<script>
jQuery(document).ready(function($) {
    
    // Test API
    $('#test-api').on('click', function() {
        const button = $(this);
        const result = $('#test-api-result');
        
        button.prop('disabled', true).html('Probando... <span class="f1-loading"></span>');
        result.removeClass('success error info').hide();
        
        $.ajax({
            url: f1StarsAdmin.ajax_url,
            type: 'POST',
            timeout: 60000,
            data: {
                action: 'f1_test_api',
                nonce: f1StarsAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    const d = response.data;
                    
                    function row(label, obj) {
                        if (typeof obj === 'string') {
                            return '<div class="api-test-item"><strong>' + label + ':</strong><br>' + obj + '</div>';
                        }
                        let s = '<div class="api-test-item"><strong>' + label + ':</strong><br>';
                        for (const [k, v] of Object.entries(obj)) {
                            const val = Array.isArray(v) ? v.join(', ') : v;
                            s += '<em>' + k + ':</em> ' + val + '<br>';
                        }
                        return s + '</div>';
                    }
                    
                    let html = '<strong>Diagnóstico completo</strong><div class="api-test-details">';
                    html += row('Columna alpha_round_id', d.columna_alpha_round_id || '?');
                    html += row('Calendario', d.calendario);
                    html += row('Pilotos', d.pilotos);
                    
                    if (d.sprint_race_session)  html += row('Sesión sprintRace en BD', d.sprint_race_session);
                    if (d.sprint_race_ergast)   html += row('API sprint race (ergast)', d.sprint_race_ergast);
                    if (d.sprint_qualy_session) html += row('Sesión sprintQualy en BD', d.sprint_qualy_session);
                    if (d.alpha_rounds_lookup)  html += row('Alpha rounds lookup', d.alpha_rounds_lookup);
                    if (d.sprint_qualy_alpha)   html += row('Alpha SQ results', d.sprint_qualy_alpha);
                    if (d.sprint_race_alpha)    html += row('Alpha SR results', d.sprint_race_alpha);
                    html += '</div>';
                    
                    result.addClass('success').html(html).fadeIn();
                } else {
                    result.addClass('error').html('✗ ' + response.data).fadeIn();
                }
            },
            error: function() {
                result.addClass('error').html('✗ Error de conexión. Intenta de nuevo.').fadeIn();
            },
            complete: function() {
                button.prop('disabled', false).html('Probar Conexión');
            }
        });
    });
    
    // Crear tablas
    $('#create-tables').on('click', function() {
        const button = $(this);
        const result = $('#create-tables-result');
        
        button.prop('disabled', true).html('Creando... <span class="f1-loading"></span>');
        result.removeClass('success error').hide();
        
        $.ajax({
            url: f1StarsAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'f1_create_tables',
                nonce: f1StarsAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    result.addClass('success').html('✓ ' + response.data).fadeIn();
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    result.addClass('error').html('✗ ' + response.data).fadeIn();
                }
            },
            error: function() {
                result.addClass('error').html('✗ Error de conexión. Intenta de nuevo.').fadeIn();
            },
            complete: function() {
                button.prop('disabled', false).html('Crear Tablas');
            }
        });
    });
    
    // Cargar carreras y sesiones
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
                    result.addClass('success').html('✓ ' + response.data.message).fadeIn();
                    setTimeout(function() {
                        location.reload();
                    }, 3000);
                } else {
                    result.addClass('error').html('✗ ' + response.data).fadeIn();
                }
            },
            error: function(xhr, status, error) {
                result.addClass('error').html('✗ Error de conexión: ' + error + '\n\nIntenta de nuevo o verifica la conectividad del servidor.').fadeIn();
            },
            complete: function() {
                button.prop('disabled', false).html('Cargar Carreras y Sesiones');
            }
        });
    });
    
    // Eliminar apuestas de temporada
    function deleteBetsAction(action) {
        const season = $('#delete-season-select').val();
        const result = $('#delete-bets-result');
        const label = action === 'f1_delete_bets' ? 'Solo borrar Apuestas' : 'Borrar TODO';
        
        if (!confirm('¿Seguro que quieres ' + label.toLowerCase() + ' de la temporada ' + season + '? Esta acción no se puede deshacer.')) return;
        
        result.removeClass('success error').hide();
        
        $.ajax({
            url: f1StarsAdmin.ajax_url,
            type: 'POST',
            data: {
                action: action,
                nonce: f1StarsAdmin.nonce,
                season: season
            },
            success: function(response) {
                if (response.success) {
                    result.addClass('success').html('✓ ' + response.data).fadeIn();
                    setTimeout(function() { location.reload(); }, 3000);
                } else {
                    result.addClass('error').html('✗ ' + response.data).fadeIn();
                }
            },
            error: function() {
                result.addClass('error').html('✗ Error de conexión.').fadeIn();
            }
        });
    }
    
    $('#delete-bets').on('click', function() { deleteBetsAction('f1_delete_bets'); });
    $('#delete-season-data').on('click', function() { deleteBetsAction('f1_delete_season_data'); });

    // Eliminar duplicados
    $('#dedup-sessions').on('click', function() {
        const button = $(this);
        const result = $('#dedup-sessions-result');
        button.prop('disabled', true).html('Procesando...');
        result.removeClass('success error').hide();
        $.ajax({
            url: f1StarsAdmin.ajax_url,
            type: 'POST',
            data: { action: 'f1_dedup_sessions', nonce: f1StarsAdmin.nonce },
            success: function(response) {
                if (response.success) {
                    result.addClass('success').html(response.data).fadeIn();
                    setTimeout(function() { location.reload(); }, 3000);
                } else {
                    result.addClass('error').html('✗ ' + response.data).fadeIn();
                }
            },
            error: function() { result.addClass('error').html('✗ Error de conexión').fadeIn(); },
            complete: function() { button.prop('disabled', false).html('🔧 Eliminar Duplicados'); }
        });
    });

    // Cargar resultados
    $('#load-results').on('click', function() {
        const button = $(this);
        const result = $('#load-results-result');
        
        button.prop('disabled', true).html('Cargando... <span class="f1-loading"></span>');
        result.removeClass('success error').hide();
        
        $.ajax({
            url: f1StarsAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'f1_load_results',
                nonce: f1StarsAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    result.addClass('success').html('✓ ' + response.data.message).fadeIn();
                    setTimeout(function() {
                        location.reload();
                    }, 3000);
                } else {
                    result.addClass('error').html('✗ ' + response.data).fadeIn();
                }
            },
            error: function() {
                result.addClass('error').html('✗ Error de conexión. Intenta de nuevo.').fadeIn();
            },
            complete: function() {
                button.prop('disabled', false).html('Cargar Resultados');
            }
        });
    });
    
});
</script>
