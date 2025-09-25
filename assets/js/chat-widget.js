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
        
        // Mostrar mensaje primero
        widgetContent.innerHTML = stepHTML;
        
        // Si hay opciones, mostrar typing indicator y luego las opciones
        console.log('WAC Frontend - step.options:', step.options);
        if (step.options && Array.isArray(step.options) && step.options.length > 0) {
            console.log('WAC Frontend - Mostrando opciones:', step.options.length);
            
            // Mostrar typing indicator primero
            showTypingIndicator(() => {
                // Después de 1.5 segundos, mostrar las opciones
                showOptions(step.options);
            });
        } else {
            console.log('WAC Frontend - No hay opciones o están vacías');
            // Si no hay opciones, agregar event listeners inmediatamente
            addEventListeners();
        }
    }
    
    function showTypingIndicator(callback) {
        const widgetContent = chatWidget.querySelector('.wac-widget-content');
        if (!widgetContent) return;
        
        // Agregar typing indicator
        const typingHTML = `
            <div class="wac-typing-indicator">
                <div class="wac-typing-dots">
                    <div class="wac-typing-dot"></div>
                    <div class="wac-typing-dot"></div>
                    <div class="wac-typing-dot"></div>
                </div>
            </div>
        `;
        
        widgetContent.insertAdjacentHTML('beforeend', typingHTML);
        
        // Ejecutar callback después de 1.5 segundos
        setTimeout(() => {
            // Remover typing indicator
            const typingIndicator = widgetContent.querySelector('.wac-typing-indicator');
            if (typingIndicator) {
                typingIndicator.remove();
            }
            
            // Ejecutar callback
            if (callback) callback();
        }, 1500);
    }
    
    function showOptions(options) {
        const widgetContent = chatWidget.querySelector('.wac-widget-content');
        if (!widgetContent) return;
        
        console.log('WAC Frontend - Mostrando opciones con animación');
        
        let optionsHTML = '<div class="wac-options animate-in" style="margin-bottom: 15px;">';
        
        options.forEach((option, index) => {
            console.log(`WAC Frontend - Opción ${index}:`, option);
            
            if (option.type === 'link') {
                // Opción de enlace
                console.log(`WAC Frontend - Enlace:`, option.text, '-> URL:', option.url);
                optionsHTML += `
                    <div class="wac-option wac-link-option" role="button" tabindex="0" data-url="${option.url}">
                        🔗 ${option.text}
                    </div>
                `;
            } else if (option.type === 'whatsapp') {
                // Opción de WhatsApp
                console.log(`WAC Frontend - WhatsApp:`, option.text, '-> contacto:', option.contact);
                optionsHTML += `
                    <div class="wac-option wac-whatsapp-option" role="button" tabindex="0" data-contact="${option.contact}">
                        📱 ${option.text}
                    </div>
                `;
            } else if (option.type === 'form') {
                // Opción de formulario
                console.log(`WAC Frontend - Formulario:`, option.text, '-> contacto:', option.contact);
                optionsHTML += `
                    <div class="wac-option wac-form-option" role="button" tabindex="0" data-form='${JSON.stringify(option)}'>
                        📝 ${option.text}
                    </div>
                `;
            } else {
                // Opción normal de paso
                const targetStep = option.target - 1; // Convertir a índice base 0
                console.log(`WAC Frontend - Paso:`, option.text, '-> paso', targetStep);
                optionsHTML += `
                    <div class="wac-option" role="button" tabindex="0" data-option="${targetStep}">
                        ${option.text}
                    </div>
                `;
            }
        });
        
        optionsHTML += '</div>';
        
        // Insertar opciones
        widgetContent.insertAdjacentHTML('beforeend', optionsHTML);
        
        // Pequeña demora para que se vea la animación
        setTimeout(() => {
            // Agregar event listeners después de insertar las opciones
            addEventListeners();
        }, 100);
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
                    // Verificar si es una opción de WhatsApp
                    const contactIndex = this.getAttribute('data-contact');
                    if (contactIndex) {
                        console.log('WAC Frontend - WhatsApp clickeado, contacto:', contactIndex);
                        handleWhatsAppContact(parseInt(contactIndex));
                    } else {
                        // Verificar si es una opción de formulario
                        const formData = this.getAttribute('data-form');
                        if (formData) {
                            console.log('WAC Frontend - Formulario clickeado, datos:', formData);
                            showForm(JSON.parse(formData));
                        } else {
                            // Opción normal de paso
                            const targetStep = parseInt(this.getAttribute('data-option'));
                            console.log('WAC Frontend - Opción clickeada, target step:', targetStep);
                            goToStep(targetStep);
                        }
                    }
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
    
    function handleWhatsAppContact(contactIndex) {
        console.log('WAC Frontend - handleWhatsAppContact llamado con:', contactIndex);
        
        // Obtener contactos de WhatsApp desde los datos del funnel
        if (typeof wacFunnelData !== 'undefined' && wacFunnelData.whatsapp_contacts) {
            const contacts = wacFunnelData.whatsapp_contacts;
            if (contacts && contacts[contactIndex - 1]) {
                const phoneNumber = contacts[contactIndex - 1];
                const message = encodeURIComponent('Hola, quiero más información');
                console.log('WAC Frontend - Abriendo WhatsApp con número:', phoneNumber);
                window.open(`https://wa.me/${phoneNumber.replace('+', '')}?text=${message}`, '_blank');
            } else {
                console.error('WAC Frontend - Contacto WhatsApp no encontrado:', contactIndex);
                alert('Error: Contacto de WhatsApp no encontrado');
            }
        } else {
            console.error('WAC Frontend - Datos de contactos WhatsApp no disponibles');
            alert('Error: Contactos de WhatsApp no configurados');
        }
    }
    
    function showForm(formOption) {
        console.log('WAC Frontend - showForm llamado con:', formOption);
        
        const widgetContent = chatWidget.querySelector('.wac-widget-content');
        if (!widgetContent) return;
        
        // Crear HTML del formulario
        let formHTML = `
            <div class="wac-form-container" style="margin-top: 15px;">
                <div class="wac-form-header" style="background: #f8f9fa; padding: 10px; border-radius: 4px 4px 0 0; font-weight: bold;">
                    📝 ${formOption.text}
                </div>
                <form id="wac-form" style="background: white; padding: 15px; border: 1px solid #ddd; border-radius: 0 0 4px 4px;">
        `;
        
        // Agregar campos del formulario
        if (formOption.fields && Array.isArray(formOption.fields)) {
            formOption.fields.forEach((field, index) => {
                const fieldId = `form_field_${index}`;
                const isRequired = field.required ? 'required' : '';
                const requiredMark = field.required ? ' *' : '';
                
                formHTML += `
                    <div class="wac-form-field" style="margin-bottom: 15px;">
                        <label for="${fieldId}" style="display: block; margin-bottom: 5px; font-weight: bold;">
                            ${field.label}${requiredMark}
                        </label>
                `;
                
                if (field.type === 'textarea') {
                    formHTML += `
                        <textarea id="${fieldId}" name="${field.name || field.label}" ${isRequired} 
                                  style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; resize: vertical; min-height: 80px;"
                                  placeholder="Ingresa tu ${field.label.toLowerCase()}"></textarea>
                    `;
                } else if (field.type === 'select') {
                    formHTML += `
                        <select id="${fieldId}" name="${field.name || field.label}" ${isRequired} 
                                style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">Selecciona una opción</option>
                    `;
                    if (field.options && Array.isArray(field.options)) {
                        field.options.forEach(option => {
                            formHTML += `<option value="${option}">${option}</option>`;
                        });
                    }
                    formHTML += `</select>`;
                } else {
                    const inputType = field.type === 'email' ? 'email' : 'text';
                    formHTML += `
                        <input type="${inputType}" id="${fieldId}" name="${field.name || field.label}" ${isRequired}
                               style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"
                               placeholder="Ingresa tu ${field.label.toLowerCase()}">
                    `;
                }
                
                formHTML += `</div>`;
            });
        }
        
        // Botones del formulario
        formHTML += `
                    <div class="wac-form-buttons" style="margin-top: 20px; display: flex; gap: 10px;">
                        <button type="button" class="wac-form-cancel" style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: white; cursor: pointer;">
                            Cancelar
                        </button>
                        <button type="submit" class="wac-form-submit" style="flex: 1; padding: 10px; border: none; border-radius: 4px; background: #25d366; color: white; cursor: pointer;">
                            Enviar 📱
                        </button>
                    </div>
                </form>
            </div>
        `;
        
        // Reemplazar contenido del widget
        widgetContent.innerHTML = formHTML;
        
        // Agregar event listeners del formulario
        const form = document.getElementById('wac-form');
        const cancelBtn = document.querySelector('.wac-form-cancel');
        
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                handleFormSubmit(formOption);
            });
        }
        
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                // Volver al paso actual
                showCurrentStep();
            });
        }
    }
    
    function handleFormSubmit(formOption) {
        console.log('WAC Frontend - handleFormSubmit llamado con:', formOption);
        
        const form = document.getElementById('wac-form');
        if (!form) return;
        
        const formData = new FormData(form);
        const data = {};
        
        // Recopilar datos del formulario
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        // Validar campos obligatorios
        let isValid = true;
        const errors = [];
        
        if (formOption.fields && Array.isArray(formOption.fields)) {
            formOption.fields.forEach(field => {
                if (field.required) {
                    const value = data[field.name || field.label];
                    if (!value || value.trim() === '') {
                        isValid = false;
                        errors.push(`${field.label} es obligatorio`);
                    }
                }
            });
        }
        
        if (!isValid) {
            alert('Por favor completa todos los campos obligatorios:\n' + errors.join('\n'));
            return;
        }
        
        // Crear mensaje para WhatsApp
        let message = `*Nuevo formulario:* ${formOption.text}\n\n`;
        
        Object.keys(data).forEach(key => {
            const value = data[key];
            if (value && value.trim() !== '') {
                message += `*${key}:* ${value}\n`;
            }
        });
        
        // Obtener número de WhatsApp
        if (typeof wacFunnelData !== 'undefined' && wacFunnelData.whatsapp_contacts) {
            const contacts = wacFunnelData.whatsapp_contacts;
            const contactIndex = formOption.contact - 1;
            
            if (contacts && contacts[contactIndex]) {
                const phoneNumber = contacts[contactIndex];
                const encodedMessage = encodeURIComponent(message);
                
                console.log('WAC Frontend - Enviando formulario a WhatsApp:', phoneNumber);
                console.log('WAC Frontend - Mensaje:', message);
                
                // Abrir WhatsApp con el mensaje
                window.open(`https://wa.me/${phoneNumber.replace('+', '')}?text=${encodedMessage}`, '_blank');
                
                // Mostrar mensaje de confirmación
                const widgetContent = chatWidget.querySelector('.wac-widget-content');
                if (widgetContent) {
                    widgetContent.innerHTML = `
                        <div style="padding: 20px; text-align: center;">
                            <h4>✅ Formulario enviado</h4>
                            <p>¡Gracias! Hemos enviado tu información por WhatsApp.</p>
                            <p>Te contactaremos pronto.</p>
                        </div>
                    `;
                }
            } else {
                console.error('WAC Frontend - Contacto WhatsApp no encontrado:', formOption.contact);
                alert('Error: Contacto de WhatsApp no encontrado');
            }
        } else {
            console.error('WAC Frontend - Datos de contactos WhatsApp no disponibles');
            alert('Error: Contactos de WhatsApp no configurados');
        }
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