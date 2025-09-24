<?php
/**
 * Plugin Name: WAC Chat Funnels
 * Plugin URI: https://wacosta.com/wac-chat-funnels
 * Description: Sistema simple de chat funnels para capturar leads y dirigir a WhatsApp
 * Version: 2.0.0
 * Author: WACosta
 * License: GPL v2 or later
 * Text Domain: wac-chat-funnels
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('WAC_CHAT_VERSION', '2.0.0');
define('WAC_CHAT_PLUGIN_FILE', __FILE__);
define('WAC_CHAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WAC_CHAT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WAC_CHAT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Clase principal del plugin - SIMPLE y FUNCIONAL
 */
class WAC_Chat_Funnels {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('wp_footer', array($this, 'render_chat_widget'));
        
        // Hooks de activación/desactivación
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Registrar Custom Post Type para funnels
        $this->register_post_type();
        
        // Agregar metaboxes
        add_action('add_meta_boxes', array($this, 'add_metaboxes'));
        add_action('save_post', array($this, 'save_metaboxes'));
        
        // Agregar menú de admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    public function register_post_type() {
        $labels = array(
            'name' => __('Chat Funnels', 'wac-chat-funnels'),
            'singular_name' => __('Chat Funnel', 'wac-chat-funnels'),
            'menu_name' => __('Chat Funnels', 'wac-chat-funnels'),
            'add_new' => __('Agregar Nuevo', 'wac-chat-funnels'),
            'add_new_item' => __('Agregar Nuevo Funnel', 'wac-chat-funnels'),
            'edit_item' => __('Editar Funnel', 'wac-chat-funnels'),
            'new_item' => __('Nuevo Funnel', 'wac-chat-funnels'),
            'view_item' => __('Ver Funnel', 'wac-chat-funnels'),
            'search_items' => __('Buscar Funnels', 'wac-chat-funnels'),
            'not_found' => __('No se encontraron funnels', 'wac-chat-funnels'),
            'not_found_in_trash' => __('No se encontraron funnels en la papelera', 'wac-chat-funnels')
        );
        
        $args = array(
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => false,
            'show_in_rest' => false,
            'query_var' => false,
            'rewrite' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-format-chat',
            'supports' => array('title', 'editor')
        );
        
        register_post_type('wac_chat_funnel', $args);
    }
    
    public function add_metaboxes() {
        add_meta_box(
            'wac-funnel-config',
            __('Configuración del Funnel', 'wac-chat-funnels'),
            array($this, 'funnel_config_metabox'),
            'wac_chat_funnel',
            'normal',
            'high'
        );
    }
    
    public function funnel_config_metabox($post) {
        wp_nonce_field('wac_funnel_config', 'wac_funnel_config_nonce');
        
        $enabled = get_post_meta($post->ID, '_wac_funnel_enabled', true);
        $teaser_text = get_post_meta($post->ID, '_wac_funnel_teaser_text', true);
        $teaser_delay = get_post_meta($post->ID, '_wac_funnel_teaser_delay', true);
        $whatsapp_number = get_post_meta($post->ID, '_wac_funnel_whatsapp_number', true);
        
        // Valores por defecto
        if (empty($teaser_text)) $teaser_text = '¿Necesitas ayuda?';
        if (empty($teaser_delay)) $teaser_delay = 3000;
        if (empty($whatsapp_number)) $whatsapp_number = '+573154543344';
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="wac_funnel_enabled"><?php _e('Estado', 'wac-chat-funnels'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="wac_funnel_enabled" name="wac_funnel_enabled" value="1" <?php checked($enabled, '1'); ?>>
                    <label for="wac_funnel_enabled"><?php _e('Activar este funnel', 'wac-chat-funnels'); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wac_funnel_teaser_text"><?php _e('Texto del Teaser', 'wac-chat-funnels'); ?></label>
                </th>
                <td>
                    <input type="text" id="wac_funnel_teaser_text" name="wac_funnel_teaser_text" value="<?php echo esc_attr($teaser_text); ?>" class="regular-text">
                    <p class="description"><?php _e('Texto que aparece en la burbuja inicial', 'wac-chat-funnels'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wac_funnel_teaser_delay"><?php _e('Retraso del Teaser (ms)', 'wac-chat-funnels'); ?></label>
                </th>
                <td>
                    <input type="number" id="wac_funnel_teaser_delay" name="wac_funnel_teaser_delay" value="<?php echo esc_attr($teaser_delay); ?>" min="0" max="30000" step="500">
                    <p class="description"><?php _e('Tiempo antes de mostrar el teaser', 'wac-chat-funnels'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wac_funnel_whatsapp_number"><?php _e('Número de WhatsApp', 'wac-chat-funnels'); ?></label>
                </th>
                <td>
                    <input type="text" id="wac_funnel_whatsapp_number" name="wac_funnel_whatsapp_number" value="<?php echo esc_attr($whatsapp_number); ?>" class="regular-text">
                    <p class="description"><?php _e('Número completo con código de país (ej: +573154543344)', 'wac-chat-funnels'); ?></p>
                </td>
            </tr>
        </table>
        
        <div style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 5px;">
            <h4>🎯 Vista Previa</h4>
            <p>Guarda el funnel y ve a tu sitio web para ver el chat en acción.</p>
            <p><strong>Mensajes por defecto:</strong></p>
            <ul>
                <li>📝 Bienvenida: "¡Hola! Soy tu asistente virtual. ¿En qué puedo ayudarte?"</li>
                <li>🔘 Opciones: Cotización | WhatsApp | Ver portafolio</li>
                <li>📋 Formulario: Nombre, Email, Teléfono</li>
            </ul>
        </div>
        <?php
    }
    
    public function save_metaboxes($post_id) {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (isset($_POST['wac_funnel_config_nonce']) && wp_verify_nonce($_POST['wac_funnel_config_nonce'], 'wac_funnel_config')) {
            update_post_meta($post_id, '_wac_funnel_enabled', isset($_POST['wac_funnel_enabled']) ? '1' : '0');
            update_post_meta($post_id, '_wac_funnel_teaser_text', sanitize_text_field($_POST['wac_funnel_teaser_text']));
            update_post_meta($post_id, '_wac_funnel_teaser_delay', intval($_POST['wac_funnel_teaser_delay']));
            update_post_meta($post_id, '_wac_funnel_whatsapp_number', sanitize_text_field($_POST['wac_funnel_whatsapp_number']));
        }
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=wac_chat_funnel',
            __('Configuración', 'wac-chat-funnels'),
            __('Configuración', 'wac-chat-funnels'),
            'manage_options',
            'wac-chat-config',
            array($this, 'admin_config_page')
        );
    }
    
    public function admin_config_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Configuración de Chat Funnels', 'wac-chat-funnels'); ?></h1>
            <div class="card">
                <h2>✅ Plugin Instalado Correctamente</h2>
                <p>El plugin está funcionando. Para crear un funnel:</p>
                <ol>
                    <li>Ve a <strong>Chat Funnels → Agregar Nuevo</strong></li>
                    <li>Configura el funnel</li>
                    <li>Guarda y ve a tu sitio web</li>
                </ol>
                <p><a href="<?php echo admin_url('post-new.php?post_type=wac_chat_funnel'); ?>" class="button button-primary">Crear Primer Funnel</a></p>
            </div>
        </div>
        <?php
    }
    
    public function enqueue_frontend_scripts() {
        wp_enqueue_style('wac-chat-funnels', WAC_CHAT_PLUGIN_URL . 'assets/css/chat-widget.css', array(), WAC_CHAT_VERSION);
        wp_enqueue_script('wac-chat-funnels', WAC_CHAT_PLUGIN_URL . 'assets/js/chat-widget.js', array(), WAC_CHAT_VERSION, true);
    }
    
    public function render_chat_widget() {
        // Obtener funnel activo
        $funnels = get_posts(array(
            'post_type' => 'wac_chat_funnel',
            'meta_query' => array(
                array(
                    'key' => '_wac_funnel_enabled',
                    'value' => '1',
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        ));
        
        if (empty($funnels)) {
            return;
        }
        
        $funnel = $funnels[0];
        $teaser_text = get_post_meta($funnel->ID, '_wac_funnel_teaser_text', true);
        $teaser_delay = get_post_meta($funnel->ID, '_wac_funnel_teaser_delay', true);
        $whatsapp_number = get_post_meta($funnel->ID, '_wac_funnel_whatsapp_number', true);
        
        if (empty($teaser_text)) $teaser_text = '¿Necesitas ayuda?';
        if (empty($teaser_delay)) $teaser_delay = 3000;
        if (empty($whatsapp_number)) $whatsapp_number = '+573154543344';
        
        ?>
        <div id="wac-chat-widget" data-delay="<?php echo esc_attr($teaser_delay); ?>" data-whatsapp="<?php echo esc_attr($whatsapp_number); ?>" style="display: none;">
            <div id="wac-chat-teaser">
                <span id="wac-chat-teaser-text"><?php echo esc_html($teaser_text); ?></span>
            </div>
            <div id="wac-chat-container" style="display: none;">
                <div id="wac-chat-header">
                    <span id="wac-chat-title">Asistente Virtual</span>
                    <button id="wac-chat-close">×</button>
                </div>
                <div id="wac-chat-messages"></div>
                <div id="wac-chat-input" style="display: none;">
                    <input type="text" id="wac-chat-field" placeholder="Escribe aquí...">
                    <button id="wac-chat-send">Enviar</button>
                </div>
                <div id="wac-chat-buttons"></div>
            </div>
        </div>
        <?php
    }
    
    public function activate() {
        // Crear tablas si es necesario
        $this->create_tables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wac_chat_leads';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            funnel_id bigint(20) NOT NULL,
            lead_data longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Inicializar el plugin
WAC_Chat_Funnels::get_instance();
