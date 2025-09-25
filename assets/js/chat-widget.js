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
        console.log('WAC Frontend - DOM Content Loaded, inicializando widget...');
        initChatWidget();
    });
    
    // También intentar inicializar después de un pequeño delay
    setTimeout(function() {
        console.log('WAC Frontend - Timeout initialization...');
        if (!chatWidget) {
            initChatWidget();
        }
    }, 1000);
    
    function initChatWidget() {
        console.log('WAC Frontend - initChatWidget llamado');
        chatWidget = document.getElementById('wac-chat-widget');
        if (!chatWidget) {
            console.log('WAC Frontend - No se encontró el widget, buscando alternativas...');
            chatWidget = document.querySelector('#wac-chat-widget');
            if (!chatWidget) {
                console.log('WAC Frontend - Widget no encontrado en el DOM');
                return;
            }
        }
        
        console.log('WAC Frontend - Widget encontrado:', chatWidget);
        console.log('WAC Chat Widget inicializado');
        
        // Agregar event listeners para los botones principales del widget
        addMainWidgetEventListeners();
        
        // Cargar configuración del funnel
        loadFunnelConfig();
        
        // Mostrar primer paso
        showCurrentStep();
    }
    
    function addMainWidgetEventListeners() {
        // Event listener para el botón toggle del chat
        const toggleButton = document.getElementById('wac-chat-toggle');
        if (toggleButton) {
            toggleButton.addEventListener('click', function() {
                console.log('WAC Frontend - Toggle button clickeado');
                toggleChat();
            });
        }
        
        // Event listener para el botón cerrar del chat
        const closeButton = chatWidget.querySelector('.wac-widget-close');
        if (closeButton) {
            closeButton.addEventListener('click', function() {
                console.log('WAC Frontend - Close button clickeado');
                toggleChat();
            });
        }
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
            <div class="wac-message">
                ${step.message || 'Mensaje del paso'}
            </div>
        `;
        
        // Mostrar opciones si existen
        console.log('WAC Frontend - step.options:', step.options);
        if (step.options && Array.isArray(step.options) && step.options.length > 0) {
            console.log('WAC Frontend - Mostrando opciones:', step.options.length);
            stepHTML += '<div class="wac-options" style="margin-bottom: 15px;">';
            
            step.options.forEach((option, index) => {
                console.log(`WAC Frontend - Opción ${index}:`, option);
                
                if (option.type === 'link') {
                    // Opción de enlace
                    console.log(`WAC Frontend - Enlace:`, option.text, '-> URL:', option.url);
                    stepHTML += `
                        <div class="wac-option wac-link-option" role="button" tabindex="0" data-url="${option.url}">
                            🔗 ${option.text}
                        </div>
                    `;
                } else {
                    // Opción normal de paso
                    const targetStep = option.target - 1; // Convertir a índice base 0
                    console.log(`WAC Frontend - Paso:`, option.text, '-> paso', targetStep);
                    stepHTML += `
                        <div class="wac-option" role="button" tabindex="0" data-option="${targetStep}">
                            ${option.text}
                        </div>
                    `;
                }
            });
            
            stepHTML += '</div>';
        } else {
            console.log('WAC Frontend - No hay opciones o están vacías');
            // Si no hay opciones, no mostrar nada adicional
            // Solo el mensaje del paso
        }
        
        widgetContent.innerHTML = stepHTML;
        
        // Agregar event listeners después de insertar el HTML
        addEventListeners();
    }
    
    function addEventListeners() {
        const widgetContent = chatWidget.querySelector('.wac-widget-content');
        if (!widgetContent) {
            console.log('WAC Frontend - No se encontró widgetContent');
            return;
        }
        
        console.log('WAC Frontend - Agregando event listeners...');
        console.log('WAC Frontend - HTML del widget:', widgetContent.innerHTML);
        
        // Event listeners para opciones (estilo JoinChat)
        const options = widgetContent.querySelectorAll('.wac-option');
        console.log('WAC Frontend - Opciones encontradas:', options.length);
        
        options.forEach((option, index) => {
            console.log(`WAC Frontend - Opción ${index}:`, option);
            option.addEventListener('click', function() {
                // Verificar si es una opción de enlace
                const url = this.getAttribute('data-url');
                if (url) {
                    console.log('WAC Frontend - Enlace clickeado, URL:', url);
                    window.open(url, '_blank');
                } else {
                    // Opción normal de paso
                    const targetStep = parseInt(this.getAttribute('data-option'));
                    console.log('WAC Frontend - Opción clickeada, target step:', targetStep);
                    goToStep(targetStep);
                }
            });
        });
        
        // Los botones "Continuar" y "Finalizar" ya no existen
        // Solo se navega mediante opciones específicas
    }
    
    function nextStep() {
        currentStep++;
        console.log('WAC Frontend - Pasando al siguiente paso:', currentStep);
        showCurrentStep();
    }
    
    function goToStep(stepIndex) {
        console.log('WAC Frontend - goToStep llamado con:', stepIndex);
        console.log('WAC Frontend - funnelSteps:', funnelSteps);
        console.log('WAC Frontend - funnelSteps.length:', funnelSteps.length);
        
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