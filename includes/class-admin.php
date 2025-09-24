<?php

class WAC_Chat_Admin {
    
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
    }
    
    public static function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=chat_funnel',
            __('Analytics', 'wac-chat-funnels'),
            __('Analytics', 'wac-chat-funnels'),
            'manage_chat_funnels',
            'wac-chat-analytics',
            array(__CLASS__, 'analytics_page')
        );
        
        add_submenu_page(
            'edit.php?post_type=chat_funnel',
            __('Configuración', 'wac-chat-funnels'),
            __('Configuración', 'wac-chat-funnels'),
            'manage_chat_funnels',
            'wac-chat-settings',
            array(__CLASS__, 'settings_page')
        );
    }
    
    public static function register_settings() {
        register_setting('wac_chat_settings', 'wac_chat_ga4_id');
        register_setting('wac_chat_settings', 'wac_chat_meta_pixel_id');
        register_setting('wac_chat_settings', 'wac_chat_global_teaser');
    }
    
    public static function analytics_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Analytics de Chat Funnels', 'wac-chat-funnels'); ?></h1>
            
            <div id="wac-analytics-dashboard">
                <div class="wac-stats-grid">
                    <div class="wac-stat-card">
                        <h3><?php _e('Funnels Activos', 'wac-chat-funnels'); ?></h3>
                        <p class="wac-stat-number"><?php echo self::get_active_funnels_count(); ?></p>
                    </div>
                    
                    <div class="wac-stat-card">
                        <h3><?php _e('Leads Totales (30 días)', 'wac-chat-funnels'); ?></h3>
                        <p class="wac-stat-number"><?php echo self::get_total_leads_count(); ?></p>
                    </div>
                    
                    <div class="wac-stat-card">
                        <h3><?php _e('Eventos Totales (30 días)', 'wac-chat-funnels'); ?></h3>
                        <p class="wac-stat-number"><?php echo self::get_total_events_count(); ?></p>
                    </div>
                </div>
                
                <div class="wac-funnels-list">
                    <h2><?php _e('Rendimiento por Funnel', 'wac-chat-funnels'); ?></h2>
                    <?php self::render_funnels_performance(); ?>
                </div>
            </div>
        </div>
        
        <style>
        .wac-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .wac-stat-card {
            background: #fff;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 4px;
            text-align: center;
        }
        
        .wac-stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #0073aa;
            margin: 10px 0;
        }
        
        .wac-funnels-list table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .wac-funnels-list th,
        .wac-funnels-list td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        
        .wac-funnels-list th {
            background: #f9f9f9;
        }
        </style>
        <?php
    }
    
    public static function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Configuración de WAC Chat Funnels', 'wac-chat-funnels'); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('wac_chat_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Google Analytics 4 ID', 'wac-chat-funnels'); ?></th>
                        <td>
                            <input type="text" name="wac_chat_ga4_id" value="<?php echo esc_attr(get_option('wac_chat_ga4_id')); ?>" class="regular-text">
                            <p class="description"><?php _e('Ejemplo: G-XXXXXXXXXX', 'wac-chat-funnels'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Meta Pixel ID', 'wac-chat-funnels'); ?></th>
                        <td>
                            <input type="text" name="wac_chat_meta_pixel_id" value="<?php echo esc_attr(get_option('wac_chat_meta_pixel_id')); ?>" class="regular-text">
                            <p class="description"><?php _e('ID numérico del pixel de Facebook', 'wac-chat-funnels'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    private static function get_active_funnels_count() {
        $count = get_posts(array(
            'post_type' => 'chat_funnel',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => array(
                array(
                    'key' => '_wac_funnel_active',
                    'value' => '1'
                )
            ),
            'fields' => 'ids'
        ));
        
        return count($count);
    }
    
    private static function get_total_leads_count() {
        global $wpdb;
        
        $table_leads = $wpdb->prefix . 'wac_chat_leads';
        $date_from = date('Y-m-d', strtotime('-30 days'));
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_leads WHERE created_at >= %s",
            $date_from
        ));
    }
    
    private static function get_total_events_count() {
        global $wpdb;
        
        $table_events = $wpdb->prefix . 'wac_chat_events';
        $date_from = date('Y-m-d', strtotime('-30 days'));
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_events WHERE created_at >= %s",
            $date_from
        ));
    }
    
    private static function render_funnels_performance() {
        $funnels = get_posts(array(
            'post_type' => 'chat_funnel',
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Funnel', 'wac-chat-funnels'); ?></th>
                    <th><?php _e('Estado', 'wac-chat-funnels'); ?></th>
                    <th><?php _e('Leads (30 días)', 'wac-chat-funnels'); ?></th>
                    <th><?php _e('Eventos (30 días)', 'wac-chat-funnels'); ?></th>
                    <th><?php _e('Conversión', 'wac-chat-funnels'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($funnels as $funnel): ?>
                    <?php $stats = WAC_Chat_Analytics::get_funnel_stats($funnel->ID, 30); ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($funnel->post_title); ?></strong>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo get_edit_post_link($funnel->ID); ?>"><?php _e('Editar', 'wac-chat-funnels'); ?></a>
                                </span>
                            </div>
                        </td>
                        <td>
                            <?php 
                            $active = get_post_meta($funnel->ID, '_wac_funnel_active', true);
                            echo $active === '1' ? '<span style="color: green;">●</span> ' . __('Activo', 'wac-chat-funnels') : '<span style="color: red;">●</span> ' . __('Inactivo', 'wac-chat-funnels');
                            ?>
                        </td>
                        <td><?php echo intval($stats['total_leads']); ?></td>
                        <td><?php echo intval($stats['total_events']); ?></td>
                        <td><?php echo $stats['conversion_rate']; ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
}
