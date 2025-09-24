<?php

class WAC_Chat_Metaboxes {
    
    public static function init() {
        add_action('add_meta_boxes', array(__CLASS__, 'add_metaboxes'));
        add_action('save_post', array(__CLASS__, 'save_metaboxes'));
    }
    
    public static function add_metaboxes() {
        add_meta_box(
            'wac-funnel-integrations',
            __('Integraciones', 'wac-chat-funnels'),
            array(__CLASS__, 'integrations_metabox'),
            'chat_funnel',
            'side',
            'default'
        );
    }
    
    public static function integrations_metabox($post) {
        wp_nonce_field('wac_funnel_integrations', 'wac_funnel_integrations_nonce');
        
        $email_config = get_post_meta($post->ID, '_wac_email_config', true);
        $webhook_config = get_post_meta($post->ID, '_wac_webhook_config', true);
        
        if (empty($email_config)) {
            $email_config = array(
                'enabled' => false,
                'to' => get_option('admin_email'),
                'subject' => 'Nuevo lead desde Chat Funnel',
                'template' => 'default'
            );
        }
        
        if (empty($webhook_config)) {
            $webhook_config = array(
                'enabled' => false,
                'url' => '',
                'secret' => ''
            );
        }
        
        ?>
        <h4><?php _e('Configuración de Email', 'wac-chat-funnels'); ?></h4>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enviar Email', 'wac-chat-funnels'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wac_email_enabled" value="1" <?php checked($email_config['enabled'], true); ?>>
                        <?php _e('Enviar email cuando se capture un lead', 'wac-chat-funnels'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Email Destino', 'wac-chat-funnels'); ?></th>
                <td>
                    <input type="email" name="wac_email_to" value="<?php echo esc_attr($email_config['to']); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Asunto', 'wac-chat-funnels'); ?></th>
                <td>
                    <input type="text" name="wac_email_subject" value="<?php echo esc_attr($email_config['subject']); ?>" class="regular-text">
                </td>
            </tr>
        </table>
        
        <h4><?php _e('Configuración de Webhook', 'wac-chat-funnels'); ?></h4>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enviar Webhook', 'wac-chat-funnels'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wac_webhook_enabled" value="1" <?php checked($webhook_config['enabled'], true); ?>>
                        <?php _e('Enviar webhook cuando se capture un lead', 'wac-chat-funnels'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('URL del Webhook', 'wac-chat-funnels'); ?></th>
                <td>
                    <input type="url" name="wac_webhook_url" value="<?php echo esc_attr($webhook_config['url']); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Secret (HMAC)', 'wac-chat-funnels'); ?></th>
                <td>
                    <input type="text" name="wac_webhook_secret" value="<?php echo esc_attr($webhook_config['secret']); ?>" class="regular-text">
                    <p class="description"><?php _e('Opcional: para firmar las peticiones', 'wac-chat-funnels'); ?></p>
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
        
        // Guardar configuración de integraciones
        if (isset($_POST['wac_funnel_integrations_nonce']) && wp_verify_nonce($_POST['wac_funnel_integrations_nonce'], 'wac_funnel_integrations')) {
            
            // Configuración de email
            $email_config = array(
                'enabled' => isset($_POST['wac_email_enabled']),
                'to' => sanitize_email($_POST['wac_email_to']),
                'subject' => sanitize_text_field($_POST['wac_email_subject'])
            );
            update_post_meta($post_id, '_wac_email_config', $email_config);
            
            // Configuración de webhook
            $webhook_config = array(
                'enabled' => isset($_POST['wac_webhook_enabled']),
                'url' => esc_url_raw($_POST['wac_webhook_url']),
                'secret' => sanitize_text_field($_POST['wac_webhook_secret'])
            );
            update_post_meta($post_id, '_wac_webhook_config', $webhook_config);
        }
    }
}
