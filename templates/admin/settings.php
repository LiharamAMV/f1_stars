<!-- ========================================== -->
<!-- templates/admin/settings.php -->
<!-- ========================================== -->
<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap f1-stars-admin">
    <h1><?php _e('Ajustes de F1 Stars', 'f1-stars'); ?></h1>
    
    <?php
    if (isset($_POST['save_settings'])) {
        update_option('f1_stars_season', intval($_POST['season']));
        update_option('f1_stars_timezone', sanitize_text_field($_POST['timezone']));
        update_option('f1_stars_grace_minutes', max(0, intval($_POST['grace_minutes'])));
        echo '<div class="notice notice-success"><p>Ajustes guardados</p></div>';
    }
    
    $season = get_option('f1_stars_season', 2026);
    $timezone = get_option('f1_stars_timezone', 'Europe/Madrid');
    $grace_minutes = intval(get_option('f1_stars_grace_minutes', 30));
    ?>
    
    <form method="post" class="f1-card">
        <h2>Configuración General</h2>
        <table class="form-table">
            <tr>
                <th><label for="season">Temporada</label></th>
                <td>
                    <input type="number" name="season" id="season" value="<?php echo $season; ?>" class="regular-text">
                    <p class="description">Año de la temporada de F1</p>
                </td>
            </tr>
            <tr>
                <th><label for="timezone">Zona Horaria</label></th>
                <td>
                    <select name="timezone" id="timezone">
                        <option value="Europe/Madrid" <?php selected($timezone, 'Europe/Madrid'); ?>>Madrid (Europe/Madrid)</option>
                        <option value="America/New_York" <?php selected($timezone, 'America/New_York'); ?>>New York</option>
                        <option value="America/Los_Angeles" <?php selected($timezone, 'America/Los_Angeles'); ?>>Los Angeles</option>
                        <option value="America/Mexico_City" <?php selected($timezone, 'America/Mexico_City'); ?>>Ciudad de México</option>
                        <option value="America/Buenos_Aires" <?php selected($timezone, 'America/Buenos_Aires'); ?>>Buenos Aires</option>
                    </select>
                    <p class="description">Zona horaria para mostrar las fechas de las sesiones</p>
                </td>
            </tr>
            <tr>
                <th><label for="grace_minutes">Margen de Cortesía</label></th>
                <td>
                    <input type="number" name="grace_minutes" id="grace_minutes" value="<?php echo $grace_minutes; ?>" class="small-text" min="0" step="5"> minutos
                    <p class="description">Tiempo permitido para enviar o modificar apuestas una vez comenzada la sesión (por defecto: 30 minutos). Establece 0 para cierre estricto al inicio de la sesión.</p>
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" name="save_settings" class="button button-primary" value="Guardar Ajustes">
        </p>
    </form>
    
    <div class="f1-card">
        <h2>Shortcodes Disponibles</h2>
        <ul>
            <li><code>[f1_standings]</code> - Muestra la clasificación general</li>
            <li><code>[f1_bet_form]</code> - Formulario para enviar apuestas</li>
            <li><code>[f1_all_bets]</code> - Muestra todas las apuestas de los participantes</li>
            <li><code>[f1_calendar]</code> - Calendario de Grandes Premios con puntos</li>
        </ul>
    </div>
</div>