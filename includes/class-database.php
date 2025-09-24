<?php

class WAC_Chat_Database {
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabla de eventos
        $table_events = $wpdb->prefix . 'wac_chat_events';
        $sql_events = "CREATE TABLE $table_events (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            funnel_id bigint(20) NOT NULL,
            session_id varchar(255) NOT NULL,
            event_type varchar(50) NOT NULL,
            step varchar(100) DEFAULT NULL,
            metadata longtext,
            ip_address varchar(45),
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY funnel_id (funnel_id),
            KEY session_id (session_id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Tabla de leads
        $table_leads = $wpdb->prefix . 'wac_chat_leads';
        $sql_leads = "CREATE TABLE $table_leads (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            funnel_id bigint(20) NOT NULL,
            session_id varchar(255) NOT NULL,
            lead_data longtext NOT NULL,
            status varchar(20) DEFAULT 'pending',
            sent_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY funnel_id (funnel_id),
            KEY session_id (session_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_events);
        dbDelta($sql_leads);
        
        // Actualizar versión de la base de datos
        update_option('wac_chat_db_version', WAC_CHAT_VERSION);
    }
    
    public static function drop_tables() {
        global $wpdb;
        
        $table_events = $wpdb->prefix . 'wac_chat_events';
        $table_leads = $wpdb->prefix . 'wac_chat_leads';
        
        $wpdb->query("DROP TABLE IF EXISTS $table_events");
        $wpdb->query("DROP TABLE IF EXISTS $table_leads");
        
        delete_option('wac_chat_db_version');
    }
    
    public static function get_events($funnel_id = null, $limit = 100, $offset = 0) {
        global $wpdb;
        
        $table_events = $wpdb->prefix . 'wac_chat_events';
        
        $where = '';
        $values = array();
        
        if ($funnel_id) {
            $where = 'WHERE funnel_id = %d';
            $values[] = $funnel_id;
        }
        
        $sql = "SELECT * FROM $table_events $where ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $values[] = $limit;
        $values[] = $offset;
        
        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }
        
        return $wpdb->get_results($sql);
    }
    
    public static function get_leads($funnel_id = null, $limit = 100, $offset = 0) {
        global $wpdb;
        
        $table_leads = $wpdb->prefix . 'wac_chat_leads';
        
        $where = '';
        $values = array();
        
        if ($funnel_id) {
            $where = 'WHERE funnel_id = %d';
            $values[] = $funnel_id;
        }
        
        $sql = "SELECT * FROM $table_leads $where ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $values[] = $limit;
        $values[] = $offset;
        
        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }
        
        return $wpdb->get_results($sql);
    }
    
    public static function insert_event($data) {
        global $wpdb;
        
        $table_events = $wpdb->prefix . 'wac_chat_events';
        
        $insert_data = array(
            'funnel_id' => intval($data['funnel_id']),
            'session_id' => sanitize_text_field($data['session_id']),
            'event_type' => sanitize_text_field($data['event_type']),
            'step' => sanitize_text_field($data['step']),
            'metadata' => json_encode($data['metadata']),
            'ip_address' => self::get_client_ip(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '')
        );
        
        return $wpdb->insert($table_events, $insert_data);
    }
    
    public static function insert_lead($data) {
        global $wpdb;
        
        $table_leads = $wpdb->prefix . 'wac_chat_leads';
        
        $insert_data = array(
            'funnel_id' => intval($data['funnel_id']),
            'session_id' => sanitize_text_field($data['session_id']),
            'lead_data' => json_encode($data['lead_data']),
            'status' => 'pending'
        );
        
        return $wpdb->insert($table_leads, $insert_data);
    }
    
    private static function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
