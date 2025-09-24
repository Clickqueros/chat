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

    // Simple textarea editor for now
    // In production, you'd integrate Monaco Editor
    editorElement.innerHTML = `
      <textarea id="yaml-content" style="width: 100%; height: 100%; border: none; outline: none; font-family: 'Courier New', monospace; font-size: 13px; padding: 10px; resize: none;">${document.getElementById('wac-funnel-config').value}</textarea>
    `;

    this.yamlEditor = document.getElementById('yaml-content');
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
            ${result.errors.join('<br>')}
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
    
    try {
      const response = await fetch(wacChatAdmin.apiUrl + 'validate-yaml', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': wacChatAdmin.apiNonce
        },
        body: JSON.stringify({ yaml: content })
      });

      return await response.json();
    } catch (error) {
      return { valid: false, errors: [error.message] };
    }
  }

  renderPreview(config) {
    const funnel = config.funnel;
    const nodes = funnel.nodes;
    const startNode = funnel.start;

    let html = `
      <div style="padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
        <h3 style="margin: 0 0 15px 0; color: #1d2327;">${funnel.id}</h3>
        <div style="background: #f0f0f1; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
          <strong>Nodo de inicio:</strong> ${startNode}
        </div>
    `;

    // Render nodes
    Object.entries(nodes).forEach(([nodeId, node]) => {
      html += `
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
          <h4 style="margin: 0 0 10px 0; color: #1d2327; display: flex; align-items: center; gap: 10px;">
            ${nodeId}
            <span style="background: #0073aa; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: normal;">
              ${node.type}
            </span>
          </h4>
      `;

      if (node.text) {
        html += `<div style="margin: 10px 0; line-height: 1.5;">${this.parseMarkdown(node.text)}</div>`;
      }

      if (node.options) {
        html += `<div style="margin: 10px 0;"><strong>Opciones:</strong></div><ul style="margin: 5px 0; padding-left: 20px;">`;
        node.options.forEach(option => {
          html += `<li>${option.label}</li>`;
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

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('wac-funnel-editor')) {
    new WACAdminEditor();
  }
});
