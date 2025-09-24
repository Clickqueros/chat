<?php
/**
 * Plugin Name: WAC Chat Funnels
 * Plugin URI: https://wacosta.com/wac-chat-funnels
 * Description: Sistema simple de chat funnels para capturar leads y dirigir a WhatsApp
 * Version: 2.0.0
 * Author: WACosta
 * License: GPL v2 or later
 * Text Domain: wac-chat-funnels
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('WAC_CHAT_VERSION', '2.0.0');
define('WAC_CHAT_PLUGIN_FILE', __FILE__);
define('WAC_CHAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WAC_CHAT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WAC_CHAT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Clase principal del plugin - SIMPLE y FUNCIONAL
 */
class WAC_Chat_Funnels {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('wp_footer', array($this, 'render_chat_widget'));
        
        // Hooks de activación/desactivación
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Registrar Custom Post Type para funnels
        $this->register_post_type();
        
        // Agregar metaboxes
        add_action('add_meta_boxes', array($this, 'add_metaboxes'));
        add_action('save_post', array($this, 'save_metaboxes'));
        
        // Agregar menú de admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    public function register_post_type() {
        $labels = array(
            'name' => __('Chat Funnels', 'wac-chat-funnels'),
            'singular_name' => __('Chat Funnel', 'wac-chat-funnels'),
            'menu_name' => __('Chat Funnels', 'wac-chat-funnels'),
            'add_new' => __('Agregar Nuevo', 'wac-chat-funnels'),
            'add_new_item' => __('Agregar Nuevo Funnel', 'wac-chat-funnels'),
            'edit_item' => __('Editar Funnel', 'wac-chat-funnels'),
            'new_item' => __('Nuevo Funnel', 'wac-chat-funnels'),
            'view_item' => __('Ver Funnel', 'wac-chat-funnels'),
            'search_items' => __('Buscar Funnels', 'wac-chat-funnels'),
            'not_found' => __('No se encontraron funnels', 'wac-chat-funnels'),
            'not_found_in_trash' => __('No se encontraron funnels en la papelera', 'wac-chat-funnels')
        );
        
        $args = array(
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => false,
            'show_in_rest' => false,
            'query_var' => false,
            'rewrite' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-format-chat',
            'supports' => array('title', 'editor')
        );
        
        register_post_type('wac_chat_funnel', $args);
    }
    
    public function add_metaboxes() {
        add_meta_box(
            'wac-funnel-config',
            __('Configuración del Funnel', 'wac-chat-funnels'),
            array($this, 'funnel_config_metabox'),
            'wac_chat_funnel',
            'normal',
            'high'
        );
    }
    
    public function funnel_config_metabox($post) {
        wp_nonce_field('wac_funnel_config', 'wac_funnel_config_nonce');
        
        $enabled = get_post_meta($post->ID, '_wac_funnel_enabled', true);
        $teaser_text = get_post_meta($post->ID, '_wac_funnel_teaser_text', true);
        $teaser_delay = get_post_meta($post->ID, '_wac_funnel_teaser_delay', true);
        $whatsapp_number = get_post_meta($post->ID, '_wac_funnel_whatsapp_number', true);
        
        // Cargar configuración del funnel guardada
        $saved_steps = get_post_meta($post->ID, '_wac_funnel_steps_data', true);
        
        // Valores por defecto
        if (empty($teaser_text)) $teaser_text = '¿Necesitas ayuda?';
        if (empty($teaser_delay)) $teaser_delay = 3000;
        if (empty($whatsapp_number)) $whatsapp_number = '+573154543344';
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="wac_funnel_enabled"><?php _e('Estado', 'wac-chat-funnels'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="wac_funnel_enabled" name="wac_funnel_enabled" value="1" <?php checked($enabled, '1'); ?>>
                    <label for="wac_funnel_enabled"><?php _e('Activar este funnel', 'wac-chat-funnels'); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wac_funnel_teaser_text"><?php _e('Texto del Teaser', 'wac-chat-funnels'); ?></label>
                </th>
                <td>
                    <input type="text" id="wac_funnel_teaser_text" name="wac_funnel_teaser_text" value="<?php echo esc_attr($teaser_text); ?>" class="regular-text">
                    <p class="description"><?php _e('Texto que aparece en la burbuja inicial', 'wac-chat-funnels'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wac_funnel_teaser_delay"><?php _e('Retraso del Teaser (ms)', 'wac-chat-funnels'); ?></label>
                </th>
                <td>
                    <input type="number" id="wac_funnel_teaser_delay" name="wac_funnel_teaser_delay" value="<?php echo esc_attr($teaser_delay); ?>" min="0" max="30000" step="500">
                    <p class="description"><?php _e('Tiempo antes de mostrar el teaser', 'wac-chat-funnels'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wac_funnel_whatsapp_number"><?php _e('Número de WhatsApp', 'wac-chat-funnels'); ?></label>
                </th>
                <td>
                    <input type="text" id="wac_funnel_whatsapp_number" name="wac_funnel_whatsapp_number" value="<?php echo esc_attr($whatsapp_number); ?>" class="regular-text">
                    <p class="description"><?php _e('Número completo con código de país (ej: +573154543344)', 'wac-chat-funnels'); ?></p>
                </td>
            </tr>
        </table>
        
        <h3><?php _e('Editor de Funnel', 'wac-chat-funnels'); ?></h3>
        <div id="wac-funnel-builder">
            <div id="wac-steps-container">
                <div class="wac-step" data-step="1">
                    <div class="wac-step-header">
                        <span class="wac-step-number">1</span>
                        <span class="wac-step-title">Mensaje de Bienvenida</span>
                        <button class="wac-step-delete" onclick="deleteStep(1)">×</button>
                    </div>
                    <div class="wac-step-content">
                        <label>Mensaje:</label>
                        <textarea name="step_1_message" placeholder="Escribe el mensaje de bienvenida...">¡Hola! Soy tu asistente virtual. ¿En qué puedo ayudarte?</textarea>
                        <label>Siguiente paso:</label>
                        <select name="step_1_next">
                            <option value="2">Paso 2 - Opciones</option>
                        </select>
                    </div>
                </div>
                
                <div class="wac-step" data-step="2">
                    <div class="wac-step-header">
                        <span class="wac-step-number">2</span>
                        <span class="wac-step-title">Pregunta con Opciones</span>
                        <button class="wac-step-delete" onclick="deleteStep(2)">×</button>
                    </div>
                    <div class="wac-step-content">
                        <label>Pregunta:</label>
                        <textarea name="step_2_message" placeholder="Escribe tu pregunta...">Elige una opción:</textarea>
                        
                        <div class="wac-options">
                            <div class="wac-option">
                                <input type="text" name="step_2_option_1_text" placeholder="Texto del botón" value="Cotización">
                                <select name="step_2_option_1_action">
                                    <option value="3">Paso 3 - Formulario</option>
                                    <option value="whatsapp">WhatsApp directo</option>
                                    <option value="redirect">Redirigir a URL</option>
                                </select>
                                <input type="text" name="step_2_option_1_url" placeholder="URL (si es redirección)" style="display:none;">
                            </div>
                            
                            <div class="wac-option">
                                <input type="text" name="step_2_option_2_text" placeholder="Texto del botón" value="WhatsApp">
                                <select name="step_2_option_2_action">
                                    <option value="whatsapp">WhatsApp directo</option>
                                    <option value="3">Paso 3 - Formulario</option>
                                    <option value="redirect">Redirigir a URL</option>
                                </select>
                                <input type="text" name="step_2_option_2_url" placeholder="URL (si es redirección)" style="display:none;">
                            </div>
                            
                            <div class="wac-option">
                                <input type="text" name="step_2_option_3_text" placeholder="Texto del botón" value="Ver portafolio">
                                <select name="step_2_option_3_action">
                                    <option value="redirect">Redirigir a URL</option>
                                    <option value="3">Paso 3 - Formulario</option>
                                    <option value="whatsapp">WhatsApp directo</option>
                                </select>
                                <input type="text" name="step_2_option_3_url" placeholder="URL (si es redirección)" value="/portafolio">
                            </div>
                        </div>
                        
                        <button type="button" class="button" onclick="addOption(2)">+ Agregar Opción</button>
                    </div>
                </div>
                
                <div class="wac-step" data-step="3">
                    <div class="wac-step-header">
                        <span class="wac-step-number">3</span>
                        <span class="wac-step-title">Formulario de Captura</span>
                        <button class="wac-step-delete" onclick="deleteStep(3)">×</button>
                    </div>
                    <div class="wac-step-content">
                        <label>Mensaje del formulario:</label>
                        <textarea name="step_3_message" placeholder="Escribe el mensaje...">Déjame tus datos:</textarea>
                        
                        <div class="wac-form-fields">
                            <div class="wac-field">
                                <label>
                                    <input type="checkbox" name="step_3_field_nombre" checked> Nombre (requerido)
                                </label>
                            </div>
                            <div class="wac-field">
                                <label>
                                    <input type="checkbox" name="step_3_field_email" checked> Email (requerido)
                                </label>
                            </div>
                            <div class="wac-field">
                                <label>
                                    <input type="checkbox" name="step_3_field_telefono"> Teléfono (opcional)
                                </label>
                            </div>
                        </div>
                        
                        <label>Después del formulario:</label>
                        <select name="step_3_next">
                            <option value="whatsapp">Enviar a WhatsApp</option>
                            <option value="message">Mostrar mensaje de agradecimiento</option>
                        </select>
                        
                        <textarea name="step_3_thanks" placeholder="Mensaje de agradecimiento (opcional)" style="display:none;">¡Gracias por tus datos! Te contactaré pronto.</textarea>
                    </div>
                </div>
            </div>
            
            <div class="wac-builder-actions">
                <button type="button" class="button button-primary" onclick="addNewStep()">+ Agregar Paso</button>
                <button type="button" class="button" onclick="previewFunnel()">👁️ Vista Previa</button>
                <button type="button" class="button" onclick="loadFunnelConfig()">🔄 Cargar Config</button>
                <button type="button" class="button" onclick="resetToDefault()">🔄 Restaurar Por Defecto</button>
            </div>
            
            <div class="wac-debug-info" style="margin-top: 15px; padding: 10px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; font-size: 12px;">
                <strong>Debug Info:</strong>
                <button type="button" class="button button-small" onclick="debugFunnelData()" style="margin-left: 10px;">🔍 Ver Datos</button>
                <button type="button" class="button button-small" onclick="clearFunnelData()" style="margin-left: 5px;">🗑️ Limpiar</button>
                <button type="button" class="button button-small" onclick="showDebugArea()" style="margin-left: 5px;">📋 Mostrar Debug</button>
            </div>
            
            <!-- Área de Debug Visible -->
            <div id="wac-debug-area" style="display:none; margin-top: 15px; padding: 15px; background: #f8f9fa; border: 2px solid #007cba; border-radius: 5px;">
                <h4 style="margin-top: 0; color: #007cba;">🔍 Área de Debug</h4>
                <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                    <button type="button" class="button button-small" onclick="refreshDebugData()">🔄 Actualizar</button>
                    <button type="button" class="button button-small" onclick="copyDebugData()">📋 Copiar</button>
                    <button type="button" class="button button-small" onclick="hideDebugArea()">❌ Cerrar</button>
                </div>
                <div id="wac-debug-content" style="background: white; padding: 10px; border: 1px solid #ddd; border-radius: 3px; font-family: monospace; font-size: 11px; max-height: 300px; overflow-y: auto; white-space: pre-wrap;"></div>
            </div>
            
            <div class="wac-save-notice" id="wac-save-notice" style="display:none;">
                <p>✅ <strong>Configuración cargada!</strong> Se han restaurado tus cambios guardados.</p>
            </div>
            
            <div class="wac-help-notice" style="margin-top: 15px; padding: 10px; background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 4px; font-size: 13px;">
                <strong>💡 Cómo funciona:</strong><br>
                • Los cambios se guardan automáticamente mientras escribes<br>
                • Haz clic en <strong>"Actualizar"</strong> (botón azul arriba) para guardar permanentemente<br>
                • Solo necesitas un botón de guardado: el de WordPress
            </div>
        </div>
        
        <div id="wac-preview-modal" style="display:none;">
            <div class="wac-modal-content">
                <div class="wac-modal-header">
                    <h3>Vista Previa del Funnel</h3>
                    <button onclick="closePreview()">×</button>
                </div>
                <div class="wac-modal-body">
                    <div id="wac-preview-chat"></div>
                </div>
            </div>
        </div>
        
        <!-- Campos ocultos para guardar la configuración del funnel -->
        <input type="hidden" name="wac_funnel_steps" id="wac-funnel-steps-hidden" value="" />
        
        <!-- Script para pasar datos guardados al JavaScript -->
        <script type="text/javascript">
            var wacSavedSteps = <?php echo json_encode($saved_steps ?: array()); ?>;
        </script>
        
        <style>
        #wac-funnel-builder {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9f9f9;
        }
        
        .wac-step {
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 15px;
            padding: 15px;
        }
        
        .wac-step-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .wac-step-number {
            background: #25D366;
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .wac-step-title {
            flex: 1;
            font-weight: bold;
        }
        
        .wac-step-delete {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            cursor: pointer;
        }
        
        .wac-step-content label {
            display: block;
            margin: 10px 0 5px 0;
            font-weight: bold;
        }
        
        .wac-step-content textarea,
        .wac-step-content select,
        .wac-step-content input[type="text"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .wac-option {
            display: flex;
            gap: 8px;
            margin-bottom: 10px;
            align-items: center;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        
        .wac-option input[type="text"] {
            flex: 2;
            min-width: 120px;
        }
        
        .wac-option select {
            flex: 1;
            min-width: 100px;
        }
        
        .wac-option button {
            flex-shrink: 0;
            width: 30px;
            height: 30px;
        }
        
        .wac-builder-actions {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        
        .wac-builder-actions button {
            margin-right: 10px;
        }
        
        .button-secondary {
            background-color: #25D366 !important;
            border-color: #25D366 !important;
            color: white !important;
        }
        
        .button-secondary:hover {
            background-color: #1da851 !important;
            border-color: #1da851 !important;
        }
        
        .wac-save-notice {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        
        #wac-preview-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
        }
        
        .wac-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow: hidden;
        }
        
        .wac-modal-header {
            background: #25D366;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .wac-modal-header button {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
        }
        
        .wac-modal-body {
            padding: 20px;
            max-height: 60vh;
            overflow-y: auto;
        }
        
        #wac-preview-chat {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            background: #f9f9f9;
            min-height: 300px;
        }
        </style>
        
        <script>
        function addNewStep() {
            const container = document.getElementById('wac-steps-container');
            const existingSteps = container.querySelectorAll('.wac-step');
            const stepCount = existingSteps.length + 1;
            
            // Generar ID único basado en timestamp para evitar conflictos
            const stepId = `step_${stepCount}_${Date.now()}`;
            
            const newStep = document.createElement('div');
            newStep.className = 'wac-step';
            newStep.setAttribute('data-step', stepCount);
            newStep.setAttribute('data-step-id', stepId);
            newStep.innerHTML = `
                <div class="wac-step-header">
                    <span class="wac-step-number">${stepCount}</span>
                    <span class="wac-step-title">Nuevo Paso</span>
                    <button class="wac-step-delete" onclick="deleteStep(${stepCount})">×</button>
                </div>
                <div class="wac-step-content">
                    <label>Tipo de paso:</label>
                    <select name="${stepId}_type" onchange="updateStepType('${stepId}', this.value)">
                        <option value="message">Mensaje</option>
                        <option value="question">Pregunta con opciones</option>
                        <option value="form">Formulario</option>
                        <option value="redirect">Redirección</option>
                    </select>
                    
                    <div id="${stepId}_content">
                        <label>Mensaje:</label>
                        <textarea name="${stepId}_message" placeholder="Escribe tu mensaje..."></textarea>
                        
                        <label>Siguiente paso:</label>
                        <select name="${stepId}_next">
                            <option value="">Seleccionar...</option>
                            <option value="whatsapp">WhatsApp directo</option>
                            <option value="redirect">Redirección</option>
                            <option value="end">Finalizar</option>
                        </select>
                        
                        <div class="wac-step-options" id="${stepId}_options" style="display:none;">
                            <label>Opciones (para preguntas):</label>
                            <div class="wac-options">
                                <div class="wac-option">
                                    <input type="text" name="${stepId}_option_1_text" placeholder="Texto del botón (ej: Opción A, Ver más, etc.)">
                                    <select name="${stepId}_option_1_action" onchange="handleOptionActionChange('${stepId}', 1, this.value)">
                                        <option value="next">Ir a paso específico</option>
                                        <option value="whatsapp">WhatsApp directo</option>
                                        <option value="redirect">Redirección a URL</option>
                                    </select>
                                    <input type="text" name="${stepId}_option_1_target" placeholder="Número de paso o URL" style="display:none;">
                                    <button type="button" onclick="this.parentElement.remove()" style="background: #dc3545; color: white; border: none; border-radius: 3px; padding: 5px 8px; cursor: pointer;">×</button>
                                </div>
                            </div>
                            <button type="button" class="button button-small" onclick="addOptionToStep('${stepId}')">+ Agregar Opción</button>
                        </div>
                        
                        <div class="wac-step-form" id="${stepId}_form" style="display:none;">
                            <label>Campos del formulario:</label>
                            <div class="wac-form-fields">
                                <div class="wac-field">
                                    <label>
                                        <input type="checkbox" name="${stepId}_field_nombre" checked> Nombre (requerido)
                                    </label>
                                </div>
                                <div class="wac-field">
                                    <label>
                                        <input type="checkbox" name="${stepId}_field_email" checked> Email (requerido)
                                    </label>
                                </div>
                                <div class="wac-field">
                                    <label>
                                        <input type="checkbox" name="${stepId}_field_telefono"> Teléfono (opcional)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            container.appendChild(newStep);
            
            // Actualizar números de paso
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
                
                // Actualizar onclick del botón de eliminar
                const deleteBtn = step.querySelector('.wac-step-delete');
                if (deleteBtn) {
                    deleteBtn.setAttribute('onclick', `deleteStep(${stepNumber})`);
                }
            });
        }
        
        function updateStepType(stepId, type) {
            console.log(`Cambiando tipo de paso ${stepId} a: ${type}`);
            
            const contentDiv = document.getElementById(`${stepId}_content`);
            const optionsDiv = document.getElementById(`${stepId}_options`);
            const formDiv = document.getElementById(`${stepId}_form`);
            
            if (!contentDiv) {
                console.error('No se encontró el contenedor de contenido:', `${stepId}_content`);
                return;
            }
            
            // Ocultar todas las secciones específicas
            if (optionsDiv) {
                optionsDiv.style.display = 'none';
                console.log('Ocultando opciones');
            }
            if (formDiv) {
                formDiv.style.display = 'none';
                console.log('Ocultando formulario');
            }
            
            // Mostrar la sección correspondiente al tipo
            switch(type) {
                case 'question':
                    if (optionsDiv) {
                        optionsDiv.style.display = 'block';
                        console.log('Mostrando opciones para pregunta');
                    } else {
                        console.error('No se encontró el div de opciones');
                    }
                    break;
                case 'form':
                    if (formDiv) {
                        formDiv.style.display = 'block';
                        console.log('Mostrando formulario');
                    } else {
                        console.error('No se encontró el div de formulario');
                    }
                    break;
                case 'message':
                    console.log('Tipo mensaje - sin secciones adicionales');
                    break;
                case 'redirect':
                    console.log('Tipo redirección - sin secciones adicionales');
                    break;
                default:
                    console.log('Tipo no reconocido:', type);
            }
            
            // Forzar actualización visual
            contentDiv.style.display = 'none';
            setTimeout(() => {
                contentDiv.style.display = 'block';
            }, 10);
        }
        
        function addOptionToStep(stepId) {
            const optionsContainer = document.querySelector(`#${stepId}_options .wac-options`);
            if (!optionsContainer) {
                console.error('No se encontró el contenedor de opciones:', `#${stepId}_options .wac-options`);
                return;
            }
            
            const optionCount = optionsContainer.children.length + 1;
            
            const newOption = document.createElement('div');
            newOption.className = 'wac-option';
            newOption.innerHTML = `
                <input type="text" name="${stepId}_option_${optionCount}_text" placeholder="Texto del botón (ej: Opción A, Ver más, etc.)">
                <select name="${stepId}_option_${optionCount}_action" onchange="handleOptionActionChange('${stepId}', ${optionCount}, this.value)">
                    <option value="next">Ir a paso específico</option>
                    <option value="whatsapp">WhatsApp directo</option>
                    <option value="redirect">Redirección a URL</option>
                </select>
                <input type="text" name="${stepId}_option_${optionCount}_target" placeholder="Número de paso o URL" style="display:none;">
                <button type="button" onclick="this.parentElement.remove()" style="background: #dc3545; color: white; border: none; border-radius: 3px; padding: 5px 8px; cursor: pointer;">×</button>
            `;
            
            optionsContainer.appendChild(newOption);
            console.log(`Opción ${optionCount} agregada al paso ${stepId}`);
        }
        
        function handleOptionActionChange(stepId, optionCount, action) {
            const targetInput = document.querySelector(`input[name="${stepId}_option_${optionCount}_target"]`);
            if (!targetInput) return;
            
            switch(action) {
                case 'next':
                    targetInput.placeholder = 'Número de paso (ej: 3, 4, 5)';
                    targetInput.style.display = 'block';
                    break;
                case 'whatsapp':
                    targetInput.placeholder = 'Mensaje predefinido (opcional)';
                    targetInput.style.display = 'block';
                    break;
                case 'redirect':
                    targetInput.placeholder = 'URL completa (ej: /portafolio, https://ejemplo.com)';
                    targetInput.style.display = 'block';
                    break;
                default:
                    targetInput.style.display = 'none';
            }
        }
        
        function addOption(stepNumber) {
            const optionsContainer = document.querySelector(`[data-step="${stepNumber}"] .wac-options`);
            const optionCount = optionsContainer.children.length + 1;
            
            const newOption = document.createElement('div');
            newOption.className = 'wac-option';
            newOption.innerHTML = `
                <input type="text" name="step_${stepNumber}_option_${optionCount}_text" placeholder="Texto del botón">
                <select name="step_${stepNumber}_option_${optionCount}_action">
                    <option value="whatsapp">WhatsApp directo</option>
                    <option value="redirect">Redirigir a URL</option>
                    <option value="next">Siguiente paso</option>
                </select>
                <input type="text" name="step_${stepNumber}_option_${optionCount}_url" placeholder="URL (si es redirección)" style="display:none;">
                <button type="button" onclick="this.parentElement.remove()">×</button>
            `;
            
            optionsContainer.appendChild(newOption);
        }
        
        function updateStepType(stepNumber, type) {
            const contentDiv = document.getElementById(`step_${stepNumber}_content`);
            // Aquí agregarías la lógica para cambiar el contenido según el tipo
            console.log(`Cambiar paso ${stepNumber} a tipo ${type}`);
        }
        
        function previewFunnel() {
            document.getElementById('wac-preview-modal').style.display = 'block';
            
            // Aquí agregarías la lógica para mostrar la vista previa
            document.getElementById('wac-preview-chat').innerHTML = `
                <div style="text-align: center; color: #666; margin-top: 100px;">
                    <h4>Vista Previa del Funnel</h4>
                    <p>Aquí se mostraría cómo se ve el chat</p>
                    <p><em>Funcionalidad en desarrollo...</em></p>
                </div>
            `;
        }
        
        function closePreview() {
            document.getElementById('wac-preview-modal').style.display = 'none';
        }
        
        function resetToDefault() {
            if (confirm('¿Estás seguro de restaurar la configuración por defecto?')) {
                location.reload();
            }
        }
        
        function saveFunnelConfig() {
            // Recopilar todos los datos del editor
            const funnelData = collectFunnelData();
            const stepsCount = Object.keys(funnelData).length;
            
            // Guardar en campo oculto
            const hiddenField = document.getElementById('wac-funnel-steps-hidden');
            hiddenField.value = JSON.stringify(funnelData);
            
            // Mostrar notificación visual con más detalles
            const notice = document.getElementById('wac-save-notice');
            if (notice) {
                notice.innerHTML = `<p>✅ <strong>Funnel guardado!</strong> ${stepsCount} pasos guardados. Los cambios se aplicarán cuando actualices el post.</p>`;
                notice.style.display = 'block';
                notice.style.background = '#d4edda';
                notice.style.color = '#155724';
                notice.style.border = '1px solid #c3e6cb';
                notice.style.padding = '10px';
                notice.style.borderRadius = '4px';
                notice.style.margin = '10px 0';
                
                // Ocultar después de 4 segundos
                setTimeout(() => {
                    notice.style.display = 'none';
                }, 4000);
            }
            
            console.log('=== SAVE FUNNEL CONFIG ===');
            console.log('Steps saved:', stepsCount);
            console.log('Data saved:', funnelData);
            console.log('Hidden field length:', hiddenField.value.length);
            console.log('Hidden field value:', hiddenField.value);
            
            // Actualizar área de debug si está visible
            if (document.getElementById('wac-debug-area').style.display === 'block') {
                refreshDebugData();
            }
        }
        
        function collectFunnelData() {
            const steps = {};
            const stepsContainer = document.getElementById('wac-steps-container');
            
            // Recopilar datos de cada paso
            stepsContainer.querySelectorAll('.wac-step').forEach((step, index) => {
                const stepNumber = index + 1;
                const stepId = step.getAttribute('data-step-id') || `step_${stepNumber}`;
                const stepData = {
                    stepId: stepId,
                    stepNumber: stepNumber
                };
                
                // Recopilar campos del paso
                step.querySelectorAll('input, select, textarea').forEach(field => {
                    if (field.name) {
                        if (field.type === 'checkbox') {
                            stepData[field.name] = field.checked;
                        } else {
                            stepData[field.name] = field.value;
                        }
                    }
                });
                
                steps[stepNumber] = stepData;
            });
            
            console.log('Datos recopilados del funnel:', steps);
            return steps;
        }
        
        function loadFunnelConfig() {
            // Recargar la configuración guardada
            loadSavedConfiguration();
            
            // Mostrar mensaje de confirmación
            const notice = document.getElementById('wac-save-notice');
            if (notice) {
                notice.innerHTML = '<p>🔄 <strong>Configuración recargada!</strong> Se han restaurado los datos guardados.</p>';
                notice.style.display = 'block';
                notice.style.background = '#fff3cd';
                notice.style.color = '#856404';
                notice.style.border = '1px solid #ffeaa7';
                
                setTimeout(() => {
                    notice.style.display = 'none';
                }, 3000);
            }
        }
        
        // Auto-guardar cada vez que se hace un cambio
        document.addEventListener('DOMContentLoaded', function() {
            const editorContainer = document.getElementById('wac-funnel-builder');
            if (editorContainer) {
                // Cargar configuración guardada al inicializar
                loadSavedConfiguration();
                
                editorContainer.addEventListener('input', function() {
                    // Auto-guardar silenciosamente cada 1 segundo después del último cambio
                    clearTimeout(window.autoSaveTimeout);
                    window.autoSaveTimeout = setTimeout(autoSaveSilent, 1000);
                });
            }
        });
        
        function autoSaveSilent() {
            // Guardar silenciosamente sin notificaciones
            const funnelData = collectFunnelData();
            const hiddenField = document.getElementById('wac-funnel-steps-hidden');
            hiddenField.value = JSON.stringify(funnelData);
            
            console.log('Auto-saved silently:', Object.keys(funnelData).length, 'steps');
        }
        
        function loadSavedConfiguration() {
            // Verificar si hay datos guardados
            if (typeof wacSavedSteps !== 'undefined' && wacSavedSteps && Object.keys(wacSavedSteps).length > 0) {
                console.log('Cargando configuración guardada:', wacSavedSteps);
                
                // Primero aplicar datos a los pasos existentes (pasos 1, 2, 3)
                Object.keys(wacSavedSteps).forEach(fieldName => {
                    const field = document.querySelector(`[name="${fieldName}"]`);
                    if (field) {
                        if (field.type === 'checkbox') {
                            field.checked = wacSavedSteps[fieldName] === 'on' || wacSavedSteps[fieldName] === true;
                        } else {
                            field.value = wacSavedSteps[fieldName];
                        }
                    }
                });
                
                // Buscar pasos dinámicos guardados (pasos 4+)
                const dynamicSteps = findDynamicSteps(wacSavedSteps);
                
                if (dynamicSteps.length > 0) {
                    console.log('Recreando pasos dinámicos:', dynamicSteps);
                    recreateDynamicSteps(dynamicSteps);
                }
                
                // Mostrar notificación de carga
                const notice = document.getElementById('wac-save-notice');
                if (notice) {
                    notice.innerHTML = '<p>✅ <strong>Configuración cargada!</strong> Se han restaurado tus cambios guardados.</p>';
                    notice.style.display = 'block';
                    notice.style.background = '#d1ecf1';
                    notice.style.color = '#0c5460';
                    notice.style.border = '1px solid #bee5eb';
                    
                    setTimeout(() => {
                        notice.style.display = 'none';
                    }, 4000);
                }
            } else {
                console.log('No hay configuración guardada, usando valores por defecto');
            }
        }
        
        function findDynamicSteps(savedData) {
            const dynamicSteps = [];
            const stepIds = new Set();
            
            // Buscar todos los campos que pertenecen a pasos dinámicos
            Object.keys(savedData).forEach(fieldName => {
                if (fieldName.includes('step_4_') || fieldName.includes('step_5_') || fieldName.includes('step_6_')) {
                    const stepId = fieldName.substring(0, fieldName.indexOf('_', fieldName.indexOf('_', fieldName.indexOf('_') + 1) + 1));
                    if (!stepIds.has(stepId)) {
                        stepIds.add(stepId);
                        dynamicSteps.push({
                            stepId: stepId,
                            data: {}
                        });
                    }
                }
            });
            
            // Agrupar datos por stepId
            dynamicSteps.forEach(step => {
                Object.keys(savedData).forEach(fieldName => {
                    if (fieldName.startsWith(step.stepId + '_')) {
                        step.data[fieldName] = savedData[fieldName];
                    }
                });
            });
            
            return dynamicSteps;
        }
        
        function recreateDynamicSteps(dynamicSteps) {
            dynamicSteps.forEach(step => {
                console.log('Recreando paso:', step.stepId);
                
                // Agregar el paso dinámico
                const container = document.getElementById('wac-steps-container');
                const stepCount = container.children.length + 1;
                
                const newStep = document.createElement('div');
                newStep.className = 'wac-step';
                newStep.setAttribute('data-step', stepCount);
                newStep.setAttribute('data-step-id', step.stepId);
                
                // Obtener el tipo del paso
                const stepType = step.data[step.stepId + '_type'] || 'message';
                
                newStep.innerHTML = generateStepHTML(stepCount, step.stepId, stepType);
                container.appendChild(newStep);
                
                // Aplicar los datos guardados al nuevo paso
                Object.keys(step.data).forEach(fieldName => {
                    const field = newStep.querySelector(`[name="${fieldName}"]`);
                    if (field) {
                        if (field.type === 'checkbox') {
                            field.checked = step.data[fieldName] === 'on' || step.data[fieldName] === true;
                        } else {
                            field.value = step.data[fieldName];
                        }
                    }
                });
                
                // Actualizar números de paso
                updateStepNumbers();
            });
        }
        
        function generateStepHTML(stepNumber, stepId, type) {
            const optionsDisplay = type === 'question' ? 'block' : 'none';
            const formDisplay = type === 'form' ? 'block' : 'none';
            
            return `
                <div class="wac-step-header">
                    <span class="wac-step-number">${stepNumber}</span>
                    <span class="wac-step-title">Paso ${stepNumber}</span>
                    <button class="wac-step-delete" onclick="deleteStep(${stepNumber})">×</button>
                </div>
                <div class="wac-step-content">
                    <label>Tipo de paso:</label>
                    <select name="${stepId}_type" onchange="updateStepType('${stepId}', this.value)">
                        <option value="message" ${type === 'message' ? 'selected' : ''}>Mensaje</option>
                        <option value="question" ${type === 'question' ? 'selected' : ''}>Pregunta con opciones</option>
                        <option value="form" ${type === 'form' ? 'selected' : ''}>Formulario</option>
                        <option value="redirect" ${type === 'redirect' ? 'selected' : ''}>Redirección</option>
                    </select>
                    
                    <div id="${stepId}_content">
                        <label>Mensaje:</label>
                        <textarea name="${stepId}_message" placeholder="Escribe tu mensaje..."></textarea>
                        
                        <label>Siguiente paso:</label>
                        <select name="${stepId}_next">
                            <option value="">Seleccionar...</option>
                            <option value="whatsapp">WhatsApp directo</option>
                            <option value="redirect">Redirección</option>
                            <option value="end">Finalizar</option>
                        </select>
                        
                        <div class="wac-step-options" id="${stepId}_options" style="display:${optionsDisplay};">
                            <label>Opciones (para preguntas):</label>
                            <div class="wac-options">
                                <div class="wac-option">
                                    <input type="text" name="${stepId}_option_1_text" placeholder="Texto del botón (ej: Opción A, Ver más, etc.)">
                                    <select name="${stepId}_option_1_action" onchange="handleOptionActionChange('${stepId}', 1, this.value)">
                                        <option value="next">Ir a paso específico</option>
                                        <option value="whatsapp">WhatsApp directo</option>
                                        <option value="redirect">Redirección a URL</option>
                                    </select>
                                    <input type="text" name="${stepId}_option_1_target" placeholder="Número de paso o URL" style="display:none;">
                                    <button type="button" onclick="this.parentElement.remove()" style="background: #dc3545; color: white; border: none; border-radius: 3px; padding: 5px 8px; cursor: pointer;">×</button>
                                </div>
                            </div>
                            <button type="button" class="button button-small" onclick="addOptionToStep('${stepId}')">+ Agregar Opción</button>
                        </div>
                        
                        <div class="wac-step-form" id="${stepId}_form" style="display:${formDisplay};">
                            <label>Campos del formulario:</label>
                            <div class="wac-form-fields">
                                <div class="wac-field">
                                    <label>
                                        <input type="checkbox" name="${stepId}_field_nombre" checked> Nombre (requerido)
                                    </label>
                                </div>
                                <div class="wac-field">
                                    <label>
                                        <input type="checkbox" name="${stepId}_field_email" checked> Email (requerido)
                                    </label>
                                </div>
                                <div class="wac-field">
                                    <label>
                                        <input type="checkbox" name="${stepId}_field_telefono"> Teléfono (opcional)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        function debugFunnelData() {
            const currentData = collectFunnelData();
            const savedData = typeof wacSavedSteps !== 'undefined' ? wacSavedSteps : 'No hay datos guardados';
            
            alert(`🔍 DEBUG INFO:

📊 DATOS ACTUALES DEL EDITOR:
${JSON.stringify(currentData, null, 2)}

💾 DATOS GUARDADOS EN BD:
${JSON.stringify(savedData, null, 2)}

📝 CAMPOS ENCONTRADOS:
${Object.keys(currentData).length} campos

Ver consola para más detalles.`);
            
            console.log('=== DEBUG FUNNEL DATA ===');
            console.log('Current editor data:', currentData);
            console.log('Saved data from DB:', savedData);
            console.log('Hidden field value:', document.getElementById('wac-funnel-steps-hidden').value);
        }
        
        function showDebugArea() {
            document.getElementById('wac-debug-area').style.display = 'block';
            refreshDebugData();
        }
        
        function hideDebugArea() {
            document.getElementById('wac-debug-area').style.display = 'none';
        }
        
        function refreshDebugData() {
            const currentData = collectFunnelData();
            const savedData = typeof wacSavedSteps !== 'undefined' ? wacSavedSteps : 'No hay datos guardados';
            const hiddenFieldValue = document.getElementById('wac-funnel-steps-hidden').value;
            const stepsCount = document.querySelectorAll('.wac-step').length;
            
            const debugInfo = `=== DEBUG INFO - ${new Date().toLocaleTimeString()} ===

📊 PASOS ENCONTRADOS: ${stepsCount}
💾 CAMPOS OCULTOS GUARDADOS: ${hiddenFieldValue.length} caracteres

📋 DATOS ACTUALES DEL EDITOR:
${JSON.stringify(currentData, null, 2)}

💾 DATOS GUARDADOS EN BD:
${JSON.stringify(savedData, null, 2)}

🔍 CAMPO OCULTO COMPLETO:
${hiddenFieldValue}

📝 ANÁLISIS DE PASOS:
${analyzeSteps()}`;

            document.getElementById('wac-debug-content').textContent = debugInfo;
        }
        
        function analyzeSteps() {
            const steps = document.querySelectorAll('.wac-step');
            let analysis = '';
            
            steps.forEach((step, index) => {
                const stepNumber = index + 1;
                const stepId = step.getAttribute('data-step-id') || `step_${stepNumber}`;
                const fields = step.querySelectorAll('input, select, textarea');
                
                analysis += `\nPaso ${stepNumber} (${stepId}):\n`;
                analysis += `  - Campos encontrados: ${fields.length}\n`;
                
                fields.forEach(field => {
                    if (field.name) {
                        const value = field.type === 'checkbox' ? field.checked : field.value;
                        analysis += `  - ${field.name}: "${value}"\n`;
                    }
                });
            });
            
            return analysis;
        }
        
        function copyDebugData() {
            const debugContent = document.getElementById('wac-debug-content').textContent;
            navigator.clipboard.writeText(debugContent).then(() => {
                alert('✅ Debug data copiado al portapapeles!');
            }).catch(() => {
                // Fallback para navegadores que no soportan clipboard API
                const textArea = document.createElement('textarea');
                textArea.value = debugContent;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('✅ Debug data copiado al portapapeles!');
            });
        }
        
        function clearFunnelData() {
            if (confirm('¿Estás seguro de limpiar todos los datos del funnel?')) {
                // Limpiar campos
                document.querySelectorAll('#wac-funnel-builder input, #wac-funnel-builder select, #wac-funnel-builder textarea').forEach(field => {
                    if (field.type === 'checkbox') {
                        field.checked = false;
                    } else {
                        field.value = '';
                    }
                });
                
                // Limpiar campo oculto
                document.getElementById('wac-funnel-steps-hidden').value = '';
                
                alert('🗑️ Datos del funnel limpiados.');
            }
        }
        
        // Manejar cambios en selects de acción
        document.addEventListener('change', function(e) {
            // Manejar cambios en tipo de paso
            if (e.target.name && e.target.name.includes('_type')) {
                const stepId = e.target.name.replace('_type', '');
                console.log('Cambio detectado en tipo de paso:', stepId, '->', e.target.value);
                updateStepType(stepId, e.target.value);
            }
            
            // Manejar cambios en selects de acción (nueva estructura)
            if (e.target.name && e.target.name.includes('_action')) {
                const fieldName = e.target.name;
                const stepId = fieldName.substring(0, fieldName.lastIndexOf('_option_'));
                const optionNumber = fieldName.match(/_option_(\d+)_action/)[1];
                
                console.log(`Cambio en acción de opción ${optionNumber} del paso ${stepId}:`, e.target.value);
                handleOptionActionChange(stepId, optionNumber, e.target.value);
            }
        });
        </script>
        <?php
    }
    
    public function save_metaboxes($post_id) {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (isset($_POST['wac_funnel_config_nonce']) && wp_verify_nonce($_POST['wac_funnel_config_nonce'], 'wac_funnel_config')) {
            // Guardar configuración básica
            update_post_meta($post_id, '_wac_funnel_enabled', isset($_POST['wac_funnel_enabled']) ? '1' : '0');
            update_post_meta($post_id, '_wac_funnel_teaser_text', sanitize_text_field($_POST['wac_funnel_teaser_text']));
            update_post_meta($post_id, '_wac_funnel_teaser_delay', intval($_POST['wac_funnel_teaser_delay']));
            update_post_meta($post_id, '_wac_funnel_whatsapp_number', sanitize_text_field($_POST['wac_funnel_whatsapp_number']));
            
            // Guardar configuración del funnel (pasos)
            if (isset($_POST['wac_funnel_steps'])) {
                $funnel_steps = json_decode(stripslashes($_POST['wac_funnel_steps']), true);
                if ($funnel_steps) {
                    update_post_meta($post_id, '_wac_funnel_steps', $funnel_steps);
                }
            }
            
            // Guardar configuración individual de pasos
            $this->save_funnel_steps($post_id);
        }
    }
    
    private function save_funnel_steps($post_id) {
        $steps_data = array();
        
        // Recopilar todos los datos de los pasos
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'step_') === 0) {
                $steps_data[$key] = sanitize_text_field($value);
            }
        }
        
        if (!empty($steps_data)) {
            update_post_meta($post_id, '_wac_funnel_steps_data', $steps_data);
        }
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=wac_chat_funnel',
            __('Configuración', 'wac-chat-funnels'),
            __('Configuración', 'wac-chat-funnels'),
            'manage_options',
            'wac-chat-config',
            array($this, 'admin_config_page')
        );
    }
    
    public function admin_config_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Configuración de Chat Funnels', 'wac-chat-funnels'); ?></h1>
            <div class="card">
                <h2>✅ Plugin Instalado Correctamente</h2>
                <p>El plugin está funcionando. Para crear un funnel:</p>
                <ol>
                    <li>Ve a <strong>Chat Funnels → Agregar Nuevo</strong></li>
                    <li>Configura el funnel</li>
                    <li>Guarda y ve a tu sitio web</li>
                </ol>
                <p><a href="<?php echo admin_url('post-new.php?post_type=wac_chat_funnel'); ?>" class="button button-primary">Crear Primer Funnel</a></p>
            </div>
        </div>
        <?php
    }
    
    public function enqueue_frontend_scripts() {
        wp_enqueue_style('wac-chat-funnels', WAC_CHAT_PLUGIN_URL . 'assets/css/chat-widget.css', array(), WAC_CHAT_VERSION);
        wp_enqueue_script('wac-chat-funnels', WAC_CHAT_PLUGIN_URL . 'assets/js/chat-widget.js', array(), WAC_CHAT_VERSION, true);
    }
    
    public function render_chat_widget() {
        // Obtener funnel activo
        $funnels = get_posts(array(
            'post_type' => 'wac_chat_funnel',
            'meta_query' => array(
                array(
                    'key' => '_wac_funnel_enabled',
                    'value' => '1',
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        ));
        
        if (empty($funnels)) {
            return;
        }
        
        $funnel = $funnels[0];
        $teaser_text = get_post_meta($funnel->ID, '_wac_funnel_teaser_text', true);
        $teaser_delay = get_post_meta($funnel->ID, '_wac_funnel_teaser_delay', true);
        $whatsapp_number = get_post_meta($funnel->ID, '_wac_funnel_whatsapp_number', true);
        
        if (empty($teaser_text)) $teaser_text = '¿Necesitas ayuda?';
        if (empty($teaser_delay)) $teaser_delay = 3000;
        if (empty($whatsapp_number)) $whatsapp_number = '+573154543344';
        
        ?>
        <div id="wac-chat-widget" data-delay="<?php echo esc_attr($teaser_delay); ?>" data-whatsapp="<?php echo esc_attr($whatsapp_number); ?>" style="display: none;">
            <div id="wac-chat-teaser">
                <span id="wac-chat-teaser-text"><?php echo esc_html($teaser_text); ?></span>
            </div>
            <div id="wac-chat-container" style="display: none;">
                <div id="wac-chat-header">
                    <span id="wac-chat-title">Asistente Virtual</span>
                    <button id="wac-chat-close">×</button>
                </div>
                <div id="wac-chat-messages"></div>
                <div id="wac-chat-input" style="display: none;">
                    <input type="text" id="wac-chat-field" placeholder="Escribe aquí...">
                    <button id="wac-chat-send">Enviar</button>
                </div>
                <div id="wac-chat-buttons"></div>
            </div>
        </div>
        <?php
    }
    
    public function activate() {
        // Crear tablas si es necesario
        $this->create_tables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wac_chat_leads';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            funnel_id bigint(20) NOT NULL,
            lead_data longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Inicializar el plugin
WAC_Chat_Funnels::get_instance();
