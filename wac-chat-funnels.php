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

// Cargar el autoloader
require_once WAC_CHAT_PLUGIN_DIR . 'includes/class-autoloader.php';

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
    WAC_Chat_Funnels::get_instance();
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
    // Crear tablas personalizadas si es necesario
    WAC_Chat_Database::create_tables();
    
    // Crear roles y capacidades
    WAC_Chat_Roles::create_capabilities();
    
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
    // Limpiar datos si es necesario
    WAC_Chat_Database::drop_tables();
}
