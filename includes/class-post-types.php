<?php

class WAC_Chat_Post_Types {
    
    public static function register_chat_funnel_cpt() {
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
            'show_in_rest' => true,
            'query_var' => false,
            'rewrite' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-format-chat',
            'supports' => array('title', 'editor', 'revisions'),
            'capabilities' => array(
                'edit_post' => 'manage_chat_funnels',
                'read_post' => 'manage_chat_funnels',
                'delete_post' => 'manage_chat_funnels',
                'edit_posts' => 'manage_chat_funnels',
                'edit_others_posts' => 'manage_chat_funnels',
                'publish_posts' => 'manage_chat_funnels',
                'read_private_posts' => 'manage_chat_funnels'
            )
        );
        
        register_post_type('chat_funnel', $args);
        
        // Registrar metaboxes
        add_action('add_meta_boxes', array(__CLASS__, 'add_metaboxes'));
        add_action('save_post', array(__CLASS__, 'save_metaboxes'));
    }
    
    public static function add_metaboxes() {
        add_meta_box(
            'wac-funnel-config',
            __('Configuración del Funnel', 'wac-chat-funnels'),
            array(__CLASS__, 'funnel_config_metabox'),
            'chat_funnel',
            'normal',
            'high'
        );
        
        add_meta_box(
            'wac-funnel-rules',
            __('Reglas de Targeting', 'wac-chat-funnels'),
            array(__CLASS__, 'funnel_rules_metabox'),
            'chat_funnel',
            'side',
            'default'
        );
        
        add_meta_box(
            'wac-funnel-settings',
            __('Configuración General', 'wac-chat-funnels'),
            array(__CLASS__, 'funnel_settings_metabox'),
            'chat_funnel',
            'side',
            'default'
        );
    }
    
    public static function funnel_config_metabox($post) {
        wp_nonce_field('wac_funnel_config', 'wac_funnel_config_nonce');
        
        $config = get_post_meta($post->ID, '_wac_funnel_config', true);
        if (empty($config)) {
            $config = self::get_default_funnel_config();
        }
        
        ?>
        <div id="wac-funnel-editor">
            <div id="wac-yaml-editor" style="height: 400px; border: 1px solid #ddd;"></div>
            <textarea id="wac-funnel-config" name="wac_funnel_config" style="display: none;"><?php echo esc_textarea($config); ?></textarea>
        </div>
        
        <div id="wac-funnel-preview" style="margin-top: 20px;">
            <h3><?php _e('Vista Previa', 'wac-chat-funnels'); ?></h3>
            <div id="wac-chat-preview" style="border: 1px solid #ddd; height: 500px; background: #f9f9f9;"></div>
        </div>
        <?php
    }
    
    public static function funnel_rules_metabox($post) {
        wp_nonce_field('wac_funnel_rules', 'wac_funnel_rules_nonce');
        
        $rules = get_post_meta($post->ID, '_wac_funnel_rules', true);
        if (empty($rules)) {
            $rules = array();
        }
        
        ?>
        <div id="wac-rules-builder">
            <p><?php _e('Configura cuándo mostrar este funnel:', 'wac-chat-funnels'); ?></p>
            
            <div id="wac-rules-list">
                <!-- Las reglas se cargarán dinámicamente con JavaScript -->
            </div>
            
            <button type="button" id="wac-add-rule" class="button"><?php _e('Agregar Regla', 'wac-chat-funnels'); ?></button>
            
            <textarea id="wac-funnel-rules" name="wac_funnel_rules" style="display: none;"><?php echo esc_textarea(json_encode($rules)); ?></textarea>
        </div>
        <?php
    }
    
    public static function funnel_settings_metabox($post) {
        wp_nonce_field('wac_funnel_settings', 'wac_funnel_settings_nonce');
        
        $active = get_post_meta($post->ID, '_wac_funnel_active', true);
        $priority = get_post_meta($post->ID, '_wac_funnel_priority', true);
        $teaser_config = get_post_meta($post->ID, '_wac_funnel_teaser', true);
        
        if (empty($priority)) {
            $priority = 10;
        }
        
        if (empty($teaser_config)) {
            $teaser_config = array(
                'enabled' => true,
                'delay' => 3000,
                'text' => '¿Necesitas ayuda?',
                'icon' => 'chat'
            );
        }
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Estado', 'wac-chat-funnels'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wac_funnel_active" value="1" <?php checked($active, '1'); ?>>
                        <?php _e('Activo', 'wac-chat-funnels'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Prioridad', 'wac-chat-funnels'); ?></th>
                <td>
                    <input type="number" name="wac_funnel_priority" value="<?php echo esc_attr($priority); ?>" min="1" max="100">
                    <p class="description"><?php _e('Mayor número = mayor prioridad', 'wac-chat-funnels'); ?></p>
                </td>
            </tr>
        </table>
        
        <h4><?php _e('Configuración del Teaser', 'wac-chat-funnels'); ?></h4>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Mostrar Teaser', 'wac-chat-funnels'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wac_teaser_enabled" value="1" <?php checked($teaser_config['enabled'], true); ?>>
                        <?php _e('Mostrar burbuja teaser', 'wac-chat-funnels'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Delay (ms)', 'wac-chat-funnels'); ?></th>
                <td>
                    <input type="number" name="wac_teaser_delay" value="<?php echo esc_attr($teaser_config['delay']); ?>" min="0" max="30000">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Texto del Teaser', 'wac-chat-funnels'); ?></th>
                <td>
                    <input type="text" name="wac_teaser_text" value="<?php echo esc_attr($teaser_config['text']); ?>" class="regular-text">
                </td>
            </tr>
        </table>
        <?php
    }
    
    public static function save_metaboxes($post_id) {
        // Verificar nonces y permisos
        if (!current_user_can('manage_chat_funnels')) {
            return;
        }
        
        // Guardar configuración del funnel
        if (isset($_POST['wac_funnel_config_nonce']) && wp_verify_nonce($_POST['wac_funnel_config_nonce'], 'wac_funnel_config')) {
            update_post_meta($post_id, '_wac_funnel_config', sanitize_textarea_field($_POST['wac_funnel_config']));
        }
        
        // Guardar reglas
        if (isset($_POST['wac_funnel_rules_nonce']) && wp_verify_nonce($_POST['wac_funnel_rules_nonce'], 'wac_funnel_rules')) {
            $rules = json_decode(stripslashes($_POST['wac_funnel_rules']), true);
            update_post_meta($post_id, '_wac_funnel_rules', $rules);
        }
        
        // Guardar configuración general
        if (isset($_POST['wac_funnel_settings_nonce']) && wp_verify_nonce($_POST['wac_funnel_settings_nonce'], 'wac_funnel_settings')) {
            update_post_meta($post_id, '_wac_funnel_active', isset($_POST['wac_funnel_active']) ? '1' : '0');
            update_post_meta($post_id, '_wac_funnel_priority', intval($_POST['wac_funnel_priority']));
            
            $teaser_config = array(
                'enabled' => isset($_POST['wac_teaser_enabled']),
                'delay' => intval($_POST['wac_teaser_delay']),
                'text' => sanitize_text_field($_POST['wac_teaser_text'])
            );
            update_post_meta($post_id, '_wac_funnel_teaser', $teaser_config);
        }
    }
    
    private static function get_default_funnel_config() {
        return <<<YAML
funnel:
  id: "lead_basico"
  start: "intro"
  nodes:
    intro:
      type: message
      text: |
        ¡Hola! Soy **Asistente WACosta** 👋
        ¿En qué puedo ayudarte hoy?
      next: menu

    menu:
      type: question
      style: choice
      options:
        - label: "Quiero cotización"
          next: form_nombre
        - label: "Ver portafolio"
          action: redirect
          url: "/portafolio"
        - label: "Hablar por WhatsApp"
          action: whatsapp
          phone: "+573154543344"
          prefill: "Hola, quiero una asesoría."

    form_nombre:
      type: question
      style: input
      validation: "name"
      store_as: "nombre"
      text: "¿Cuál es tu nombre?"
      next: form_email

    form_email:
      type: question
      style: input
      validation: "email"
      store_as: "email"
      text: "¿Cuál es tu email?"
      next: gracias

    gracias:
      type: message
      text: "¡Gracias {{nombre}}! Te escribo al correo {{email}} en breve."
      action: event
      event_name: "lead_capturado"
YAML;
    }
}
