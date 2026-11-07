<!-- ========================================== -->
<!-- templates/admin/participants.php -->
<!-- ========================================== -->
<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap f1-stars-admin">
    <h1><?php _e('Participantes', 'f1-stars'); ?></h1>
    
    <?php
    global $wpdb;
    $table = $wpdb->prefix . 'f1_participants';
    
    // Manejar eliminación
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $wpdb->delete($table, array('id' => $id));
        echo '<div class="notice notice-success"><p>Participante eliminado</p></div>';
    }
    
    // Manejar nuevo participante
    if (isset($_POST['add_participant'])) {
        $nick = sanitize_text_field($_POST['nick']);
        if (!empty($nick)) {
            $wpdb->insert($table, array('nick' => $nick));
            echo '<div class="notice notice-success"><p>Participante añadido</p></div>';
        }
    }
    
    $participants = $wpdb->get_results("SELECT * FROM $table ORDER BY nick ASC");
    ?>
    
    <div class="f1-card">
        <h2>Añadir Participante</h2>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="nick">Nick</label></th>
                    <td>
                        <input type="text" name="nick" id="nick" class="regular-text" required>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="add_participant" class="button button-primary" value="Añadir Participante">
            </p>
        </form>
    </div>
    
    <div class="f1-card">
        <h2>Lista de Participantes</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nick</th>
                    <th>Fecha de Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($participants as $p): ?>
                <tr>
                    <td><?php echo $p->id; ?></td>
                    <td><?php echo esc_html($p->nick); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($p->created_at)); ?></td>
                    <td>
                        <a href="?page=f1-stars-participants&action=delete&id=<?php echo $p->id; ?>" 
                           onclick="return confirm('¿Eliminar este participante?')" 
                           class="button button-small">Eliminar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>