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
        </style>
        
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
                <button type="button" class="button button-small" onclick="debugSaveStatus()">🔍 Ver Estado de Guardado</button>
            </div>
            
            <div id="wac-save-notice" style="display:none; margin-top: 15px; padding: 10px; border-radius: 4px;"></div>
        </div>
        
        <!-- Campo oculto para guardar -->
        <input type="hidden" name="wac_funnel_steps" id="wac-funnel-steps-hidden" value="" />
        
        <!-- Script para pasar datos guardados -->
        <script type="text/javascript">
            var wacSavedSteps = <?php echo json_encode($saved_steps ?: array()); ?>;
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
                    
                    <label>Después de este mensaje:</label>
                    <select name="${stepId}_next">
                        <option value="">Seleccionar...</option>
                        <option value="whatsapp">Ir a WhatsApp</option>
                        <option value="end">Finalizar chat</option>
                    </select>
                    
                    <div class="wac-tip">
                        <strong>💡 Tip:</strong> Este es un paso tipo "Mensaje" simple. Solo necesitas escribir el texto y elegir qué pasa después.
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
            
            steps.forEach(step => {
                const stepId = step.getAttribute('data-step-id');
                const messageField = step.querySelector(`textarea[name="${stepId}_message"]`);
                const nextField = step.querySelector(`select[name="${stepId}_next"]`);
                
                if (messageField && nextField) {
                    funnelData[`${stepId}_message`] = messageField.value;
                    funnelData[`${stepId}_next`] = nextField.value;
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
            
            steps.forEach(step => {
                const stepId = step.getAttribute('data-step-id');
                const messageField = step.querySelector(`textarea[name="${stepId}_message"]`);
                const nextField = step.querySelector(`select[name="${stepId}_next"]`);
                
                if (messageField && nextField) {
                    funnelData[`${stepId}_message`] = messageField.value;
                    funnelData[`${stepId}_next`] = nextField.value;
                }
            });
            
            // Guardar silenciosamente en campo oculto
            const hiddenField = document.getElementById('wac-funnel-steps-hidden');
            hiddenField.value = JSON.stringify(funnelData);
        }
        
        function loadFunnelConfig() {
            if (typeof wacSavedSteps !== 'undefined' && wacSavedSteps && Object.keys(wacSavedSteps).length > 0) {
                console.log('Cargando configuración guardada:', wacSavedSteps);
                
                // Limpiar pasos existentes
                const container = document.getElementById('wac-steps-container');
                container.innerHTML = '';
                
                // Recrear pasos desde datos guardados
                const stepIds = new Set();
                Object.keys(wacSavedSteps).forEach(fieldName => {
                    const match = fieldName.match(/^(step_\d+_\d+)_/);
                    if (match) {
                        stepIds.add(match[1]);
                    }
                });
                
                stepIds.forEach(stepId => {
                    const stepNumber = stepIds.size > 0 ? Array.from(stepIds).indexOf(stepId) + 1 : 1;
                    
                    const newStep = document.createElement('div');
                    newStep.className = 'wac-step';
                    newStep.setAttribute('data-step', stepNumber);
                    newStep.setAttribute('data-step-id', stepId);
                    newStep.innerHTML = `
                        <div class="wac-step-header">
                            <span class="wac-step-number">${stepNumber}</span>
                            <span class="wac-step-title">Mensaje ${stepNumber}</span>
                            <button class="wac-step-delete" onclick="deleteStep(${stepNumber})">×</button>
                        </div>
                        <div class="wac-step-content">
                            <label>Mensaje:</label>
                            <textarea name="${stepId}_message" placeholder="Escribe tu mensaje aquí...">${wacSavedSteps[stepId + '_message'] || ''}</textarea>
                            
                            <label>Después de este mensaje:</label>
                            <select name="${stepId}_next">
                                <option value="">Seleccionar...</option>
                                <option value="whatsapp" ${wacSavedSteps[stepId + '_next'] === 'whatsapp' ? 'selected' : ''}>Ir a WhatsApp</option>
                                <option value="end" ${wacSavedSteps[stepId + '_next'] === 'end' ? 'selected' : ''}>Finalizar chat</option>
                            </select>
                            
                            <div class="wac-tip">
                                <strong>💡 Tip:</strong> Este es un paso tipo "Mensaje" simple. Solo necesitas escribir el texto y elegir qué pasa después.
                            </div>
                        </div>
                    `;
                    
                    container.appendChild(newStep);
                });
                
                updateStepNumbers();
                showNotice('✅ Configuración cargada desde la base de datos.', 'success');
            } else {
                showNotice('⚠️ No hay configuración guardada.', 'warning');
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
        
        function debugSaveStatus() {
            const hiddenField = document.getElementById('wac-funnel-steps-hidden');
            const steps = document.querySelectorAll('.wac-step');
            
            let debugInfo = `🔍 DEBUG - Estado de Guardado\n\n`;
            debugInfo += `📊 Pasos en pantalla: ${steps.length}\n`;
            debugInfo += `💾 Campo oculto: ${hiddenField.value.length} caracteres\n\n`;
            
            if (steps.length > 0) {
                debugInfo += `📝 Datos actuales:\n`;
                steps.forEach((step, index) => {
                    const stepId = step.getAttribute('data-step-id');
                    const messageField = step.querySelector(`textarea[name="${stepId}_message"]`);
                    const nextField = step.querySelector(`select[name="${stepId}_next"]`);
                    
                    debugInfo += `Paso ${index + 1} (${stepId}):\n`;
                    debugInfo += `  Mensaje: "${messageField ? messageField.value : 'NO ENCONTRADO'}"\n`;
                    debugInfo += `  Acción: "${nextField ? nextField.value : 'NO ENCONTRADO'}"\n\n`;
                });
            }
            
            if (hiddenField.value) {
                try {
                    const savedData = JSON.parse(hiddenField.value);
                    debugInfo += `💾 Datos guardados en campo oculto:\n`;
                    debugInfo += JSON.stringify(savedData, null, 2);
                } catch (e) {
                    debugInfo += `❌ Error al parsear datos guardados: ${e.message}\n`;
                    debugInfo += `Contenido: ${hiddenField.value}`;
                }
            } else {
                debugInfo += `❌ Campo oculto está vacío`;
            }
            
            alert(debugInfo);
        }
        
        // Auto-guardado silencioso cada 2 segundos
        setInterval(autoSaveSilent, 2000);
        
        // Event listeners para guardar automáticamente cuando cambie algo
        document.addEventListener('input', function(e) {
            if (e.target.matches('textarea[name*="_message"], select[name*="_next"]')) {
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
            if (typeof wacSavedSteps !== 'undefined' && wacSavedSteps && Object.keys(wacSavedSteps).length > 0) {
                loadFunnelConfig();
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
        // Scripts del frontend (por implementar)
    }
}

// Inicializar el plugin
new WAC_Chat_Funnels_Simple();
?>
