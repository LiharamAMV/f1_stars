<!-- ========================================== -->
<!-- templates/admin/sessions.php -->
<!-- ========================================== -->
<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap f1-stars-admin">
    <h1><?php _e('Sesiones', 'f1-stars'); ?></h1>
    
    <?php
    global $wpdb;
    $season = get_option('f1_stars_season', 2026);
    $table = $wpdb->prefix . 'f1_sessions';
    
    // Eliminar completamente sesiones de entrenamientos libres y cualquier apuesta asociada de la base de datos de apuestas
    $wpdb->query("DELETE b FROM {$wpdb->prefix}f1_bets b INNER JOIN {$table} s ON b.session_id = s.id WHERE s.session_type LIKE 'practice%'");
    $wpdb->query("DELETE FROM {$table} WHERE session_type LIKE 'practice%'");

    $sessions = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE season = %d AND session_type IN ('qualy', 'race', 'sprintQualy', 'sprintRace') ORDER BY session_date ASC",
        $season
    ));
    
    $session_names = array(
        'qualy'       => 'Clasificación',
        'race'        => 'Carrera',
        'sprintQualy' => 'Clasificación Sprint',
        'sprintRace'  => 'Carrera Sprint'
    );
    ?>
    
    <div id="f1-reset-notice" style="display:none;margin:10px 0;" class="notice notice-success is-dismissible"><p></p></div>

    <div class="f1-card">
        <h2>Sesiones de la Temporada <?php echo $season; ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Ronda</th>
                    <th>Gran Premio</th>
                    <th>Sesión</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Apuestas</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessions as $s): 
                    $bets_count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}f1_bets WHERE session_id = %d",
                        $s->id
                    ));
                    $is_past = strtotime($s->session_date) < time();
                ?>
                <tr id="session-row-<?php echo $s->id; ?>">
                    <td><?php echo $s->round_number; ?></td>
                    <td><?php echo esc_html($s->country); ?></td>
                    <td><?php echo $session_names[$s->session_type] ?? $s->session_type; ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($s->session_date)); ?></td>
                    <td class="session-status-<?php echo $s->id; ?>">
                        <?php if ($s->is_completed): ?>
                            <span class="f1-status completed">✓ Completada</span>
                        <?php elseif ($is_past): ?>
                            <span class="f1-status pending">⏳ Pendiente</span>
                        <?php else: ?>
                            <span class="f1-status upcoming">📅 Próxima</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $bets_count; ?> apuestas</td>
                    <td>
                        <?php if ($s->is_completed && $is_past): ?>
                        <button class="button button-small btn-reset-session"
                                data-session-id="<?php echo $s->id; ?>"
                                data-session-label="<?php echo esc_attr($s->round_name . ' – ' . ($session_names[$s->session_type] ?? $s->session_type)); ?>"
                                title="Borrar resultados y recargar desde la API">
                            🔄 Recargar
                        </button>
                        <?php elseif ($is_past && !$s->is_completed): ?>
                        <span style="color:#999;font-size:12px;margin-right:4px;">Pendiente de carga</span>
                        <?php endif; ?>

                        <button class="button button-small btn-delete-session"
                                data-session-id="<?php echo $s->id; ?>"
                                data-session-label="<?php echo esc_attr(($s->country ?: $s->round_name) . ' – ' . ($session_names[$s->session_type] ?? $s->session_type) . ' (' . date('d/m/Y H:i', strtotime($s->session_date)) . ')'); ?>"
                                style="color:#c0392b;border-color:#e74c3c;margin-left:4px;"
                                title="Eliminar sesión permanentemente">
                            🗑️
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
jQuery(function($) {
    $('.btn-reset-session').on('click', function() {
        const btn       = $(this);
        const sessionId = btn.data('session-id');
        const label     = btn.data('session-label');

        if (!confirm('¿Resetear «' + label + '»?\n\nSe borrarán los resultados actuales y la sesión quedará lista para recargarse con «Cargar Resultados».')) {
            return;
        }

        btn.prop('disabled', true).text('Reseteando…');

        $.ajax({
            url:  f1StarsAdmin.ajax_url,
            type: 'POST',
            data: {
                action:     'f1_reset_session',
                nonce:      f1StarsAdmin.nonce,
                session_id: sessionId
            },
            success: function(response) {
                if (response.success) {
                    // Actualizar estado en tabla
                    $('#session-row-' + sessionId + ' .session-status-' + sessionId)
                        .html('<span class="f1-status pending">⏳ Pendiente</span>');
                    btn.replaceWith('<span style="color:#999;font-size:12px;margin-right:4px;">Pendiente de carga</span>');

                    // Mostrar aviso
                    $('#f1-reset-notice p').text(response.data.message);
                    $('#f1-reset-notice').slideDown();
                    setTimeout(function() { $('#f1-reset-notice').slideUp(); }, 6000);
                } else {
                    alert('Error: ' + response.data);
                    btn.prop('disabled', false).text('🔄 Recargar');
                }
            },
            error: function(xhr) {
                alert('Error HTTP ' + xhr.status + '. Intenta de nuevo.');
                btn.prop('disabled', false).text('🔄 Recargar');
            }
        });
    });

    $('.btn-delete-session').on('click', function() {
        const btn       = $(this);
        const sessionId = btn.data('session-id');
        const label     = btn.data('session-label');

        if (!confirm('¿Eliminar la sesión «' + label + '»?\n\nEsta acción borrará la sesión y cualquier apuesta asociada de la base de datos.')) {
            return;
        }

        btn.prop('disabled', true).text('⏳');

        $.ajax({
            url:  f1StarsAdmin.ajax_url,
            type: 'POST',
            data: {
                action:     'f1_delete_session',
                nonce:      f1StarsAdmin.nonce,
                session_id: sessionId
            },
            success: function(response) {
                if (response.success) {
                    $('#session-row-' + sessionId).fadeOut(400, function() { $(this).remove(); });
                    $('#f1-reset-notice p').text(response.data.message || 'Sesión eliminada correctamente.');
                    $('#f1-reset-notice').slideDown();
                    setTimeout(function() { $('#f1-reset-notice').slideUp(); }, 5000);
                } else {
                    alert('Error: ' + response.data);
                    btn.prop('disabled', false).text('🗑️');
                }
            },
            error: function(xhr) {
                alert('Error HTTP ' + xhr.status + '. Intenta de nuevo.');
                btn.prop('disabled', false).text('🗑️');
            }
        });
    });
});
</script>
