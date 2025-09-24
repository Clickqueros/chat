<?php

class WAC_Chat_Funnels {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('wp_ajax_wac_chat_track_event', array($this, 'handle_track_event'));
        add_action('wp_ajax_nopriv_wac_chat_track_event', array($this, 'handle_track_event'));
    }
    
    public function init() {
        // Registrar Custom Post Type
        WAC_Chat_Post_Types::register_chat_funnel_cpt();
        
        // Registrar shortcodes
        add_shortcode('chat_funnel', array($this, 'chat_funnel_shortcode'));
        
        // Registrar Gutenberg blocks
        if (function_exists('register_block_type')) {
            register_block_type('wac-chat-funnels/chat-funnel', array(
                'render_callback' => array($this, 'chat_funnel_shortcode')
            ));
        }
    }
    
    public function enqueue_frontend_scripts() {
        // Solo cargar en páginas que tengan funnels activos
        if ($this->should_load_widget()) {
            wp_enqueue_script(
                'wac-chat-widget',
                WAC_CHAT_PLUGIN_URL . 'assets/js/widget.min.js',
                array(),
                WAC_CHAT_VERSION,
                true
            );
            
            wp_localize_script('wac-chat-widget', 'wacChatConfig', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wac_chat_nonce'),
                'apiUrl' => rest_url('wac-chat/v1/'),
                'apiNonce' => wp_create_nonce('wp_rest')
            ));
        }
    }
    
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if ($post_type === 'chat_funnel' || strpos($hook, 'wac-chat') !== false) {
            wp_enqueue_script(
                'wac-chat-admin',
                WAC_CHAT_PLUGIN_URL . 'assets/js/admin.js',
                array('wp-element', 'wp-api-fetch', 'wp-components', 'wp-i18n'),
                WAC_CHAT_VERSION,
                true
            );
            
            wp_enqueue_style(
                'wac-chat-admin',
                WAC_CHAT_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                WAC_CHAT_VERSION
            );
            
            wp_localize_script('wac-chat-admin', 'wacChatAdmin', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wac_chat_admin_nonce'),
                'apiUrl' => rest_url('wac-chat/v1/'),
                'apiNonce' => wp_create_nonce('wp_rest'),
                'strings' => array(
                    'save' => __('Guardar', 'wac-chat-funnels'),
                    'preview' => __('Vista previa', 'wac-chat-funnels'),
                    'test' => __('Probar', 'wac-chat-funnels')
                )
            ));
        }
    }
    
    public function register_rest_routes() {
        $controller = new WAC_Chat_REST_Controller();
        $controller->register_routes();
    }
    
    public function chat_funnel_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'title' => '',
            'position' => 'bottom-right'
        ), $atts);
        
        if (empty($atts['id'])) {
            return '';
        }
        
        $funnel = get_post($atts['id']);
        if (!$funnel || $funnel->post_type !== 'chat_funnel') {
            return '';
        }
        
        $yaml_config = get_post_meta($funnel->ID, '_wac_funnel_config', true);
        $rules = get_post_meta($funnel->ID, '_wac_funnel_rules', true);
        
        // Evaluar reglas de targeting
        if (!WAC_Chat_Rules::should_show_funnel($rules)) {
            return '';
        }
        
        $config = array(
            'funnelId' => $funnel->ID,
            'config' => $yaml_config,
            'position' => $atts['position'],
            'title' => $atts['title'] ?: $funnel->post_title
        );
        
        ob_start();
        ?>
        <div id="wac-chat-funnel-<?php echo esc_attr($funnel->ID); ?>" 
             class="wac-chat-funnel" 
             data-config="<?php echo esc_attr(json_encode($config)); ?>">
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function handle_track_event() {
        check_ajax_referer('wac_chat_nonce', 'nonce');
        
        $event_data = array(
            'funnel_id' => intval($_POST['funnel_id']),
            'session_id' => sanitize_text_field($_POST['session_id']),
            'event_type' => sanitize_text_field($_POST['event_type']),
            'step' => sanitize_text_field($_POST['step']),
            'metadata' => json_decode(stripslashes($_POST['metadata']), true)
        );
        
        WAC_Chat_Analytics::track_event($event_data);
        
        wp_send_json_success();
    }
    
    private function should_load_widget() {
        global $post;
        
        // Verificar si hay funnels activos en esta página
        $active_funnels = get_posts(array(
            'post_type' => 'chat_funnel',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => array(
                array(
                    'key' => '_wac_funnel_active',
                    'value' => '1'
                )
            )
        ));
        
        foreach ($active_funnels as $funnel) {
            $rules = get_post_meta($funnel->ID, '_wac_funnel_rules', true);
            if (WAC_Chat_Rules::should_show_funnel($rules)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function load_dependencies() {
        // Las clases ya están cargadas por el archivo principal
        // No necesitamos cargar nada más aquí
    }
}
