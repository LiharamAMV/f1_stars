<!-- ========================================== -->
<!-- templates/shortcodes/standings.php -->
<!-- ========================================== -->
<?php if (!defined('ABSPATH')) exit; ?>

<div class="f1-stars-standings">
    <h2>🏆 Clasificación General</h2>
    
    <?php
    global $wpdb;
    $season = get_option('f1_stars_season', 2026);
    
    $standings = $wpdb->get_results("
        SELECT 
            p.nick,
            SUM(b.points) as total_points,
            COUNT(b.id) as total_bets
        FROM {$wpdb->prefix}f1_participants p
        LEFT JOIN {$wpdb->prefix}f1_bets b ON p.id = b.participant_id
        WHERE b.points IS NOT NULL
        GROUP BY p.id
        ORDER BY total_points DESC, p.nick ASC
    ");
    ?>
    
    
    <div class="f1-table-scroll">
    <table class="f1-table">
        <thead>
            <tr>
                <th class="position">Pos</th>
                <th class="nick">Participante</th>
                <th class="points">Puntos</th>
                <th class="bets">Apuestas</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $position = 1;
            $position_emojis = array(1 => '👑', 2 => '👏', 3 => '🤡', 4 => '💩');
            foreach ($standings as $s): 
                $emoji = isset($position_emojis[$position]) ? $position_emojis[$position] : '💩';
            ?>
            <tr>
                <td class="position"><?php echo $emoji; ?></td>
                <td class="nick"><?php echo esc_html($s->nick); ?></td>
                <td class="points"><strong><?php echo $s->total_points ?: 0; ?></strong></td>
                <td class="bets"><?php echo $s->total_bets; ?></td>
            </tr>
            <?php $position++; endforeach; ?>
        </tbody>
    </table>
    </div>

    <div class="f1-load-results-section" style="margin-top: 20px;">
        <button id="f1-load-results-btn" class="f1-button">
            📊 Cargar Resultados
        </button>
        <p class="f1-load-results-desc" style="color:#888;font-size:13px;margin-top:6px;">
            Actualiza los resultados de las sesiones completadas y calcula los puntos.
        </p>
        <div id="f1-load-results-msg" style="display:none;margin-top:10px;"></div>
    </div>
</div>