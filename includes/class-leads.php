<?php

class WAC_Chat_Leads {
    
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_leads_menu'));
        add_action('wp_ajax_wac_export_leads', array(__CLASS__, 'export_leads_csv'));
    }
    
    public static function add_leads_menu() {
        add_submenu_page(
            'edit.php?post_type=chat_funnel',
            __('Leads', 'wac-chat-funnels'),
            __('Leads', 'wac-chat-funnels'),
            'manage_chat_funnels',
            'wac-chat-leads',
            array(__CLASS__, 'leads_page')
        );
    }
    
    public static function leads_page() {
        $funnel_id = isset($_GET['funnel_id']) ? intval($_GET['funnel_id']) : null;
        $limit = 50;
        $offset = isset($_GET['paged']) ? (intval($_GET['paged']) - 1) * $limit : 0;
        
        $leads = WAC_Chat_Database::get_leads($funnel_id, $limit, $offset);
        $total_leads = self::get_total_leads_count($funnel_id);
        $total_pages = ceil($total_leads / $limit);
        $current_page = $offset / $limit + 1;
        
        ?>
        <div class="wrap">
            <h1><?php _e('Leads Capturados', 'wac-chat-funnels'); ?></h1>
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <form method="get" action="">
                        <input type="hidden" name="post_type" value="chat_funnel">
                        <input type="hidden" name="page" value="wac-chat-leads">
                        
                        <select name="funnel_id">
                            <option value=""><?php _e('Todos los funnels', 'wac-chat-funnels'); ?></option>
                            <?php
                            $funnels = get_posts(array(
                                'post_type' => 'chat_funnel',
                                'post_status' => 'publish',
                                'numberposts' => -1
                            ));
                            
                            foreach ($funnels as $funnel) {
                                $selected = $funnel_id == $funnel->ID ? 'selected' : '';
                                echo '<option value="' . $funnel->ID . '" ' . $selected . '>' . esc_html($funnel->post_title) . '</option>';
                            }
                            ?>
                        </select>
                        
                        <?php submit_button(__('Filtrar', 'wac-chat-funnels'), 'secondary', 'filter', false); ?>
                    </form>
                </div>
                
                <div class="alignright actions">
                    <a href="<?php echo wp_nonce_url(add_query_arg(array('action' => 'export_leads', 'funnel_id' => $funnel_id)), 'wac_export_leads'); ?>" class="button">
                        <?php _e('Exportar CSV', 'wac-chat-funnels'); ?>
                    </a>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'wac-chat-funnels'); ?></th>
                        <th><?php _e('Funnel', 'wac-chat-funnels'); ?></th>
                        <th><?php _e('Datos del Lead', 'wac-chat-funnels'); ?></th>
                        <th><?php _e('Estado', 'wac-chat-funnels'); ?></th>
                        <th><?php _e('Fecha', 'wac-chat-funnels'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leads)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">
                                <?php _e('No se encontraron leads', 'wac-chat-funnels'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($leads as $lead): ?>
                            <tr>
                                <td><?php echo $lead->id; ?></td>
                                <td>
                                    <?php
                                    if ($lead->funnel_id > 0) {
                                        $funnel = get_post($lead->funnel_id);
                                        if ($funnel) {
                                            echo '<a href="' . get_edit_post_link($lead->funnel_id) . '">' . esc_html($funnel->post_title) . '</a>';
                                        } else {
                                            echo __('Funnel eliminado', 'wac-chat-funnels');
                                        }
                                    } else {
                                        echo __('Webhook externo', 'wac-chat-funnels');
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $lead_data = json_decode($lead->lead_data, true);
                                    if ($lead_data) {
                                        echo '<div class="wac-lead-data">';
                                        foreach ($lead_data as $key => $value) {
                                            if (is_string($value)) {
                                                echo '<strong>' . esc_html($key) . ':</strong> ' . esc_html($value) . '<br>';
                                            }
                                        }
                                        echo '</div>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="wac-status-<?php echo esc_attr($lead->status); ?>">
                                        <?php echo esc_html(ucfirst($lead->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($lead->created_at)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if ($total_pages > 1): ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('« Anterior'),
                            'next_text' => __('Siguiente »'),
                            'total' => $total_pages,
                            'current' => $current_page
                        ));
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
        .wac-lead-data {
            max-width: 300px;
            font-size: 12px;
        }
        
        .wac-status-pending {
            color: #f56e28;
        }
        
        .wac-status-sent {
            color: #46b450;
        }
        
        .wac-status-failed {
            color: #dc3232;
        }
        </style>
        <?php
    }
    
    public static function export_leads_csv() {
        if (!current_user_can('manage_chat_funnels')) {
            wp_die(__('No tienes permisos para realizar esta acción', 'wac-chat-funnels'));
        }
        
        check_admin_referer('wac_export_leads');
        
        $funnel_id = isset($_GET['funnel_id']) ? intval($_GET['funnel_id']) : null;
        $leads = WAC_Chat_Database::get_leads($funnel_id, 10000, 0); // Exportar hasta 10k leads
        
        $filename = 'leads_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, array(
            __('ID', 'wac-chat-funnels'),
            __('Funnel ID', 'wac-chat-funnels'),
            __('Funnel Nombre', 'wac-chat-funnels'),
            __('Session ID', 'wac-chat-funnels'),
            __('Estado', 'wac-chat-funnels'),
            __('Fecha', 'wac-chat-funnels'),
            __('Datos JSON', 'wac-chat-funnels')
        ));
        
        // Datos
        foreach ($leads as $lead) {
            $funnel_name = '';
            if ($lead->funnel_id > 0) {
                $funnel = get_post($lead->funnel_id);
                if ($funnel) {
                    $funnel_name = $funnel->post_title;
                }
            }
            
            fputcsv($output, array(
                $lead->id,
                $lead->funnel_id,
                $funnel_name,
                $lead->session_id,
                $lead->status,
                $lead->created_at,
                $lead->lead_data
            ));
        }
        
        fclose($output);
        exit;
    }
    
    private static function get_total_leads_count($funnel_id = null) {
        global $wpdb;
        
        $table_leads = $wpdb->prefix . 'wac_chat_leads';
        
        if ($funnel_id) {
            return $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_leads WHERE funnel_id = %d",
                $funnel_id
            ));
        } else {
            return $wpdb->get_var("SELECT COUNT(*) FROM $table_leads");
        }
    }
}
