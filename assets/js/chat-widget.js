/**
 * WAC Chat Funnels - Widget JavaScript
 * Sistema simple y funcional de chat funnels
 */

(function() {
    'use strict';
    
    // Variables globales
    let chatWidget = null;
    let isOpen = false;
    let currentStep = 0;
    let funnelSteps = [];
    let userData = {};
    
    // Inicializar cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        initChatWidget();
    });
    
    function initChatWidget() {
        chatWidget = document.getElementById('wac-chat-widget');
        if (!chatWidget) return;
        
        console.log('WAC Chat Widget inicializado');
        
        // Cargar configuración del funnel
        loadFunnelConfig();
        
        // Mostrar primer paso
        showCurrentStep();
    }
    
    function loadFunnelConfig() {
        // Obtener configuración del funnel desde el DOM
        const funnelDataElement = document.getElementById('wac-funnel-data');
        if (funnelDataElement) {
            try {
                const config = JSON.parse(funnelDataElement.textContent);
                funnelSteps = config.steps || [];
                console.log('Configuración del funnel cargada:', funnelSteps);
            } catch (e) {
                console.error('Error al cargar configuración del funnel:', e);
                funnelSteps = [];
            }
        } else {
            console.log('No se encontró configuración del funnel, usando datos por defecto');
            funnelSteps = [];
        }
    }
    
    function showCurrentStep() {
        const widgetContent = chatWidget.querySelector('.wac-widget-content');
        if (!widgetContent) return;
        
        if (funnelSteps.length === 0) {
            // Si no hay configuración, mostrar mensaje
            widgetContent.innerHTML = `
                <div style="padding: 20px; text-align: center; color: #666;">
                    <h4>🚧 Chat Funnel en Desarrollo</h4>
                    <p>Configura tu funnel en el panel de administración.</p>
                </div>
            `;
            return;
        }
        
        if (currentStep >= funnelSteps.length) {
            // Fin del funnel
            widgetContent.innerHTML = `
                <div style="padding: 20px; text-align: center;">
                    <h4>✅ ¡Gracias!</h4>
                    <p>Hemos recibido tu información.</p>
                </div>
            `;
            return;
        }
        
        const step = funnelSteps[currentStep];
        let stepHTML = '';
        
        // Mostrar mensaje del paso
        stepHTML += `
            <div class="wac-message" style="margin-bottom: 15px; padding: 10px; background: #f0f0f0; border-radius: 10px;">
                ${step.message || 'Mensaje del paso'}
            </div>
        `;
        
        // Mostrar opciones si las hay
        if (step.next === 'whatsapp') {
            stepHTML += `
                <button class="wac-button" onclick="handleWhatsApp()" style="width: 100%; padding: 10px; background: #25D366; color: white; border: none; border-radius: 5px; margin-bottom: 10px; cursor: pointer;">
                    📱 Ir a WhatsApp
                </button>
            `;
        } else if (step.next === 'end') {
            stepHTML += `
                <button class="wac-button" onclick="nextStep()" style="width: 100%; padding: 10px; background: #007cba; color: white; border: none; border-radius: 5px; margin-bottom: 10px; cursor: pointer;">
                    ✅ Finalizar
                </button>
            `;
        } else {
            stepHTML += `
                <button class="wac-button" onclick="nextStep()" style="width: 100%; padding: 10px; background: #007cba; color: white; border: none; border-radius: 5px; margin-bottom: 10px; cursor: pointer;">
                    ➡️ Continuar
                </button>
            `;
        }
        
        widgetContent.innerHTML = stepHTML;
    }
    
    function nextStep() {
        currentStep++;
        showCurrentStep();
    }
    
    function handleWhatsApp() {
        // Redirigir a WhatsApp
        const phoneNumber = '+573142400850'; // Número por defecto
        const message = encodeURIComponent('Hola, quiero más información');
        window.open(`https://wa.me/${phoneNumber.replace('+', '')}?text=${message}`, '_blank');
    }
    
    // Función para abrir/cerrar el chat
    function toggleChat() {
        if (!chatWidget) return;
        
        isOpen = !isOpen;
        if (isOpen) {
            chatWidget.classList.add('wac-open');
        } else {
            chatWidget.classList.remove('wac-open');
        }
    }
    
    // Exportar funciones globalmente
    window.WACChat = {
        toggle: toggleChat,
        isOpen: () => isOpen,
        nextStep: nextStep,
        handleWhatsApp: handleWhatsApp
    };
    
})();