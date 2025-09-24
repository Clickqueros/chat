<?php

class WAC_Chat_Analytics {
    
    public static function track_event($event_data) {
        // Insertar en base de datos local
        $result = WAC_Chat_Database::insert_event($event_data);
        
        // Enviar a Google Analytics si está configurado
        self::send_to_google_analytics($event_data);
        
        // Enviar a Meta Pixel si está configurado
        self::send_to_meta_pixel($event_data);
        
        // Procesar eventos especiales
        self::process_special_events($event_data);
        
        return $result;
    }
    
    private static function send_to_google_analytics($event_data) {
        $ga4_id = get_option('wac_chat_ga4_id');
        if (empty($ga4_id)) {
            return;
        }
        
        // Los eventos se enviarán desde el frontend con gtag
        // Esta función es para referencia futura si necesitamos server-side tracking
    }
    
    private static function send_to_meta_pixel($event_data) {
        $pixel_id = get_option('wac_chat_meta_pixel_id');
        if (empty($pixel_id)) {
            return;
        }
        
        // Los eventos se enviarán desde el frontend con fbq
        // Esta función es para referencia futura si necesitamos server-side tracking
    }
    
    private static function process_special_events($event_data) {
        // Procesar eventos especiales como captura de leads
        if ($event_data['event_type'] === 'lead_captured') {
            self::process_lead_capture($event_data);
        }
        
        // Procesar eventos de WhatsApp
        if ($event_data['event_type'] === 'whatsapp_click') {
            self::process_whatsapp_click($event_data);
        }
    }
    
    private static function process_lead_capture($event_data) {
        // Guardar lead en base de datos
        $lead_data = $event_data['metadata']['lead_data'] ?? array();
        
        if (!empty($lead_data)) {
            WAC_Chat_Database::insert_lead(array(
                'funnel_id' => $event_data['funnel_id'],
                'session_id' => $event_data['session_id'],
                'lead_data' => $lead_data
            ));
            
            // Enviar email si está configurado
            self::send_lead_email($event_data['funnel_id'], $lead_data);
            
            // Enviar webhook si está configurado
            self::send_lead_webhook($event_data['funnel_id'], $lead_data);
        }
    }
    
    private static function process_whatsapp_click($event_data) {
        // Procesar click a WhatsApp (analytics, etc.)
        // Por ahora solo tracking
    }
    
    private static function send_lead_email($funnel_id, $lead_data) {
        $email_config = get_post_meta($funnel_id, '_wac_email_config', true);
        
        if (empty($email_config) || !$email_config['enabled']) {
            return;
        }
        
        $to = $email_config['to'] ?? get_option('admin_email');
        $subject = $email_config['subject'] ?? 'Nuevo lead desde Chat Funnel';
        
        $message = self::build_email_message($lead_data, $email_config);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail($to, $subject, $message, $headers);
    }
    
    private static function send_lead_webhook($funnel_id, $lead_data) {
        $webhook_config = get_post_meta($funnel_id, '_wac_webhook_config', true);
        
        if (empty($webhook_config) || !$webhook_config['enabled'] || empty($webhook_config['url'])) {
            return;
        }
        
        $payload = array(
            'funnel_id' => $funnel_id,
            'lead_data' => $lead_data,
            'timestamp' => current_time('mysql'),
            'source' => 'wac-chat-funnels'
        );
        
        // Agregar firma HMAC si está configurada
        if (!empty($webhook_config['secret'])) {
            $payload['signature'] = hash_hmac('sha256', json_encode($payload), $webhook_config['secret']);
        }
        
        $args = array(
            'body' => json_encode($payload),
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        );
        
        wp_remote_post($webhook_config['url'], $args);
    }
    
    private static function build_email_message($lead_data, $email_config) {
        $template = $email_config['template'] ?? 'default';
        
        $message = '<h2>Nuevo Lead Capturado</h2>';
        $message .= '<table border="1" cellpadding="10" cellspacing="0">';
        
        foreach ($lead_data as $key => $value) {
            $message .= '<tr>';
            $message .= '<td><strong>' . ucfirst($key) . '</strong></td>';
            $message .= '<td>' . esc_html($value) . '</td>';
            $message .= '</tr>';
        }
        
        $message .= '</table>';
        $message .= '<p><small>Generado por WAC Chat Funnels</small></p>';
        
        return $message;
    }
    
    public static function get_funnel_stats($funnel_id, $days = 30) {
        global $wpdb;
        
        $table_events = $wpdb->prefix . 'wac_chat_events';
        $table_leads = $wpdb->prefix . 'wac_chat_leads';
        
        $date_from = date('Y-m-d', strtotime("-{$days} days"));
        
        // Estadísticas básicas
        $stats = array();
        
        // Total de eventos
        $stats['total_events'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_events WHERE funnel_id = %d AND created_at >= %s",
            $funnel_id, $date_from
        ));
        
        // Total de leads
        $stats['total_leads'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_leads WHERE funnel_id = %d AND created_at >= %s",
            $funnel_id, $date_from
        ));
        
        // Eventos por tipo
        $event_types = $wpdb->get_results($wpdb->prepare(
            "SELECT event_type, COUNT(*) as count FROM $table_events 
             WHERE funnel_id = %d AND created_at >= %s 
             GROUP BY event_type",
            $funnel_id, $date_from
        ));
        
        $stats['events_by_type'] = array();
        foreach ($event_types as $event) {
            $stats['events_by_type'][$event->event_type] = intval($event->count);
        }
        
        // Conversión (leads / eventos de apertura)
        $opens = $stats['events_by_type']['chat_open'] ?? 0;
        $stats['conversion_rate'] = $opens > 0 ? round(($stats['total_leads'] / $opens) * 100, 2) : 0;
        
        return $stats;
    }
}
