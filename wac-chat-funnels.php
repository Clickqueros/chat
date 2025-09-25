<?php
/**
 * Plugin Name: WAC Chat Funnels - SIMPLE
 * Description: Chat funnel simple - solo tipo Mensaje para pruebas
 * Version: 1.0.0
 * Author: WAC
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class WAC_Chat_Funnels_Simple {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_metaboxes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_wac_debug_database', array($this, 'ajax_debug_database'));
    }
    
    public function init() {
        // Registrar Custom Post Type
        register_post_type('wac_chat_funnel', array(
            'labels' => array(
                'name' => 'Chat Funnels',
                'singular_name' => 'Chat Funnel',
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor'),
            'menu_icon' => 'dashicons-format-chat',
        ));
    }
    
    public function add_meta_boxes() {
        add_meta_box(
            'funnel_config',
            'Configuración del Funnel',
            array($this, 'funnel_config_metabox'),
            'wac_chat_funnel',
            'normal',
            'high'
        );
    }
    
    public function funnel_config_metabox($post) {
        // Obtener datos guardados
        $saved_steps = get_post_meta($post->ID, '_wac_funnel_steps_data', true);
        
        // Configuración básica
        $enabled = get_post_meta($post->ID, '_wac_enabled', true);
        $teaser_text = get_post_meta($post->ID, '_wac_teaser_text', true);
        $teaser_delay = get_post_meta($post->ID, '_wac_teaser_delay', true);
        $whatsapp_number = get_post_meta($post->ID, '_wac_whatsapp_number', true);
        ?>
        
        <style>
        .wac-step {
            border: 2px solid #007cba;
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
        }
        
        .wac-step-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .wac-step-number {
            background: #007cba;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .wac-step-title {
            font-weight: bold;
            color: #007cba;
            flex: 1;
        }
        
        .wac-step-delete {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            font-size: 18px;
        }
        
        .wac-step-content label {
            display: block;
            margin: 10px 0 5px 0;
            font-weight: bold;
        }
        
        .wac-step-content textarea {
            width: 100%;
            min-height: 80px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .wac-step-content select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .wac-builder-actions {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #007cba;
            text-align: center;
        }
        
        .wac-tip {
            margin-top: 10px;
            padding: 10px;
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 4px;
            font-size: 12px;
        }
        
        /* Estilos para opciones múltiples */
        .wac-options-section {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
        }
        
        .wac-options-list {
            margin-bottom: 10px;
        }
        
        .wac-option-item {
            margin-bottom: 10px;
            padding: 10px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .wac-option-fields {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .wac-option-text {
            flex: 2;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .wac-option-target {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            max-width: 120px;
        }
        
        .wac-remove-option {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .wac-remove-option:hover {
            background: #c82333;
        }
        
        .wac-add-option-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .wac-add-option-btn:hover {
            background: #218838;
        }
        </style>
        
        <h3><?php _e('Configuración Básica', 'wac-chat-funnels'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Estado', 'wac-chat-funnels'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wac_enabled" value="1" <?php checked($enabled, 1); ?> />
                        <?php _e('Activar este funnel', 'wac-chat-funnels'); ?>
                    </label>
                    <p class="description"><?php _e('Solo un funnel puede estar activo a la vez', 'wac-chat-funnels'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Texto del Teaser', 'wac-chat-funnels'); ?></th>
                <td>
                    <input type="text" name="wac_teaser_text" value="<?php echo esc_attr($teaser_text); ?>" class="regular-text" />
                    <p class="description"><?php _e('Texto que aparece en la burbuja inicial', 'wac-chat-funnels'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Retraso del Teaser (ms)', 'wac-chat-funnels'); ?></th>
                <td>
                    <input type="number" name="wac_teaser_delay" value="<?php echo esc_attr($teaser_delay); ?>" min="1000" max="10000" step="1000" />
                    <p class="description"><?php _e('Tiempo antes de mostrar el teaser', 'wac-chat-funnels'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Número de WhatsApp', 'wac-chat-funnels'); ?></th>
                <td>
                    <input type="text" name="wac_whatsapp_number" value="<?php echo esc_attr($whatsapp_number); ?>" class="regular-text" />
                    <p class="description"><?php _e('Número completo con código de país (ej: +573154543344)', 'wac-chat-funnels'); ?></p>
                </td>
            </tr>
        </table>
        
        <h3>📱 Contactos de WhatsApp</h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Contactos', 'wac-chat-funnels'); ?></th>
                <td>
                    <div id="wac-whatsapp-contacts">
                        <!-- Los contactos se agregarán aquí dinámicamente -->
                    </div>
                    <button type="button" class="button" onclick="addWhatsAppContact()">+ Añadir nuevo contacto WhatsApp</button>
                    <p class="description"><?php _e('Agrega múltiples contactos de WhatsApp para usar en los pasos', 'wac-chat-funnels'); ?></p>
                </td>
            </tr>
        </table>
        
        <h3>🚀 Editor de Funnel - SOLO TIPO MENSAJE</h3>
        <p><strong>Instrucciones:</strong> Haz clic en "+ Agregar Paso" para crear mensajes simples. Cada paso solo tiene un mensaje y una acción.</p>
        
        <div id="wac-funnel-builder">
            <div id="wac-steps-container">
                <!-- Los pasos se agregarán aquí dinámicamente -->
            </div>
            
            <div class="wac-builder-actions">
                <button type="button" class="button button-primary" onclick="addNewStep()">+ Agregar Paso</button>
                <button type="button" class="button" onclick="loadFunnelConfig()">🔄 Cargar Guardado</button>
                <button type="button" class="button" onclick="clearAllSteps()">🗑️ Limpiar Todo</button>
            </div>
            
            <div style="margin-top: 15px; padding: 10px; background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 4px; font-size: 13px;">
                <strong>💡 Cómo funciona:</strong><br>
                • Los cambios se guardan automáticamente mientras escribes<br>
                • Haz clic en <strong>"Actualizar"</strong> (botón azul arriba) para guardar permanentemente<br>
                • Solo necesitas un botón de guardado: el de WordPress
            </div>
            
            <div style="margin-top: 10px;">
                <button type="button" class="button button-small" onclick="debugEverything()">🔍 Debug Todo (Copiable)</button>
            </div>
            
            <div id="wac-debug-output" style="display:none; margin-top: 15px; padding: 15px; background: #f8f9fa; border: 2px solid #007cba; border-radius: 5px; max-height: 400px; overflow-y: auto;">
                <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 10px;">
                    <h4 style="margin: 0; color: #007cba;">🔍 Debug Output</h4>
                    <button type="button" class="button button-small" onclick="copyDebugOutput()">📋 Copiar</button>
                </div>
                <pre id="wac-debug-text" style="margin: 0; font-family: monospace; font-size: 12px; white-space: pre-wrap; background: white; padding: 10px; border: 1px solid #ddd; border-radius: 3px;"></pre>
            </div>
            
            <div id="wac-save-notice" style="display:none; margin-top: 15px; padding: 10px; border-radius: 4px;"></div>
        </div>
        
        <!-- Campo oculto para guardar -->
        <input type="hidden" name="wac_funnel_steps" id="wac-funnel-steps-hidden" value="" />
        
        <!-- Script para pasar datos guardados -->
        <script type="text/javascript">
            var wacSavedSteps = <?php echo json_encode($saved_steps ?: array()); ?>;
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        </script>
        
        <script>
        function addNewStep() {
            const container = document.getElementById('wac-steps-container');
            const existingSteps = container.querySelectorAll('.wac-step');
            const stepCount = existingSteps.length + 1;
            
            // ID único para el paso
            const stepId = `step_${stepCount}_${Date.now()}`;
            
            const newStep = document.createElement('div');
            newStep.className = 'wac-step';
            newStep.setAttribute('data-step', stepCount);
            newStep.setAttribute('data-step-id', stepId);
            newStep.innerHTML = `
                <div class="wac-step-header">
                    <span class="wac-step-number">${stepCount}</span>
                    <span class="wac-step-title">Mensaje ${stepCount}</span>
                    <button class="wac-step-delete" onclick="deleteStep(${stepCount})">×</button>
                </div>
                <div class="wac-step-content">
                    <label>Mensaje:</label>
                    <textarea name="${stepId}_message" placeholder="Escribe tu mensaje aquí..."></textarea>
                    
                    <div class="wac-options-section">
                        <label>Opciones de respuesta:</label>
                        <div class="wac-options-list" id="options_${stepId}">
                            <!-- Las opciones se agregarán aquí dinámicamente -->
                        </div>
                        <div style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;">
                            <button type="button" class="wac-add-option-btn" onclick="addOption('${stepId}')">
                                + Añadir opción
                            </button>
                            <button type="button" class="wac-add-option-btn" onclick="addLinkOption('${stepId}')" style="background: #28a745; border-color: #28a745;">
                                🔗 Añadir enlace
                            </button>
                            <button type="button" class="wac-add-option-btn" onclick="addWhatsAppContactOption('${stepId}')" style="background: #25d366; border-color: #25d366;">
                                📱 Añadir contacto
                            </button>
                        </div>
                    </div>
                    
                    <div class="wac-tip">
                        <strong>💡 Tip:</strong> Agrega opciones para que el usuario pueda elegir qué hacer después de leer tu mensaje.
                    </div>
                </div>
            `;
            
            container.appendChild(newStep);
            updateStepNumbers();
            
            console.log(`Nuevo paso agregado: ${stepId}`);
        }
        
        function deleteStep(stepNumber) {
            if (confirm('¿Estás seguro de eliminar este paso?')) {
                const step = document.querySelector(`[data-step="${stepNumber}"]`);
                if (step) {
                    step.remove();
                    updateStepNumbers();
                }
            }
        }
        
        // Función para agregar una opción de paso a un paso
        function addOption(stepId) {
            const optionsList = document.getElementById(`options_${stepId}`);
            const optionId = Date.now();
            
            const optionDiv = document.createElement('div');
            optionDiv.className = 'wac-option-item';
            optionDiv.innerHTML = `
                <div class="wac-option-fields">
                    <input type="text" name="${stepId}_option_text_${optionId}" placeholder="Texto de la opción (ej: Cotización)" class="wac-option-text">
                    <input type="number" name="${stepId}_option_target_${optionId}" placeholder="Paso destino" class="wac-option-target" min="1">
                    <button type="button" class="wac-remove-option" onclick="removeOption('${stepId}', '${optionId}')">×</button>
                </div>
            `;
            
            optionsList.appendChild(optionDiv);
        }
        
        // Función para agregar una opción de enlace a un paso
        function addLinkOption(stepId) {
            const optionsList = document.getElementById(`options_${stepId}`);
            const optionId = Date.now();
            
            const optionDiv = document.createElement('div');
            optionDiv.className = 'wac-option-item wac-link-option';
            optionDiv.innerHTML = `
                <div class="wac-option-fields">
                    <input type="text" name="${stepId}_link_text_${optionId}" placeholder="Texto del enlace (ej: Ver portafolio)" class="wac-option-text">
                    <input type="url" name="${stepId}_link_url_${optionId}" placeholder="URL (ej: https://ejemplo.com)" class="wac-option-target">
                    <button type="button" class="wac-remove-option" onclick="removeOption('${stepId}', '${optionId}')">×</button>
                </div>
            `;
            
            optionsList.appendChild(optionDiv);
        }
        
        // Función para eliminar una opción
        function removeOption(stepId, optionId) {
            console.log('removeOption llamado con:', stepId, optionId);
            
            // Buscar opciones normales, de enlace y de WhatsApp
            let option = null;
            
            // Buscar opción normal
            const normalInput = document.querySelector(`input[name="${stepId}_option_text_${optionId}"]`);
            if (normalInput) {
                option = normalInput.closest('.wac-option-item');
                console.log('Encontrada opción normal:', option);
            }
            
            // Buscar opción de enlace
            if (!option) {
                const linkInput = document.querySelector(`input[name="${stepId}_link_text_${optionId}"]`);
                if (linkInput) {
                    option = linkInput.closest('.wac-option-item');
                    console.log('Encontrada opción de enlace:', option);
                }
            }
            
            // Buscar opción de WhatsApp
            if (!option) {
                const whatsappInput = document.querySelector(`input[name="${stepId}_whatsapp_text_${optionId}"]`);
                if (whatsappInput) {
                    option = whatsappInput.closest('.wac-option-item');
                    console.log('Encontrada opción de WhatsApp:', option);
                }
            }
            
            if (option) {
                console.log('Eliminando opción:', option);
                option.remove();
            } else {
                console.error('No se encontró la opción para eliminar:', stepId, optionId);
            }
        }
        
        // Función para agregar un contacto WhatsApp
        function addWhatsAppContact() {
            const container = document.getElementById('wac-whatsapp-contacts');
            const contactId = Date.now();
            const contactCount = container.children.length + 1;
            
            const contactDiv = document.createElement('div');
            contactDiv.className = 'wac-whatsapp-contact';
            contactDiv.style.cssText = 'margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;';
            contactDiv.innerHTML = `
                <div style="display: flex; gap: 10px; align-items: center;">
                    <strong>WhatsApp ${contactCount}:</strong>
                    <input type="text" name="wac_whatsapp_contact_${contactId}" placeholder="Número (ej: +573001234567)" style="flex: 1;" />
                    <button type="button" class="button" onclick="removeWhatsAppContact('${contactId}')">Eliminar</button>
                </div>
            `;
            
            container.appendChild(contactDiv);
        }
        
        // Función para eliminar un contacto WhatsApp
        function removeWhatsAppContact(contactId) {
            const contact = document.querySelector(`input[name="wac_whatsapp_contact_${contactId}"]`).closest('.wac-whatsapp-contact');
            if (contact) {
                contact.remove();
                updateWhatsAppContactNumbers();
            }
        }
        
        // Función para actualizar los números de los contactos WhatsApp
        function updateWhatsAppContactNumbers() {
            const contacts = document.querySelectorAll('.wac-whatsapp-contact');
            contacts.forEach((contact, index) => {
                const label = contact.querySelector('strong');
                if (label) {
                    label.textContent = `WhatsApp ${index + 1}:`;
                }
            });
        }
        
        // Función para agregar una opción de contacto WhatsApp a un paso
        function addWhatsAppContactOption(stepId) {
            const optionsList = document.getElementById(`options_${stepId}`);
            const optionId = Date.now();
            
            // Obtener contactos disponibles
            const contacts = document.querySelectorAll('input[name^="wac_whatsapp_contact_"]');
            let contactOptions = '';
            contacts.forEach((contact, index) => {
                const contactNumber = contact.value.trim();
                if (contactNumber) {
                    contactOptions += `<option value="${index + 1}">WhatsApp ${index + 1} (${contactNumber})</option>`;
                }
            });
            
            if (!contactOptions) {
                alert('Primero agrega contactos de WhatsApp en la configuración');
                return;
            }
            
            const optionDiv = document.createElement('div');
            optionDiv.className = 'wac-option-item wac-whatsapp-contact-option';
            optionDiv.innerHTML = `
                <div class="wac-option-fields">
                    <input type="text" name="${stepId}_whatsapp_text_${optionId}" placeholder="Texto del contacto (ej: Hablar con ventas)" class="wac-option-text">
                    <select name="${stepId}_whatsapp_contact_${optionId}" class="wac-option-target">
                        <option value="">Seleccionar contacto</option>
                        ${contactOptions}
                    </select>
                    <button type="button" class="wac-remove-option" onclick="removeOption('${stepId}', '${optionId}')">×</button>
                </div>
            `;
            
            optionsList.appendChild(optionDiv);
        }
        
        function updateStepNumbers() {
            const steps = document.querySelectorAll('.wac-step');
            steps.forEach((step, index) => {
                const stepNumber = index + 1;
                step.setAttribute('data-step', stepNumber);
                step.querySelector('.wac-step-number').textContent = stepNumber;
                step.querySelector('.wac-step-title').textContent = `Mensaje ${stepNumber}`;
                
                // Actualizar onclick del botón de eliminar
                const deleteBtn = step.querySelector('.wac-step-delete');
                if (deleteBtn) {
                    deleteBtn.setAttribute('onclick', `deleteStep(${stepNumber})`);
                }
            });
        }
        
        function saveFunnelConfig() {
            const funnelData = {};
            const steps = document.querySelectorAll('.wac-step');
            
            // Guardar contactos de WhatsApp
            const whatsappContacts = document.querySelectorAll('input[name^="wac_whatsapp_contact_"]');
            const contacts = [];
            whatsappContacts.forEach(contact => {
                if (contact.value.trim()) {
                    contacts.push(contact.value.trim());
                }
            });
            funnelData['whatsapp_contacts'] = contacts;
            
            steps.forEach(step => {
                const stepId = step.getAttribute('data-step-id');
                const messageField = step.querySelector(`textarea[name="${stepId}_message"]`);
                
                if (messageField) {
                    funnelData[`${stepId}_message`] = messageField.value;
                    
                    // Recopilar opciones del paso (normales, de enlace y de WhatsApp)
                    const optionTexts = step.querySelectorAll(`input[name^="${stepId}_option_text_"]`);
                    const optionTargets = step.querySelectorAll(`input[name^="${stepId}_option_target_"]`);
                    const linkTexts = step.querySelectorAll(`input[name^="${stepId}_link_text_"]`);
                    const linkUrls = step.querySelectorAll(`input[name^="${stepId}_link_url_"]`);
                    const whatsappTexts = step.querySelectorAll(`input[name^="${stepId}_whatsapp_text_"]`);
                    const whatsappContacts = step.querySelectorAll(`select[name^="${stepId}_whatsapp_contact_"]`);
                    
                    const options = [];
                    
                    // Agregar opciones normales
                    optionTexts.forEach((textInput, index) => {
                        const targetInput = optionTargets[index];
                        if (textInput.value.trim() && targetInput.value.trim()) {
                            options.push({
                                text: textInput.value.trim(),
                                target: parseInt(targetInput.value),
                                type: 'step'
                            });
                        }
                    });
                    
                    // Agregar opciones de enlace
                    linkTexts.forEach((textInput, index) => {
                        const urlInput = linkUrls[index];
                        if (textInput.value.trim() && urlInput.value.trim()) {
                            options.push({
                                text: textInput.value.trim(),
                                url: urlInput.value.trim(),
                                type: 'link'
                            });
                        }
                    });
                    
                    // Agregar opciones de WhatsApp
                    whatsappTexts.forEach((textInput, index) => {
                        const contactSelect = whatsappContacts[index];
                        if (textInput.value.trim() && contactSelect.value.trim()) {
                            options.push({
                                text: textInput.value.trim(),
                                contact: parseInt(contactSelect.value),
                                type: 'whatsapp'
                            });
                        }
                    });
                    
                    funnelData[`${stepId}_options`] = options;
                }
            });
            
            // Guardar en campo oculto
            const hiddenField = document.getElementById('wac-funnel-steps-hidden');
            hiddenField.value = JSON.stringify(funnelData);
            
            // Mostrar notificación
            showNotice('✅ Datos guardados en memoria. Haz clic en "Actualizar" arriba para guardar permanentemente.', 'success');
            
            console.log('Datos del funnel:', funnelData);
        }
        
        function autoSaveSilent() {
            const funnelData = {};
            const steps = document.querySelectorAll('.wac-step');
            
            // Guardar contactos de WhatsApp
            const whatsappContacts = document.querySelectorAll('input[name^="wac_whatsapp_contact_"]');
            const contacts = [];
            whatsappContacts.forEach(contact => {
                if (contact.value.trim()) {
                    contacts.push(contact.value.trim());
                }
            });
            funnelData['whatsapp_contacts'] = contacts;
            
            steps.forEach(step => {
                const stepId = step.getAttribute('data-step-id');
                const messageField = step.querySelector(`textarea[name="${stepId}_message"]`);
                
                if (messageField) {
                    funnelData[`${stepId}_message`] = messageField.value;
                    
                    // Recopilar opciones del paso (normales, de enlace y de WhatsApp)
                    const optionTexts = step.querySelectorAll(`input[name^="${stepId}_option_text_"]`);
                    const optionTargets = step.querySelectorAll(`input[name^="${stepId}_option_target_"]`);
                    const linkTexts = step.querySelectorAll(`input[name^="${stepId}_link_text_"]`);
                    const linkUrls = step.querySelectorAll(`input[name^="${stepId}_link_url_"]`);
                    const whatsappTexts = step.querySelectorAll(`input[name^="${stepId}_whatsapp_text_"]`);
                    const whatsappContacts = step.querySelectorAll(`select[name^="${stepId}_whatsapp_contact_"]`);
                    
                    const options = [];
                    
                    // Agregar opciones normales
                    optionTexts.forEach((textInput, index) => {
                        const targetInput = optionTargets[index];
                        if (textInput.value.trim() && targetInput.value.trim()) {
                            options.push({
                                text: textInput.value.trim(),
                                target: parseInt(targetInput.value),
                                type: 'step'
                            });
                        }
                    });
                    
                    // Agregar opciones de enlace
                    linkTexts.forEach((textInput, index) => {
                        const urlInput = linkUrls[index];
                        if (textInput.value.trim() && urlInput.value.trim()) {
                            options.push({
                                text: textInput.value.trim(),
                                url: urlInput.value.trim(),
                                type: 'link'
                            });
                        }
                    });
                    
                    // Agregar opciones de WhatsApp
                    whatsappTexts.forEach((textInput, index) => {
                        const contactSelect = whatsappContacts[index];
                        if (textInput.value.trim() && contactSelect.value.trim()) {
                            options.push({
                                text: textInput.value.trim(),
                                contact: parseInt(contactSelect.value),
                                type: 'whatsapp'
                            });
                        }
                    });
                    
                    funnelData[`${stepId}_options`] = options;
                }
            });
            
            // Guardar silenciosamente en campo oculto
            const hiddenField = document.getElementById('wac-funnel-steps-hidden');
            hiddenField.value = JSON.stringify(funnelData);
        }
        
        function loadFunnelConfig() {
            console.log('=== INICIANDO LOADFUNNELCONFIG ===');
            console.log('wacSavedSteps:', wacSavedSteps);
            
            // Cargar contactos de WhatsApp primero
            if (wacSavedSteps.whatsapp_contacts && Array.isArray(wacSavedSteps.whatsapp_contacts)) {
                console.log('Cargando contactos WhatsApp:', wacSavedSteps.whatsapp_contacts);
                const contactsContainer = document.getElementById('wac-whatsapp-contacts');
                if (contactsContainer) {
                    contactsContainer.innerHTML = '';
                    wacSavedSteps.whatsapp_contacts.forEach((contactNumber, index) => {
                        if (contactNumber.trim()) {
                            const contactId = Date.now() + index;
                            const contactDiv = document.createElement('div');
                            contactDiv.className = 'wac-whatsapp-contact';
                            contactDiv.style.cssText = 'margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;';
                            contactDiv.innerHTML = `
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <strong>WhatsApp ${index + 1}:</strong>
                                    <input type="text" name="wac_whatsapp_contact_${contactId}" placeholder="Número (ej: +573001234567)" style="flex: 1;" value="${contactNumber}" />
                                    <button type="button" class="button" onclick="removeWhatsAppContact('${contactId}')">Eliminar</button>
                                </div>
                            `;
                            contactsContainer.appendChild(contactDiv);
                        }
                    });
                }
            }
            
            if (typeof wacSavedSteps !== 'undefined' && wacSavedSteps && Object.keys(wacSavedSteps).length > 0) {
                console.log('Cargando configuración guardada:', wacSavedSteps);
                
                // Limpiar pasos existentes
                const container = document.getElementById('wac-steps-container');
                if (!container) {
                    console.error('No se encontró el contenedor wac-steps-container');
                    return;
                }
                container.innerHTML = '';
                console.log('Contenedor limpiado');
                
                // Recrear pasos desde datos guardados
                const stepIds = new Set();
                Object.keys(wacSavedSteps).forEach(fieldName => {
                    console.log('Procesando campo:', fieldName);
                    const match = fieldName.match(/^(step_\d+_\d+)_/);
                    if (match) {
                        const stepId = match[1];
                        console.log('Step ID encontrado:', stepId);
                        stepIds.add(stepId);
                    }
                });
                
                console.log('Step IDs encontrados:', Array.from(stepIds));
                
                if (stepIds.size > 0) {
                    let stepNumber = 1;
                    stepIds.forEach(stepId => {
                        console.log(`Recreando paso ${stepNumber}: ${stepId}`);
                        
                        const messageValue = wacSavedSteps[stepId + '_message'] || '';
                        const optionsValue = wacSavedSteps[stepId + '_options'] || [];
                        
                        console.log(`  Mensaje: "${messageValue}"`);
                        console.log(`  Opciones:`, optionsValue);
                        
                        const newStep = document.createElement('div');
                        newStep.className = 'wac-step';
                        newStep.setAttribute('data-step', stepNumber);
                        newStep.setAttribute('data-step-id', stepId);
                        
                        // Generar HTML de opciones
                        let optionsHTML = '';
                        if (Array.isArray(optionsValue) && optionsValue.length > 0) {
                            optionsValue.forEach((option, index) => {
                                const optionId = Date.now() + index;
                                if (option.type === 'link') {
                                    // Opción de enlace
                                    optionsHTML += `
                                        <div class="wac-option-item wac-link-option">
                                            <div class="wac-option-fields">
                                                <input type="text" name="${stepId}_link_text_${optionId}" placeholder="Texto del enlace (ej: Ver portafolio)" class="wac-option-text" value="${option.text || ''}">
                                                <input type="url" name="${stepId}_link_url_${optionId}" placeholder="URL (ej: https://ejemplo.com)" class="wac-option-target" value="${option.url || ''}">
                                                <button type="button" class="wac-remove-option" onclick="removeOption('${stepId}', '${optionId}')">×</button>
                                            </div>
                                        </div>
                                    `;
                                } else if (option.type === 'whatsapp') {
                                    // Opción de WhatsApp
                                    const contacts = wacSavedSteps.whatsapp_contacts || [];
                                    let contactOptions = '';
                                    contacts.forEach((contactNumber, index) => {
                                        if (contactNumber.trim()) {
                                            const selected = option.contact === (index + 1) ? 'selected' : '';
                                            contactOptions += `<option value="${index + 1}" ${selected}>WhatsApp ${index + 1} (${contactNumber})</option>`;
                                        }
                                    });
                                    
                                    optionsHTML += `
                                        <div class="wac-option-item wac-whatsapp-contact-option">
                                            <div class="wac-option-fields">
                                                <input type="text" name="${stepId}_whatsapp_text_${optionId}" placeholder="Texto del contacto (ej: Hablar con ventas)" class="wac-option-text" value="${option.text || ''}">
                                                <select name="${stepId}_whatsapp_contact_${optionId}" class="wac-option-target">
                                                    <option value="">Seleccionar contacto</option>
                                                    ${contactOptions}
                                                </select>
                                                <button type="button" class="wac-remove-option" onclick="removeOption('${stepId}', '${optionId}')">×</button>
                                            </div>
                                        </div>
                                    `;
                                } else {
                                    // Opción normal de paso
                                    optionsHTML += `
                                        <div class="wac-option-item">
                                            <div class="wac-option-fields">
                                                <input type="text" name="${stepId}_option_text_${optionId}" placeholder="Texto de la opción (ej: Cotización)" class="wac-option-text" value="${option.text || ''}">
                                                <input type="number" name="${stepId}_option_target_${optionId}" placeholder="Paso destino" class="wac-option-target" min="1" value="${option.target || ''}">
                                                <button type="button" class="wac-remove-option" onclick="removeOption('${stepId}', '${optionId}')">×</button>
                                            </div>
                                        </div>
                                    `;
                                }
                            });
                        }
                        
                        newStep.innerHTML = `
                            <div class="wac-step-header">
                                <span class="wac-step-number">${stepNumber}</span>
                                <span class="wac-step-title">Mensaje ${stepNumber}</span>
                                <button class="wac-step-delete" onclick="deleteStep(${stepNumber})">×</button>
                            </div>
                            <div class="wac-step-content">
                                <label>Mensaje:</label>
                                <textarea name="${stepId}_message" placeholder="Escribe tu mensaje aquí...">${messageValue}</textarea>
                                
                                <div class="wac-options-section">
                                    <label>Opciones de respuesta:</label>
                                    <div class="wac-options-list" id="options_${stepId}">
                                        ${optionsHTML}
                                    </div>
                                    <div style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;">
                                        <button type="button" class="wac-add-option-btn" onclick="addOption('${stepId}')">
                                            + Añadir opción
                                        </button>
                                        <button type="button" class="wac-add-option-btn" onclick="addLinkOption('${stepId}')" style="background: #28a745; border-color: #28a745;">
                                            🔗 Añadir enlace
                                        </button>
                                        <button type="button" class="wac-add-option-btn" onclick="addWhatsAppContactOption('${stepId}')" style="background: #25d366; border-color: #25d366;">
                                            📱 Añadir contacto
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="wac-tip">
                                    <strong>💡 Tip:</strong> Agrega opciones para que el usuario pueda elegir qué hacer después de leer tu mensaje.
                                </div>
                            </div>
                        `;
                        
                        container.appendChild(newStep);
                        console.log(`Paso ${stepNumber} agregado al DOM`);
                        stepNumber++;
                    });
                    
                    updateStepNumbers();
                    showNotice(`✅ ${stepIds.size} paso(s) cargado(s) desde la base de datos.`, 'success');
                    console.log('Configuración cargada exitosamente');
                } else {
                    showNotice('⚠️ No se encontraron pasos en los datos guardados.', 'warning');
                    console.log('No se encontraron step IDs válidos');
                }
            } else {
                showNotice('⚠️ No hay configuración guardada.', 'warning');
                console.log('No hay datos guardados');
            }
        }
        
        function clearAllSteps() {
            if (confirm('¿Estás seguro de eliminar todos los pasos?')) {
                const container = document.getElementById('wac-steps-container');
                container.innerHTML = '';
                document.getElementById('wac-funnel-steps-hidden').value = '';
                showNotice('🗑️ Todos los pasos eliminados.', 'info');
            }
        }
        
        function showNotice(message, type) {
            const notice = document.getElementById('wac-save-notice');
            const colors = {
                success: '#d4edda',
                warning: '#fff3cd',
                info: '#d1ecf1'
            };
            
            notice.innerHTML = message;
            notice.style.backgroundColor = colors[type] || colors.info;
            notice.style.display = 'block';
            
            setTimeout(() => {
                notice.style.display = 'none';
            }, 3000);
        }
        
        function debugEverything() {
            const hiddenField = document.getElementById('wac-funnel-steps-hidden');
            const steps = document.querySelectorAll('.wac-step');
            const postId = window.location.href.match(/post=(\d+)/);
            const currentPostId = postId ? postId[1] : 'NO ENCONTRADO';
            
            let debugInfo = `=== WAC CHAT FUNNELS DEBUG ===\n`;
            debugInfo += `Timestamp: ${new Date().toLocaleString()}\n`;
            debugInfo += `URL: ${window.location.href}\n`;
            debugInfo += `Post ID: ${currentPostId}\n\n`;
            
            debugInfo += `=== JAVASCRIPT STATE ===\n`;
            debugInfo += `Pasos en pantalla: ${steps.length}\n`;
            debugInfo += `Campo oculto length: ${hiddenField.value.length} caracteres\n`;
            debugInfo += `wacSavedSteps definido: ${typeof wacSavedSteps !== 'undefined' ? 'SÍ' : 'NO'}\n\n`;
            
            if (steps.length > 0) {
                debugInfo += `=== PASOS ACTUALES ===\n`;
                steps.forEach((step, index) => {
                    const stepId = step.getAttribute('data-step-id');
                    const messageField = step.querySelector(`textarea[name="${stepId}_message"]`);
                    const nextField = step.querySelector(`select[name="${stepId}_next"]`);
                    
                    debugInfo += `Paso ${index + 1}:\n`;
                    debugInfo += `  ID: ${stepId}\n`;
                    debugInfo += `  Mensaje: "${messageField ? messageField.value : 'NO ENCONTRADO'}"\n`;
                    debugInfo += `  Acción: "${nextField ? nextField.value : 'NO ENCONTRADO'}"\n\n`;
                });
            } else {
                debugInfo += `=== PROBLEMA: NO HAY PASOS VISIBLES ===\n\n`;
            }
            
            debugInfo += `=== CAMPO OCULTO ===\n`;
            if (hiddenField.value && hiddenField.value !== '{}') {
                try {
                    const savedData = JSON.parse(hiddenField.value);
                    debugInfo += `Contenido válido:\n${JSON.stringify(savedData, null, 2)}\n\n`;
                } catch (e) {
                    debugInfo += `Error al parsear: ${e.message}\n`;
                    debugInfo += `Contenido crudo: ${hiddenField.value}\n\n`;
                }
            } else {
                debugInfo += `VACÍO o inválido\n\n`;
            }
            
            debugInfo += `=== DATOS CARGADOS (wacSavedSteps) ===\n`;
            if (typeof wacSavedSteps !== 'undefined' && wacSavedSteps && Object.keys(wacSavedSteps).length > 0) {
                debugInfo += `Contenido:\n${JSON.stringify(wacSavedSteps, null, 2)}\n\n`;
            } else {
                debugInfo += `VACÍO o no definido\n\n`;
            }
            
            // Mostrar en el área de debug
            document.getElementById('wac-debug-text').textContent = debugInfo;
            document.getElementById('wac-debug-output').style.display = 'block';
        }
        
        function copyDebugOutput() {
            const debugText = document.getElementById('wac-debug-text').textContent;
            navigator.clipboard.writeText(debugText).then(() => {
                showNotice('✅ Debug copiado al portapapeles', 'success');
            }).catch(() => {
                showNotice('❌ Error al copiar', 'warning');
            });
        }
        
        // Auto-guardado silencioso cada 2 segundos
        setInterval(autoSaveSilent, 2000);
        
        // Event listeners para guardar automáticamente cuando cambie algo
        document.addEventListener('input', function(e) {
            if (e.target.matches('textarea[name*="_message"], input[name*="_option_text_"], input[name*="_option_target_"]')) {
                autoSaveSilent();
            }
        });
        
        document.addEventListener('change', function(e) {
            if (e.target.matches('select[name*="_next"]')) {
                autoSaveSilent();
            }
        });
        
        // Cargar configuración al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM cargado, verificando datos guardados...');
            if (typeof wacSavedSteps !== 'undefined' && wacSavedSteps && Object.keys(wacSavedSteps).length > 0) {
                console.log('Datos encontrados, cargando configuración...');
                loadFunnelConfig();
            } else {
                console.log('No hay datos guardados para cargar');
            }
        });
        </script>
        
        <?php
    }
    
    public function save_metaboxes($post_id) {
        // Verificar nonce y permisos
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Guardar configuración básica
        if (isset($_POST['wac_enabled'])) {
            update_post_meta($post_id, '_wac_enabled', sanitize_text_field($_POST['wac_enabled']));
        }
        
        if (isset($_POST['wac_teaser_text'])) {
            update_post_meta($post_id, '_wac_teaser_text', sanitize_text_field($_POST['wac_teaser_text']));
        }
        
        if (isset($_POST['wac_teaser_delay'])) {
            update_post_meta($post_id, '_wac_teaser_delay', intval($_POST['wac_teaser_delay']));
        }
        
        if (isset($_POST['wac_whatsapp_number'])) {
            update_post_meta($post_id, '_wac_whatsapp_number', sanitize_text_field($_POST['wac_whatsapp_number']));
        }
        
        // Guardar pasos del funnel
        if (isset($_POST['wac_funnel_steps'])) {
            $steps_data = json_decode(stripslashes($_POST['wac_funnel_steps']), true);
            update_post_meta($post_id, '_wac_funnel_steps_data', $steps_data);
        }
    }
    
    public function enqueue_scripts() {
        // Solo cargar en páginas públicas
        if (is_admin()) return;
        
        // Verificar si hay un funnel activo
        $active_funnel = $this->get_active_funnel();
        if (!$active_funnel) return;
        
        // Encolar CSS y JS del widget
        wp_enqueue_style('wac-chat-widget', plugin_dir_url(__FILE__) . 'assets/css/chat-widget.css', array(), time());
        wp_enqueue_script('wac-chat-widget', plugin_dir_url(__FILE__) . 'assets/js/chat-widget.js', array(), time(), true);
        
        // Pasar datos del funnel al JavaScript
        $funnel_data = $this->prepare_funnel_data($active_funnel);
        wp_localize_script('wac-chat-widget', 'wacFunnelData', $funnel_data);
        
        // Agregar el widget al footer
        add_action('wp_footer', array($this, 'render_chat_widget'));
    }
    
    private function get_active_funnel() {
        // Buscar un funnel activo
        $funnels = get_posts(array(
            'post_type' => 'wac_chat_funnel',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => '_wac_enabled',
                    'value' => '1',
                    'compare' => '='
                )
            )
        ));
        
        return !empty($funnels) ? $funnels[0] : null;
    }
    
    private function prepare_funnel_data($funnel_post) {
        $steps_data = get_post_meta($funnel_post->ID, '_wac_funnel_steps_data', true);
        $teaser_text = get_post_meta($funnel_post->ID, '_wac_teaser_text', true);
        $teaser_delay = get_post_meta($funnel_post->ID, '_wac_teaser_delay', true);
        $whatsapp_number = get_post_meta($funnel_post->ID, '_wac_whatsapp_number', true);
        
        // Debug: Log de datos
        error_log('WAC Debug - steps_data: ' . print_r($steps_data, true));
        
        // Convertir datos de pasos al formato esperado por el frontend
        $steps = array();
        if ($steps_data && is_array($steps_data)) {
            // Agrupar por step_id
            $step_groups = array();
            foreach ($steps_data as $key => $value) {
                if (strpos($key, '_message') !== false) {
                    $step_id = str_replace('_message', '', $key);
                    $step_groups[$step_id] = array(
                        'id' => $step_id,
                        'message' => $value
                    );
                } elseif (strpos($key, '_next') !== false) {
                    $step_id = str_replace('_next', '', $key);
                    if (!isset($step_groups[$step_id])) {
                        $step_groups[$step_id] = array('id' => $step_id);
                    }
                    $step_groups[$step_id]['next'] = $value;
                } elseif (strpos($key, '_options') !== false) {
                    $step_id = str_replace('_options', '', $key);
                    if (!isset($step_groups[$step_id])) {
                        $step_groups[$step_id] = array('id' => $step_id);
                    }
                    $step_groups[$step_id]['options'] = $value;
                }
            }
            
            // Convertir a array indexado y ordenar por orden de creación
            foreach ($step_groups as $step) {
                $steps[] = array(
                    'id' => $step['id'],
                    'message' => $step['message'] ?? 'Sin mensaje',
                    'next' => $step['next'] ?? 'end',
                    'options' => $step['options'] ?? array()
                );
            }
        }
        
        error_log('WAC Debug - steps procesados: ' . print_r($steps, true));
        
        // Obtener contactos de WhatsApp
        $whatsapp_contacts = array();
        if (isset($steps_data['whatsapp_contacts']) && is_array($steps_data['whatsapp_contacts'])) {
            $whatsapp_contacts = $steps_data['whatsapp_contacts'];
        }
        
        return array(
            'steps' => $steps,
            'whatsapp_contacts' => $whatsapp_contacts,
            'teaser' => array(
                'text' => $teaser_text ?: '¿Necesitas ayuda?',
                'delay' => $teaser_delay ?: 3000
            ),
            'whatsapp' => $whatsapp_number ?: '+573142400850'
        );
    }
    
    public function render_chat_widget() {
        $funnel_data = $this->get_active_funnel();
        if (!$funnel_data) return;
        
        $teaser_text = get_post_meta($funnel_data->ID, '_wac_teaser_text', true) ?: '¿Necesitas ayuda?';
        $teaser_delay = get_post_meta($funnel_data->ID, '_wac_teaser_delay', true) ?: 3000;
        ?>
        
        <!-- WAC Chat Widget -->
        <div id="wac-chat-toggle" style="display: none;">
            💬
        </div>
        
        <div id="wac-chat-widget" class="wac-open">
            <div class="wac-widget-header">
                <h3 class="wac-widget-title">Asistente Virtual</h3>
                <button class="wac-widget-close">×</button>
            </div>
            <div class="wac-widget-content">
                <!-- El contenido se carga dinámicamente via JavaScript -->
            </div>
        </div>
        
        <!-- Datos del funnel para JavaScript -->
        <script type="application/json" id="wac-funnel-data">
            <?php echo json_encode($this->prepare_funnel_data($funnel_data)); ?>
        </script>
        
        <script>
        // Mostrar el teaser después del delay
        setTimeout(function() {
            document.getElementById('wac-chat-toggle').style.display = 'block';
        }, <?php echo intval($teaser_delay); ?>);
        </script>
        
        <?php
    }
    
    public function ajax_debug_database() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wac_debug_nonce')) {
            wp_die('Nonce verification failed');
        }
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id) {
            echo "❌ Post ID no válido";
            wp_die();
        }
        
        // Obtener todos los meta del post
        $all_meta = get_post_meta($post_id);
        $funnel_steps = get_post_meta($post_id, '_wac_funnel_steps_data', true);
        
        $debug_info = "📋 Post ID: {$post_id}\n";
        $debug_info .= "📊 Post existe: " . (get_post($post_id) ? 'SÍ' : 'NO') . "\n\n";
        
        $debug_info .= "🗄️ META COMPLETO:\n";
        foreach ($all_meta as $key => $value) {
            $debug_info .= "  {$key}: " . print_r($value, true) . "\n";
        }
        
        $debug_info .= "\n🎯 FUNNEL STEPS ESPECÍFICO:\n";
        if ($funnel_steps) {
            $debug_info .= "  Encontrado: SÍ\n";
            $debug_info .= "  Tipo: " . gettype($funnel_steps) . "\n";
            $debug_info .= "  Contenido: " . print_r($funnel_steps, true) . "\n";
        } else {
            $debug_info .= "  Encontrado: NO\n";
            $debug_info .= "  Valor: " . var_export($funnel_steps, true) . "\n";
        }
        
        echo $debug_info;
        wp_die();
    }
}

// Inicializar el plugin
new WAC_Chat_Funnels_Simple();
?>
