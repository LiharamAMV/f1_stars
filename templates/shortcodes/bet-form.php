<!-- ========================================== -->
<!-- templates/shortcodes/bet-form.php -->
<!-- ========================================== -->
<?php if (!defined('ABSPATH')) exit; ?>

<div class="f1-stars-bet-form">
    <h2>🎯 Enviar Apuesta</h2>
    
    <form id="f1-bet-form">
        <div class="form-group">
            <label for="nick">Tu Nick:</label>
            <?php
            global $wpdb;
            $participants = $wpdb->get_results(
                "SELECT nick FROM {$wpdb->prefix}f1_participants ORDER BY nick ASC"
            );
            ?>
            <?php if (!empty($participants)): ?>
                <select id="nick" name="nick" required>
                    <option value="">-- Selecciona tu nick --</option>
                    <?php foreach ($participants as $p): ?>
                        <option value="<?php echo esc_attr($p->nick); ?>">
                            <?php echo esc_html($p->nick); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <input type="text" id="nick" name="nick" placeholder="Escribe tu nick" required>
                <p class="description" style="color:#888;font-size:12px;margin-top:4px;">Aún no hay participantes registrados.</p>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="session">Selecciona la Sesión:</label>
            <select id="session" name="session" required>
                <option value="">-- Selecciona una sesión --</option>
                <?php
                $season = get_option('f1_stars_season', 2026);
                $grace_minutes = intval(get_option('f1_stars_grace_minutes', 30));
                $is_admin = current_user_can('manage_options');
                $admin_flag = $is_admin ? 1 : 0;
                $sessions = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}f1_sessions 
                     WHERE season = %d 
                     AND is_completed = 0
                     AND (DATE_ADD(session_date, INTERVAL %d MINUTE) > NOW() OR %d = 1) 
                     AND session_type IN ('qualy', 'race', 'sprintQualy', 'sprintRace')
                     ORDER BY session_date ASC",
                    $season,
                    $grace_minutes,
                    $admin_flag
                ));
                
                $session_names = array(
                    'qualy'       => 'Clasificación',
                    'race'        => 'Carrera',
                    'sprintQualy' => 'Clasificación Sprint',
                    'sprintRace'  => 'Carrera Sprint'
                );
                
                if (empty($sessions)): ?>
                    <option value="" disabled>-- No hay sesiones disponibles para apostar --</option>
                <?php else:
                    foreach ($sessions as $s):
                        if (!isset($session_names[$s->session_type])) continue;
                        $session_name = $session_names[$s->session_type];
                        $date = date('d/m/Y H:i', strtotime($s->session_date));
                        $session_time  = strtotime($s->session_date);
                        $is_started    = ($session_time <= time());
                        $is_past_grace = (time() > ($session_time + ($grace_minutes * 60)));
                        
                        $grace_badge = '';
                        if ($is_started && !$is_past_grace) {
                            $grace_badge = ' ⏱️ [En curso - Cortesía activa]';
                        } elseif ($is_past_grace && $is_admin) {
                            $grace_badge = ' 🛡️ [Admin: Fuera de plazo]';
                        }
                        $display_country = $s->country;
                        if (!empty($s->circuit_name) && stripos($s->circuit_name, 'Sepang') !== false && stripos($display_country, 'Sepang') === false) {
                            $display_country .= ' (Sepang)';
                        }
                    ?>
                        <option value="<?php echo $s->id; ?>">
                            <?php echo "GP {$display_country} - {$session_name} ({$date}){$grace_badge}"; ?>
                        </option>
                    <?php endforeach;
                endif; ?>
            </select>
        </div>
        
        <?php
        $plugin = F1_Stars_Plugin::get_instance();
        $drivers_list = $plugin->get_drivers_for_season($season);
        $sorted_drivers = $drivers_list;
        usort($sorted_drivers, function($a, $b) {
            $a_name = is_object($a) ? $a->full_name : $a['full_name'];
            $b_name = is_object($b) ? $b->full_name : $b['full_name'];
            $lastA = substr(strrchr(trim($a_name), ' '), 1) ?: $a_name;
            $lastB = substr(strrchr(trim($b_name), ' '), 1) ?: $b_name;
            return strcasecmp($lastA, $lastB);
        });
        ?>
        <div class="form-group">
            <label>Tu Podio (3 pilotos):</label>
            <div class="drivers-selection">
                <?php 
                $labels = array(1 => '1º Posición', 2 => '2º Posición', 3 => '3º Posición');
                for ($pos = 1; $pos <= 3; $pos++): 
                ?>
                <div class="f1-driver-select-wrap">
                    <select id="driver<?php echo $pos; ?>" name="driver<?php echo $pos; ?>" required>
                        <option value=""><?php echo $labels[$pos]; ?></option>
                        <?php foreach ($sorted_drivers as $d):
                            $d_num    = is_object($d) ? $d->driver_number : $d['driver_number'];
                            $d_name   = is_object($d) ? $d->full_name : $d['full_name'];
                            $d_team   = is_object($d) ? ($d->team ?? '') : ($d['team'] ?? '');
                            $d_colour = is_object($d) ? ($d->team_colour ?? '') : ($d['team_colour'] ?? '');
                            $d_img    = is_object($d) ? ($d->headshot_url ?? '') : ($d['headshot_url'] ?? '');
                            $lastName = substr(strrchr(trim($d_name), ' '), 1) ?: $d_name;
                        ?>
                            <option value="<?php echo esc_attr($d_num); ?>"
                                    data-headshot="<?php echo esc_attr($d_img); ?>"
                                    data-colour="<?php echo esc_attr($d_colour); ?>"
                                    data-team="<?php echo esc_attr($d_team); ?>"
                                    data-fullname="<?php echo esc_attr($d_name); ?>">
                                <?php echo esc_html($lastName); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="f1-driver-preview" id="preview-driver<?php echo $pos; ?>"></div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
        
        <button type="submit" class="f1-button">Enviar Apuesta</button>
        <div id="bet-result"></div>
    </form>
</div>
