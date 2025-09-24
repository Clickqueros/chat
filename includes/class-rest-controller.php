<?php

class WAC_Chat_REST_Controller extends WP_REST_Controller {
    
    protected $namespace = 'wac-chat/v1';
    
    public function register_routes() {
        // Endpoint para obtener configuración de funnels
        register_rest_route($this->namespace, '/funnels/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_funnel'),
            'permission_callback' => array($this, 'get_funnel_permissions_check'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                )
            )
        ));
        
        // Endpoint para obtener analytics
        register_rest_route($this->namespace, '/analytics/(?P<funnel_id>\d+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_analytics'),
            'permission_callback' => array($this, 'analytics_permissions_check')
        ));
        
        // Endpoint para obtener leads
        register_rest_route($this->namespace, '/leads/(?P<funnel_id>\d+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_leads'),
            'permission_callback' => array($this, 'leads_permissions_check')
        ));
        
        // Endpoint para validar YAML
        register_rest_route($this->namespace, '/validate-yaml', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'validate_yaml'),
            'permission_callback' => array($this, 'validate_yaml_permissions_check')
        ));
        
        // Endpoint para preview de funnel
        register_rest_route($this->namespace, '/preview', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'preview_funnel'),
            'permission_callback' => array($this, 'preview_permissions_check')
        ));
    }
    
    public function get_funnel($request) {
        $funnel_id = $request['id'];
        
        $funnel = get_post($funnel_id);
        if (!$funnel || $funnel->post_type !== 'chat_funnel') {
            return new WP_Error('funnel_not_found', __('Funnel no encontrado', 'wac-chat-funnels'), array('status' => 404));
        }
        
        $config = get_post_meta($funnel_id, '_wac_funnel_config', true);
        $rules = get_post_meta($funnel_id, '_wac_funnel_rules', true);
        $teaser = get_post_meta($funnel_id, '_wac_funnel_teaser', true);
        
        $response_data = array(
            'id' => $funnel_id,
            'title' => $funnel->post_title,
            'status' => $funnel->post_status,
            'config' => $config,
            'rules' => $rules,
            'teaser' => $teaser,
            'active' => get_post_meta($funnel_id, '_wac_funnel_active', true) === '1'
        );
        
        return rest_ensure_response($response_data);
    }
    
    public function get_analytics($request) {
        $funnel_id = $request['funnel_id'];
        $days = $request->get_param('days') ?: 30;
        
        $stats = WAC_Chat_Analytics::get_funnel_stats($funnel_id, $days);
        
        return rest_ensure_response($stats);
    }
    
    public function get_leads($request) {
        $funnel_id = $request['funnel_id'];
        $limit = $request->get_param('limit') ?: 50;
        $offset = $request->get_param('offset') ?: 0;
        
        $leads = WAC_Chat_Database::get_leads($funnel_id, $limit, $offset);
        
        return rest_ensure_response($leads);
    }
    
    public function validate_yaml($request) {
        $yaml_content = $request->get_param('yaml');
        
        if (empty($yaml_content)) {
            return new WP_Error('empty_yaml', __('Contenido YAML vacío', 'wac-chat-funnels'), array('status' => 400));
        }
        
        try {
            // Validar YAML básico
            $parsed = yaml_parse($yaml_content);
            
            if ($parsed === false) {
                return new WP_Error('invalid_yaml', __('YAML inválido', 'wac-chat-funnels'), array('status' => 400));
            }
            
            // Validar estructura del funnel
            $validation_result = self::validate_funnel_structure($parsed);
            
            if ($validation_result['valid']) {
                return rest_ensure_response(array(
                    'valid' => true,
                    'message' => __('YAML válido', 'wac-chat-funnels')
                ));
            } else {
                return rest_ensure_response(array(
                    'valid' => false,
                    'message' => $validation_result['message'],
                    'errors' => $validation_result['errors']
                ));
            }
            
        } catch (Exception $e) {
            return new WP_Error('yaml_error', $e->getMessage(), array('status' => 400));
        }
    }
    
    public function preview_funnel($request) {
        $yaml_content = $request->get_param('yaml');
        
        if (empty($yaml_content)) {
            return new WP_Error('empty_yaml', __('Contenido YAML vacío', 'wac-chat-funnels'), array('status' => 400));
        }
        
        try {
            $parsed = yaml_parse($yaml_content);
            
            if ($parsed === false) {
                return new WP_Error('invalid_yaml', __('YAML inválido', 'wac-chat-funnels'), array('status' => 400));
            }
            
            // Generar preview HTML
            $preview_html = self::generate_preview_html($parsed);
            
            return rest_ensure_response(array(
                'html' => $preview_html,
                'config' => $parsed
            ));
            
        } catch (Exception $e) {
            return new WP_Error('preview_error', $e->getMessage(), array('status' => 400));
        }
    }
    
    public function get_funnel_permissions_check($request) {
        // Permitir lectura pública para funnels activos
        return true;
    }
    
    public function analytics_permissions_check($request) {
        return current_user_can('manage_chat_funnels');
    }
    
    public function leads_permissions_check($request) {
        return current_user_can('manage_chat_funnels');
    }
    
    public function validate_yaml_permissions_check($request) {
        return current_user_can('manage_chat_funnels');
    }
    
    public function preview_permissions_check($request) {
        return current_user_can('manage_chat_funnels');
    }
    
    private static function validate_funnel_structure($parsed) {
        $errors = array();
        
        // Verificar estructura básica
        if (!isset($parsed['funnel'])) {
            $errors[] = __('Falta la clave "funnel" en la raíz', 'wac-chat-funnels');
            return array('valid' => false, 'message' => __('Estructura inválida', 'wac-chat-funnels'), 'errors' => $errors);
        }
        
        $funnel = $parsed['funnel'];
        
        // Verificar campos requeridos
        if (!isset($funnel['id'])) {
            $errors[] = __('Falta el campo "id" en el funnel', 'wac-chat-funnels');
        }
        
        if (!isset($funnel['start'])) {
            $errors[] = __('Falta el campo "start" en el funnel', 'wac-chat-funnels');
        }
        
        if (!isset($funnel['nodes'])) {
            $errors[] = __('Falta el campo "nodes" en el funnel', 'wac-chat-funnels');
        } else {
            // Validar nodos
            $nodes = $funnel['nodes'];
            $start_node = $funnel['start'];
            
            if (!isset($nodes[$start_node])) {
                $errors[] = sprintf(__('El nodo de inicio "%s" no existe', 'wac-chat-funnels'), $start_node);
            }
            
            // Validar cada nodo
            foreach ($nodes as $node_id => $node) {
                if (!isset($node['type'])) {
                    $errors[] = sprintf(__('El nodo "%s" no tiene tipo', 'wac-chat-funnels'), $node_id);
                }
                
                // Validar tipos de nodo
                $valid_types = array('message', 'question', 'delay', 'condition', 'action');
                if (isset($node['type']) && !in_array($node['type'], $valid_types)) {
                    $errors[] = sprintf(__('Tipo de nodo inválido "%s" en el nodo "%s"', 'wac-chat-funnels'), $node['type'], $node_id);
                }
            }
        }
        
        return array(
            'valid' => empty($errors),
            'message' => empty($errors) ? __('YAML válido', 'wac-chat-funnels') : __('YAML tiene errores', 'wac-chat-funnels'),
            'errors' => $errors
        );
    }
    
    private static function generate_preview_html($parsed) {
        $funnel = $parsed['funnel'];
        $nodes = $funnel['nodes'];
        $start_node = $funnel['start'];
        
        $html = '<div class="wac-preview-container">';
        $html .= '<h3>' . esc_html($funnel['id']) . '</h3>';
        
        if (isset($nodes[$start_node])) {
            $html .= '<div class="wac-preview-start">';
            $html .= '<strong>Nodo de inicio:</strong> ' . esc_html($start_node);
            $html .= '</div>';
            
            $html .= '<div class="wac-preview-nodes">';
            foreach ($nodes as $node_id => $node) {
                $html .= '<div class="wac-preview-node">';
                $html .= '<h4>' . esc_html($node_id) . ' (' . esc_html($node['type']) . ')</h4>';
                
                if (isset($node['text'])) {
                    $html .= '<p>' . esc_html($node['text']) . '</p>';
                }
                
                if (isset($node['options'])) {
                    $html .= '<ul>';
                    foreach ($node['options'] as $option) {
                        $html .= '<li>' . esc_html($option['label']) . '</li>';
                    }
                    $html .= '</ul>';
                }
                
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
