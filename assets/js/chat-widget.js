/**
 * WAC Chat Funnels - Widget JavaScript
 * Sistema simple y funcional de chat funnels
 */

(function() {
    'use strict';
    
    // Variables globales
    let chatWidget = null;
    let isOpen = false;
    
    // Inicializar cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        initChatWidget();
    });
    
    function initChatWidget() {
        chatWidget = document.getElementById('wac-chat-widget');
        if (!chatWidget) return;
        
        console.log('WAC Chat Widget inicializado - Versión limpia');
        
        // Por ahora, solo mostrar mensaje de que está en desarrollo
        const widgetContent = chatWidget.querySelector('.wac-widget-content');
        if (widgetContent) {
            widgetContent.innerHTML = `
                <div style="padding: 20px; text-align: center; color: #666;">
                    <h4>🚧 Chat Funnel en Desarrollo</h4>
                    <p>El sistema de chat se está desarrollando. Pronto estará disponible.</p>
                </div>
            `;
        }
    }
    
    // Función para abrir/cerrar el chat (placeholder)
    function toggleChat() {
        if (!chatWidget) return;
        
        isOpen = !isOpen;
        if (isOpen) {
            chatWidget.classList.add('wac-open');
        } else {
            chatWidget.classList.remove('wac-open');
        }
    }
    
    // Exportar funciones globalmente si es necesario
    window.WACChat = {
        toggle: toggleChat,
        isOpen: () => isOpen
    };
    
})();