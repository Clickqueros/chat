<?php
/**
 * Plugin Name: WAC Chat Funnels
 * Plugin URI: https://wacosta.com/wac-chat-funnels
 * Description: Sistema de chat funnels conversacionales para capturar leads y dirigir a WhatsApp
 * Version: 1.0.0
 * Author: WACosta
 * License: GPL v2 or later
 * Text Domain: wac-chat-funnels
 * Domain Path: /languages
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('WAC_CHAT_VERSION', '1.0.0');
define('WAC_CHAT_PLUGIN_FILE', __FILE__);
define('WAC_CHAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WAC_CHAT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WAC_CHAT_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Función para cargar clases
function wac_chat_funnels_load_classes() {
    $classes = array(
        'WAC_Chat_Autoloader' => 'class-autoloader.php',
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

    foreach ($classes as $class_name => $file_name) {
        $file_path = WAC_CHAT_PLUGIN_DIR . 'includes/' . $file_name;
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
}

// Cargar clases
wac_chat_funnels_load_classes();

// Inicializar el plugin
function wac_chat_funnels_init() {
    // Verificar versión mínima de WordPress
    if (version_compare(get_bloginfo('version'), '6.0', '<')) {
        add_action('admin_notices', 'wac_chat_funnels_version_notice');
        return;
    }

    // Verificar versión mínima de PHP
    if (version_compare(PHP_VERSION, '8.1', '<')) {
        add_action('admin_notices', 'wac_chat_funnels_php_notice');
        return;
    }

    // Inicializar el plugin principal
    if (class_exists('WAC_Chat_Funnels')) {
        WAC_Chat_Funnels::get_instance();
    }
}
add_action('plugins_loaded', 'wac_chat_funnels_init');

// Avisos de compatibilidad
function wac_chat_funnels_version_notice() {
    echo '<div class="notice notice-error"><p>';
    echo '<strong>WAC Chat Funnels</strong> requiere WordPress 6.0 o superior.';
    echo '</p></div>';
}

function wac_chat_funnels_php_notice() {
    echo '<div class="notice notice-error"><p>';
    echo '<strong>WAC Chat Funnels</strong> requiere PHP 8.1 o superior.';
    echo '</p></div>';
}

// Hook de activación
register_activation_hook(__FILE__, 'wac_chat_funnels_activate');
function wac_chat_funnels_activate() {
    // Cargar clases necesarias
    wac_chat_funnels_load_classes();
    
    // Crear tablas personalizadas si es necesario
    if (class_exists('WAC_Chat_Database')) {
        WAC_Chat_Database::create_tables();
    }
    
    // Crear roles y capacidades
    if (class_exists('WAC_Chat_Roles')) {
        WAC_Chat_Roles::create_capabilities();
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Hook de desactivación
register_deactivation_hook(__FILE__, 'wac_chat_funnels_deactivate');
function wac_chat_funnels_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Hook de desinstalación
register_uninstall_hook(__FILE__, 'wac_chat_funnels_uninstall');
function wac_chat_funnels_uninstall() {
    // Cargar clases necesarias
    wac_chat_funnels_load_classes();
    
    // Limpiar datos si es necesario
    if (class_exists('WAC_Chat_Database')) {
        WAC_Chat_Database::drop_tables();
    }
}
