<?php

class WAC_Chat_Webhooks {
    
    public static function init() {
        add_action('init', array(__CLASS__, 'register_webhook_endpoints'));
    }
    
    public static function register_webhook_endpoints() {
        add_rewrite_rule('^wac-webhook/([^/]+)/?$', 'index.php?wac_webhook=1&webhook_type=$matches[1]', 'top');
        add_filter('query_vars', array(__CLASS__, 'add_query_vars'));
        add_action('template_redirect', array(__CLASS__, 'handle_webhook'));
    }
    
    public static function add_query_vars($vars) {
        $vars[] = 'wac_webhook';
        $vars[] = 'webhook_type';
        return $vars;
    }
    
    public static function handle_webhook() {
        if (get_query_var('wac_webhook')) {
            $webhook_type = get_query_var('webhook_type');
            
            switch ($webhook_type) {
                case 'zapier':
                    self::handle_zapier_webhook();
                    break;
                    
                case 'make':
                    self::handle_make_webhook();
                    break;
                    
                default:
                    self::handle_generic_webhook();
                    break;
            }
            
            exit;
        }
    }
    
    private static function handle_zapier_webhook() {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            wp_send_json_error('Invalid JSON', 400);
        }
        
        // Procesar datos de Zapier
        $lead_data = array(
            'source' => 'zapier',
            'data' => $data,
            'timestamp' => current_time('mysql')
        );
        
        // Guardar en base de datos
        WAC_Chat_Database::insert_lead(array(
            'funnel_id' => 0, // Webhook externo
            'session_id' => 'webhook_' . uniqid(),
            'lead_data' => $lead_data
        ));
        
        wp_send_json_success('Lead processed');
    }
    
    private static function handle_make_webhook() {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            wp_send_json_error('Invalid JSON', 400);
        }
        
        // Procesar datos de Make (Integromat)
        $lead_data = array(
            'source' => 'make',
            'data' => $data,
            'timestamp' => current_time('mysql')
        );
        
        // Guardar en base de datos
        WAC_Chat_Database::insert_lead(array(
            'funnel_id' => 0, // Webhook externo
            'session_id' => 'webhook_' . uniqid(),
            'lead_data' => $lead_data
        ));
        
        wp_send_json_success('Lead processed');
    }
    
    private static function handle_generic_webhook() {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            wp_send_json_error('Invalid JSON', 400);
        }
        
        // Procesar datos genéricos
        $lead_data = array(
            'source' => 'generic',
            'data' => $data,
            'timestamp' => current_time('mysql')
        );
        
        // Guardar en base de datos
        WAC_Chat_Database::insert_lead(array(
            'funnel_id' => 0, // Webhook externo
            'session_id' => 'webhook_' . uniqid(),
            'lead_data' => $lead_data
        ));
        
        wp_send_json_success('Lead processed');
    }
    
    public static function send_webhook($url, $data, $secret = '') {
        $payload = json_encode($data);
        
        $args = array(
            'body' => $payload,
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        );
        
        // Agregar firma HMAC si se proporciona secret
        if (!empty($secret)) {
            $signature = hash_hmac('sha256', $payload, $secret);
            $args['headers']['X-WAC-Signature'] = 'sha256=' . $signature;
        }
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        return wp_remote_retrieve_response_code($response) === 200;
    }
    
    public static function get_webhook_url($type = 'generic') {
        return home_url("wac-webhook/{$type}/");
    }
}
