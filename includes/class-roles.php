<?php

class WAC_Chat_Roles {
    
    public static function create_capabilities() {
        // Obtener el rol de administrador
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('manage_chat_funnels');
            $admin_role->add_cap('edit_chat_funnels');
            $admin_role->add_cap('delete_chat_funnels');
            $admin_role->add_cap('publish_chat_funnels');
            $admin_role->add_cap('read_private_chat_funnels');
        }
        
        // Crear rol personalizado para gestores de chat
        add_role('chat_manager', __('Gestor de Chat', 'wac-chat-funnels'), array(
            'read' => true,
            'manage_chat_funnels' => true,
            'edit_chat_funnels' => true,
            'delete_chat_funnels' => true,
            'publish_chat_funnels' => true,
            'read_private_chat_funnels' => true,
            'upload_files' => true,
            'edit_posts' => true,
            'publish_posts' => true
        ));
    }
    
    public static function remove_capabilities() {
        // Remover capacidades del rol administrador
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->remove_cap('manage_chat_funnels');
            $admin_role->remove_cap('edit_chat_funnels');
            $admin_role->remove_cap('delete_chat_funnels');
            $admin_role->remove_cap('publish_chat_funnels');
            $admin_role->remove_cap('read_private_chat_funnels');
        }
        
        // Remover rol personalizado
        remove_role('chat_manager');
    }
}
