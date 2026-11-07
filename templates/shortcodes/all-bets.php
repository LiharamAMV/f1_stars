<!-- ========================================== -->
<!-- templates/shortcodes/all-bets.php -->
<!-- ========================================== -->
<?php if (!defined('ABSPATH')) exit;

/**
 * Devuelve solo el apellido de un piloto dado su nombre completo.
 * Ej: "Andrea Kimi Antonelli" → "Antonelli", "George Russell" → "Russell"
 */
function f1_stars_last_name( $full_name ) {
    $parts = explode( ' ', trim( $full_name ) );
    return end( $parts );
}

/**
 * Renderiza un nombre de piloto con headshot y barra de color de equipo.
 */
function f1_stars_driver_cell( $full_name, $headshot_url, $team_colour, $is_bold = false ) {
    $last_name = f1_stars_last_name( $full_name );
    $colour = $team_colour ? '#' . ltrim($team_colour, '#') : '#999';
    $tag_open = $is_bold ? '<strong>' : '';
    $tag_close = $is_bold ? '</strong>' : '';

    $html = '<div class="f1-driver-cell">';
    $html .= '<span class="f1-team-bar" style="background:' . esc_attr($colour) . ';"></span>';
    if ( $headshot_url ) {
        $html .= '<img class="f1-driver-thumb" src="' . esc_url($headshot_url) . '" alt="' . esc_attr($last_name) . '" loading="lazy">';
    }
    $html .= $tag_open . esc_html($last_name) . $tag_close;
    $html .= '</div>';
    return $html;
}
?>

<div class="f1-stars-all-bets">
    <h2>📋 Apuestas de Todos los Participantes</h2>
    
    <?php
    global $wpdb;
    $season = get_option('f1_stars_season', 2026);

    $session_names = array(
        'qualy'       => 'Clasificación',
        'race'        => 'Carrera',
        'sprintQualy' => 'Clasificación Sprint',
        'sprintRace'  => 'Carrera Sprint'
    );

    $all_sessions = $wpdb->get_results($wpdb->prepare(
        "SELECT
            MIN(s.id)           AS representative_id,
            s.round_number,
            s.session_type,
            s.country,
            s.round_name,
            MIN(s.session_date) AS session_date,
            MAX(s.is_completed) AS is_completed,
            GROUP_CONCAT(s.id) AS all_ids
         FROM {$wpdb->prefix}f1_sessions s
         INNER JOIN {$wpdb->prefix}f1_bets b ON s.id = b.session_id
         WHERE s.season = %d AND s.session_type NOT LIKE 'practice%'
         GROUP BY s.round_number, s.session_type
         ORDER BY session_date DESC",
        $season
    ));

    foreach ($all_sessions as $session):

        $ids = array_map('intval', explode(',', $session->all_ids));
        $ids_placeholder = implode(',', $ids);

        $bets = $wpdb->get_results($wpdb->prepare("
            SELECT
                p.nick,
                b.*,
                d1.full_name AS driver1_name,
                d1.headshot_url AS driver1_headshot,
                d1.team_colour AS driver1_colour,
                d2.full_name AS driver2_name,
                d2.headshot_url AS driver2_headshot,
                d2.team_colour AS driver2_colour,
                d3.full_name AS driver3_name,
                d3.headshot_url AS driver3_headshot,
                d3.team_colour AS driver3_colour
            FROM {$wpdb->prefix}f1_bets b
            JOIN {$wpdb->prefix}f1_participants p ON b.participant_id = p.id
            LEFT JOIN {$wpdb->prefix}f1_drivers d1 ON b.driver1_number = d1.driver_number AND d1.season = %d
            LEFT JOIN {$wpdb->prefix}f1_drivers d2 ON b.driver2_number = d2.driver_number AND d2.season = %d
            LEFT JOIN {$wpdb->prefix}f1_drivers d3 ON b.driver3_number = d3.driver_number AND d3.season = %d
            WHERE b.session_id IN ($ids_placeholder)
            ORDER BY p.nick ASC
        ", $season, $season, $season));

        if (empty($bets)) continue;

        // Obtener resultado real (top 3) si la sesión está completada
        $real_results = array();
        if ($session->is_completed) {
            $real_results = $wpdb->get_results($wpdb->prepare("
                SELECT d.full_name, d.headshot_url, d.team_colour
                FROM {$wpdb->prefix}f1_results r
                LEFT JOIN {$wpdb->prefix}f1_drivers d ON r.driver_number = d.driver_number AND d.season = %d
                WHERE r.session_id IN ($ids_placeholder)
                ORDER BY r.position ASC
                LIMIT 3
            ", $season));
        }

        $session_label = $session_names[$session->session_type] ?? $session->session_type;
        $date_label    = date('d/m/Y H:i', strtotime($session->session_date));
    ?>

    <div class="f1-session-bets">
        <h3>🏁 GP <?php echo esc_html($session->country); ?> - <?php echo esc_html($session_label); ?></h3>
        <p class="session-date"><?php echo $date_label; ?></p>

        <div class="f1-table-scroll">
        <table class="f1-table f1-table-drivers">
            <thead>
                <tr>
                    <th>Participante</th>
                    <th>1º</th>
                    <th>2º</th>
                    <th>3º</th>
                    <?php if ($session->is_completed): ?>
                    <th>Puntos</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($session->is_completed && !empty($real_results)): ?>
                <tr class="f1-result-row">
                    <td><strong>✅ Resultado</strong></td>
                    <?php for ($i = 0; $i < 3; $i++): ?>
                    <td>
                        <?php if (isset($real_results[$i])):
                            echo f1_stars_driver_cell(
                                $real_results[$i]->full_name,
                                $real_results[$i]->headshot_url,
                                $real_results[$i]->team_colour,
                                true
                            );
                        endif; ?>
                    </td>
                    <?php endfor; ?>
                    <td></td>
                </tr>
                <?php endif; ?>
                <?php foreach ($bets as $bet): ?>
                <tr>
                    <td><?php echo esc_html($bet->nick); ?></td>
                    <td><?php echo f1_stars_driver_cell($bet->driver1_name, $bet->driver1_headshot, $bet->driver1_colour); ?></td>
                    <td><?php echo f1_stars_driver_cell($bet->driver2_name, $bet->driver2_headshot, $bet->driver2_colour); ?></td>
                    <td><?php echo f1_stars_driver_cell($bet->driver3_name, $bet->driver3_headshot, $bet->driver3_colour); ?></td>
                    <?php if ($session->is_completed): ?>
                    <td><strong><?php echo intval($bet->points); ?></strong></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

    <?php endforeach; ?>
</div>
