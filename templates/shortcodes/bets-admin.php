<?php
/* ==========================================
   templates/shortcodes/bets-admin.php
   Shortcode [f1_bets_admin]
   ========================================== */
if (!defined('ABSPATH')) exit;

global $wpdb;
$season         = get_option('f1_stars_season', 2026);
$table_bets     = $wpdb->prefix . 'f1_bets';
$table_sessions = $wpdb->prefix . 'f1_sessions';
$table_parts    = $wpdb->prefix . 'f1_participants';
$table_drivers  = $wpdb->prefix . 'f1_drivers';
$session_type_names = array(
    'qualy'       => 'Clasificación',
    'race'        => 'Carrera',
    'sprintQualy' => 'Clasificación Sprint',
    'sprintRace'  => 'Carrera Sprint',
);

// ── Acciones de borrado (abiertas a todos) ─────────────────────────────────
if (
    isset($_GET['f1_action'], $_GET['f1_bet_id'], $_GET['_wpnonce']) &&
    $_GET['f1_action'] === 'delete_bet' &&
    wp_verify_nonce($_GET['_wpnonce'], 'f1sc_delete_bet_' . intval($_GET['f1_bet_id']))
) {
    $wpdb->delete($table_bets, array('id' => intval($_GET['f1_bet_id'])));
    echo '<div class="f1-admin-notice success">✅ Apuesta eliminada correctamente.</div>';
}

if (
    isset($_GET['f1_action'], $_GET['f1_session_id'], $_GET['_wpnonce']) &&
    $_GET['f1_action'] === 'delete_session_bets' &&
    wp_verify_nonce($_GET['_wpnonce'], 'f1sc_delete_session_bets_' . intval($_GET['f1_session_id']))
) {
    $deleted = $wpdb->delete($table_bets, array('session_id' => intval($_GET['f1_session_id'])));
    echo '<div class="f1-admin-notice success">✅ ' . intval($deleted) . ' apuesta(s) eliminadas de esta sesión.</div>';
}

if (
    isset($_POST['f1_action'], $_POST['_wpnonce']) &&
    $_POST['f1_action'] === 'save_manual_bet' &&
    wp_verify_nonce($_POST['_wpnonce'], 'f1sc_manual_bet')
) {
    $p_nick = sanitize_text_field($_POST['manual_nick']);
    $s_id   = intval($_POST['manual_session_id']);
    $d1     = intval($_POST['manual_driver1']);
    $d2     = intval($_POST['manual_driver2']);
    $d3     = intval($_POST['manual_driver3']);

    if (!$p_nick || !$s_id || !$d1 || !$d2 || !$d3) {
        echo '<div class="f1-admin-notice error">❌ Faltan campos obligatorios para guardar la apuesta.</div>';
    } elseif ($d1 == $d2 || $d1 == $d3 || $d2 == $d3) {
        echo '<div class="f1-admin-notice error">❌ Debes seleccionar 3 pilotos diferentes.</div>';
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
            echo '<div class="f1-admin-notice success">✅ Apuesta de <strong>' . esc_html($p_nick) . '</strong> actualizada correctamente.</div>';
        } else {
            $wpdb->insert($table_bets, array(
                'participant_id' => $part_id,
                'session_id'     => $s_id,
                'driver1_number' => $d1,
                'driver2_number' => $d2,
                'driver3_number' => $d3,
                'created_at'     => current_time('mysql')
            ));
            echo '<div class="f1-admin-notice success">✅ Apuesta de <strong>' . esc_html($p_nick) . '</strong> añadida con éxito.</div>';
        }

        $sess_row = $wpdb->get_row($wpdb->prepare("SELECT is_completed FROM {$table_sessions} WHERE id = %d", $s_id));
        if ($sess_row && $sess_row->is_completed) {
            $plugin_inst = F1_Stars_Plugin::get_instance();
            $plugin_inst->calculate_points_for_session($s_id);
        }
    }
}

// ── Filtros ────────────────────────────────────────────────────────────────
$current_url    = get_permalink();
$filter_session = isset($_GET['f1_filter_session']) ? intval($_GET['f1_filter_session']) : 0;
$filter_nick    = isset($_GET['f1_filter_nick'])    ? sanitize_text_field($_GET['f1_filter_nick']) : '';

// Sesiones con apuestas
$sessions_with_bets = $wpdb->get_results($wpdb->prepare(
    "SELECT
        MIN(s.id)            AS id,
        s.round_number,
        s.session_type,
        s.country,
        MIN(s.session_date)  AS session_date,
        MAX(s.is_completed)  AS is_completed,
        GROUP_CONCAT(s.id)   AS all_ids,
        COUNT(DISTINCT b.id) AS total_bets
     FROM {$table_sessions} s
     INNER JOIN {$table_bets} b ON s.id = b.session_id
     WHERE s.season = %d AND s.session_type NOT LIKE 'practice%'
     GROUP BY s.round_number, s.session_type
     ORDER BY session_date DESC",
    $season
));

$participants = $wpdb->get_results("SELECT DISTINCT nick FROM {$table_parts} ORDER BY nick ASC");
?>

<div class="f1-bets-admin-sc">

    <!-- Modal de advertencia de borrado -->
    <div id="f1-confirm-modal" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.55);align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:12px;padding:32px 28px;max-width:420px;width:90%;box-shadow:0 8px 40px rgba(0,0,0,.3);text-align:center;">
            <div style="font-size:48px;margin-bottom:12px;">⚠️</div>
            <h3 style="margin:0 0 10px;font-size:20px;color:#c0392b;">¿Confirmar borrado?</h3>
            <p id="f1-confirm-msg" style="color:#555;margin:0 0 24px;font-size:15px;"></p>
            <p style="color:#888;font-size:13px;margin:0 0 24px;">Esta acción <strong>no se puede deshacer</strong>.</p>
            <div style="display:flex;gap:12px;justify-content:center;">
                <button onclick="document.getElementById('f1-confirm-modal').style.display='none'" style="padding:10px 24px;border-radius:6px;border:1px solid #ccc;background:#fff;color:#333;font-size:14px;cursor:pointer;">Cancelar</button>
                <a id="f1-confirm-ok" href="#" style="padding:10px 24px;border-radius:6px;background:#e74c3c;color:#fff;font-size:14px;font-weight:700;text-decoration:none;">Sí, borrar</a>
            </div>
        </div>
    </div>
    <script>
    function f1ConfirmDelete(url, msg) {
        document.getElementById('f1-confirm-msg').textContent = msg;
        document.getElementById('f1-confirm-ok').href = url;
        var modal = document.getElementById('f1-confirm-modal');
        modal.style.display = 'flex';
        modal.onclick = function(e){ if(e.target===modal) modal.style.display='none'; };
    }
    </script>

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

    <!-- Añadir Apuesta Manual (Front-end Ajustes) -->
    <div class="f1-bets-filter-bar" style="margin-bottom:24px; background:#fff; border-top:3px solid #e10600; box-shadow:0 2px 6px rgba(0,0,0,.06);">
        <h3 style="margin:0 0 14px; font-size:16px; color:#15151e;">➕ Añadir / Modificar Apuesta Manual (Participantes fuera de plazo)</h3>
        <form method="post" action="<?php echo esc_url($current_url); ?>" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
            <?php wp_nonce_field('f1sc_manual_bet'); ?>
            <input type="hidden" name="f1_action" value="save_manual_bet">

            <div class="f1-filter-field">
                <label>Participante</label>
                <input type="text" name="manual_nick" list="f1sc_participants_list" placeholder="Escribe o elige nick" required style="padding:7px 10px; border:1px solid #ccc; border-radius:5px; font-size:14px; width:160px;">
                <datalist id="f1sc_participants_list">
                    <?php foreach ($participants as $p): ?>
                        <option value="<?php echo esc_attr($p->nick); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>

            <div class="f1-filter-field">
                <label>Sesión</label>
                <select name="manual_session_id" required style="max-width:280px;">
                    <option value="">— Selecciona sesión —</option>
                    <?php foreach ($all_sessions_list as $as): 
                        $s_label = 'GP ' . $as->country . ' – ' . ($session_type_names[$as->session_type] ?? $as->session_type) . ' (' . date('d/m/Y H:i', strtotime($as->session_date)) . ')';
                    ?>
                        <option value="<?php echo $as->id; ?>">
                            <?php echo esc_html($s_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="f1-filter-field">
                <label>1º Posición</label>
                <select name="manual_driver1" required>
                    <option value="">— Piloto 1º —</option>
                    <?php foreach ($all_drivers_list as $d): ?>
                        <option value="<?php echo $d->driver_number; ?>"><?php echo esc_html($d->full_name . ' (#' . $d->driver_number . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="f1-filter-field">
                <label>2º Posición</label>
                <select name="manual_driver2" required>
                    <option value="">— Piloto 2º —</option>
                    <?php foreach ($all_drivers_list as $d): ?>
                        <option value="<?php echo $d->driver_number; ?>"><?php echo esc_html($d->full_name . ' (#' . $d->driver_number . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="f1-filter-field">
                <label>3º Posición</label>
                <select name="manual_driver3" required>
                    <option value="">— Piloto 3º —</option>
                    <?php foreach ($all_drivers_list as $d): ?>
                        <option value="<?php echo $d->driver_number; ?>"><?php echo esc_html($d->full_name . ' (#' . $d->driver_number . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="f1-filter-actions">
                <button type="submit" class="f1-btn f1-btn-primary">💾 Guardar Apuesta</button>
            </div>
        </form>
    </div>

    <!-- Filtros -->
    <div class="f1-bets-filter-bar">
        <form method="get" action="<?php echo esc_url($current_url); ?>">
            <div class="f1-filter-field">
                <label>Sesión</label>
                <select name="f1_filter_session">
                    <option value="0">— Todas las sesiones —</option>
                    <?php foreach ($sessions_with_bets as $s):
                        $label = 'GP ' . $s->country . ' – ' . ($session_type_names[$s->session_type] ?? $s->session_type)
                               . ' (' . date('d/m/Y', strtotime($s->session_date)) . ')'
                               . ' · ' . $s->total_bets . ' apuesta(s)';
                    ?>
                        <option value="<?php echo $s->id; ?>" <?php selected($filter_session, $s->id); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="f1-filter-field">
                <label>Participante</label>
                <select name="f1_filter_nick">
                    <option value="">— Todos —</option>
                    <?php foreach ($participants as $p): ?>
                        <option value="<?php echo esc_attr($p->nick); ?>" <?php selected($filter_nick, $p->nick); ?>>
                            <?php echo esc_html($p->nick); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="f1-filter-actions">
                <button type="submit" class="f1-btn f1-btn-primary">🔍 Filtrar</button>
                <a href="<?php echo esc_url($current_url); ?>" class="f1-btn f1-btn-secondary">✕ Limpiar</a>
            </div>
        </form>
    </div>

    <?php
    // ── Query de apuestas ──────────────────────────────────────────────────
    $where_parts  = array("s.season = {$season}");
    $query_params = array();

    if ($filter_session) {
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

    $where_sql  = 'WHERE ' . implode(' AND ', $where_parts);
    $bets_query = "
        SELECT
            b.id            AS bet_id,
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
            d1.full_name    AS driver1_name,
            d1.headshot_url AS driver1_img,
            d1.team_colour  AS driver1_colour,
            d2.full_name    AS driver2_name,
            d2.headshot_url AS driver2_img,
            d2.team_colour  AS driver2_colour,
            d3.full_name    AS driver3_name,
            d3.headshot_url AS driver3_img,
            d3.team_colour  AS driver3_colour
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

    // Agrupar por sesión
    $by_session = array();
    foreach ($bets as $bet) {
        $key = $bet->round_number . '_' . $bet->session_type;
        if (!isset($by_session[$key])) {
            $by_session[$key]['session'] = $bet;
        }
        $by_session[$key]['bets'][] = $bet;
    }
    ?>

    <?php if (empty($by_session)): ?>
    <div class="f1-bets-empty">No se encontraron apuestas con los filtros seleccionados.</div>
    <?php endif; ?>

    <?php foreach ($by_session as $group):
        $s         = $group['session'];
        $type_name = $session_type_names[$s->session_type] ?? $s->session_type;
        $date_fmt  = date('d/m/Y H:i', strtotime($s->session_date));
        $completed = $s->is_completed
            ? '<span class="f1-status-badge completed">✅ Completada</span>'
            : '<span class="f1-status-badge pending">⏳ Pendiente</span>';

        // URLs de borrado de sesión (solo admin)
        $url_delete_all = '';
        if ($is_admin) {
            $nonce_session  = wp_create_nonce('f1sc_delete_session_bets_' . $s->session_id);
            $url_delete_all = add_query_arg(array(
                'f1_action'          => 'delete_session_bets',
                'f1_session_id'      => $s->session_id,
                '_wpnonce'           => $nonce_session,
                'f1_filter_session'  => $filter_session,
                'f1_filter_nick'     => $filter_nick,
            ), $current_url);
        }
    ?>

    <div class="f1-bets-session-card">
        <div class="f1-session-header">
            <div class="f1-session-info">
                <h3>🏁 GP <?php echo esc_html($s->country); ?> — <?php echo esc_html($type_name); ?></h3>
                <p class="f1-session-meta"><?php echo $date_fmt; ?> &nbsp;|&nbsp; <?php echo $completed; ?></p>
            </div>
        </div>

        <div class="f1-bets-table-wrap">
            <table class="f1-bets-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Participante</th>
                        <th>1º</th>
                        <th>2º</th>
                        <th>3º</th>
                        <th>Puntos</th>
                        <th>Fecha apuesta</th>
                        <?php if ($is_admin): ?><th>Acción</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($group['bets'] as $bet):
                    $nonce_bet      = wp_create_nonce('f1sc_delete_bet_' . $bet->bet_id);
                    $url_delete_bet = add_query_arg(array(
                        'f1_action'         => 'delete_bet',
                        'f1_bet_id'         => $bet->bet_id,
                        '_wpnonce'          => $nonce_bet,
                        'f1_filter_session' => $filter_session,
                        'f1_filter_nick'    => $filter_nick,
                    ), $current_url);

                    $drivers = array(
                        array('name' => $bet->driver1_name, 'num' => $bet->driver1_number, 'img' => $bet->driver1_img, 'colour' => $bet->driver1_colour),
                        array('name' => $bet->driver2_name, 'num' => $bet->driver2_number, 'img' => $bet->driver2_img, 'colour' => $bet->driver2_colour),
                        array('name' => $bet->driver3_name, 'num' => $bet->driver3_number, 'img' => $bet->driver3_img, 'colour' => $bet->driver3_colour),
                    );
                ?>
                <tr>
                    <td class="f1-bet-id"><?php echo $bet->bet_id; ?></td>
                    <td class="f1-bet-nick"><strong><?php echo esc_html($bet->nick); ?></strong></td>
                    <?php foreach ($drivers as $d):
                        $label = $d['name'] ? end(explode(' ', trim($d['name']))) : '#' . $d['num'];
                        $colour = $d['colour'] ? '#' . ltrim($d['colour'], '#') : '#666';
                    ?>
                    <td class="f1-bet-driver">
                        <?php if (!empty($d['img'])): ?>
                        <span class="f1-driver-inline" style="border-color:<?php echo esc_attr($colour); ?>">
                            <img src="<?php echo esc_url($d['img']); ?>" alt="<?php echo esc_attr($label); ?>" />
                        </span>
                        <?php endif; ?>
                        <?php echo esc_html($label); ?>
                    </td>
                    <?php endforeach; ?>
                    <td class="f1-bet-points">
                        <?php if ($bet->points !== null): ?>
                            <strong class="f1-pts"><?php echo intval($bet->points); ?> pts</strong>
                        <?php else: ?>
                            <span class="f1-pts-empty">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="f1-bet-date"><?php echo date('d/m/Y H:i', strtotime($bet->created_at)); ?></td>
                    <td>
                        <?php $msg_bet = '¿Eliminar la apuesta de ' . esc_js($bet->nick) . '? Esta acción no se puede deshacer.'; ?>
                        <button class="f1-btn f1-btn-danger f1-btn-sm"
                            onclick="f1ConfirmDelete('<?php echo esc_url($url_delete_bet); ?>', '<?php echo $msg_bet; ?>')">
                            🗑️ Borrar
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php endforeach; ?>
</div>

<style>
.f1-bets-admin-sc { max-width: 1100px; margin: 0 auto; font-family: inherit; }

.f1-admin-badge {
    background: #fff3cd; border: 1px solid #ffc107; color: #856404;
    padding: 8px 14px; border-radius: 6px; font-size: 13px; margin-bottom: 16px;
    display: inline-block;
}

.f1-admin-notice {
    padding: 10px 16px; border-radius: 6px; margin-bottom: 16px; font-weight: 600;
}
.f1-admin-notice.success { background: #d5f5e3; border: 1px solid #27ae60; color: #1e7e34; }
.f1-admin-notice.error   { background: #fadbd8; border: 1px solid #e74c3c; color: #c0392b; }

.f1-bets-filter-bar {
    background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px;
    padding: 16px 20px; margin-bottom: 24px;
}
.f1-bets-filter-bar form { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }

.f1-filter-field { display: flex; flex-direction: column; gap: 4px; }
.f1-filter-field label { font-weight: 600; font-size: 13px; color: #555; }
.f1-filter-field select {
    min-width: 220px; padding: 7px 10px; border: 1px solid #ccc;
    border-radius: 5px; font-size: 14px; background: #fff;
}
.f1-filter-actions { display: flex; gap: 8px; align-items: flex-end; }

.f1-btn { display: inline-block; padding: 7px 16px; border-radius: 5px; font-size: 13px;
          font-weight: 600; cursor: pointer; text-decoration: none; border: none; }
.f1-btn-primary  { background: #e10600; color: #fff; }
.f1-btn-primary:hover { background: #c10500; color: #fff; }
.f1-btn-secondary { background: #6c757d; color: #fff; }
.f1-btn-secondary:hover { background: #5a6268; color: #fff; }
.f1-btn-danger   { background: #e74c3c; color: #fff; border-color: #c0392b; }
.f1-btn-danger:hover { background: #c0392b; color: #fff; }
.f1-btn-sm { padding: 4px 10px; font-size: 12px; }

.f1-bets-empty {
    text-align: center; color: #888; padding: 40px;
    background: #f8f9fa; border-radius: 8px; font-size: 15px;
}

.f1-bets-session-card {
    background: #fff; border: 1px solid #dee2e6; border-radius: 10px;
    margin-bottom: 24px; overflow: hidden;
    box-shadow: 0 2px 6px rgba(0,0,0,.06);
}

.f1-session-header {
    display: flex; justify-content: space-between; align-items: center;
    flex-wrap: wrap; gap: 10px;
    padding: 16px 20px; border-bottom: 2px solid #e10600;
    background: #fafafa;
}
.f1-session-info h3 { margin: 0; font-size: 17px; color: #15151e; }
.f1-session-meta { margin: 4px 0 0; font-size: 13px; color: #666; }

.f1-status-badge { padding: 3px 8px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.f1-status-badge.completed { background: #d5f5e3; color: #1e7e34; }
.f1-status-badge.pending   { background: #fff3cd; color: #856404; }

.f1-bets-table-wrap { overflow-x: auto; }

.f1-bets-table { width: 100%; border-collapse: collapse; font-size: 14px; }
.f1-bets-table thead { background: #15151e; color: #fff; }
.f1-bets-table th, .f1-bets-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #eee; }
.f1-bets-table tbody tr:hover { background: #f8f9fa; }

.f1-bet-id   { color: #aaa; font-size: 12px; width: 40px; }
.f1-bet-date { font-size: 12px; color: #888; }
.f1-pts      { color: #27ae60; font-size: 15px; }
.f1-pts-empty { color: #aaa; }

.f1-bet-driver { white-space: nowrap; }

/* Headshot inline (reutiliza el estilo global si existe) */
.f1-bets-admin-sc .f1-driver-inline {
    display: inline-flex; align-items: center; justify-content: center;
    width: 26px; height: 26px; border-radius: 50%; border: 2px solid #666;
    overflow: hidden; vertical-align: middle; margin-right: 5px; background: #15151e;
}
.f1-bets-admin-sc .f1-driver-inline img { width: 100%; height: 100%; object-fit: cover; }

@media (max-width: 640px) {
    .f1-bets-filter-bar form { flex-direction: column; }
    .f1-filter-field select  { min-width: 100%; }
    .f1-bets-table th, .f1-bets-table td { padding: 7px 8px; font-size: 13px; }
}
</style>
