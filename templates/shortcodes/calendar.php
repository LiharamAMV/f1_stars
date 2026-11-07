<!-- ========================================== -->
<!-- templates/shortcodes/calendar.php -->
<!-- ========================================== -->
<?php if (!defined('ABSPATH')) exit; ?>

<div class="f1-stars-calendar">
    <h2>📅 Calendario de Grandes Premios</h2>
    
    <?php
    global $wpdb;
    $season = get_option('f1_stars_season', 2026);
    
    $races = $wpdb->get_results($wpdb->prepare(
        "SELECT DISTINCT round_number, round_name, country, circuit_name,
         MIN(session_date) as start_date,
         MAX(session_date) as end_date
         FROM {$wpdb->prefix}f1_sessions
         WHERE season = %d
         GROUP BY round_number
         ORDER BY round_number ASC",
        $season
    ));
    ?>
    
    <div class="f1-calendar-grid">
        <?php foreach ($races as $race): ?>
        <div class="f1-race-card">
            <div class="race-round">Ronda <?php echo $race->round_number; ?></div>
            <h3><?php echo esc_html($race->country); ?></h3>
            <p class="circuit"><?php echo esc_html($race->circuit_name); ?></p>
            <p class="dates">
                <?php 
                echo date('d/m', strtotime($race->start_date)) . ' - ' . 
                     date('d/m/Y', strtotime($race->end_date)); 
                ?>
            </p>
        </div>
        <?php endforeach; ?>
    </div>
</div>