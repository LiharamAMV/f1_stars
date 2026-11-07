<!-- ========================================== -->
<!-- templates/admin/bets.php -->
<!-- ========================================== -->
<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap f1-stars-admin">
    <h1>🎯 Gestión de Apuestas</h1>

    <?php
    global $wpdb;
    $season          = get_option('f1_stars_season', 2026);
    $table_bets      = $wpdb->prefix . 'f1_bets';
    $table_sessions  = $wpdb->prefix . 'f1_sessions';
    $table_parts     = $wpdb->prefix . 'f1_participants';
    $table_drivers   = $wpdb->prefix . 'f1_drivers';

    $session_type_names = array(
        'qualy'       => 'Clasificación',
        'race'        => 'Carrera',
        'sprintQualy' => 'Clasificación Sprint',
        'sprintRace'  => 'Carrera Sprint',
    );

    // ── Acción: borrar apuesta individual ──────────────────────────────────
    if (
        isset($_GET['action'], $_GET['bet_id'], $_GET['_wpnonce']) &&
        $_GET['action'] === 'delete_bet' &&
        wp_verify_nonce($_GET['_wpnonce'], 'f1_delete_bet_' . intval($_GET['bet_id']))
    ) {
        $wpdb->delete($table_bets, array('id' => intval($_GET['bet_id'])));
        echo '<div class="notice notice-success is-dismissible"><p>✅ Apuesta eliminada correctamente.</p></div>';
    }

    // ── Acción: borrar todas las apuestas de una sesión ────────────────────
    if (
        isset($_GET['action'], $_GET['session_id'], $_GET['_wpnonce']) &&
        $_GET['action'] === 'delete_session_bets' &&
        wp_verify_nonce($_GET['_wpnonce'], 'f1_delete_session_bets_' . intval($_GET['session_id']))
    ) {
        $deleted = $wpdb->delete($table_bets, array('session_id' => intval($_GET['session_id'])));
        echo '<div class="notice notice-success is-dismissible"><p>✅ ' . intval($deleted) . ' apuesta(s) eliminadas de esta sesión.</p></div>';
    }

    // ── Acción: añadir / modificar apuesta manual (para apuestas fuera de plazo) ──
    if (
        isset($_POST['f1_action'], $_POST['_wpnonce']) &&
        $_POST['f1_action'] === 'save_manual_bet' &&
        wp_verify_nonce($_POST['_wpnonce'], 'f1_admin_manual_bet')
    ) {
        $p_nick = sanitize_text_field($_POST['manual_nick']);
        $s_id   = intval($_POST['manual_session_id']);
        $d1     = intval($_POST['manual_driver1']);
        $d2     = intval($_POST['manual_driver2']);
        $d3     = intval($_POST['manual_driver3']);

        if (!$p_nick || !$s_id || !$d1 || !$d2 || !$d3) {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Faltan campos obligatorios para guardar la apuesta.</p></div>';
        } elseif ($d1 == $d2 || $d1 == $d3 || $d2 == $d3) {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Debes seleccionar 3 pilotos diferentes.</p></div>';
        } else {
            $part = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$table_parts} WHERE nick = %s", $p_nick));
            if (!$part) {
                $wpdb->insert($table_parts, array('nick' => $p_nick));
                $part_id = $wpdb->insert_id;
            } else {
                $part_id = $part->id;
            }

            $bet_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table_bets} WHERE participant_id = %d AND session_id = %d",
                $part_id, $s_id
            ));

            if ($bet_id) {
                $wpdb->update($table_bets, array(
                    'driver1_number' => $d1,
                    'driver2_number' => $d2,
                    'driver3_number' => $d3
                ), array('id' => $bet_id));
                echo '<div class="notice notice-success is-dismissible"><p>✅ Apuesta de <strong>' . esc_html($p_nick) . '</strong> actualizada correctamente.</p></div>';
            } else {
                $wpdb->insert($table_bets, array(
                    'participant_id' => $part_id,
                    'session_id'     => $s_id,
                    'driver1_number' => $d1,
                    'driver2_number' => $d2,
                    'driver3_number' => $d3,
                    'created_at'     => current_time('mysql')
                ));
                echo '<div class="notice notice-success is-dismissible"><p>✅ Apuesta de <strong>' . esc_html($p_nick) . '</strong> añadida con éxito.</p></div>';
            }

            $sess_row = $wpdb->get_row($wpdb->prepare("SELECT is_completed FROM {$table_sessions} WHERE id = %d", $s_id));
            if ($sess_row && $sess_row->is_completed) {
                $plugin_inst = F1_Stars_Plugin::get_instance();
                $plugin_inst->calculate_points_for_session($s_id);
            }
        }
    }

    // ── Filtros ────────────────────────────────────────────────────────────
    $filter_session = isset($_GET['filter_session']) ? intval($_GET['filter_session']) : 0;
    $filter_nick    = isset($_GET['filter_nick'])    ? sanitize_text_field($_GET['filter_nick']) : '';

    // Sesiones con apuestas (agrupadas para evitar duplicados)
    $sessions_with_bets = $wpdb->get_results($wpdb->prepare(
        "SELECT
            MIN(s.id)           AS id,
            s.round_number,
            s.session_type,
            s.country,
            MIN(s.session_date) AS session_date,
            MAX(s.is_completed) AS is_completed,
            GROUP_CONCAT(s.id)  AS all_ids,
            COUNT(DISTINCT b.id) AS total_bets
         FROM {$table_sessions} s
         INNER JOIN {$table_bets} b ON s.id = b.session_id
         WHERE s.season = %d AND s.session_type NOT LIKE 'practice%'
         GROUP BY s.round_number, s.session_type
         ORDER BY session_date DESC",
        $season
    ));

    // Participantes
    $participants = $wpdb->get_results("SELECT DISTINCT nick FROM {$table_parts} ORDER BY nick ASC");
    ?>

    <?php
    $all_drivers_list = $wpdb->get_results($wpdb->prepare(
        "SELECT driver_number, full_name, team FROM {$table_drivers} WHERE season = %d ORDER BY full_name ASC",
        $season
    ));
    $all_sessions_list = $wpdb->get_results($wpdb->prepare(
        "SELECT id, country, session_type, session_date FROM {$table_sessions} 
         WHERE season = %d AND session_type IN ('qualy', 'race', 'sprintQualy', 'sprintRace')
         ORDER BY session_date DESC",
        $season
    ));
    ?>

    <!-- Añadir Apuesta Manual -->
    <div class="f1-card" style="margin-bottom:20px; background:#fdfdfd; border-left:4px solid #e74c3c;">
        <h3 style="margin-top:0;">➕ Añadir / Modificar Apuesta Manual (Para participantes fuera de plazo)</h3>
        <form method="post" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
            <?php wp_nonce_field('f1_admin_manual_bet'); ?>
            <input type="hidden" name="f1_action" value="save_manual_bet">

            <div>
                <label style="display:block; font-weight:600; margin-bottom:4px;">Participante</label>
                <input type="text" name="manual_nick" list="participants_list" placeholder="Escribe o elige nick" required style="padding:5px 8px; width:160px;">
                <datalist id="participants_list">
                    <?php foreach ($participants as $p): ?>
                        <option value="<?php echo esc_attr($p->nick); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>

            <div>
                <label style="display:block; font-weight:600; margin-bottom:4px;">Sesión</label>
                <select name="manual_session_id" required style="max-width:280px; padding:5px 8px;">
                    <option value="">— Selecciona sesión —</option>
                    <?php foreach ($all_sessions_list as $as): 
                        $s_label = 'GP ' . $as->country . ' - ' . ($session_type_names[$as->session_type] ?? $as->session_type) . ' (' . date('d/m/Y H:i', strtotime($as->session_date)) . ')';
                    ?>
                        <option value="<?php echo $as->id; ?>">
                            <?php echo esc_html($s_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="display:block; font-weight:600; margin-bottom:4px;">1º Posición</label>
                <select name="manual_driver1" required style="padding:5px 8px;">
                    <option value="">— Piloto 1º —</option>
                    <?php foreach ($all_drivers_list as $d): ?>
                        <option value="<?php echo $d->driver_number; ?>"><?php echo esc_html($d->full_name . ' (#' . $d->driver_number . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="display:block; font-weight:600; margin-bottom:4px;">2º Posición</label>
                <select name="manual_driver2" required style="padding:5px 8px;">
                    <option value="">— Piloto 2º —</option>
                    <?php foreach ($all_drivers_list as $d): ?>
                        <option value="<?php echo $d->driver_number; ?>"><?php echo esc_html($d->full_name . ' (#' . $d->driver_number . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="display:block; font-weight:600; margin-bottom:4px;">3º Posición</label>
                <select name="manual_driver3" required style="padding:5px 8px;">
                    <option value="">— Piloto 3º —</option>
                    <?php foreach ($all_drivers_list as $d): ?>
                        <option value="<?php echo $d->driver_number; ?>"><?php echo esc_html($d->full_name . ' (#' . $d->driver_number . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <button type="submit" class="button button-primary" style="background:#e74c3c; border-color:#c0392b;">💾 Guardar Apuesta</button>
            </div>
        </form>
    </div>

    <!-- Filtros -->
    <div class="f1-card" style="margin-bottom:20px;">
        <form method="get" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
            <input type="hidden" name="page" value="f1-stars-bets">

            <div>
                <label style="display:block; font-weight:600; margin-bottom:4px;">Sesión</label>
                <select name="filter_session" style="min-width:260px; padding:5px 8px;">
                    <option value="0">— Todas las sesiones —</option>
                    <?php foreach ($sessions_with_bets as $s):
                        $label = 'GP ' . $s->country . ' - ' . ($session_type_names[$s->session_type] ?? $s->session_type)
                               . ' (' . date('d/m/Y', strtotime($s->session_date)) . ')'
                               . ' · ' . $s->total_bets . ' apuesta(s)';
                    ?>
                        <option value="<?php echo $s->id; ?>" <?php selected($filter_session, $s->id); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="display:block; font-weight:600; margin-bottom:4px;">Participante</label>
                <select name="filter_nick" style="min-width:180px; padding:5px 8px;">
                    <option value="">— Todos —</option>
                    <?php foreach ($participants as $p): ?>
                        <option value="<?php echo esc_attr($p->nick); ?>" <?php selected($filter_nick, $p->nick); ?>>
                            <?php echo esc_html($p->nick); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <button type="submit" class="button button-primary">🔍 Filtrar</button>
                <a href="?page=f1-stars-bets" class="button" style="margin-left:4px;">✕ Limpiar</a>
            </div>
        </form>
    </div>

    <?php
    // ── Construir la query de apuestas con filtros ─────────────────────────
    $where_parts  = array("s.season = {$season}");
    $query_params = array();

    if ($filter_session) {
        // Incluir todos los IDs del grupo (duplicados)
        $group = null;
        foreach ($sessions_with_bets as $sw) {
            if ($sw->id == $filter_session) { $group = $sw; break; }
        }
        if ($group) {
            $ids = implode(',', array_map('intval', explode(',', $group->all_ids)));
            $where_parts[] = "b.session_id IN ($ids)";
        }
    }

    if ($filter_nick !== '') {
        $where_parts[]  = "p.nick = %s";
        $query_params[] = $filter_nick;
    }

    $where_sql = 'WHERE ' . implode(' AND ', $where_parts);

    $bets_query = "
        SELECT
            b.id           AS bet_id,
            b.session_id,
            b.driver1_number,
            b.driver2_number,
            b.driver3_number,
            b.points,
            b.created_at,
            p.nick,
            s.country,
            s.session_type,
            s.session_date,
            s.is_completed,
            s.round_number,
            d1.full_name   AS driver1_name,
            d2.full_name   AS driver2_name,
            d3.full_name   AS driver3_name
        FROM {$table_bets} b
        JOIN {$table_parts}    p  ON b.participant_id = p.id
        JOIN {$table_sessions} s  ON b.session_id     = s.id
        LEFT JOIN {$table_drivers} d1 ON b.driver1_number = d1.driver_number AND d1.season = {$season}
        LEFT JOIN {$table_drivers} d2 ON b.driver2_number = d2.driver_number AND d2.season = {$season}
        LEFT JOIN {$table_drivers} d3 ON b.driver3_number = d3.driver_number AND d3.season = {$season}
        {$where_sql}
        ORDER BY s.session_date DESC, p.nick ASC
    ";

    $bets = empty($query_params)
        ? $wpdb->get_results($bets_query)
        : $wpdb->get_results($wpdb->prepare($bets_query, ...$query_params));

    // Agrupar por sesión para mostrar secciones
    $by_session = array();
    foreach ($bets as $bet) {
        $key = $bet->round_number . '_' . $bet->session_type;
        $by_session[$key]['session'] = $bet;
        $by_session[$key]['bets'][]  = $bet;
    }
    ?>

    <?php if (empty($by_session)): ?>
        <div class="f1-card">
            <p style="color:#888; text-align:center; padding:20px;">No se encontraron apuestas con los filtros seleccionados.</p>
        </div>
    <?php endif; ?>

    <?php foreach ($by_session as $group):
        $s         = $group['session'];
        $type_name = $session_type_names[$s->session_type] ?? $s->session_type;
        $date_fmt  = date('d/m/Y H:i', strtotime($s->session_date));
        $completed = $s->is_completed ? '<span style="color:#27ae60;">✅ Completada</span>' : '<span style="color:#e67e22;">🟡 Pendiente</span>';

        // nonce para borrar toda la sesión
        $nonce_session = wp_create_nonce('f1_delete_session_bets_' . $s->session_id);
        $url_delete_all = add_query_arg(array(
            'page'       => 'f1-stars-bets',
            'action'     => 'delete_session_bets',
            'session_id' => $s->session_id,
            '_wpnonce'   => $nonce_session,
            'filter_session' => $filter_session,
            'filter_nick'    => $filter_nick,
        ), admin_url('admin.php'));
    ?>

    <div class="f1-card" style="margin-bottom:24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; border-bottom:2px solid #e74c3c; padding-bottom:10px; margin-bottom:16px;">
            <div>
                <h2 style="margin:0;">🏁 GP <?php echo esc_html($s->country); ?> — <?php echo esc_html($type_name); ?></h2>
                <p style="margin:4px 0 0; color:#666; font-size:13px;"><?php echo $date_fmt; ?> &nbsp;|&nbsp; <?php echo $completed; ?></p>
            </div>
            <a href="<?php echo esc_url($url_delete_all); ?>"
               class="button"
               style="background:#e74c3c;color:#fff;border-color:#c0392b;"
               onclick="return confirm('¿Borrar TODAS las apuestas de esta sesión? No se puede deshacer.')">
               🗑️ Borrar todas (<?php echo count($group['bets']); ?>)
            </a>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Participante</th>
                    <th>1º</th>
                    <th>2º</th>
                    <th>3º</th>
                    <th style="width:80px;">Puntos</th>
                    <th style="width:130px;">Fecha apuesta</th>
                    <th style="width:90px;">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($group['bets'] as $bet):
                    $nonce_bet  = wp_create_nonce('f1_delete_bet_' . $bet->bet_id);
                    $url_delete = add_query_arg(array(
                        'page'     => 'f1-stars-bets',
                        'action'   => 'delete_bet',
                        'bet_id'   => $bet->bet_id,
                        '_wpnonce' => $nonce_bet,
                        'filter_session' => $filter_session,
                        'filter_nick'    => $filter_nick,
                    ), admin_url('admin.php'));
                ?>
                <tr>
                    <td style="color:#999;"><?php echo $bet->bet_id; ?></td>
                    <td><strong><?php echo esc_html($bet->nick); ?></strong></td>
                    <td><?php $n1 = $bet->driver1_name ?: '#' . $bet->driver1_number; $p1 = explode(' ', trim($n1)); echo esc_html(end($p1)); ?></td>
                    <td><?php $n2 = $bet->driver2_name ?: '#' . $bet->driver2_number; $p2 = explode(' ', trim($n2)); echo esc_html(end($p2)); ?></td>
                    <td><?php $n3 = $bet->driver3_name ?: '#' . $bet->driver3_number; $p3 = explode(' ', trim($n3)); echo esc_html(end($p3)); ?></td>
                    <td>
                        <?php if ($bet->points !== null): ?>
                            <strong style="color:#27ae60;"><?php echo intval($bet->points); ?> pts</strong>
                        <?php else: ?>
                            <span style="color:#aaa;">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px; color:#666;"><?php echo date('d/m/Y H:i', strtotime($bet->created_at)); ?></td>
                    <td>
                        <a href="<?php echo esc_url($url_delete); ?>"
                           class="button button-small"
                           style="color:#e74c3c; border-color:#e74c3c;"
                           onclick="return confirm('¿Eliminar la apuesta de <?php echo esc_js($bet->nick); ?>?')">
                           🗑️ Borrar
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php endforeach; ?>
</div>
