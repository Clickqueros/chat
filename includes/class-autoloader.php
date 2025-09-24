<?php

class WAC_Chat_Autoloader {
    
    private static $classes = array(
        'WAC_Chat_Funnels' => 'class-wac-chat-funnels.php',
        'WAC_Chat_Post_Types' => 'class-post-types.php',
        'WAC_Chat_Roles' => 'class-roles.php',
        'WAC_Chat_Database' => 'class-database.php',
        'WAC_Chat_REST_Controller' => 'class-rest-controller.php',
        'WAC_Chat_Rules' => 'class-rules.php',
        'WAC_Chat_Analytics' => 'class-analytics.php',
        'WAC_Chat_Metaboxes' => 'class-metaboxes.php',
        'WAC_Chat_Admin' => 'class-admin.php',
        'WAC_Chat_Webhooks' => 'class-webhooks.php',
        'WAC_Chat_Leads' => 'class-leads.php'
    );
    
    public static function load_classes() {
        foreach (self::$classes as $class_name => $file_name) {
            $file_path = WAC_CHAT_PLUGIN_DIR . 'includes/' . $file_name;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
}
