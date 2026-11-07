<?php
/**
 * Plugin Name: F1 Stars
 * Plugin URI: https://f1stars.com
 * Description: Sistema de apuestas para la temporada de Fórmula 1
 * Version: 1.4.3
 * Author: Iván Morales
 * Author URI: https://f1stars.com
 * License: GPL v2 or later
 * Text Domain: f1-stars
 * Network: true
 */

if (!defined('ABSPATH')) exit;

define('F1_STARS_VERSION', '1.4.3');
define('F1_STARS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('F1_STARS_PLUGIN_URL', plugin_dir_url(__FILE__));

class F1_Stars_Plugin {
    private static $instance = null;
    private $season = 2026;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_f1_load_races', array($this, 'ajax_load_races'));
        add_action('wp_ajax_f1_load_results', array($this, 'ajax_load_results'));
        add_action('wp_ajax_nopriv_f1_load_results', array($this, 'ajax_load_results'));
        add_action('wp_ajax_f1_submit_bet', array($this, 'ajax_submit_bet'));
        add_action('wp_ajax_nopriv_f1_submit_bet', array($this, 'ajax_submit_bet'));
        add_action('wp_ajax_f1_save_participant', array($this, 'ajax_save_participant'));
        add_action('wp_ajax_f1_delete_participant', array($this, 'ajax_delete_participant'));
        add_action('wp_ajax_f1_save_session', array($this, 'ajax_save_session'));
        add_action('wp_ajax_f1_delete_session', array($this, 'ajax_delete_session'));
        add_action('wp_ajax_f1_save_results', array($this, 'ajax_save_results'));
        add_action('wp_ajax_f1_calculate_points', array($this, 'ajax_calculate_points'));
        add_action('wp_ajax_f1_sync_openf1_sessions', array($this, 'ajax_sync_openf1_sessions'));
        add_action('wp_ajax_f1_sync_openf1_results', array($this, 'ajax_sync_openf1_results'));
        add_action('wp_ajax_f1_admin_save_bet', array($this, 'ajax_admin_save_bet'));
        add_action('wp_ajax_f1_chart_data', array($this, 'ajax_chart_data'));
        add_action('wp_ajax_nopriv_f1_chart_data', array($this, 'ajax_chart_data'));
        add_action('wp_ajax_f1_get_drivers', array($this, 'ajax_get_drivers'));
        add_action('wp_ajax_nopriv_f1_get_drivers', array($this, 'ajax_get_drivers'));
        add_action('wp_ajax_f1_test_api', array($this, 'ajax_test_api'));
        add_action('wp_ajax_f1_create_tables', array($this, 'ajax_create_tables'));
        add_action('wp_ajax_f1_reset_session', array($this, 'ajax_reset_session'));
        add_action('wp_ajax_f1_dedup_sessions', array($this, 'ajax_dedup_sessions'));
        add_action('wp_ajax_f1_delete_bets', array($this, 'ajax_delete_bets'));
        add_action('wp_ajax_f1_delete_season_data', array($this, 'ajax_delete_season_data'));

        // Shortcodes
        add_shortcode('f1_standings',   array($this, 'shortcode_standings'));
        add_shortcode('f1_bet_form',     array($this, 'shortcode_bet_form'));
        add_shortcode('f1_all_bets',     array($this, 'shortcode_all_bets'));
        add_shortcode('f1_calendar',     array($this, 'shortcode_calendar'));
        add_shortcode('f1_chart',        array($this, 'shortcode_chart'));
        add_shortcode('f1_bets_admin',   array($this, 'shortcode_bets_admin'));

        // Excluir páginas con shortcodes de F1 Stars de la caché
        add_action('wp', array($this, 'maybe_disable_cache'));
        // Limpiar caché al enviar apuesta (via AJAX completado)
        add_action('wp_ajax_f1_submit_bet',        array($this, 'purge_cache_after'), 99);
        add_action('wp_ajax_nopriv_f1_submit_bet', array($this, 'purge_cache_after'), 99);

        // Auto-limpieza de carpetas duplicadas de instalaciones anteriores
        add_action('admin_init', array($this, 'auto_cleanup_duplicate_plugins'));
    }

    public function auto_cleanup_duplicate_plugins() {
        if (!current_user_can('activate_plugins')) return;
        
        $plugins_dir = WP_PLUGIN_DIR;
        $current_dir = realpath(F1_STARS_PLUGIN_DIR);
        
        if (!$current_dir || !is_dir($plugins_dir)) return;
        
        $entries = scandir($plugins_dir);
        if (!$entries) return;

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $full_path = $plugins_dir . '/' . $entry;
            
            if (is_dir($full_path)) {
                $real_entry_dir = realpath($full_path);
                if ($real_entry_dir && $real_entry_dir !== $current_dir) {
                    if (file_exists($full_path . '/f1_stars_fixed.php') || file_exists($full_path . '/f1_stars.php')) {
                        $this->delete_dir_recursive($full_path);
                    }
                }
            }
        }
    }

    private function delete_dir_recursive($dir) {
        if (!is_dir($dir)) return false;
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                @chmod($path, 0777);
                $this->delete_dir_recursive($path);
            } else {
                @chmod($path, 0777);
                @unlink($path);
            }
        }
        @chmod($dir, 0777);
        return @rmdir($dir);
    }
    
    public function init() {
        load_plugin_textdomain('f1-stars', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Migración automática de esquema en cada carga (comprobación rápida de columna)
        if (get_option('f1_stars_db_version') !== F1_STARS_VERSION) {
            $this->maybe_upgrade_schema();
            update_option('f1_stars_db_version', F1_STARS_VERSION);
        }
    }
    
    public function activate() {
        $this->create_tables();
        $this->maybe_upgrade_schema();
        
        // Opciones por defecto
        add_option('f1_stars_season', $this->season);
        add_option('f1_stars_timezone', 'Europe/Madrid');
        
        $this->get_drivers_for_season($this->season);
        
        update_option('f1_stars_db_version', F1_STARS_VERSION);
    }
    
    private function maybe_upgrade_schema() {
        global $wpdb;
        $table_sessions = $wpdb->prefix . 'f1_sessions';
        $table_drivers  = $wpdb->prefix . 'f1_drivers';
        
        $col1 = $wpdb->get_results("SHOW COLUMNS FROM {$table_sessions} LIKE 'openf1_session_key'");
        if (empty($col1)) {
            $wpdb->query("ALTER TABLE {$table_sessions} ADD COLUMN openf1_session_key bigint(20) DEFAULT NULL");
        }
        
        $col2 = $wpdb->get_results("SHOW COLUMNS FROM {$table_drivers} LIKE 'team_colour'");
        if (empty($col2)) {
            $wpdb->query("ALTER TABLE {$table_drivers} ADD COLUMN team_colour varchar(10) DEFAULT NULL");
        }
        
        $col3 = $wpdb->get_results("SHOW COLUMNS FROM {$table_drivers} LIKE 'headshot_url'");
        if (empty($col3)) {
            $wpdb->query("ALTER TABLE {$table_drivers} ADD COLUMN headshot_url varchar(255) DEFAULT NULL");
        }

        // Auto-sembrar pilotos de respaldo si la tabla está vacía
        $this->get_drivers_for_season(get_option('f1_stars_season', $this->season));

        // Auto-asegurar sesiones del GP de Malasia / Sepang 2026 (carrera trasladada de Bahrein en 2026)
        $cur_season = intval(get_option('f1_stars_season', $this->season));
        if ($cur_season === 2026) {
            // 1. Si existían sesiones de esta semana con 'Bahrain' o 'Kuala Lumpur', corregir a 'Malasia' y 'Sepang'
            $wpdb->query(
                "UPDATE {$table_sessions} 
                 SET country = 'Malasia', circuit_name = 'Sepang', round_name = 'Gran Premio de Malasia (Sepang)'
                 WHERE season = 2026 
                   AND (openf1_session_key IN (11730, 11731) 
                        OR (circuit_name IN ('Kuala Lumpur', 'Sepang') AND session_date BETWEEN '2026-10-01' AND '2026-10-06')
                        OR (country = 'Bahrain' AND session_date BETWEEN '2026-10-01' AND '2026-10-06'))"
            );

            // 2. Insertar Clasificación si aún no existe
            $qualy_exists = $wpdb->get_var(
                "SELECT id FROM {$table_sessions} 
                 WHERE season = 2026 AND (openf1_session_key = 11730 OR (session_type = 'qualy' AND session_date BETWEEN '2026-10-02' AND '2026-10-04'))"
            );
            if (!$qualy_exists) {
                $wpdb->insert($table_sessions, array(
                    'season'             => 2026,
                    'round_number'       => 16,
                    'round_name'         => 'Gran Premio de Malasia (Sepang)',
                    'country'            => 'Malasia',
                    'circuit_name'       => 'Sepang',
                    'session_type'       => 'qualy',
                    'session_date'       => '2026-10-03 08:00:00',
                    'openf1_session_key' => 11730,
                    'is_completed'       => 0
                ));
            }

            // 3. Insertar Carrera si aún no existe
            $race_exists = $wpdb->get_var(
                "SELECT id FROM {$table_sessions} 
                 WHERE season = 2026 AND (openf1_session_key = 11731 OR (session_type = 'race' AND session_date BETWEEN '2026-10-03' AND '2026-10-05'))"
            );
            if (!$race_exists) {
                $wpdb->insert($table_sessions, array(
                    'season'             => 2026,
                    'round_number'       => 16,
                    'round_name'         => 'Gran Premio de Malasia (Sepang)',
                    'country'            => 'Malasia',
                    'circuit_name'       => 'Sepang',
                    'session_type'       => 'race',
                    'session_date'       => '2026-10-04 07:00:00',
                    'openf1_session_key' => 11731,
                    'is_completed'       => 0
                ));
            }

            // 4. Limpiar cualquier duplicado existente de la carrera de Sepang (04/10/2026)
            $table_bets = $wpdb->prefix . 'f1_bets';
            $sepang_races = $wpdb->get_results(
                "SELECT id, openf1_session_key FROM {$table_sessions} 
                 WHERE season = 2026 AND session_type = 'race' 
                   AND session_date BETWEEN '2026-10-02' AND '2026-10-06' 
                 ORDER BY (openf1_session_key = 11731) DESC, (openf1_session_key IS NOT NULL) DESC, id DESC"
            );
            if (!empty($sepang_races) && count($sepang_races) > 1) {
                $keep_race = $sepang_races[0];
                for ($k = 1; $k < count($sepang_races); $k++) {
                    $dup_id = intval($sepang_races[$k]->id);
                    $wpdb->query($wpdb->prepare(
                        "UPDATE IGNORE {$table_bets} SET session_id = %d WHERE session_id = %d",
                        $keep_race->id, $dup_id
                    ));
                    $wpdb->delete($table_bets, array('session_id' => $dup_id));
                    $wpdb->delete($table_sessions, array('id' => $dup_id));
                }
            }

            // 5. Limpiar cualquier duplicado existente de la clasificación de Sepang (03/10/2026)
            $sepang_qualys = $wpdb->get_results(
                "SELECT id, openf1_session_key FROM {$table_sessions} 
                 WHERE season = 2026 AND session_type = 'qualy' 
                   AND session_date BETWEEN '2026-10-02' AND '2026-10-04' 
                 ORDER BY (openf1_session_key = 11730) DESC, (openf1_session_key IS NOT NULL) DESC, id DESC"
            );
            if (!empty($sepang_qualys) && count($sepang_qualys) > 1) {
                $keep_qualy = $sepang_qualys[0];
                for ($k = 1; $k < count($sepang_qualys); $k++) {
                    $dup_id = intval($sepang_qualys[$k]->id);
                    $wpdb->query($wpdb->prepare(
                        "UPDATE IGNORE {$table_bets} SET session_id = %d WHERE session_id = %d",
                        $keep_qualy->id, $dup_id
                    ));
                    $wpdb->delete($table_bets, array('session_id' => $dup_id));
                    $wpdb->delete($table_sessions, array('id' => $dup_id));
                }
            }

            // 6. Regularizar / Añadir apuesta de Iván para Clasificación GP United States (24/10/2026)
            $table_participants = $wpdb->prefix . 'f1_participants';
            $ivan_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table_participants} WHERE nick = %s", 'Iván'));
            if (!$ivan_id) {
                $ivan_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table_participants} WHERE nick = %s", 'Ivan'));
            }
            if ($ivan_id) {
                $us_qualy = $wpdb->get_row("SELECT id, is_completed FROM {$table_sessions} WHERE season = 2026 AND session_type = 'qualy' AND country LIKE '%United States%' LIMIT 1");
                if ($us_qualy) {
                    $us_s_id = intval($us_qualy->id);
                    $existing_bet = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM {$table_bets} WHERE participant_id = %d AND session_id = %d",
                        $ivan_id, $us_s_id
                    ));
                    if (!$existing_bet) {
                        $wpdb->insert($table_bets, array(
                            'participant_id' => $ivan_id,
                            'session_id'     => $us_s_id,
                            'driver1_number' => 16, // Charles Leclerc
                            'driver2_number' => 12, // Kimi Antonelli
                            'driver3_number' => 44, // Lewis Hamilton
                            'points'         => null,
                            'created_at'     => '2026-10-24 21:00:00'
                        ));
                    } else {
                        $wpdb->update($table_bets, array(
                            'driver1_number' => 16,
                            'driver2_number' => 12,
                            'driver3_number' => 44
                        ), array('id' => $existing_bet));
                    }
                    if ($us_qualy->is_completed) {
                        $this->calculate_points_for_session($us_s_id);
                    }
                }
            }
        }
    }
    
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $table_participants = $wpdb->prefix . 'f1_participants';
        $sql_participants = "CREATE TABLE $table_participants (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  nick varchar(100) NOT NULL,
  user_id bigint(20) DEFAULT NULL,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY nick (nick)
) $charset_collate;";
        
        $table_drivers = $wpdb->prefix . 'f1_drivers';
        $sql_drivers = "CREATE TABLE $table_drivers (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  season int(4) NOT NULL,
  driver_number int(3) NOT NULL,
  short_name varchar(10) NOT NULL,
  full_name varchar(100) NOT NULL,
  team varchar(100) DEFAULT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY season_number (season,driver_number)
) $charset_collate;";
        
        $table_sessions = $wpdb->prefix . 'f1_sessions';
        $sql_sessions = "CREATE TABLE $table_sessions (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  season int(4) NOT NULL,
  round_number int(3) NOT NULL,
  round_name varchar(200) NOT NULL,
  country varchar(100) NOT NULL,
  circuit_name varchar(200) NOT NULL,
  session_type varchar(50) NOT NULL,
  session_date datetime NOT NULL,
  is_completed tinyint(1) DEFAULT 0,
  dates_range varchar(100) DEFAULT NULL,
  alpha_round_id varchar(50) DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY season_round (season,round_number)
) $charset_collate;";
        
        $table_results = $wpdb->prefix . 'f1_results';
        $sql_results = "CREATE TABLE $table_results (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  session_id mediumint(9) NOT NULL,
  position int(3) NOT NULL,
  driver_number int(3) NOT NULL,
  PRIMARY KEY  (id),
  KEY session_id (session_id)
) $charset_collate;";
        
        $table_bets = $wpdb->prefix . 'f1_bets';
        $sql_bets = "CREATE TABLE $table_bets (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  participant_id mediumint(9) NOT NULL,
  session_id mediumint(9) NOT NULL,
  driver1_number int(3) NOT NULL,
  driver2_number int(3) NOT NULL,
  driver3_number int(3) NOT NULL,
  points int(3) DEFAULT NULL,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY participant_session (participant_id,session_id),
  KEY session_id (session_id)
) $charset_collate;";
        
        dbDelta($sql_participants);
        dbDelta($sql_drivers);
        dbDelta($sql_sessions);
        dbDelta($sql_results);
        dbDelta($sql_bets);
    }
    
    public function ajax_test_api() {
        check_ajax_referer('f1_stars_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Permisos insuficientes');
        
        $season = get_option('f1_stars_season', $this->season);
        $results = array();
        
        $test = function($url) {
            $resp = wp_remote_get($url, array('timeout' => 15, 'sslverify' => false));
            if (is_wp_error($resp)) return array('status' => 'ERROR', 'error' => $resp->get_error_message());
            $code = wp_remote_retrieve_response_code($resp);
            $body = wp_remote_retrieve_body($resp);
            return array('status' => $code == 200 ? 'OK' : "FAIL ($code)", 'excerpt' => substr($body, 0, 150) . '...');
        };
        
        $results['meetings'] = $test("https://api.openf1.org/v1/meetings?year={$season}");
        $results['sessions'] = $test("https://api.openf1.org/v1/sessions?year={$season}");
        $results['drivers']  = $test("https://api.openf1.org/v1/drivers?session_key=latest");
        
        global $wpdb;
        $t_sess = $wpdb->prefix . 'f1_sessions';
        $t_driv = $wpdb->prefix . 'f1_drivers';
        
        $results['db_col_openf1']   = empty($wpdb->get_results("SHOW COLUMNS FROM {$t_sess} LIKE 'openf1_session_key'")) ? 'NO' : 'SI';
        $results['db_col_colour']   = empty($wpdb->get_results("SHOW COLUMNS FROM {$t_driv} LIKE 'team_colour'")) ? 'NO' : 'SI';
        $results['db_col_headshot'] = empty($wpdb->get_results("SHOW COLUMNS FROM {$t_driv} LIKE 'headshot_url'")) ? 'NO' : 'SI';
        
        wp_send_json_success($results);
    }
    
    public function ajax_create_tables() {
        check_ajax_referer('f1_stars_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes');
        }
        
        try {
            $this->create_tables();
            
            global $wpdb;
            $tables = array(
                'f1_participants',
                'f1_drivers', 
                'f1_sessions',
                'f1_results',
                'f1_bets'
            );
            
            $created = array();
            foreach ($tables as $table) {
                $table_name = $wpdb->prefix . $table;
                if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                    $created[] = $table;
                }
            }
            
            if (count($created) == 5) {
                wp_send_json_success('✓ Todas las tablas creadas correctamente: ' . implode(', ', $created));
            } else {
                wp_send_json_error('⚠ Solo se crearon ' . count($created) . ' de 5 tablas: ' . implode(', ', $created));
            }
        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }
    
    public function deactivate() {
        // Cleanup si es necesario
    }
    
    public function admin_menu() {
        add_menu_page(
            'F1 Stars',
            'F1 Stars',
            'manage_options',
            'f1-stars',
            array($this, 'admin_page'),
            'dashicons-games',
            30
        );
        
        add_submenu_page(
            'f1-stars',
            'Participantes',
            'Participantes',
            'manage_options',
            'f1-stars-participants',
            array($this, 'participants_page')
        );

        add_submenu_page(
            'f1-stars',
            'Apuestas',
            'Apuestas',
            'manage_options',
            'f1-stars-bets',
            array($this, 'bets_page')
        );
        
        add_submenu_page(
            'f1-stars',
            'Sesiones',
            'Sesiones',
            'manage_options',
            'f1-stars-sessions',
            array($this, 'sessions_page')
        );
        
        add_submenu_page(
            'f1-stars',
            'Ajustes',
            'Ajustes',
            'manage_options',
            'f1-stars-settings',
            array($this, 'settings_page')
        );
    }
    
    public function admin_scripts($hook) {
        if (strpos($hook, 'f1-stars') === false) return;
        
        wp_enqueue_style('f1-stars-admin', F1_STARS_PLUGIN_URL . 'assets/css/admin.css', array(), F1_STARS_VERSION);
        wp_enqueue_script('f1-stars-admin', F1_STARS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), F1_STARS_VERSION, true);
        
        wp_localize_script('f1-stars-admin', 'f1StarsAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('f1_stars_admin_nonce')
        ));
    }
    
    public function frontend_scripts() {
        wp_enqueue_style('f1-stars-frontend', F1_STARS_PLUGIN_URL . 'assets/css/frontend.css', array(), F1_STARS_VERSION);
        wp_enqueue_script('f1-stars-frontend', F1_STARS_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), F1_STARS_VERSION, true);
        wp_enqueue_script('chartjs', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js', array(), '4.4.1', true);

        wp_localize_script('f1-stars-frontend', 'f1Stars', array(
            'ajax_url'    => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('f1_stars_nonce'),
            'admin_nonce' => current_user_can('manage_options') ? wp_create_nonce('f1_stars_admin_nonce') : ''
        ));
    }
    
    public function admin_page() {
        if (file_exists(F1_STARS_PLUGIN_DIR . 'templates/admin/dashboard.php')) {
            include F1_STARS_PLUGIN_DIR . 'templates/admin/dashboard.php';
        } else {
            echo '<div class="wrap"><h1>F1 Stars</h1><p>Error: No se encuentra el archivo dashboard.php</p></div>';
        }
    }
    
    public function bets_page() {
        if (file_exists(F1_STARS_PLUGIN_DIR . 'templates/admin/bets.php')) {
            include F1_STARS_PLUGIN_DIR . 'templates/admin/bets.php';
        } else {
            echo '<div class="wrap"><h1>Apuestas</h1><p>Error: No se encuentra el archivo bets.php</p></div>';
        }
    }

    public function participants_page() {
        if (file_exists(F1_STARS_PLUGIN_DIR . 'templates/admin/participants.php')) {
            include F1_STARS_PLUGIN_DIR . 'templates/admin/participants.php';
        } else {
            echo '<div class="wrap"><h1>Participantes</h1><p>Error: No se encuentra el archivo participants.php</p></div>';
        }
    }
    
    public function sessions_page() {
        if (file_exists(F1_STARS_PLUGIN_DIR . 'templates/admin/sessions.php')) {
            include F1_STARS_PLUGIN_DIR . 'templates/admin/sessions.php';
        } else {
            echo '<div class="wrap"><h1>Sesiones</h1><p>Error: No se encuentra el archivo sessions.php</p></div>';
        }
    }
    
    public function settings_page() {
        if (file_exists(F1_STARS_PLUGIN_DIR . 'templates/admin/settings.php')) {
            include F1_STARS_PLUGIN_DIR . 'templates/admin/settings.php';
        } else {
            echo '<div class="wrap"><h1>Ajustes</h1><p>Error: No se encuentra el archivo settings.php</p></div>';
        }
    }
    
    public function ajax_load_races() {
        check_ajax_referer('f1_stars_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Permisos insuficientes');
        
        global $wpdb;
        $season = get_option('f1_stars_season', $this->season);
        $api_base = 'https://api.openf1.org/v1';
        
        $meetings_url = "{$api_base}/meetings?year={$season}";
        $resp_m = wp_remote_get($meetings_url, array('timeout' => 15, 'sslverify' => false));
        $meetings = (!is_wp_error($resp_m)) ? json_decode(wp_remote_retrieve_body($resp_m), true) : null;
        
        if (empty($meetings) || !is_array($meetings)) {
            $meetings = array(
                array('meeting_key' => 1229, 'meeting_name' => 'Austrian Grand Prix', 'meeting_official_name' => 'Grosser Preis Von Österreich', 'country_name' => 'Austria', 'circuit_short_name' => 'Red Bull Ring', 'date_start' => '2026-06-26'),
                array('meeting_key' => 1230, 'meeting_name' => 'Spanish Grand Prix', 'meeting_official_name' => 'Gran Premio de España', 'country_name' => 'Spain', 'circuit_short_name' => 'Catalunya', 'date_start' => '2026-06-19'),
                array('meeting_key' => 1231, 'meeting_name' => 'Monaco Grand Prix', 'meeting_official_name' => 'Grand Prix de Monaco', 'country_name' => 'Monaco', 'circuit_short_name' => 'Monte Carlo', 'date_start' => '2026-05-24'),
                array('meeting_key' => 1232, 'meeting_name' => 'British Grand Prix', 'meeting_official_name' => 'British Grand Prix', 'country_name' => 'Great Britain', 'circuit_short_name' => 'Silverstone', 'date_start' => '2026-07-05'),
                array('meeting_key' => 1244, 'meeting_name' => 'Italian Grand Prix', 'meeting_official_name' => 'Gran Premio d’Italia', 'country_name' => 'Italia', 'circuit_short_name' => 'Monza', 'date_start' => '2026-09-04'),
                array('meeting_key' => 1308, 'meeting_name' => 'Malaysian Grand Prix', 'meeting_official_name' => 'Gran Premio de Malasia (Sepang)', 'country_name' => 'Malasia', 'circuit_short_name' => 'Sepang', 'date_start' => '2026-10-02'),
            );
        }
        
        $valid_meetings = array();
        foreach ($meetings as $m) {
            if (empty($m['is_cancelled']) && strpos($m['meeting_name'], 'Testing') === false) {
                $valid_meetings[] = $m;
            }
        }
        usort($valid_meetings, function($a, $b) { return strtotime($a['date_start']) <=> strtotime($b['date_start']); });
        
        $meeting_map = array();
        $round = 1;
        foreach ($valid_meetings as $m) {
            $country = $m['country_name'];
            $circuit = $m['circuit_short_name'];
            $name    = $m['meeting_official_name'];
            
            // Detección y normalización de Sepang / Malasia (carrera trasladada de Bahrein en 2026)
            if (
                $m['meeting_key'] == 1308 ||
                stripos($name, 'Malaysia') !== false ||
                stripos($circuit, 'Kuala Lumpur') !== false ||
                stripos($circuit, 'Sepang') !== false ||
                stripos($m['location'] ?? '', 'Kuala Lumpur') !== false ||
                stripos($m['location'] ?? '', 'Sepang') !== false
            ) {
                $country = 'Malasia';
                $circuit = 'Sepang';
                $name    = 'Gran Premio de Malasia (Sepang)';
            }
            
            $meeting_map[$m['meeting_key']] = array(
                'round_number' => $round++,
                'name'         => $name,
                'country'      => $country,
                'circuit'      => $circuit
            );
        }
        
        $sessions_url = "{$api_base}/sessions?year={$season}";
        $resp_s = wp_remote_get($sessions_url, array('timeout' => 15, 'sslverify' => false));
        $sessions = (!is_wp_error($resp_s)) ? json_decode(wp_remote_retrieve_body($resp_s), true) : null;
        
        if (empty($sessions) || !is_array($sessions)) {
            $sessions = array(
                array('session_key' => 9569, 'meeting_key' => 1229, 'session_name' => 'Qualifying', 'date_start' => current_time('Y-m-d') . 'T16:00:00'),
                array('session_key' => 9570, 'meeting_key' => 1229, 'session_name' => 'Race', 'date_start' => date('Y-m-d', strtotime('+1 day')) . 'T15:00:00'),
                array('session_key' => 11730, 'meeting_key' => 1308, 'session_name' => 'Qualifying', 'date_start' => '2026-10-03T08:00:00'),
                array('session_key' => 11731, 'meeting_key' => 1308, 'session_name' => 'Race', 'date_start' => '2026-10-04T07:00:00'),
            );
        }
        
        $table_sessions = $wpdb->prefix . 'f1_sessions';
        // Eliminar cualquier sesión de entrenamientos libres y sus apuestas de la base de datos
        $wpdb->query("DELETE b FROM {$wpdb->prefix}f1_bets b INNER JOIN {$table_sessions} s ON b.session_id = s.id WHERE s.session_type LIKE 'practice%'");
        $wpdb->query("DELETE FROM {$table_sessions} WHERE session_type LIKE 'practice%'");

        $loaded = 0;
        $dedup = 0;
        
        foreach ($sessions as $s) {
            if (!empty($s['is_cancelled'])) continue;
            if (!isset($meeting_map[$s['meeting_key']])) continue;
            
            $m_info = $meeting_map[$s['meeting_key']];
            $type = '';
            if ($s['session_name'] === 'Race') $type = 'race';
            elseif ($s['session_name'] === 'Qualifying') $type = 'qualy';
            elseif ($s['session_name'] === 'Sprint') $type = 'sprintRace';
            elseif ($s['session_name'] === 'Sprint Qualifying') $type = 'sprintQualy';
            
            if (!$type) continue;
            
            $s_date = str_replace('T', ' ', substr($s['date_start'], 0, 19));
            $session_key = intval($s['session_key']);
            
            // 1) ¿Ya existe con este openf1_session_key? → actualizar
            $exists = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $table_sessions WHERE openf1_session_key = %d",
                $session_key
            ));
            
            // 2) Si no, buscar por fecha del MISMO DÍA + mismo tipo (ventana de 12h, no 4 días)
            if (!$exists) {
                $exists = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM $table_sessions WHERE season = %d AND session_type = %s AND ABS(TIMESTAMPDIFF(HOUR, session_date, %s)) <= 36 LIMIT 1",
                    $season, $type, $s_date
                ));
            }
            
            // NO usar round_number como fallback: Ergast y OpenF1 numeran diferente y causa intercambio de GPs
            
            $data = array(
                'season'             => $season,
                'round_number'       => $m_info['round_number'],
                'round_name'         => $m_info['name'],
                'country'            => $m_info['country'],
                'circuit_name'       => $m_info['circuit'],
                'session_type'       => $type,
                'session_date'       => $s_date,
                'openf1_session_key' => $session_key
            );
            
            if ($exists) {
                // NO sobreescribir is_completed para no perder estado
                $wpdb->update($table_sessions, $data, array('id' => $exists->id));
            } else {
                $data['is_completed'] = 0;
                $wpdb->insert($table_sessions, $data);
            }
            $loaded++;
        }
        
        // === REPARACIÓN DE SESIONES CORRUPTAS ===
        // Detectar sesiones cuyas apuestas no cuadran con su fecha (señal de corrupción por round_number)
        // Ejemplo: sesión dice "Monaco 7 junio" pero las apuestas se hicieron el 2 de mayo → era Miami
        $table_bets = $wpdb->prefix . 'f1_bets';
        $table_results = $wpdb->prefix . 'f1_results';
        $repaired = 0;
        
        $suspect = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, MAX(b.created_at) as last_bet, COUNT(b.id) as bet_count
             FROM $table_sessions s
             INNER JOIN $table_bets b ON b.session_id = s.id
             WHERE s.season = %d
             GROUP BY s.id
             HAVING DATEDIFF(s.session_date, MAX(b.created_at)) > 14",
            $season
        ));
        
        foreach ($suspect as $bad) {
            // Esta sesión tiene apuestas pero su fecha está >14 días después de la última apuesta.
            // Buscar la sesión CORRECTA: mismo tipo, con openf1_session_key, fecha cercana a las apuestas
            $correct = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $table_sessions 
                 WHERE season = %d AND session_type = %s AND openf1_session_key IS NOT NULL 
                 AND ABS(TIMESTAMPDIFF(DAY, session_date, %s)) <= 3 
                 AND id != %d",
                $season, $bad->session_type, $bad->last_bet, $bad->id
            ));
            
            if ($correct) {
                // Mover apuestas a la sesión correcta
                $wpdb->query($wpdb->prepare(
                    "UPDATE $table_bets SET session_id = %d WHERE session_id = %d",
                    $correct->id, $bad->id
                ));
                // Mover resultados si los tenía
                $wpdb->query($wpdb->prepare(
                    "UPDATE $table_results SET session_id = %d WHERE session_id = %d",
                    $correct->id, $bad->id
                ));
                // Recalcular puntos en la sesión destino
                $this->calculate_points_for_session($correct->id);
                // Borrar la sesión corrupta (ya no tiene apuestas)
                $wpdb->delete($table_sessions, array('id' => $bad->id));
                $repaired++;
            }
        }
        
        // Recalcular puntos para cualquier sesión completada que tenga apuestas sin puntos
        $needs_recalc = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT s.id FROM $table_sessions s
             INNER JOIN $table_bets b ON b.session_id = s.id
             INNER JOIN $table_results r ON r.session_id = s.id
             WHERE s.season = %d AND s.is_completed = 1 AND b.points IS NULL",
            $season
        ));
        foreach ($needs_recalc as $nr) {
            $this->calculate_points_for_session($nr->id);
        }
        
        // === LIMPIEZA DE DUPLICADOS HUÉRFANOS ===
        // Eliminar sesiones sin apuestas ni openf1_session_key que tienen un equivalente con key
        $dedup = 0;
        $orphans = $wpdb->get_results($wpdb->prepare(
            "SELECT s.id FROM $table_sessions s 
             LEFT JOIN $table_bets b ON b.session_id = s.id 
             WHERE s.season = %d AND s.openf1_session_key IS NULL AND b.id IS NULL",
            $season
        ));
        foreach ($orphans as $o) {
            $orphan_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_sessions WHERE id = %d", $o->id));
            if (!$orphan_row) continue;
            $has_replacement = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_sessions WHERE season = %d AND session_type = %s AND openf1_session_key IS NOT NULL AND ABS(TIMESTAMPDIFF(HOUR, session_date, %s)) <= 48 AND id != %d",
                $season, $orphan_row->session_type, $orphan_row->session_date, $o->id
            ));
            if ($has_replacement > 0) {
                $wpdb->delete($table_sessions, array('id' => $o->id));
                $dedup++;
            }
        }
        
        $drivers_loaded = $this->load_drivers($season);
        $msg = "✓ Cargadas {$loaded} sesiones y {$drivers_loaded} pilotos desde OpenF1.";
        if ($repaired > 0) $msg .= " Reparadas {$repaired} sesiones con apuestas mal asignadas.";
        if ($dedup > 0) $msg .= " Eliminados {$dedup} duplicados huérfanos.";
        wp_send_json_success(array(
            'message'  => $msg,
            'loaded'   => $loaded,
            'drivers'  => $drivers_loaded,
            'repaired' => $repaired,
            'dedup'    => $dedup
        ));
    }
    
    private function load_drivers($season) {
        global $wpdb;
        $table_drivers  = $wpdb->prefix . 'f1_drivers';
        $table_bets     = $wpdb->prefix . 'f1_bets';
        $table_sessions = $wpdb->prefix . 'f1_sessions';
        $api_base = 'https://api.openf1.org/v1';
        $url = "{$api_base}/drivers?session_key=latest";
        $response = wp_remote_get($url, array('timeout' => 5, 'sslverify' => false));
        $drivers = (!is_wp_error($response)) ? json_decode(wp_remote_retrieve_body($response), true) : null;
        
        if (empty($drivers) || !is_array($drivers)) {
            $drivers = array(
                array('driver_number' => 1, 'full_name' => 'Max Verstappen', 'name_acronym' => 'VER', 'team_name' => 'Red Bull Racing', 'team_colour' => '3671C6'),
                array('driver_number' => 44, 'full_name' => 'Lewis Hamilton', 'name_acronym' => 'HAM', 'team_name' => 'Ferrari', 'team_colour' => 'E8002D'),
                array('driver_number' => 16, 'full_name' => 'Charles Leclerc', 'name_acronym' => 'LEC', 'team_name' => 'Ferrari', 'team_colour' => 'E8002D'),
                array('driver_number' => 4, 'full_name' => 'Lando Norris', 'name_acronym' => 'NOR', 'team_name' => 'McLaren', 'team_colour' => 'FF8000'),
                array('driver_number' => 81, 'full_name' => 'Oscar Piastri', 'name_acronym' => 'PIA', 'team_name' => 'McLaren', 'team_colour' => 'FF8000'),
                array('driver_number' => 14, 'full_name' => 'Fernando Alonso', 'name_acronym' => 'ALO', 'team_name' => 'Aston Martin', 'team_colour' => '229971'),
                array('driver_number' => 63, 'full_name' => 'George Russell', 'name_acronym' => 'RUS', 'team_name' => 'Mercedes', 'team_colour' => '27F4D2'),
                array('driver_number' => 55, 'full_name' => 'Carlos Sainz', 'name_acronym' => 'SAI', 'team_name' => 'Williams', 'team_colour' => '64C4FF'),
                array('driver_number' => 11, 'full_name' => 'Sergio Perez', 'name_acronym' => 'PER', 'team_name' => 'Red Bull Racing', 'team_colour' => '3671C6'),
            );
        }
        
        $loaded = 0;
        $valid_numbers = array();
        $unique_drivers = array();
        foreach ($drivers as $d) {
            // Filtrar pilotos de reserva/test: solo los que tienen team_name
            if (empty($d['team_name'])) continue;
            $unique_drivers[$d['driver_number']] = $d;
            $valid_numbers[] = intval($d['driver_number']);
        }
        
        foreach ($unique_drivers as $num => $driver) {
            $short_name = isset($driver['name_acronym']) ? strtoupper($driver['name_acronym']) : strtoupper(substr($driver['last_name'], 0, 3));
            
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_drivers WHERE season = %d AND driver_number = %d",
                $season, $num
            ));
            
            $data = array(
                'season'        => $season,
                'driver_number' => $num,
                'short_name'    => $short_name,
                'full_name'     => $driver['full_name'],
                'team'          => $driver['team_name'] ?? '',
                'team_colour'   => $driver['team_colour'] ?? '',
                'headshot_url'  => $driver['headshot_url'] ?? ''
            );
            
            if (!$exists) {
                if ($wpdb->insert($table_drivers, $data)) $loaded++;
            } else {
                $wpdb->update($table_drivers, $data, array('season' => $season, 'driver_number' => $num));
            }
        }
        
        // === FUSIÓN DE PILOTOS DUPLICADOS ===
        // Si un piloto viejo (Ergast) tiene el mismo nombre que uno nuevo (OpenF1) pero diferente número,
        // migrar las apuestas al número correcto y borrar el viejo
        foreach ($unique_drivers as $num => $driver) {
            $openf1_name = $driver['full_name'];
            // Buscar entradas con el mismo nombre pero diferente número
            $dupes = $wpdb->get_results($wpdb->prepare(
                "SELECT id, driver_number, short_name FROM $table_drivers 
                 WHERE season = %d AND full_name = %s AND driver_number != %d",
                $season, $openf1_name, $num
            ));
            // También buscar por short_name similar (ej: "Norris" vs "NOR")
            $short = isset($driver['name_acronym']) ? strtoupper($driver['name_acronym']) : strtoupper(substr($driver['last_name'], 0, 3));
            $dupes2 = $wpdb->get_results($wpdb->prepare(
                "SELECT id, driver_number, short_name FROM $table_drivers 
                 WHERE season = %d AND UPPER(short_name) = UPPER(full_name) AND driver_number != %d
                 AND (full_name LIKE %s OR short_name LIKE %s)",
                $season, $num, '%' . $wpdb->esc_like($driver['last_name']) . '%', '%' . $wpdb->esc_like($driver['last_name']) . '%'
            ));
            $all_dupes = array_merge($dupes, $dupes2);
            $seen_ids = array();
            foreach ($all_dupes as $dup) {
                if (isset($seen_ids[$dup->id])) continue;
                $seen_ids[$dup->id] = true;
                $old_num = intval($dup->driver_number);
                $new_num = intval($num);
                // Migrar apuestas: cambiar driver_number viejo por el nuevo
                $wpdb->query($wpdb->prepare(
                    "UPDATE $table_bets SET driver1_number = %d WHERE driver1_number = %d AND session_id IN (SELECT id FROM $table_sessions WHERE season = %d)",
                    $new_num, $old_num, $season
                ));
                $wpdb->query($wpdb->prepare(
                    "UPDATE $table_bets SET driver2_number = %d WHERE driver2_number = %d AND session_id IN (SELECT id FROM $table_sessions WHERE season = %d)",
                    $new_num, $old_num, $season
                ));
                $wpdb->query($wpdb->prepare(
                    "UPDATE $table_bets SET driver3_number = %d WHERE driver3_number = %d AND session_id IN (SELECT id FROM $table_sessions WHERE season = %d)",
                    $new_num, $old_num, $season
                ));
                // Migrar resultados
                $wpdb->query($wpdb->prepare(
                    "UPDATE $table_results SET driver_number = %d WHERE driver_number = %d AND session_id IN (SELECT id FROM $table_sessions WHERE season = %d)",
                    $new_num, $old_num, $season
                ));
                // Borrar entrada vieja
                $wpdb->delete($table_drivers, array('id' => $dup->id));
            }
        }
        
        // === LIMPIEZA DE PILOTOS ===
        // Eliminar pilotos de esta temporada que NO están en la parrilla actual de OpenF1
        // Solo si no tienen apuestas asociadas
        if (!empty($valid_numbers)) {
            $placeholders = implode(',', $valid_numbers);
            $old_drivers = $wpdb->get_results($wpdb->prepare(
                "SELECT d.id, d.driver_number FROM $table_drivers d 
                 WHERE d.season = %d AND d.driver_number NOT IN ($placeholders)",
                $season
            ));
            
            foreach ($old_drivers as $od) {
                $has_bets = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_bets b 
                     INNER JOIN $table_sessions s ON s.id = b.session_id 
                     WHERE s.season = %d AND (b.driver1_number = %d OR b.driver2_number = %d OR b.driver3_number = %d)",
                    $season, $od->driver_number, $od->driver_number, $od->driver_number
                ));
                if ($has_bets == 0) {
                    $wpdb->delete($table_drivers, array('id' => $od->id));
                }
            }
        }
        
        // Forzar todos los short_name existentes a MAYÚSCULAS
        $wpdb->query($wpdb->prepare(
            "UPDATE $table_drivers SET short_name = UPPER(short_name) WHERE season = %d",
            $season
        ));
        
        // Recalcular puntos de sesiones completadas (por si los driver_number cambiaron)
        $completed = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM $table_sessions WHERE season = %d AND is_completed = 1",
            $season
        ));
        foreach ($completed as $c) {
            $this->calculate_points_for_session($c->id);
        }
        
        return $loaded;
    }
    
    public function ajax_load_results() {
        check_ajax_referer('f1_stars_nonce', 'nonce');
        @set_time_limit(300);
        
        global $wpdb;
        $season         = get_option('f1_stars_season', $this->season);
        $table_sessions = $wpdb->prefix . 'f1_sessions';
        
        // Solo incluir sesiones que admiten apuestas y resultados oficiales (clasificación, carrera, sprint)
        $sessions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_sessions 
             WHERE season = %d AND is_completed = 0 AND session_date <= %s 
             AND session_type IN ('qualy', 'race', 'sprintQualy', 'sprintRace') 
             ORDER BY session_date ASC LIMIT 8",
            $season, current_time('mysql')
        ));
        
        $loaded = 0;
        $errors = array();
        
        // Mapeo de session_type interno → session_name de OpenF1
        $type_map = array('race' => 'Race', 'qualy' => 'Qualifying', 'sprintRace' => 'Sprint', 'sprintQualy' => 'Sprint Qualifying');
        
        foreach ($sessions as $session) {
            try {
                $sk = $session->openf1_session_key;
                
                // Si no tiene openf1_session_key, buscarlo dinámicamente
                if (empty($sk)) {
                    $of1_name = isset($type_map[$session->session_type]) ? $type_map[$session->session_type] : '';
                    if ($of1_name === '') {
                        $errors[] = "Tipo desconocido: {$session->session_type}";
                        continue;
                    }
                    $lookup_url = 'https://api.openf1.org/v1/sessions?year=' . intval($season) . '&session_name=' . rawurlencode($of1_name);
                    $lookup_resp = wp_remote_get($lookup_url, array('timeout' => 15, 'sslverify' => false));
                    if (!is_wp_error($lookup_resp)) {
                        $lookup_data = json_decode(wp_remote_retrieve_body($lookup_resp), true);
                        if (is_array($lookup_data)) {
                            $target_ts = strtotime($session->session_date);
                            $best_key = null;
                            $best_delta = PHP_INT_MAX;
                            foreach ($lookup_data as $item) {
                                if (empty($item['session_key']) || empty($item['date_start'])) continue;
                                $delta = abs(strtotime($item['date_start']) - $target_ts);
                                if ($delta < $best_delta) {
                                    $best_delta = $delta;
                                    $best_key = intval($item['session_key']);
                                }
                            }
                            if ($best_key && $best_delta < 86400) { // dentro de 24h
                                $sk = $best_key;
                                $wpdb->update($table_sessions, array('openf1_session_key' => $sk), array('id' => $session->id));
                            }
                        }
                    }
                }
                
                if (empty($sk)) {
                    $errors[] = "No se pudo obtener openf1_session_key para {$session->round_name} ({$session->session_type}). Prueba a Cargar Carreras primero.";
                    continue;
                }
                
                $url = "https://api.openf1.org/v1/session_result?session_key={$sk}";
                $resp = wp_remote_get($url, array('timeout' => 15, 'sslverify' => false));
                if (is_wp_error($resp)) {
                    $errors[] = "Error en {$session->round_name}: " . $resp->get_error_message();
                    continue;
                }
                
                $data = json_decode(wp_remote_retrieve_body($resp), true);
                if (empty($data)) {
                    $errors[] = "{$session->round_name} ({$session->session_type}) aún no tiene resultados en OpenF1.";
                    continue;
                }
                
                $valid_rows = array();
                foreach ($data as $row) {
                    $pos = intval($row['position'] ?? 0);
                    $num = intval($row['driver_number'] ?? 0);
                    if ($pos > 0 && $num > 0) {
                        $valid_rows[] = array('position' => $pos, 'driver_number' => $num);
                    }
                }
                
                if (!empty($valid_rows)) {
                    usort($valid_rows, function($a, $b) { return $a['position'] <=> $b['position']; });
                    $this->store_session_rows($session->id, $valid_rows);
                    $loaded++;
                } else {
                    $errors[] = "No hay resultados oficiales todavía para {$session->round_name} ({$session->session_type})";
                }
            } catch (Exception $e) {
                $errors[] = "Excepción en {$session->round_name}: " . $e->getMessage();
            }
        }
        
        $remaining = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_sessions WHERE season = %d AND is_completed = 0 AND session_date <= %s",
            $season, current_time('mysql')
        ));
        
        $message = "✓ Cargados resultados de {$loaded} sesiones";
        if (!empty($errors)) $message .= "\nAdvertencias:\n" . implode("\n", array_slice($errors, 0, 5));
        
        wp_send_json_success(array(
            'message'    => $message,
            'loaded'     => $loaded,
            'errors'     => $errors,
            'has_more'   => intval($remaining) > 0,
            'remaining'  => intval($remaining),
            'next_batch' => 1,
        ));
    }
    
    /**
     * Resetea is_completed = 0 en una sesión para que se pueda recargar.
     * También borra los resultados existentes y recalcula puntos si se indica.
     */
    public function ajax_reset_session() {
        check_ajax_referer('f1_stars_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        global $wpdb;
        $session_id = intval($_POST['session_id'] ?? 0);
        if ($session_id <= 0) {
            wp_send_json_error('ID de sesión inválido');
        }

        $table_sessions = $wpdb->prefix . 'f1_sessions';
        $table_results  = $wpdb->prefix . 'f1_results';

        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_sessions WHERE id = %d", $session_id
        ));
        if (!$session) {
            wp_send_json_error('Sesión no encontrada');
        }

        // Borrar resultados existentes y marcar como pendiente
        $wpdb->delete($table_results,  array('session_id' => $session_id));
        $wpdb->update($table_sessions, array('is_completed' => 0), array('id' => $session_id));

        wp_send_json_success(array(
            'message' => "Sesión #{$session_id} ({$session->round_name} - {$session->session_type}) reseteada. Ahora haz clic en «Cargar Resultados».",
        ));
    }

    private function store_session_rows($session_id, $rows) {
        global $wpdb;
        $table_results = $wpdb->prefix . 'f1_results';
        $table_sessions = $wpdb->prefix . 'f1_sessions';
        
        $wpdb->delete($table_results, array('session_id' => $session_id));
        foreach ($rows as $row) {
            $wpdb->insert($table_results, array(
                'session_id'    => intval($session_id),
                'position'      => intval($row['position']),
                'driver_number' => intval($row['driver_number']),
            ));
        }
        $wpdb->update($table_sessions, array('is_completed' => 1), array('id' => $session_id));
        $this->calculate_points_for_session($session_id);
    }
    
    // Antiguos métodos de fetch_json_url y helpers OpenF1 eliminados ya que 
    // todo se gestiona directamente en las llamadas arriba.
    
    public function calculate_points_for_session($session_id) {
        global $wpdb;
        $table_bets = $wpdb->prefix . 'f1_bets';
        $table_results = $wpdb->prefix . 'f1_results';
        
        $bets = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_bets WHERE session_id = %d",
            $session_id
        ));
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_results WHERE session_id = %d ORDER BY position ASC",
            $session_id
        ), ARRAY_A);
        
        $driver_positions = array();
        foreach ($results as $result) {
            $driver_positions[$result['driver_number']] = $result['position'];
        }
        
        foreach ($bets as $bet) {
            $points = 0;
            
            $points += $this->get_points_for_driver($bet->driver1_number, 1, $driver_positions);
            $points += $this->get_points_for_driver($bet->driver2_number, 2, $driver_positions);
            $points += $this->get_points_for_driver($bet->driver3_number, 3, $driver_positions);
            
            $wpdb->update($table_bets,
                array('points' => $points),
                array('id' => $bet->id)
            );
        }
    }
    
    private function get_points_for_driver($driver_number, $expected_position, $driver_positions) {
        if (!isset($driver_positions[$driver_number])) {
            return 0;
        }
        
        $actual_position = $driver_positions[$driver_number];
        $difference = abs($actual_position - $expected_position);
        
        if ($difference >= 3) return 0;
        
        return 3 - $difference;
    }
    
    public static function get_default_drivers_list() {
        return array(
            array('driver_number' => 1,  'full_name' => 'Max Verstappen',    'short_name' => 'VER', 'team' => 'Red Bull Racing', 'team_colour' => '3671C6', 'headshot_url' => 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/M/MAXVER01_Max_Verstappen/maxver01.png.transform/2col/image.png'),
            array('driver_number' => 6,  'full_name' => 'Isack Hadjar',      'short_name' => 'HAD', 'team' => 'Red Bull Racing', 'team_colour' => '3671C6', 'headshot_url' => 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/I/ISAHAD01_Isack_Hadjar/isahad01.png.transform/2col/image.png'),
            array('driver_number' => 44, 'full_name' => 'Lewis Hamilton',    'short_name' => 'HAM', 'team' => 'Ferrari',         'team_colour' => 'E8002D', 'headshot_url' => 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/L/LEWHAM01_Lewis_Hamilton/lewham01.png.transform/2col/image.png'),
            array('driver_number' => 16, 'full_name' => 'Charles Leclerc',   'short_name' => 'LEC', 'team' => 'Ferrari',         'team_colour' => 'E8002D', 'headshot_url' => 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/C/CHALEC01_Charles_Leclerc/chalec01.png.transform/2col/image.png'),
            array('driver_number' => 4,  'full_name' => 'Lando Norris',      'short_name' => 'NOR', 'team' => 'McLaren',         'team_colour' => 'FF8000', 'headshot_url' => 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/L/LANNOR01_Lando_Norris/lannor01.png.transform/2col/image.png'),
            array('driver_number' => 81, 'full_name' => 'Oscar Piastri',     'short_name' => 'PIA', 'team' => 'McLaren',         'team_colour' => 'FF8000', 'headshot_url' => 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/O/OSCPIA01_Oscar_Piastri/oscpia01.png.transform/2col/image.png'),
            array('driver_number' => 63, 'full_name' => 'George Russell',    'short_name' => 'RUS', 'team' => 'Mercedes',        'team_colour' => '27F4D2', 'headshot_url' => 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/G/GEORUS01_George_Russell/georus01.png.transform/2col/image.png'),
            array('driver_number' => 12, 'full_name' => 'Kimi Antonelli',    'short_name' => 'ANT', 'team' => 'Mercedes',        'team_colour' => '27F4D2', 'headshot_url' => 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/K/KIMANT01_Kimi_Antonelli/kimant01.png.transform/2col/image.png'),
            array('driver_number' => 14, 'full_name' => 'Fernando Alonso',   'short_name' => 'ALO', 'team' => 'Aston Martin',    'team_colour' => '229971', 'headshot_url' => 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/F/FERALO01_Fernando_Alonso/feralo01.png.transform/2col/image.png'),
            array('driver_number' => 18, 'full_name' => 'Lance Stroll',      'short_name' => 'STR', 'team' => 'Aston Martin',    'team_colour' => '229971', 'headshot_url' => 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/L/LANSTR01_Lance_Stroll/lanstr01.png.transform/2col/image.png'),
            array('driver_number' => 55, 'full_name' => 'Carlos Sainz',      'short_name' => 'SAI', 'team' => 'Williams',        'team_colour' => '64C4FF', 'headshot_url' => 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/C/CARSAI01_Carlos_Sainz/carsai01.png.transform/2col/image.png'),
            array('driver_number' => 23, 'full_name' => 'Alexander Albon',   'short_name' => 'ALB', 'team' => 'Williams',        'team_colour' => '64C4FF', 'headshot_url' => 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/A/ALEALB01_Alexander_Albon/alealb01.png.transform/2col/image.png'),
            array('driver_number' => 10, 'full_name' => 'Pierre Gasly',      'short_name' => 'GAS', 'team' => 'Alpine',          'team_colour' => '0093CC', 'headshot_url' => 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/P/PIEGAS01_Pierre_Gasly/piegas01.png.transform/2col/image.png'),
            array('driver_number' => 7,  'full_name' => 'Jack Doohan',       'short_name' => 'DOO', 'team' => 'Alpine',          'team_colour' => '0093CC', 'headshot_url' => 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/J/JACDOO01_Jack_Doohan/jacdoo01.png.transform/2col/image.png'),
            array('driver_number' => 30, 'full_name' => 'Liam Lawson',       'short_name' => 'LAW', 'team' => 'Racing Bulls',    'team_colour' => '6692FF', 'headshot_url' => 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/L/LIALAW01_Liam_Lawson/lialaw01.png.transform/2col/image.png'),
            array('driver_number' => 22, 'full_name' => 'Yuki Tsunoda',      'short_name' => 'TSU', 'team' => 'Racing Bulls',    'team_colour' => '6692FF', 'headshot_url' => 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/Y/YUKTSU01_Yuki_Tsunoda/yuktsu01.png.transform/2col/image.png'),
            array('driver_number' => 31, 'full_name' => 'Esteban Ocon',      'short_name' => 'OCO', 'team' => 'Haas',            'team_colour' => 'B6BABD', 'headshot_url' => 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/E/ESTOCO01_Esteban_Ocon/estoco01.png.transform/2col/image.png'),
            array('driver_number' => 87, 'full_name' => 'Oliver Bearman',    'short_name' => 'BEA', 'team' => 'Haas',            'team_colour' => 'B6BABD', 'headshot_url' => 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/O/OLIBEA01_Oliver_Bearman/olibea01.png.transform/2col/image.png'),
            array('driver_number' => 27, 'full_name' => 'Nico Hulkenberg',   'short_name' => 'HUL', 'team' => 'Kick Sauber',    'team_colour' => '52E252', 'headshot_url' => 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/N/NICHUL01_Nico_Hulkenberg/nichul01.png.transform/2col/image.png'),
            array('driver_number' => 5,  'full_name' => 'Gabriel Bortoleto', 'short_name' => 'BOR', 'team' => 'Kick Sauber',   'team_colour' => '52E252', 'headshot_url' => 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/G/GABBOR01_Gabriel_Bortoleto/gabbor01.png.transform/2col/image.png'),
            array('driver_number' => 43, 'full_name' => 'Franco Colapinto',  'short_name' => 'COL', 'team' => 'Williams',       'team_colour' => '64C4FF', 'headshot_url' => 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/F/FRACOL01_Franco_Colapinto/fracol01.png.transform/2col/image.png'),
            array('driver_number' => 11, 'full_name' => 'Sergio Perez',      'short_name' => 'PER', 'team' => 'Red Bull Racing', 'team_colour' => '3671C6', 'headshot_url' => 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/S/SERPER01_Sergio_Perez/serper01.png.transform/2col/image.png'),
        );
    }

    public function get_drivers_for_season($season = null) {
        global $wpdb;
        if (!$season) {
            $season = get_option('f1_stars_season', $this->season);
        }
        $table_drivers = $wpdb->prefix . 'f1_drivers';
        
        $drivers = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_drivers WHERE season = %d ORDER BY full_name ASC",
            $season
        ));
        
        if (empty($drivers)) {
            $defaults = self::get_default_drivers_list();
            foreach ($defaults as $d) {
                $d['season'] = $season;
                $wpdb->replace($table_drivers, $d);
            }
            $drivers = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_drivers WHERE season = %d ORDER BY full_name ASC",
                $season
            ));
            if (empty($drivers)) {
                $drivers = array_map(function($item) use ($season) {
                    $item['season'] = $season;
                    return (object)$item;
                }, $defaults);
            }
        }
        
        return $drivers;
    }

    public function ajax_get_drivers() {
        check_ajax_referer('f1_stars_nonce', 'nonce');
        
        $season = get_option('f1_stars_season', $this->season);
        $drivers = $this->get_drivers_for_season($season);
        
        wp_send_json_success($drivers);
    }
    
    public function ajax_submit_bet() {
        check_ajax_referer('f1_stars_nonce', 'nonce');
        
        global $wpdb;
        $table_participants = $wpdb->prefix . 'f1_participants';
        $table_bets = $wpdb->prefix . 'f1_bets';
        $table_sessions = $wpdb->prefix . 'f1_sessions';
        
        $nick = sanitize_text_field($_POST['nick']);
        $session_id = intval($_POST['session_id']);
        $driver1 = intval($_POST['driver1']);
        $driver2 = intval($_POST['driver2']);
        $driver3 = intval($_POST['driver3']);
        
        if ($driver1 == $driver2 || $driver1 == $driver3 || $driver2 == $driver3) {
            wp_send_json_error('Debes elegir 3 pilotos diferentes');
        }
        
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_sessions WHERE id = %d",
            $session_id
        ));
        
        if (!$session) {
            wp_send_json_error('Sesión no encontrada');
        }

        $allowed_bet_types = array('qualy', 'race', 'sprintQualy', 'sprintRace');
        if (!in_array($session->session_type, $allowed_bet_types, true)) {
            wp_send_json_error('No se admiten apuestas en sesiones de entrenamientos libres');
        }
        
        if ($session->is_completed && !current_user_can('manage_options')) {
            wp_send_json_error('La sesión ya ha finalizado y sus resultados están cargados');
        }
        
        $grace_minutes = intval(get_option('f1_stars_grace_minutes', 30));
        $session_time  = strtotime($session->session_date);
        $cutoff_time   = $session_time + ($grace_minutes * 60);

        if (time() > $cutoff_time && !current_user_can('manage_options')) {
            if ($grace_minutes > 0) {
                wp_send_json_error("El plazo para apostar ha expirado (límite de cortesía: {$grace_minutes} minutos tras el inicio de la sesión)");
            } else {
                wp_send_json_error('La sesión ya ha comenzado, no se pueden enviar apuestas');
            }
        }
        
        $participant = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_participants WHERE nick = %s",
            $nick
        ));
        
        if (!$participant) {
            $wpdb->insert($table_participants, array(
                'nick' => $nick,
                'user_id' => get_current_user_id()
            ));
            $participant_id = $wpdb->insert_id;
        } else {
            $participant_id = $participant->id;
        }
        
        $existing_bet = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_bets WHERE participant_id = %d AND session_id = %d",
            $participant_id, $session_id
        ));
        
        if ($existing_bet) {
            $wpdb->update($table_bets, array(
                'driver1_number' => $driver1,
                'driver2_number' => $driver2,
                'driver3_number' => $driver3
            ), array('id' => $existing_bet));
            
            if ($session->is_completed) {
                $this->calculate_points_for_session($session_id);
            }
            
            wp_send_json_success('Apuesta actualizada correctamente');
        } else {
            $wpdb->insert($table_bets, array(
                'participant_id' => $participant_id,
                'session_id' => $session_id,
                'driver1_number' => $driver1,
                'driver2_number' => $driver2,
                'driver3_number' => $driver3
            ));
            
            if ($session->is_completed) {
                $this->calculate_points_for_session($session_id);
            }
            
            wp_send_json_success('Apuesta enviada correctamente');
        }
    }
    
    /**
     * Detecta si la página actual tiene algún shortcode de F1 Stars
     * y, si es así, envía headers HTTP de no-caché y avisa a los plugins
     * de caché más populares para que excluyan esta página.
     */
    public function maybe_disable_cache() {
        global $post;
        if (!is_a($post, 'WP_Post')) return;

        $f1_shortcodes = array('f1_standings', 'f1_bet_form', 'f1_all_bets', 'f1_calendar', 'f1_chart', 'f1_bets_admin');
        $has_f1 = false;
        foreach ($f1_shortcodes as $sc) {
            if (has_shortcode($post->post_content, $sc)) {
                $has_f1 = true;
                break;
            }
        }
        if (!$has_f1) return;

        // --- Headers HTTP no-caché ---
        if (!headers_sent()) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        }

        // --- WP Rocket ---
        if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
        if (!defined('DONOTCACHEDB'))   define('DONOTCACHEDB',   true);

        // --- W3 Total Cache ---
        if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);

        // --- WP Super Cache ---
        if (!defined('WPFC_EXCLUDE_CURRENT_PAGE')) define('WPFC_EXCLUDE_CURRENT_PAGE', true);

        // --- LiteSpeed Cache ---
        do_action('litespeed_control_set_nocache', 'f1stars_dynamic');

        // --- SG Optimizer (SiteGround) ---
        if (function_exists('sg_cachepress_purge_cache')) {
            add_action('shutdown', 'sg_cachepress_purge_cache');
        }
    }

    /**
     * Limpia la caché de la página de apuestas tras enviar una apuesta nueva.
     * Se ejecuta justo antes de que AJAX devuelva la respuesta.
     */
    public function purge_cache_after() {
        $bets_page_url = home_url('/ver-apuestas/');

        // WP Rocket
        if (function_exists('rocket_clean_post')) {
            $page = get_page_by_path('ver-apuestas');
            if ($page) rocket_clean_post($page->ID);
        }
        if (function_exists('rocket_clean_home')) rocket_clean_home();

        // W3 Total Cache
        if (function_exists('w3tc_flush_url')) w3tc_flush_url($bets_page_url);
        if (function_exists('w3tc_flush_posts')) w3tc_flush_posts();

        // WP Super Cache
        if (function_exists('wp_cache_clear_cache')) wp_cache_clear_cache();

        // LiteSpeed Cache
        do_action('litespeed_purge_url', $bets_page_url);

        // SG Optimizer
        if (function_exists('sg_cachepress_purge_cache')) sg_cachepress_purge_cache();

        // Autoptimize
        if (class_exists('autoptimizeCache')) autoptimizeCache::clearall();

        // Cache Enabler
        if (class_exists('Cache_Enabler')) Cache_Enabler::clear_total_cache();

        // Breeze (Cloudways)
        if (class_exists('Breeze_Admin')) do_action('breeze_clear_all_cache');

        // Hummingbird
        if (class_exists('\Hummingbird\Core\Utils')) {
            do_action('wphb_clear_page_cache');
        }
    }

    public function shortcode_standings($atts) {
        ob_start();
        if (file_exists(F1_STARS_PLUGIN_DIR . 'templates/shortcodes/standings.php')) {
            include F1_STARS_PLUGIN_DIR . 'templates/shortcodes/standings.php';
        } else {
            echo '<p>Error: No se encuentra el archivo standings.php</p>';
        }
        return ob_get_clean();
    }
    
    public function shortcode_bet_form($atts) {
        global $wpdb;
        // Eliminar cualquier apuesta o sesión de entrenamientos libres de la base de datos
        $wpdb->query("DELETE b FROM {$wpdb->prefix}f1_bets b INNER JOIN {$wpdb->prefix}f1_sessions s ON b.session_id = s.id WHERE s.session_type LIKE 'practice%'");
        $wpdb->query("DELETE FROM {$wpdb->prefix}f1_sessions WHERE session_type LIKE 'practice%'");

        ob_start();
        if (file_exists(F1_STARS_PLUGIN_DIR . 'templates/shortcodes/bet-form.php')) {
            include F1_STARS_PLUGIN_DIR . 'templates/shortcodes/bet-form.php';
        } else {
            echo '<p>Error: No se encuentra el archivo bet-form.php</p>';
        }
        return ob_get_clean();
    }
    
    public function shortcode_all_bets($atts) {
        ob_start();
        if (file_exists(F1_STARS_PLUGIN_DIR . 'templates/shortcodes/all-bets.php')) {
            include F1_STARS_PLUGIN_DIR . 'templates/shortcodes/all-bets.php';
        } else {
            echo '<p>Error: No se encuentra el archivo all-bets.php</p>';
        }
        return ob_get_clean();
    }
    
    public function shortcode_calendar($atts) {
        ob_start();
        if (file_exists(F1_STARS_PLUGIN_DIR . 'templates/shortcodes/calendar.php')) {
            include F1_STARS_PLUGIN_DIR . 'templates/shortcodes/calendar.php';
        } else {
            echo '<p>Error: No se encuentra el archivo calendar.php</p>';
        }
        return ob_get_clean();
    }
    public function ajax_delete_session() {
        check_ajax_referer('f1_stars_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Permisos insuficientes');
        
        $session_id = intval($_POST['session_id'] ?? 0);
        if (!$session_id) wp_send_json_error('ID de sesión no válido');
        
        global $wpdb;
        $table_sessions = $wpdb->prefix . 'f1_sessions';
        $table_bets = $wpdb->prefix . 'f1_bets';
        $table_results = $wpdb->prefix . 'f1_results';
        
        $wpdb->delete($table_bets, array('session_id' => $session_id));
        $wpdb->delete($table_results, array('session_id' => $session_id));
        $deleted = $wpdb->delete($table_sessions, array('id' => $session_id));
        
        if ($deleted) {
            wp_send_json_success(array('message' => 'Sesión eliminada correctamente'));
        } else {
            wp_send_json_error('No se pudo encontrar o eliminar la sesión');
        }
    }

    public function ajax_dedup_sessions() {
        check_ajax_referer('f1_stars_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Permisos insuficientes');

        global $wpdb;
        $season  = get_option('f1_stars_season', 2026);
        $table_s = $wpdb->prefix . 'f1_sessions';
        $table_b = $wpdb->prefix . 'f1_bets';
        $table_r = $wpdb->prefix . 'f1_results';

        // Eliminar cualquier sesión de entrenamientos libres y sus apuestas
        $wpdb->query("DELETE b FROM $table_b b INNER JOIN $table_s s ON b.session_id = s.id WHERE s.session_type LIKE 'practice%'");
        $wpdb->query("DELETE FROM $table_s WHERE session_type LIKE 'practice%'");

        $all_sessions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_s WHERE season = %d ORDER BY id ASC",
            $season
        ));

        $sessions_removed = 0;
        $bets_moved       = 0;
        $groups_found     = 0;
        $processed_ids    = array();

        foreach ($all_sessions as $i => $s1) {
            if (in_array($s1->id, $processed_ids)) continue;

            $group = array($s1);
            foreach ($all_sessions as $j => $s2) {
                if ($i === $j || in_array($s2->id, $processed_ids)) continue;

                $is_dup = false;

                // 1) Misma clave oficial de OpenF1
                if (!empty($s1->openf1_session_key) && !empty($s2->openf1_session_key) && $s1->openf1_session_key == $s2->openf1_session_key) {
                    $is_dup = true;
                }
                // 2) Mismo tipo de sesión en el mismo fin de semana (diferencia <= 36 horas)
                elseif ($s1->session_type === $s2->session_type) {
                    $diff_hours = abs(strtotime($s1->session_date) - strtotime($s2->session_date)) / 3600;
                    if ($diff_hours <= 36) {
                        $is_dup = true;
                    }
                    // 3) Mismo número de ronda y mismo tipo de sesión
                    elseif (!empty($s1->round_number) && $s1->round_number == $s2->round_number) {
                        $is_dup = true;
                    }
                }

                if ($is_dup) {
                    $group[] = $s2;
                    $processed_ids[] = $s2->id;
                }
            }

            if (count($group) > 1) {
                $groups_found++;
                $processed_ids[] = $s1->id;

                // Ordenar para elegir la sesión canónica a conservar:
                usort($group, function($a, $b) use ($wpdb, $table_b) {
                    // Prioridad 1: tener openf1_session_key
                    $a_has_key = !empty($a->openf1_session_key) ? 1 : 0;
                    $b_has_key = !empty($b->openf1_session_key) ? 1 : 0;
                    if ($a_has_key !== $b_has_key) return $b_has_key <=> $a_has_key;

                    // Prioridad 2: sesión completada
                    if ($a->is_completed != $b->is_completed) return $b->is_completed <=> $a->is_completed;

                    // Prioridad 3: sesión con más apuestas
                    $a_bets = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_b WHERE session_id = %d", $a->id)));
                    $b_bets = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_b WHERE session_id = %d", $b->id)));
                    if ($a_bets !== $b_bets) return $b_bets <=> $a_bets;

                    // Prioridad 4: mayor ID
                    return $b->id <=> $a->id;
                });

                $keep = $group[0];
                $duplicates = array_slice($group, 1);

                foreach ($duplicates as $dup) {
                    // Reasignar apuestas
                    $moved = $wpdb->query($wpdb->prepare(
                        "UPDATE IGNORE $table_b SET session_id = %d WHERE session_id = %d",
                        $keep->id, $dup->id
                    ));
                    $bets_moved += intval($moved);

                    // Borrar apuestas residuales que no se pudieron mover
                    $wpdb->delete($table_b, array('session_id' => $dup->id));

                    // Mover resultados si existían
                    $keep_has_res = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_r WHERE session_id = %d", $keep->id));
                    if (!$keep_has_res) {
                        $wpdb->update($table_r, array('session_id' => $keep->id), array('session_id' => $dup->id));
                    } else {
                        $wpdb->delete($table_r, array('session_id' => $dup->id));
                    }

                    // Eliminar la sesión duplicada
                    $wpdb->delete($table_s, array('id' => $dup->id));
                    $sessions_removed++;
                }
            }
        }

        if ($groups_found === 0) {
            wp_send_json_success('✅ No hay sesiones duplicadas. La base de datos está limpia.');
        }

        wp_send_json_success(
            "✅ Deduplicación completada:\n" .
            "• Grupos duplicados encontrados: {$groups_found}\n" .
            "• Sesiones duplicadas eliminadas: {$sessions_removed}\n" .
            "• Apuestas reasignadas al ID correcto: {$bets_moved}"
        );
    }

    public function ajax_delete_bets() {
        check_ajax_referer('f1_stars_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes');
        }

        global $wpdb;
        $season = intval($_POST['season']);

        if ($season < 2020 || $season > 2030) {
            wp_send_json_error('Temporada no válida');
        }

        $table_bets     = $wpdb->prefix . 'f1_bets';
        $table_sessions = $wpdb->prefix . 'f1_sessions';

        // Obtener IDs de sesiones de esa temporada
        $session_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM $table_sessions WHERE season = %d",
            $season
        ));

        if (empty($session_ids)) {
            wp_send_json_error("No se encontraron sesiones para la temporada {$season}");
        }

        $ids_placeholder = implode(',', array_map('intval', $session_ids));
        $deleted = $wpdb->query("DELETE FROM $table_bets WHERE session_id IN ($ids_placeholder)");

        wp_send_json_success("✓ Se eliminaron {$deleted} apuestas de la temporada {$season}");
    }

    public function ajax_delete_season_data() {
        check_ajax_referer('f1_stars_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes');
        }

        global $wpdb;
        $season = intval($_POST['season']);

        if ($season < 2020 || $season > 2030) {
            wp_send_json_error('Temporada no válida');
        }

        $table_bets     = $wpdb->prefix . 'f1_bets';
        $table_sessions = $wpdb->prefix . 'f1_sessions';
        $table_results  = $wpdb->prefix . 'f1_results';
        $table_drivers  = $wpdb->prefix . 'f1_drivers';

        $session_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM $table_sessions WHERE season = %d",
            $season
        ));

        $bets_deleted    = 0;
        $results_deleted = 0;

        if (!empty($session_ids)) {
            $ids = implode(',', array_map('intval', $session_ids));
            $bets_deleted    = $wpdb->query("DELETE FROM $table_bets WHERE session_id IN ($ids)");
            $results_deleted = $wpdb->query("DELETE FROM $table_results WHERE session_id IN ($ids)");
        }

        $sessions_deleted = $wpdb->delete($table_sessions, array('season' => $season));
        $drivers_deleted  = $wpdb->delete($table_drivers,  array('season' => $season));

        wp_send_json_success(
            "✓ Temporada {$season} eliminada: {$bets_deleted} apuestas, {$results_deleted} resultados, " .
            "{$sessions_deleted} sesiones, {$drivers_deleted} pilotos."
        );
    }

    public function ajax_chart_data() {
        check_ajax_referer('f1_stars_nonce', 'nonce');
        global $wpdb;
        $season = get_option('f1_stars_season', $this->season);

        // Agrupamos por semana del año (YEARWEEK) para evitar que round_number distinto
        // entre Ergast y OpenF1 mezcle sesiones de GPs distintos
        $raw = $wpdb->get_results($wpdb->prepare("
            SELECT
                p.id          AS participant_id,
                p.nick,
                s.country,
                s.round_name,
                MIN(s.session_date) AS gp_date,
                YEARWEEK(s.session_date, 1) AS gp_week,
                SUM(b.points) AS round_points
            FROM {$wpdb->prefix}f1_participants p
            JOIN {$wpdb->prefix}f1_bets b ON p.id = b.participant_id
            JOIN {$wpdb->prefix}f1_sessions s ON b.session_id = s.id
            WHERE s.season = %d AND b.points IS NOT NULL
            GROUP BY p.id, p.nick, YEARWEEK(s.session_date, 1)
            ORDER BY gp_date ASC, p.nick ASC
        ", $season));

        if (empty($raw)) {
            wp_send_json_success(array('labels' => array(), 'series' => array()));
            return;
        }

        // Construir lista ordenada de GPs únicos por semana
        $gp_map = array();
        foreach ($raw as $row) {
            $wk = $row->gp_week;
            if (!isset($gp_map[$wk])) {
                $gp_map[$wk] = array(
                    'country'    => $row->country,
                    'round_name' => $row->round_name,
                    'gp_date'    => $row->gp_date,
                );
            }
        }
        uasort($gp_map, function($a, $b) {
            return strcmp($a['gp_date'], $b['gp_date']);
        });
        $gp_weeks = array_keys($gp_map);

        // Agrupar puntos por participante y semana
        $by_nick = array();
        foreach ($raw as $row) {
            $by_nick[$row->nick][$row->gp_week] = intval($row->round_points);
        }

        // Calcular acumulados para cada participante
        $series = array();
        foreach ($by_nick as $nick => $pts_by_week) {
            $cumulative = array();
            $total = 0;
            foreach ($gp_weeks as $wk) {
                $total += isset($pts_by_week[$wk]) ? $pts_by_week[$wk] : 0;
                $cumulative[] = $total;
            }
            $series[] = array('nick' => $nick, 'data' => $cumulative);
        }

        // Etiquetas para el eje X (país del GP)
        $labels     = array();
        $full_names = array();
        foreach ($gp_map as $gp) {
            $labels[]     = $gp['country'];
            $full_names[] = 'GP ' . $gp['country'] . ' · ' . date('d/m', strtotime($gp['gp_date']));
        }

        wp_send_json_success(array(
            'labels'     => $labels,
            'full_names' => $full_names,
            'series'     => $series,
        ));
    }


    public function shortcode_chart($atts) {
        ob_start();
        if (file_exists(F1_STARS_PLUGIN_DIR . 'templates/shortcodes/chart.php')) {
            include F1_STARS_PLUGIN_DIR . 'templates/shortcodes/chart.php';
        } else {
            echo '<p>Error: No se encuentra el archivo chart.php</p>';
        }
        return ob_get_clean();
    }

    public function shortcode_bets_admin($atts) {
        ob_start();
        if (file_exists(F1_STARS_PLUGIN_DIR . 'templates/shortcodes/bets-admin.php')) {
            include F1_STARS_PLUGIN_DIR . 'templates/shortcodes/bets-admin.php';
        } else {
            echo '<p>Error: No se encuentra el archivo bets-admin.php</p>';
        }
        return ob_get_clean();
    }

}

F1_Stars_Plugin::get_instance();
