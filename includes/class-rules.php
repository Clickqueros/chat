<?php

class WAC_Chat_Rules {
    
    public static function should_show_funnel($rules) {
        if (empty($rules) || !is_array($rules)) {
            return true; // Si no hay reglas, mostrar por defecto
        }
        
        $context = self::get_context();
        
        foreach ($rules as $rule) {
            if (self::evaluate_rule($rule, $context)) {
                return $rule['action'] === 'show';
            }
        }
        
        return true; // Por defecto mostrar si no hay reglas que lo impidan
    }
    
    private static function get_context() {
        global $post;
        
        $context = array(
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'full_url' => home_url($_SERVER['REQUEST_URI'] ?? ''),
            'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'post_id' => $post ? $post->ID : 0,
            'post_type' => $post ? $post->post_type : '',
            'categories' => array(),
            'tags' => array(),
            'is_home' => is_home(),
            'is_front_page' => is_front_page(),
            'is_single' => is_single(),
            'is_page' => is_page(),
            'is_archive' => is_archive(),
            'is_search' => is_search(),
            'is_404' => is_404(),
            'time_on_page' => 0, // Se calculará con JavaScript
            'scroll_percentage' => 0, // Se calculará con JavaScript
            'device_type' => self::get_device_type(),
            'language' => get_locale(),
            'hour' => intval(date('H')),
            'day_of_week' => intval(date('w')),
            'utm_source' => $_GET['utm_source'] ?? '',
            'utm_medium' => $_GET['utm_medium'] ?? '',
            'utm_campaign' => $_GET['utm_campaign'] ?? '',
            'utm_content' => $_GET['utm_content'] ?? '',
            'utm_term' => $_GET['utm_term'] ?? ''
        );
        
        // Obtener categorías y tags si es un post
        if ($post && $post->post_type === 'post') {
            $categories = get_the_category($post->ID);
            $context['categories'] = wp_list_pluck($categories, 'slug');
            
            $tags = get_the_tags($post->ID);
            if ($tags) {
                $context['tags'] = wp_list_pluck($tags, 'slug');
            }
        }
        
        return $context;
    }
    
    private static function evaluate_rule($rule, $context) {
        $condition = $rule['condition'] ?? '';
        $operator = $rule['operator'] ?? 'equals';
        $value = $rule['value'] ?? '';
        
        switch ($condition) {
            case 'url_contains':
                return strpos($context['url'], $value) !== false;
                
            case 'url_equals':
                return $context['url'] === $value;
                
            case 'url_regex':
                return preg_match($value, $context['url']);
                
            case 'post_type':
                return $context['post_type'] === $value;
                
            case 'post_id':
                return $context['post_id'] == $value;
                
            case 'category':
                return in_array($value, $context['categories']);
                
            case 'tag':
                return in_array($value, $context['tags']);
                
            case 'is_home':
                return $context['is_home'] === (bool)$value;
                
            case 'is_front_page':
                return $context['is_front_page'] === (bool)$value;
                
            case 'is_single':
                return $context['is_single'] === (bool)$value;
                
            case 'is_page':
                return $context['is_page'] === (bool)$value;
                
            case 'utm_source':
                return $context['utm_source'] === $value;
                
            case 'utm_campaign':
                return $context['utm_campaign'] === $value;
                
            case 'device_type':
                return $context['device_type'] === $value;
                
            case 'language':
                return $context['language'] === $value;
                
            case 'hour':
                return self::evaluate_time_condition($context['hour'], $operator, $value);
                
            case 'day_of_week':
                return self::evaluate_day_condition($context['day_of_week'], $operator, $value);
                
            case 'time_on_page':
                // Se evaluará con JavaScript
                return true;
                
            case 'scroll_percentage':
                // Se evaluará con JavaScript
                return true;
                
            default:
                return true;
        }
    }
    
    private static function evaluate_time_condition($current_hour, $operator, $value) {
        $hours = explode(',', $value);
        
        switch ($operator) {
            case 'in_range':
                if (count($hours) >= 2) {
                    return $current_hour >= intval($hours[0]) && $current_hour <= intval($hours[1]);
                }
                break;
                
            case 'equals':
                return $current_hour == intval($value);
                
            case 'in_list':
                return in_array($current_hour, array_map('intval', $hours));
        }
        
        return false;
    }
    
    private static function evaluate_day_condition($current_day, $operator, $value) {
        $days = explode(',', $value);
        
        switch ($operator) {
            case 'equals':
                return $current_day == intval($value);
                
            case 'in_list':
                return in_array($current_day, array_map('intval', $days));
        }
        
        return false;
    }
    
    private static function get_device_type() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (preg_match('/Mobile|Android|iPhone|iPad/', $user_agent)) {
            return 'mobile';
        }
        
        return 'desktop';
    }
    
    public static function get_rule_types() {
        return array(
            'url_contains' => __('URL contiene', 'wac-chat-funnels'),
            'url_equals' => __('URL es igual a', 'wac-chat-funnels'),
            'url_regex' => __('URL coincide con regex', 'wac-chat-funnels'),
            'post_type' => __('Tipo de contenido', 'wac-chat-funnels'),
            'post_id' => __('ID del post', 'wac-chat-funnels'),
            'category' => __('Categoría', 'wac-chat-funnels'),
            'tag' => __('Etiqueta', 'wac-chat-funnels'),
            'is_home' => __('Es página de inicio', 'wac-chat-funnels'),
            'is_front_page' => __('Es página frontal', 'wac-chat-funnels'),
            'is_single' => __('Es post individual', 'wac-chat-funnels'),
            'is_page' => __('Es página', 'wac-chat-funnels'),
            'utm_source' => __('UTM Source', 'wac-chat-funnels'),
            'utm_campaign' => __('UTM Campaign', 'wac-chat-funnels'),
            'device_type' => __('Tipo de dispositivo', 'wac-chat-funnels'),
            'language' => __('Idioma', 'wac-chat-funnels'),
            'hour' => __('Hora del día', 'wac-chat-funnels'),
            'day_of_week' => __('Día de la semana', 'wac-chat-funnels'),
            'time_on_page' => __('Tiempo en página', 'wac-chat-funnels'),
            'scroll_percentage' => __('Porcentaje de scroll', 'wac-chat-funnels')
        );
    }
}
