/**
 * WAC Chat Funnels Admin Interface
 * Editor YAML con preview en vivo
 */

class WACAdminEditor {
  constructor() {
    this.yamlEditor = null;
    this.previewContainer = null;
    this.rulesContainer = null;
    this.init();
  }

  init() {
    this.initYAMLEditor();
    this.initPreview();
    this.initRulesBuilder();
    this.bindEvents();
  }

  initYAMLEditor() {
    const editorElement = document.getElementById('wac-yaml-editor');
    if (!editorElement) return;

    // Obtener el valor del textarea oculto
    const configTextarea = document.getElementById('wac-funnel-config');
    const configValue = configTextarea ? configTextarea.value : '';

    // Simple textarea editor for now
    // In production, you'd integrate Monaco Editor
    editorElement.innerHTML = `
      <div style="display: flex; gap: 10px; margin-bottom: 10px;">
        <button id="debug-btn" type="button" class="button">🐛 Debug Info</button>
        <button id="clear-editor" type="button" class="button">🗑️ Limpiar</button>
        <button id="load-example" type="button" class="button">📝 Cargar Ejemplo</button>
      </div>
      <textarea id="yaml-content" style="width: 100%; height: calc(100% - 50px); border: none; outline: none; font-family: 'Courier New', monospace; font-size: 13px; padding: 10px; resize: none;">${configValue}</textarea>
    `;

    this.yamlEditor = document.getElementById('yaml-content');
    this.initDebugButtons();
  }

  initDebugButtons() {
    // Botón Debug Info
    const debugBtn = document.getElementById('debug-btn');
    if (debugBtn) {
      debugBtn.addEventListener('click', () => {
        this.showDebugInfo();
      });
    }

    // Botón Limpiar
    const clearBtn = document.getElementById('clear-editor');
    if (clearBtn) {
      clearBtn.addEventListener('click', () => {
        if (this.yamlEditor) {
          this.yamlEditor.value = '';
          this.updatePreview();
        }
      });
    }

    // Botón Cargar Ejemplo
    const exampleBtn = document.getElementById('load-example');
    if (exampleBtn) {
      exampleBtn.addEventListener('click', () => {
        this.loadExampleYAML();
      });
    }
  }

  showDebugInfo() {
    // Probar la función validateYAML para ver si hay errores
    let validationTest = null;
    if (this.yamlEditor && this.yamlEditor.value.trim()) {
      try {
        this.validateYAML(this.yamlEditor.value).then(result => {
          console.log('🔍 Validation test result:', result);
        }).catch(error => {
          console.error('❌ Validation test error:', error);
        });
      } catch (error) {
        console.error('❌ Validation test catch error:', error);
      }
    }

    const debugInfo = {
      'yamlEditor': !!this.yamlEditor,
      'previewContainer': !!this.previewContainer,
      'rulesContainer': !!this.rulesContainer,
      'wacChatAdmin': typeof wacChatAdmin !== 'undefined',
      'wacChatAdmin.apiUrl': typeof wacChatAdmin !== 'undefined' ? wacChatAdmin.apiUrl : 'undefined',
      'wacChatAdmin.apiNonce': typeof wacChatAdmin !== 'undefined' ? wacChatAdmin.apiNonce : 'undefined',
      'yamlContent': this.yamlEditor ? this.yamlEditor.value.length : 0,
      'yamlValue': this.yamlEditor ? this.yamlEditor.value.substring(0, 100) + '...' : 'undefined',
      'configTextarea': !!document.getElementById('wac-funnel-config'),
      'configValue': document.getElementById('wac-funnel-config') ? document.getElementById('wac-funnel-config').value.substring(0, 100) + '...' : 'undefined',
      'DOM Ready': document.readyState,
      'Current URL': window.location.href,
      'User Agent': navigator.userAgent,
      'Timestamp': new Date().toLocaleString()
    };

    console.log('🐛 WAC Chat Funnels Debug Info:', debugInfo);
    
    // Mostrar en el preview también
    if (this.previewContainer) {
      let debugHTML = '<div style="padding: 20px; background: #f0f0f1; border-radius: 4px; font-family: monospace; font-size: 12px; max-height: 400px; overflow-y: auto;">';
      debugHTML += '<h3 style="margin: 0 0 15px 0;">🐛 Debug Information</h3>';
      
      Object.entries(debugInfo).forEach(([key, value]) => {
        const displayValue = typeof value === 'object' ? JSON.stringify(value, null, 2) : value;
        debugHTML += `<div style="margin: 5px 0; padding: 5px; background: white; border-radius: 3px;"><strong>${key}:</strong><br><code style="word-break: break-all;">${displayValue}</code></div>`;
      });
      
      debugHTML += '</div>';
      this.previewContainer.innerHTML = debugHTML;
    }

    // También mostrar un alert con información básica
    const basicInfo = `Debug Info:
- YAML Editor: ${debugInfo.yamlEditor ? '✓' : '✗'}
- Preview Container: ${debugInfo.previewContainer ? '✓' : '✗'}
- wacChatAdmin: ${debugInfo.wacChatAdmin ? '✓' : '✗'}
- YAML Content Length: ${debugInfo.yamlContent}
- DOM Ready: ${debugInfo.DOMReady}`;
    
    alert(basicInfo + '\n\nFull details logged to console (F12).');
  }

    loadExampleYAML() {
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
      event_name: "lead_capturado"`;

    if (this.yamlEditor) {
      this.yamlEditor.value = exampleYAML;
      this.updatePreview();
    }
  }

  initPreview() {
    this.previewContainer = document.getElementById('wac-chat-preview');
    if (!this.previewContainer) return;

    this.updatePreview();
  }

  initRulesBuilder() {
    this.rulesContainer = document.getElementById('wac-rules-list');
    if (!this.rulesContainer) return;

    this.loadRules();
  }

  bindEvents() {
    // YAML editor change
    if (this.yamlEditor) {
      this.yamlEditor.addEventListener('input', () => {
        this.debounce(() => {
          this.updatePreview();
          this.validateYAML();
        }, 500);
      });
    } else {
      // Reintentar obtener el editor después de un pequeño delay
      setTimeout(() => {
        this.yamlEditor = document.getElementById('yaml-content');
        if (this.yamlEditor) {
          this.bindEvents();
        }
      }, 100);
    }

    // Add rule button
    const addRuleBtn = document.getElementById('wac-add-rule');
    if (addRuleBtn) {
      addRuleBtn.addEventListener('click', () => {
        this.addRule();
      });
    }

    // Save button
    const saveBtn = document.querySelector('#post #publish');
    if (saveBtn) {
      saveBtn.addEventListener('click', () => {
        this.saveConfig();
      });
    }
  }

  updatePreview() {
    if (!this.yamlEditor || !this.previewContainer) return;

    const yamlContent = this.yamlEditor.value;
    if (!yamlContent.trim()) {
      this.previewContainer.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">Ingresa contenido YAML para ver la preview</div>';
      return;
    }

    // Show loading
    this.previewContainer.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">Generando preview...</div>';

    // Validate and parse YAML
    this.validateYAML(yamlContent).then(result => {
      if (result.valid) {
        this.renderPreview(result.config);
      } else {
        this.previewContainer.innerHTML = `
          <div style="padding: 20px; color: #d63638; background: #fcf0f1; border-radius: 4px;">
            <strong>Error en YAML:</strong><br>
            ${result.errors ? result.errors.join('<br>') : result.message || 'Error desconocido'}
          </div>
        `;
      }
    }).catch(error => {
      this.previewContainer.innerHTML = `
        <div style="padding: 20px; color: #d63638; background: #fcf0f1; border-radius: 4px;">
          <strong>Error:</strong> ${error.message}
        </div>
      `;
    });
  }

  async validateYAML(yamlContent = null) {
    const content = yamlContent || this.yamlEditor.value;
    
    // Verificar que wacChatAdmin esté definido
    if (typeof wacChatAdmin === 'undefined') {
      return { 
        valid: false, 
        errors: ['Error: Configuración de administración no encontrada'],
        message: 'Error de configuración'
      };
    }
    
    try {
      const response = await fetch(wacChatAdmin.apiUrl + 'validate-yaml', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': wacChatAdmin.apiNonce
        },
        body: JSON.stringify({ yaml: content })
      });

      const result = await response.json();
      
      // Asegurar que el resultado tenga la estructura correcta
      return {
        valid: result.valid || false,
        errors: result.errors || [],
        message: result.message || '',
        config: result.config || null
      };
    } catch (error) {
      return { 
        valid: false, 
        errors: [error.message || 'Error de conexión'],
        message: 'Error de validación'
      };
    }
  }

  renderPreview(config) {
    if (!config || !config.funnel) {
      this.previewContainer.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">No hay configuración válida para mostrar</div>';
      return;
    }
    
    const funnel = config.funnel;
    const nodes = funnel.nodes || {};
    const startNode = funnel.start || '';

    let html = `
      <div style="padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
        <h3 style="margin: 0 0 15px 0; color: #1d2327;">${funnel.id || 'Sin ID'}</h3>
        <div style="background: #f0f0f1; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
          <strong>Nodo de inicio:</strong> ${startNode || 'No definido'}
        </div>
    `;

    // Render nodes
    Object.entries(nodes).forEach(([nodeId, node]) => {
      if (!node || typeof node !== 'object') {
        return; // Saltar nodos inválidos
      }
      
      html += `
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
          <h4 style="margin: 0 0 10px 0; color: #1d2327; display: flex; align-items: center; gap: 10px;">
            ${nodeId}
            <span style="background: #0073aa; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: normal;">
              ${node.type || 'sin tipo'}
            </span>
          </h4>
      `;

      if (node.text) {
        html += `<div style="margin: 10px 0; line-height: 1.5;">${this.parseMarkdown(node.text)}</div>`;
      }

      if (node.options && Array.isArray(node.options)) {
        html += `<div style="margin: 10px 0;"><strong>Opciones:</strong></div><ul style="margin: 5px 0; padding-left: 20px;">`;
        node.options.forEach(option => {
          if (option && option.label) {
            html += `<li>${option.label}</li>`;
          }
        });
        html += `</ul>`;
      }

      if (node.validation) {
        html += `<div style="margin: 10px 0; font-size: 12px; color: #666;">Validación: ${node.validation}</div>`;
      }

      if (node.store_as) {
        html += `<div style="margin: 10px 0; font-size: 12px; color: #666;">Guardar como: ${node.store_as}</div>`;
      }

      if (node.next) {
        html += `<div style="margin: 10px 0; font-size: 12px; color: #0073aa;">→ ${node.next}</div>`;
      }

      html += `</div>`;
    });

    html += `</div>`;
    this.previewContainer.innerHTML = html;
  }

  parseMarkdown(text) {
    return text
      .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
      .replace(/\*(.*?)\*/g, '<em>$1</em>')
      .replace(/\n/g, '<br>');
  }

  loadRules() {
    const rulesTextarea = document.getElementById('wac-funnel-rules');
    if (!rulesTextarea) return;

    try {
      const rules = JSON.parse(rulesTextarea.value || '[]');
      this.renderRules(rules);
    } catch (error) {
      console.error('Error loading rules:', error);
    }
  }

  renderRules(rules) {
    this.rulesContainer.innerHTML = '';

    rules.forEach((rule, index) => {
      const ruleElement = document.createElement('div');
      ruleElement.className = 'wac-rule-item';
      ruleElement.style.cssText = `
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 15px;
        margin-bottom: 10px;
        background: white;
      `;

      ruleElement.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
          <strong>Regla ${index + 1}</strong>
          <button type="button" class="button button-small wac-remove-rule" data-index="${index}">
            Eliminar
          </button>
        </div>
        
        <table class="form-table" style="margin: 0;">
          <tr>
            <th style="width: 120px;">Condición</th>
            <td>
              <select name="rule_condition_${index}" class="wac-rule-condition">
                <option value="">Seleccionar...</option>
                <option value="url_contains" ${rule.condition === 'url_contains' ? 'selected' : ''}>URL contiene</option>
                <option value="post_type" ${rule.condition === 'post_type' ? 'selected' : ''}>Tipo de contenido</option>
                <option value="is_home" ${rule.condition === 'is_home' ? 'selected' : ''}>Es página de inicio</option>
                <option value="device_type" ${rule.condition === 'device_type' ? 'selected' : ''}>Tipo de dispositivo</option>
                <option value="utm_source" ${rule.condition === 'utm_source' ? 'selected' : ''}>UTM Source</option>
              </select>
            </td>
          </tr>
          <tr>
            <th>Operador</th>
            <td>
              <select name="rule_operator_${index}" class="wac-rule-operator">
                <option value="equals" ${rule.operator === 'equals' ? 'selected' : ''}>Es igual a</option>
                <option value="contains" ${rule.operator === 'contains' ? 'selected' : ''}>Contiene</option>
                <option value="in_list" ${rule.operator === 'in_list' ? 'selected' : ''}>Está en lista</option>
              </select>
            </td>
          </tr>
          <tr>
            <th>Valor</th>
            <td>
              <input type="text" name="rule_value_${index}" class="wac-rule-value" value="${rule.value || ''}" style="width: 100%;" />
            </td>
          </tr>
          <tr>
            <th>Acción</th>
            <td>
              <select name="rule_action_${index}" class="wac-rule-action">
                <option value="show" ${rule.action === 'show' ? 'selected' : ''}>Mostrar</option>
                <option value="hide" ${rule.action === 'hide' ? 'selected' : ''}>Ocultar</option>
              </select>
            </td>
          </tr>
        </table>
      `;

      this.rulesContainer.appendChild(ruleElement);
    });

    this.bindRuleEvents();
  }

  bindRuleEvents() {
    // Remove rule buttons
    this.rulesContainer.querySelectorAll('.wac-remove-rule').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const index = parseInt(e.target.dataset.index);
        this.removeRule(index);
      });
    });

    // Rule field changes
    this.rulesContainer.querySelectorAll('select, input').forEach(field => {
      field.addEventListener('change', () => {
        this.updateRules();
      });
    });
  }

  addRule() {
    const rulesTextarea = document.getElementById('wac-funnel-rules');
    if (!rulesTextarea) return;

    try {
      const rules = JSON.parse(rulesTextarea.value || '[]');
      rules.push({
        condition: '',
        operator: 'equals',
        value: '',
        action: 'show'
      });
      rulesTextarea.value = JSON.stringify(rules);
      this.renderRules(rules);
    } catch (error) {
      console.error('Error adding rule:', error);
    }
  }

  removeRule(index) {
    const rulesTextarea = document.getElementById('wac-funnel-rules');
    if (!rulesTextarea) return;

    try {
      const rules = JSON.parse(rulesTextarea.value || '[]');
      rules.splice(index, 1);
      rulesTextarea.value = JSON.stringify(rules);
      this.renderRules(rules);
    } catch (error) {
      console.error('Error removing rule:', error);
    }
  }

  updateRules() {
    const rules = [];
    
    this.rulesContainer.querySelectorAll('.wac-rule-item').forEach((item, index) => {
      const condition = item.querySelector('.wac-rule-condition').value;
      const operator = item.querySelector('.wac-rule-operator').value;
      const value = item.querySelector('.wac-rule-value').value;
      const action = item.querySelector('.wac-rule-action').value;

      if (condition && value) {
        rules.push({
          condition,
          operator,
          value,
          action
        });
      }
    });

    const rulesTextarea = document.getElementById('wac-funnel-rules');
    if (rulesTextarea) {
      rulesTextarea.value = JSON.stringify(rules);
    }
  }

  saveConfig() {
    const configTextarea = document.getElementById('wac-funnel-config');
    if (this.yamlEditor && configTextarea) {
      configTextarea.value = this.yamlEditor.value;
    }
  }

  debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }
}

// Global error handler para capturar errores JavaScript
window.addEventListener('error', function(event) {
  console.error('🚨 Global JavaScript Error:', {
    message: event.message,
    filename: event.filename,
    lineno: event.lineno,
    colno: event.colno,
    error: event.error,
    stack: event.error ? event.error.stack : 'No stack trace'
  });
  
  // Mostrar en el preview si está disponible
  const previewContainer = document.getElementById('wac-chat-preview');
  if (previewContainer) {
    previewContainer.innerHTML = `
      <div style="padding: 20px; color: #d63638; background: #fcf0f1; border-radius: 4px;">
        <h3 style="margin: 0 0 10px 0;">🚨 JavaScript Error Captured</h3>
        <p><strong>Message:</strong> ${event.message}</p>
        <p><strong>File:</strong> ${event.filename}</p>
        <p><strong>Line:</strong> ${event.lineno}:${event.colno}</p>
        <p><strong>Error:</strong> ${event.error ? event.error.toString() : 'No error object'}</p>
        <p style="margin-top: 10px; font-size: 12px;">Check browser console (F12) for full stack trace.</p>
      </div>
    `;
  }
});

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  // Esperar un poco más para asegurar que todos los elementos estén disponibles
  setTimeout(() => {
    try {
      if (document.getElementById('wac-funnel-editor')) {
        new WACAdminEditor();
      }
    } catch (error) {
      console.error('❌ Error initializing WACAdminEditor:', error);
    }
  }, 100);
});

// También intentar inicializar si el DOM ya está listo
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
      try {
        if (document.getElementById('wac-funnel-editor')) {
          new WACAdminEditor();
        }
      } catch (error) {
        console.error('❌ Error initializing WACAdminEditor (DOM loading):', error);
      }
    }, 100);
  });
} else {
  // DOM ya está listo
  setTimeout(() => {
    try {
      if (document.getElementById('wac-funnel-editor')) {
        new WACAdminEditor();
      }
    } catch (error) {
      console.error('❌ Error initializing WACAdminEditor (DOM ready):', error);
    }
  }, 100);
}
