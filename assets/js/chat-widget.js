/**
 * WAC Chat Funnels - Widget JavaScript
 * Sistema simple y funcional de chat funnels
 */

(function() {
    'use strict';
    
    // Configuración del funnel (mensajes por defecto)
    const defaultMessages = [
        {
            type: 'message',
            text: '¡Hola! Soy tu asistente virtual. ¿En qué puedo ayudarte?',
            next: 'options'
        },
        {
            type: 'options',
            text: 'Elige una opción:',
            buttons: [
                { text: 'Cotización', action: 'form' },
                { text: 'WhatsApp', action: 'whatsapp' },
                { text: 'Ver portafolio', action: 'redirect', url: '/portafolio' }
            ]
        },
        {
            type: 'form',
            text: 'Déjame tus datos:',
            fields: [
                { name: 'nombre', label: 'Nombre', type: 'text', required: true },
                { name: 'email', label: 'Email', type: 'email', required: true },
                { name: 'telefono', label: 'Teléfono', type: 'tel', required: false }
            ]
        }
    ];
    
    // Variables globales
    let chatWidget = null;
    let currentStep = 'welcome';
    let userData = {};
    let isOpen = false;
    
    // Inicializar cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        initChatWidget();
    });
    
    function initChatWidget() {
        chatWidget = document.getElementById('wac-chat-widget');
        if (!chatWidget) return;
        
        const delay = parseInt(chatWidget.dataset.delay) || 3000;
        
        // Mostrar teaser después del delay
        setTimeout(() => {
            showTeaser();
        }, delay);
        
        // Event listeners
        const teaser = document.getElementById('wac-chat-teaser');
        const closeBtn = document.getElementById('wac-chat-close');
        const sendBtn = document.getElementById('wac-chat-send');
        const inputField = document.getElementById('wac-chat-field');
        
        if (teaser) teaser.addEventListener('click', openChat);
        if (closeBtn) closeBtn.addEventListener('click', closeChat);
        if (sendBtn) sendBtn.addEventListener('click', sendMessage);
        if (inputField) {
            inputField.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
        }
    }
    
    function showTeaser() {
        const teaser = document.getElementById('wac-chat-teaser');
        if (teaser) {
            teaser.style.display = 'block';
            chatWidget.style.display = 'block';
        }
    }
    
    function openChat() {
        isOpen = true;
        const teaser = document.getElementById('wac-chat-teaser');
        const container = document.getElementById('wac-chat-container');
        
        if (teaser) teaser.style.display = 'none';
        if (container) container.style.display = 'block';
        
        // Mostrar mensaje de bienvenida
        showMessage(defaultMessages[0].text, 'bot');
        
        // Mostrar opciones después de un breve delay
        setTimeout(() => {
            showOptions();
        }, 1000);
    }
    
    function closeChat() {
        isOpen = false;
        const teaser = document.getElementById('wac-chat-teaser');
        const container = document.getElementById('wac-chat-container');
        
        if (container) container.style.display = 'none';
        if (teaser) teaser.style.display = 'block';
        
        // Resetear estado
        currentStep = 'welcome';
        userData = {};
        clearMessages();
        clearButtons();
    }
    
    function showMessage(text, sender = 'bot') {
        const messagesContainer = document.getElementById('wac-chat-messages');
        if (!messagesContainer) return;
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `wac-message wac-message-${sender}`;
        
        const bubble = document.createElement('div');
        bubble.className = 'wac-message-bubble';
        bubble.textContent = text;
        
        messageDiv.appendChild(bubble);
        messagesContainer.appendChild(messageDiv);
        
        // Scroll al final
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    function showOptions() {
        const optionsMessage = defaultMessages[1];
        showMessage(optionsMessage.text, 'bot');
        
        const buttonsContainer = document.getElementById('wac-chat-buttons');
        if (!buttonsContainer) return;
        
        buttonsContainer.innerHTML = '';
        
        optionsMessage.buttons.forEach(button => {
            const buttonEl = document.createElement('button');
            buttonEl.className = 'wac-chat-button';
            buttonEl.textContent = button.text;
            buttonEl.addEventListener('click', () => handleOptionClick(button));
            buttonsContainer.appendChild(buttonEl);
        });
    }
    
    function handleOptionClick(button) {
        // Agregar mensaje del usuario
        showMessage(button.text, 'user');
        
        // Limpiar botones
        clearButtons();
        
        // Manejar acción
        switch (button.action) {
            case 'form':
                showForm();
                break;
            case 'whatsapp':
                openWhatsApp();
                break;
            case 'redirect':
                if (button.url) {
                    window.open(button.url, '_blank');
                }
                showMessage('Redirigiendo...', 'bot');
                break;
            default:
                showMessage('Opción no disponible', 'bot');
        }
    }
    
    function showForm() {
        const formMessage = defaultMessages[2];
        showMessage(formMessage.text, 'bot');
        
        const buttonsContainer = document.getElementById('wac-chat-buttons');
        if (!buttonsContainer) return;
        
        buttonsContainer.innerHTML = '';
        
        formMessage.fields.forEach(field => {
            const fieldDiv = document.createElement('div');
            fieldDiv.style.marginBottom = '10px';
            
            const label = document.createElement('label');
            label.textContent = field.label + (field.required ? ' *' : '');
            label.style.display = 'block';
            label.style.marginBottom = '5px';
            label.style.fontSize = '12px';
            label.style.color = '#666';
            
            const input = document.createElement('input');
            input.type = field.type;
            input.name = field.name;
            input.placeholder = field.label;
            input.required = field.required;
            input.style.width = '100%';
            input.style.padding = '8px 12px';
            input.style.border = '1px solid #ddd';
            input.style.borderRadius = '4px';
            input.style.fontSize = '14px';
            
            fieldDiv.appendChild(label);
            fieldDiv.appendChild(input);
            buttonsContainer.appendChild(fieldDiv);
        });
        
        // Botón enviar formulario
        const submitBtn = document.createElement('button');
        submitBtn.className = 'wac-chat-button';
        submitBtn.textContent = 'Enviar Datos';
        submitBtn.addEventListener('click', submitForm);
        buttonsContainer.appendChild(submitBtn);
    }
    
    function submitForm() {
        const inputs = document.querySelectorAll('#wac-chat-buttons input');
        let formData = {};
        let isValid = true;
        
        inputs.forEach(input => {
            if (input.required && !input.value.trim()) {
                isValid = false;
                input.style.borderColor = '#dc3545';
            } else {
                input.style.borderColor = '#ddd';
                formData[input.name] = input.value.trim();
            }
        });
        
        if (!isValid) {
            showMessage('Por favor completa todos los campos requeridos', 'bot');
            return;
        }
        
        // Guardar datos del usuario
        userData = { ...userData, ...formData };
        
        // Mostrar confirmación
        showMessage('¡Gracias por tus datos! Te contactaré pronto.', 'bot');
        
        // Limpiar formulario
        clearButtons();
        
        // Mostrar opción de WhatsApp
        setTimeout(() => {
            showMessage('¿Quieres hablar por WhatsApp ahora?', 'bot');
            
            const buttonsContainer = document.getElementById('wac-chat-buttons');
            if (buttonsContainer) {
                const whatsappBtn = document.createElement('button');
                whatsappBtn.className = 'wac-chat-button';
                whatsappBtn.textContent = 'Sí, WhatsApp';
                whatsappBtn.addEventListener('click', openWhatsApp);
                buttonsContainer.appendChild(whatsappBtn);
            }
        }, 1000);
        
        // Aquí podrías enviar los datos a tu servidor
        console.log('Datos del lead:', userData);
    }
    
    function openWhatsApp() {
        const whatsappNumber = chatWidget.dataset.whatsapp || '+573154543344';
        let message = 'Hola, quiero información sobre sus servicios.';
        
        // Agregar datos del formulario si existen
        if (userData.nombre) {
            message = `Hola, soy ${userData.nombre}`;
            if (userData.email) message += ` (${userData.email})`;
            message += '. Quiero información sobre sus servicios.';
        }
        
        const whatsappUrl = `https://wa.me/${whatsappNumber.replace(/[^0-9]/g, '')}?text=${encodeURIComponent(message)}`;
        window.open(whatsappUrl, '_blank');
        
        showMessage('Redirigiendo a WhatsApp...', 'bot');
    }
    
    function sendMessage() {
        const inputField = document.getElementById('wac-chat-field');
        if (!inputField || !inputField.value.trim()) return;
        
        const message = inputField.value.trim();
        showMessage(message, 'user');
        inputField.value = '';
        
        // Respuesta automática simple
        setTimeout(() => {
            showMessage('Gracias por tu mensaje. Te contactaré pronto.', 'bot');
        }, 1000);
    }
    
    function clearMessages() {
        const messagesContainer = document.getElementById('wac-chat-messages');
        if (messagesContainer) {
            messagesContainer.innerHTML = '';
        }
    }
    
    function clearButtons() {
        const buttonsContainer = document.getElementById('wac-chat-buttons');
        if (buttonsContainer) {
            buttonsContainer.innerHTML = '';
        }
    }
    
    // Función para debug (disponible en consola)
    window.wacChatDebug = {
        openChat: openChat,
        closeChat: closeChat,
        showMessage: showMessage,
        userData: () => userData,
        currentStep: () => currentStep
    };
    
})();
