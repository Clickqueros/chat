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
        
        console.log('WAC Frontend - Mostrando paso:', currentStep, 'de', funnelSteps.length);
        console.log('WAC Frontend - Datos del paso:', funnelSteps[currentStep]);
        
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
        
        // Mostrar opciones si existen
        if (step.options && Array.isArray(step.options) && step.options.length > 0) {
            stepHTML += '<div class="wac-options" style="margin-bottom: 15px;">';
            
            step.options.forEach((option, index) => {
                const targetStep = option.target - 1; // Convertir a índice base 0
                stepHTML += `
                    <button class="wac-option-button" onclick="goToStep(${targetStep})" 
                            style="width: 100%; padding: 10px; background: #007cba; color: white; border: none; border-radius: 5px; margin-bottom: 8px; cursor: pointer; text-align: left;">
                        ${option.text}
                    </button>
                `;
            });
            
            stepHTML += '</div>';
        } else {
            // Si no hay opciones, mostrar botón de continuar (comportamiento anterior)
            if (currentStep === funnelSteps.length - 1) {
                // Último paso
                stepHTML += `
                    <button class="wac-button" onclick="finishChat()" style="width: 100%; padding: 10px; background: #28a745; color: white; border: none; border-radius: 5px; margin-bottom: 10px; cursor: pointer;">
                        ✅ Finalizar Chat
                    </button>
                `;
            } else {
                // Paso intermedio
                stepHTML += `
                    <button class="wac-button" onclick="nextStep()" style="width: 100%; padding: 10px; background: #007cba; color: white; border: none; border-radius: 5px; margin-bottom: 10px; cursor: pointer;">
                        ➡️ Continuar
                    </button>
                `;
            }
        }
        
        widgetContent.innerHTML = stepHTML;
    }
    
    function nextStep() {
        currentStep++;
        console.log('WAC Frontend - Pasando al siguiente paso:', currentStep);
        showCurrentStep();
    }
    
    function goToStep(stepIndex) {
        if (stepIndex >= 0 && stepIndex < funnelSteps.length) {
            currentStep = stepIndex;
            console.log('WAC Frontend - Yendo al paso:', currentStep);
            showCurrentStep();
        } else {
            console.error('WAC Frontend - Paso inválido:', stepIndex);
        }
    }
    
    function finishChat() {
        console.log('WAC Frontend - Finalizando chat');
        showCurrentStep(); // Esto mostrará el mensaje de finalización
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
        goToStep: goToStep,
        finishChat: finishChat,
        handleWhatsApp: handleWhatsApp
    };
    
    // Exportar funciones individualmente para onclick
    window.nextStep = nextStep;
    window.goToStep = goToStep;
    window.finishChat = finishChat;
    window.handleWhatsApp = handleWhatsApp;
    
})();