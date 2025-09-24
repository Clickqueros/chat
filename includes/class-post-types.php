<?php

class WAC_Chat_Post_Types {
    
    public static function register_chat_funnel_cpt() {
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
            'show_in_rest' => true,
            'query_var' => false,
            'rewrite' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-format-chat',
            'supports' => array('title', 'editor', 'revisions'),
            'capabilities' => array(
                'edit_post' => 'manage_chat_funnels',
                'read_post' => 'manage_chat_funnels',
                'delete_post' => 'manage_chat_funnels',
                'edit_posts' => 'manage_chat_funnels',
                'edit_others_posts' => 'manage_chat_funnels',
                'publish_posts' => 'manage_chat_funnels',
                'read_private_posts' => 'manage_chat_funnels'
            )
        );
        
        register_post_type('chat_funnel', $args);
        
        // Registrar metaboxes
        add_action('add_meta_boxes', array(__CLASS__, 'add_metaboxes'));
        add_action('save_post', array(__CLASS__, 'save_metaboxes'));
    }
    
    public static function add_metaboxes() {
        add_meta_box(
            'wac-funnel-config',
            __('Configuración del Funnel', 'wac-chat-funnels'),
            array(__CLASS__, 'funnel_config_metabox'),
            'chat_funnel',
            'normal',
            'high'
        );
        
        add_meta_box(
            'wac-funnel-rules',
            __('Reglas de Targeting', 'wac-chat-funnels'),
            array(__CLASS__, 'funnel_rules_metabox'),
            'chat_funnel',
            'side',
            'default'
        );
        
        add_meta_box(
            'wac-funnel-settings',
            __('Configuración General', 'wac-chat-funnels'),
            array(__CLASS__, 'funnel_settings_metabox'),
            'chat_funnel',
            'side',
            'default'
        );
    }
    
    public static function funnel_config_metabox($post) {
        wp_nonce_field('wac_funnel_config', 'wac_funnel_config_nonce');
        
        $config = get_post_meta($post->ID, '_wac_funnel_config', true);
        if (empty($config)) {
            $config = self::get_default_funnel_config();
        }
        
        ?>
            <div id="wac-funnel-editor">
                <div style="margin-bottom: 10px; padding: 10px; background: #f0f0f1; border-radius: 4px;">
                    <button type="button" id="debug-test-btn" class="button">🔍 Test Debug</button>
                    <button type="button" id="load-example-btn" class="button">📝 Load Example</button>
                    <button type="button" id="clear-editor-btn" class="button">🗑️ Clear</button>
                    <button type="button" id="force-debug-btn" class="button" style="background: #0073aa; color: white;">🚨 Force Debug</button>
                    <button type="button" id="test-parser-btn" class="button" style="background: #d63638; color: white;">🧪 Test Parser</button>
                    <button type="button" id="install-composer-btn" class="button" style="background: #00a32a; color: white;">📦 Install Composer</button>
                    <button type="button" id="debug-save-btn" class="button" style="background: #d63638; color: white;">💾 Debug Save</button>
                    <span id="debug-status" style="margin-left: 10px; color: #666;"></span>
                </div>
                <div id="wac-yaml-editor" style="height: 400px; border: 1px solid #ddd;"></div>
                <textarea id="wac-funnel-config" name="wac_funnel_config" style="display: none;"><?php echo esc_textarea($config); ?></textarea>
            </div>
        
        <script>
        console.log('🐛 WAC Chat Funnels: Metabox script loaded');
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🐛 WAC Chat Funnels: DOM Content Loaded');
            
            // Test button
            const testBtn = document.getElementById('debug-test-btn');
            if (testBtn) {
                testBtn.addEventListener('click', function() {
                    console.log('🐛 Debug Test Button Clicked');
                    alert('Debug test button works! Check console for details.');
                    
                    // Show basic info
                    const status = document.getElementById('debug-status');
                    if (status) {
                        status.innerHTML = 'JavaScript working! ' + new Date().toLocaleTimeString();
                    }
                });
            }
            
                // Load example button
                const exampleBtn = document.getElementById('load-example-btn');
                if (exampleBtn) {
                    exampleBtn.addEventListener('click', function() {
                        console.log('📝 Load Example Button Clicked');
                        
                        const exampleYAML = `funnel:
  id: "lead_basico"
  start: "intro"
  nodes:
    intro:
      type: message
      text: |
        ¡Hola! Soy **Asistente WACosta** 👋
        ¿En qué puedo ayudarte hoy?
      next: menu

    menu:
      type: question
      style: choice
      options:
        - label: "Quiero cotización"
          next: form_nombre

        - label: "Ver portafolio"
          action:
            type: redirect
            url: "/portafolio"

        - label: "Hablar por WhatsApp"
          action:
            type: whatsapp
            phone: "+573154543344"
            prefill: "Hola, quiero una asesoría."

    form_nombre:
      type: question
      style: input
      validation: "name"
      store_as: "nombre"
      next: form_email

    form_email:
      type: question
      style: input
      validation: "email"
      store_as: "email"
      next: gracias

    gracias:
      type: message
      text: "¡Gracias, {{nombre}}! Te contacto al correo {{email}} en breve."
      action:
        type: event
        name: "lead_capturado"`;
                        
                        // Update visible editor first (try multiple elements)
                        const yamlEditor = document.getElementById('yaml-content');
                        const divEditor = document.getElementById('wac-yaml-editor');
                        
                        if (yamlEditor) {
                            yamlEditor.value = exampleYAML;
                            console.log('✅ Updated yaml-content textarea');
                        } else if (divEditor) {
                            divEditor.textContent = exampleYAML;
                            console.log('✅ Updated wac-yaml-editor div');
                        } else {
                            console.log('❌ No editor element found');
                        }
                        
                        // Update hidden textarea LAST (this is what gets saved)
                        const configTextarea = document.getElementById('wac-funnel-config');
                        if (configTextarea) {
                            configTextarea.value = exampleYAML;
                            console.log('✅ Updated hidden textarea (this is what gets saved)');
                        }
                        
                        // Show success message
                        const status = document.getElementById('debug-status');
                        if (status) {
                            status.innerHTML = '✅ Example YAML loaded! ' + new Date().toLocaleTimeString();
                        }
                        
                        alert('Example YAML loaded! Check the editor and try Force Debug again.');
                        console.log('📝 Example YAML loaded:', exampleYAML.substring(0, 100) + '...');
                    });
                }
            
                // Clear editor button
                const clearBtn = document.getElementById('clear-editor-btn');
                if (clearBtn) {
                    clearBtn.addEventListener('click', function() {
                        console.log('🗑️ Clear Button Clicked');
                        
                        // Clear hidden textarea
                        const configTextarea = document.getElementById('wac-funnel-config');
                        if (configTextarea) {
                            configTextarea.value = '';
                            console.log('✅ Cleared hidden textarea');
                        }
                        
                        // Clear visible editor
                        const yamlEditor = document.getElementById('yaml-content');
                        if (yamlEditor) {
                            yamlEditor.value = '';
                            console.log('✅ Cleared yaml editor');
                        }
                        
                        // Show success message
                        const status = document.getElementById('debug-status');
                        if (status) {
                            status.innerHTML = '🗑️ Editor cleared! ' + new Date().toLocaleTimeString();
                        }
                        
                        alert('Editor cleared! Now try Load Example again.');
                    });
                }
            
                // Test Parser button
                const testParserBtn = document.getElementById('test-parser-btn');
                if (testParserBtn) {
                    testParserBtn.addEventListener('click', function() {
                        console.log('🧪 Test Parser Button Clicked');
                        
                        const testYAML = `funnel:
  id: "test_parser"
  start: "intro"
  nodes:
    intro:
      type: message
      text: "Test parser"`;
                        
                        // Test direct API call
                        fetch('/wp-json/wac-chat/v1/validate-yaml', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': window.wacChatAdmin ? window.wacChatAdmin.apiNonce : ''
                            },
                            body: JSON.stringify({ yaml: testYAML })
                        })
                        .then(response => response.json())
                        .then(data => {
                            console.log('🧪 Parser Test Result:', data);
                            
                            const previewContainer = document.getElementById('wac-chat-preview');
                            if (previewContainer) {
                                previewContainer.innerHTML = `
                                    <div style="padding:16px;background:#fff3cd;border:1px solid #ffeaa7;border-radius:6px;">
                                        <h3 style="margin: 0 0 15px 0; color: #856404;">🧪 Test Parser Result</h3>
                                        
                                        <div style="margin-bottom: 10px;">
                                            <strong>Test YAML:</strong><br>
                                            <pre style="background: #f8f9fa; padding: 8px; border-radius: 4px; font-size: 12px; margin: 5px 0;">${testYAML}</pre>
                                        </div>
                                        
                                        <div style="margin-bottom: 10px;">
                                            <strong>API Response:</strong><br>
                                            <pre style="background: #f8f9fa; padding: 8px; border-radius: 4px; font-size: 12px; margin: 5px 0;">${JSON.stringify(data, null, 2)}</pre>
                                        </div>
                                        
                                        <div style="margin-top: 15px;">
                                            <button onclick="document.getElementById('wac-chat-preview').innerHTML='';" class="button">Cerrar Test</button>
                                        </div>
                                    </div>
                                `;
                            }
                        })
                        .catch(error => {
                            console.error('🧪 Parser Test Error:', error);
                            
                            const previewContainer = document.getElementById('wac-chat-preview');
                            if (previewContainer) {
                                previewContainer.innerHTML = `
                                    <div style="padding:16px;background:#f8d7da;border:1px solid #f5c6cb;border-radius:6px;">
                                        <h3 style="margin: 0 0 15px 0; color: #721c24;">🧪 Test Parser Error</h3>
                                        <p><strong>Error:</strong> ${error.message}</p>
                                        <p><strong>Check console for details</strong></p>
                                        <button onclick="document.getElementById('wac-chat-preview').innerHTML='';" class="button">Cerrar</button>
                                    </div>
                                `;
                            }
                        });
                    });
                }
            
                // Install Composer button
                const installComposerBtn = document.getElementById('install-composer-btn');
                if (installComposerBtn) {
                    installComposerBtn.addEventListener('click', function() {
                        console.log('📦 Install Composer Button Clicked');
                        
                        const previewContainer = document.getElementById('wac-chat-preview');
                        if (previewContainer) {
                            previewContainer.innerHTML = `
                                <div style="padding:16px;background:#e7f3ff;border:1px solid #b3d9ff;border-radius:6px;">
                                    <h3 style="margin: 0 0 15px 0; color: #0066cc;">📦 Instalar Dependencias de Composer</h3>
                                    
                                    <div style="margin-bottom: 15px;">
                                        <p><strong>Para que el parser YAML funcione correctamente, necesitas instalar las dependencias de Composer:</strong></p>
                                    </div>
                                    
                                    <div style="margin-bottom: 15px;">
                                        <h4>Opción 1: SSH/Terminal (Recomendado)</h4>
                                        <p>Accede a tu servidor vía SSH y ejecuta:</p>
                                        <pre style="background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 12px; margin: 5px 0;">
cd /ruta/a/tu/sitio/wp-content/plugins/wac-chat-funnels/
composer install --no-dev --optimize-autoloader</pre>
                                    </div>
                                    
                                    <div style="margin-bottom: 15px;">
                                        <h4>Opción 2: Panel de Control</h4>
                                        <p>Si tu hosting tiene un panel de control con terminal:</p>
                                        <ol>
                                            <li>Ve al terminal del panel de control</li>
                                            <li>Navega al directorio del plugin</li>
                                            <li>Ejecuta: <code>composer install --no-dev --optimize-autoloader</code></li>
                                        </ol>
                                    </div>
                                    
                                    <div style="margin-bottom: 15px;">
                                        <h4>Opción 3: Subir vendor/ manualmente</h4>
                                        <p>Si no tienes acceso a Composer:</p>
                                        <ol>
                                            <li>Descarga el plugin completo desde GitHub</li>
                                            <li>Incluye la carpeta <code>vendor/</code></li>
                                            <li>Sube todo al servidor</li>
                                        </ol>
                                    </div>
                                    
                                    <div style="margin-bottom: 15px;">
                                        <h4>Verificación</h4>
                                        <p>Después de instalar, haz clic en "🧪 Test Parser" para verificar que funciona.</p>
                                    </div>
                                    
                                    <div style="margin-top: 15px;">
                                        <button onclick="document.getElementById('wac-chat-preview').innerHTML='';" class="button">Cerrar</button>
                                        <button onclick="document.getElementById('test-parser-btn').click();" class="button">🧪 Test Parser</button>
                                    </div>
                                </div>
                            `;
                        }
                    });
                }
            
                // Debug Save button
                const debugSaveBtn = document.getElementById('debug-save-btn');
                if (debugSaveBtn) {
                    debugSaveBtn.addEventListener('click', function() {
                        console.log('💾 Debug Save Button Clicked');
                        
                        // Get current YAML from multiple sources
                        const yamlEditor = document.getElementById('yaml-content');
                        const divEditor = document.getElementById('wac-yaml-editor');
                        const hiddenField = document.getElementById('wac-funnel-config');
                        
                        let yaml = '';
                        if (yamlEditor && yamlEditor.value) {
                            yaml = yamlEditor.value;
                        } else if (divEditor && divEditor.textContent) {
                            yaml = divEditor.textContent;
                        } else if (hiddenField && hiddenField.value) {
                            yaml = hiddenField.value;
                        }
                        
                        const previewContainer = document.getElementById('wac-chat-preview');
                        if (previewContainer) {
                            previewContainer.innerHTML = `
                                <div style="padding:16px;background:#fff3cd;border:1px solid #ffeaa7;border-radius:6px;">
                                    <h3 style="margin: 0 0 15px 0; color: #856404;">💾 Debug Save Status</h3>
                                    
                                    <div style="margin-bottom: 10px;">
                                        <strong>Current YAML from Editor:</strong><br>
                                        <pre style="background: #f8f9fa; padding: 8px; border-radius: 4px; font-size: 12px; margin: 5px 0; max-height: 200px; overflow-y: auto;">${yaml}</pre>
                                    </div>
                                    
                                    <div style="margin-bottom: 10px;">
                                        <strong>Hidden Field Content:</strong><br>
                                        <pre style="background: #f8f9fa; padding: 8px; border-radius: 4px; font-size: 12px; margin: 5px 0; max-height: 200px; overflow-y: auto;">${hiddenField ? hiddenField.value : 'NOT FOUND'}</pre>
                                    </div>
                                    
                                    <div style="margin-bottom: 10px;">
                                        <strong>Sync Status:</strong><br>
                                        • Editor length: ${yaml.length}<br>
                                        • Hidden length: ${hiddenField ? hiddenField.value.length : 'N/A'}<br>
                                        • Are they equal? ${yaml === (hiddenField ? hiddenField.value : '') ? '✅ YES' : '❌ NO'}
                                    </div>
                                    
                                    <div style="margin-bottom: 10px;">
                                        <strong>Actions:</strong><br>
                                        <button onclick="document.getElementById('wac-funnel-config').value = document.getElementById('wac-yaml-editor').textContent || document.getElementById('yaml-content').value || ''; alert('Sincronizado!');" class="button">🔄 Force Sync</button>
                                        <button onclick="document.getElementById('wac-funnel-config').value = ''; alert('Limpiado!');" class="button">🗑️ Clear Hidden</button>
                                    </div>
                                    
                                    <div style="margin-top: 15px;">
                                        <button onclick="document.getElementById('wac-chat-preview').innerHTML='';" class="button">Cerrar Debug</button>
                                    </div>
                                </div>
                            `;
                        }
                    });
                }
            
                // Force Debug button
                const forceDebugBtn = document.getElementById('force-debug-btn');
                if (forceDebugBtn) {
                    forceDebugBtn.addEventListener('click', function() {
                        console.log('🚨 Force Debug Button Clicked');
                        
                        // Get current YAML from multiple sources
                        const yamlEditor = document.getElementById('yaml-content');
                        const divEditor = document.getElementById('wac-yaml-editor');
                        const hiddenField = document.getElementById('wac-funnel-config');
                        
                        let yaml = '';
                        if (yamlEditor && yamlEditor.value) {
                            yaml = yamlEditor.value;
                        } else if (divEditor && divEditor.textContent) {
                            yaml = divEditor.textContent;
                        } else if (hiddenField && hiddenField.value) {
                            yaml = hiddenField.value;
                        }
                        
                        // Get preview container
                        const previewContainer = document.getElementById('wac-chat-preview');
                        
                        if (previewContainer) {
                            // Show debug info in preview
                            previewContainer.innerHTML = `
                                <div style="padding:16px;background:#f0f8ff;border:1px solid #b3d9ff;border-radius:6px;">
                                    <h3 style="margin: 0 0 15px 0; color: #0066cc;">🚨 Force Debug YAML</h3>
                                    
                                    <div style="margin-bottom: 10px;">
                                        <strong>YAML Info:</strong><br>
                                        • Longitud: ${yaml.length} caracteres<br>
                                        • Líneas: ${yaml.split('\n').length}<br>
                                        • ¿Contiene 'funnel:'? ${yaml.includes('funnel:') ? '✅ Sí' : '❌ No'}<br>
                                        • Posición de 'funnel:': ${yaml.indexOf('funnel:')}<br>
                                        • ¿Contiene 'Funnel:'? ${yaml.includes('Funnel:') ? '✅ Sí' : '❌ No'}<br>
                                    </div>

                                    <div style="margin-bottom: 10px;">
                                        <strong>Primeras líneas:</strong><br>
                                        <pre style="background: #f5f5f5; padding: 8px; border-radius: 4px; font-size: 12px; margin: 5px 0;">
1: ${yaml.split('\n')[0] || '(vacío)'}
2: ${yaml.split('\n')[1] || '(vacío)'}
3: ${yaml.split('\n')[2] || '(vacío)'}
4: ${yaml.split('\n')[3] || '(vacío)'}
5: ${yaml.split('\n')[4] || '(vacío)'}
                                        </pre>
                                    </div>

                                    <div style="margin-bottom: 10px;">
                                        <strong>YAML Completo:</strong><br>
                                        <textarea readonly style="width: 100%; height: 200px; font-family: monospace; font-size: 12px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">${yaml}</textarea>
                                    </div>

                                    <div style="margin-bottom: 10px;">
                                        <strong>JavaScript Status:</strong><br>
                                        • WACAdminEditor: ${typeof WACAdminEditor !== 'undefined' ? '✅ Disponible' : '❌ No disponible'}<br>
                                        • wacChatAdmin: ${typeof wacChatAdmin !== 'undefined' ? '✅ Disponible' : '❌ No disponible'}<br>
                                        • window.wacChatAdmin: ${typeof window.wacChatAdmin !== 'undefined' ? '✅ Disponible' : '❌ No disponible'}<br>
                                        • yaml-content element: ${yamlEditor ? '✅ Encontrado' : '❌ No encontrado'}<br>
                                        • wac-yaml-editor element: ${divEditor ? '✅ Encontrado' : '❌ No encontrado'}<br>
                                        • wac-funnel-config element: ${hiddenField ? '✅ Encontrado' : '❌ No encontrado'}<br>
                                        • Hidden field value length: ${hiddenField ? hiddenField.value.length : 'N/A'}<br>
                                    </div>

                                    <div style="margin-top: 15px;">
                                        <button onclick="document.getElementById('wac-chat-preview').innerHTML=''; this.updatePreview();" class="button">Cerrar Debug</button>
                                        <button onclick="navigator.clipboard.writeText('${yaml.replace(/'/g, "\\'")}'); alert('YAML copiado');" class="button">Copiar YAML</button>
                                    </div>
                                </div>
                            `;
                        }
                        
                        // Log to console
                        console.group('🚨 Force Debug YAML');
                        console.log('YAML Content:', yaml);
                        console.log('YAML Length:', yaml.length);
                        console.log('Has funnel:', yaml.includes('funnel:'));
                        console.log('Has Funnel:', yaml.includes('Funnel:'));
                        console.log('Funnel position:', yaml.indexOf('funnel:'));
                        console.log('First 5 lines:', yaml.split('\n').slice(0, 5));
                        console.groupEnd();
                    });
                }
                
                // Check if main script is loaded
                setTimeout(function() {
                    if (typeof WACAdminEditor !== 'undefined') {
                        console.log('✅ WACAdminEditor class is available');
                    } else {
                        console.log('❌ WACAdminEditor class is NOT available');
                    }
                    
                    if (typeof wacChatAdmin !== 'undefined') {
                        console.log('✅ wacChatAdmin object is available:', wacChatAdmin);
                    } else {
                        console.log('❌ wacChatAdmin object is NOT available');
                    }
                }, 1000);
                
                // Hook para sincronizar antes de guardar
                const syncBeforeSave = function() {
                    console.log('🔄 Syncing before save...');
                    const yamlEditor = document.getElementById('yaml-content');
                    const divEditor = document.getElementById('wac-yaml-editor');
                    const hiddenField = document.getElementById('wac-funnel-config');
                    
                    let yaml = '';
                    if (yamlEditor && yamlEditor.value) {
                        yaml = yamlEditor.value;
                    } else if (divEditor && divEditor.textContent) {
                        yaml = divEditor.textContent;
                    }
                    
                    if (hiddenField && yaml) {
                        hiddenField.value = yaml;
                        console.log('✅ Synced YAML to hidden field before save');
                    }
                };
                
                // Hook en el botón de guardar
                const saveButton = document.getElementById('publish') || document.querySelector('input[name="save"]') || document.querySelector('#post #save-post');
                if (saveButton) {
                    saveButton.addEventListener('click', syncBeforeSave);
                    console.log('✅ Added sync hook to save button');
                }
                
                // Hook en el formulario
                const postForm = document.getElementById('post');
                if (postForm) {
                    postForm.addEventListener('submit', syncBeforeSave);
                    console.log('✅ Added sync hook to form submit');
                }
        });
        </script>
        
        <div id="wac-funnel-preview" style="margin-top: 20px;">
            <h3><?php _e('Vista Previa', 'wac-chat-funnels'); ?></h3>
            <div id="wac-chat-preview" style="border: 1px solid #ddd; height: 500px; background: #f9f9f9;"></div>
        </div>
        <?php
    }
    
    public static function funnel_rules_metabox($post) {
        wp_nonce_field('wac_funnel_rules', 'wac_funnel_rules_nonce');
        
        $rules = get_post_meta($post->ID, '_wac_funnel_rules', true);
        if (empty($rules)) {
            $rules = array();
        }
        
        ?>
        <div id="wac-rules-builder">
            <p><?php _e('Configura cuándo mostrar este funnel:', 'wac-chat-funnels'); ?></p>
            
            <div id="wac-rules-list">
                <!-- Las reglas se cargarán dinámicamente con JavaScript -->
                <?php if (!empty($rules)): ?>
                    <?php foreach ($rules as $index => $rule): ?>
                        <div class="wac-rule-item" data-index="<?php echo $index; ?>">
                            <select name="rule_type_<?php echo $index; ?>" class="rule-type">
                                <option value="page" <?php selected($rule['type'], 'page'); ?>><?php _e('Página específica', 'wac-chat-funnels'); ?></option>
                                <option value="post_type" <?php selected($rule['type'], 'post_type'); ?>><?php _e('Tipo de contenido', 'wac-chat-funnels'); ?></option>
                                <option value="category" <?php selected($rule['type'], 'category'); ?>><?php _e('Categoría', 'wac-chat-funnels'); ?></option>
                                <option value="time" <?php selected($rule['type'], 'time'); ?>><?php _e('Horario', 'wac-chat-funnels'); ?></option>
                            </select>
                            <input type="text" name="rule_value_<?php echo $index; ?>" class="rule-value" value="<?php echo esc_attr($rule['value']); ?>" placeholder="<?php _e('Valor de la regla', 'wac-chat-funnels'); ?>">
                            <button type="button" class="button wac-remove-rule"><?php _e('Eliminar', 'wac-chat-funnels'); ?></button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <button type="button" id="wac-add-rule" class="button"><?php _e('Agregar Regla', 'wac-chat-funnels'); ?></button>
            
            <textarea id="wac-funnel-rules" name="wac_funnel_rules" style="display: none;"><?php echo esc_textarea(json_encode($rules)); ?></textarea>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔧 Rules builder initialized');
            
            // Add rule button
            document.getElementById('wac-add-rule').addEventListener('click', function() {
                const rulesList = document.getElementById('wac-rules-list');
                const index = rulesList.children.length;
                
                const ruleHtml = `
                    <div class="wac-rule-item" data-index="${index}">
                        <select name="rule_type_${index}" class="rule-type">
                            <option value="page"><?php _e('Página específica', 'wac-chat-funnels'); ?></option>
                            <option value="post_type"><?php _e('Tipo de contenido', 'wac-chat-funnels'); ?></option>
                            <option value="category"><?php _e('Categoría', 'wac-chat-funnels'); ?></option>
                            <option value="time"><?php _e('Horario', 'wac-chat-funnels'); ?></option>
                        </select>
                        <input type="text" name="rule_value_${index}" class="rule-value" placeholder="<?php _e('Valor de la regla', 'wac-chat-funnels'); ?>">
                        <button type="button" class="button wac-remove-rule"><?php _e('Eliminar', 'wac-chat-funnels'); ?></button>
                    </div>
                `;
                
                rulesList.insertAdjacentHTML('beforeend', ruleHtml);
                updateRulesJson();
            });
            
            // Remove rule button
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('wac-remove-rule')) {
                    e.target.closest('.wac-rule-item').remove();
                    updateRulesJson();
                }
            });
            
            // Update rules when changed
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('rule-type') || e.target.classList.contains('rule-value')) {
                    updateRulesJson();
                }
            });
            
            function updateRulesJson() {
                const rules = [];
                const ruleItems = document.querySelectorAll('.wac-rule-item');
                
                ruleItems.forEach(function(item, index) {
                    const type = item.querySelector('.rule-type').value;
                    const value = item.querySelector('.rule-value').value;
                    
                    if (type && value) {
                        rules.push({
                            type: type,
                            value: value
                        });
                    }
                });
                
                document.getElementById('wac-funnel-rules').value = JSON.stringify(rules);
                console.log('📝 Rules updated:', rules);
            }
        });
        </script>
        <?php
    }
    
    public static function funnel_settings_metabox($post) {
        wp_nonce_field('wac_funnel_settings', 'wac_funnel_settings_nonce');
        
        $active = get_post_meta($post->ID, '_wac_funnel_active', true);
        $priority = get_post_meta($post->ID, '_wac_funnel_priority', true);
        $teaser_config = get_post_meta($post->ID, '_wac_funnel_teaser', true);
        
        if (empty($priority)) {
            $priority = 10;
        }
        
        if (empty($teaser_config)) {
            $teaser_config = array(
                'enabled' => true,
                'delay' => 3000,
                'text' => '¿Necesitas ayuda?',
                'icon' => 'chat'
            );
        }
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Estado', 'wac-chat-funnels'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wac_funnel_active" value="1" <?php checked($active, '1'); ?>>
                        <?php _e('Activo', 'wac-chat-funnels'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Prioridad', 'wac-chat-funnels'); ?></th>
                <td>
                    <input type="number" name="wac_funnel_priority" value="<?php echo esc_attr($priority); ?>" min="1" max="100">
                    <p class="description"><?php _e('Mayor número = mayor prioridad', 'wac-chat-funnels'); ?></p>
                </td>
            </tr>
        </table>
        
        <h4><?php _e('Configuración del Teaser', 'wac-chat-funnels'); ?></h4>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Mostrar Teaser', 'wac-chat-funnels'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wac_teaser_enabled" value="1" <?php checked($teaser_config['enabled'], true); ?>>
                        <?php _e('Mostrar burbuja teaser', 'wac-chat-funnels'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Delay (ms)', 'wac-chat-funnels'); ?></th>
                <td>
                    <input type="number" name="wac_teaser_delay" value="<?php echo esc_attr($teaser_config['delay']); ?>" min="0" max="30000">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Texto del Teaser', 'wac-chat-funnels'); ?></th>
                <td>
                    <input type="text" name="wac_teaser_text" value="<?php echo esc_attr($teaser_config['text']); ?>" class="regular-text">
                </td>
            </tr>
        </table>
        <?php
    }
    
    public static function save_metaboxes($post_id) {
        // Verificar nonces y permisos
        if (!current_user_can('manage_chat_funnels')) {
            return;
        }
        
        // Guardar configuración del funnel
        if (isset($_POST['wac_funnel_config_nonce']) && wp_verify_nonce($_POST['wac_funnel_config_nonce'], 'wac_funnel_config')) {
            if (isset($_POST['wac_funnel_config'])) {
                $raw = wp_unslash($_POST['wac_funnel_config']);          // respeta saltos de línea
                // NO uses sanitize_textarea_field: rompe la indentación del YAML
                update_post_meta($post_id, '_wac_funnel_config', $raw);
            }
        }
        
        // Guardar reglas
        if (isset($_POST['wac_funnel_rules_nonce']) && wp_verify_nonce($_POST['wac_funnel_rules_nonce'], 'wac_funnel_rules')) {
            $rules = json_decode(stripslashes($_POST['wac_funnel_rules']), true);
            update_post_meta($post_id, '_wac_funnel_rules', $rules);
        }
        
        // Guardar configuración general
        if (isset($_POST['wac_funnel_settings_nonce']) && wp_verify_nonce($_POST['wac_funnel_settings_nonce'], 'wac_funnel_settings')) {
            update_post_meta($post_id, '_wac_funnel_active', isset($_POST['wac_funnel_active']) ? '1' : '0');
            update_post_meta($post_id, '_wac_funnel_priority', intval($_POST['wac_funnel_priority']));
            
            $teaser_config = array(
                'enabled' => isset($_POST['wac_teaser_enabled']),
                'delay' => intval($_POST['wac_teaser_delay']),
                'text' => sanitize_text_field($_POST['wac_teaser_text'])
            );
            update_post_meta($post_id, '_wac_funnel_teaser', $teaser_config);
        }
    }
    
    private static function get_default_funnel_config() {
        return <<<YAML
funnel:
  id: "lead_basico"
  start: "intro"
  nodes:
    intro:
      type: message
      text: |
        ¡Hola! Soy **Asistente WACosta** 👋
        ¿En qué puedo ayudarte hoy?
      next: menu

    menu:
      type: question
      style: choice
      options:
        - label: "Quiero cotización"
          next: form_nombre
        - label: "Ver portafolio"
          action: redirect
          url: "/portafolio"
        - label: "Hablar por WhatsApp"
          action: whatsapp
          phone: "+573154543344"
          prefill: "Hola, quiero una asesoría."

    form_nombre:
      type: question
      style: input
      validation: "name"
      store_as: "nombre"
      text: "¿Cuál es tu nombre?"
      next: form_email

    form_email:
      type: question
      style: input
      validation: "email"
      store_as: "email"
      text: "¿Cuál es tu email?"
      next: gracias

    gracias:
      type: message
      text: "¡Gracias {{nombre}}! Te escribo al correo {{email}} en breve."
      action: event
      event_name: "lead_capturado"
YAML;
    }
}
