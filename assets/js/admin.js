/**
 * WAC Chat Funnels Admin Interface
 * Editor YAML con preview en vivo - Versión Corregida
 */

(() => {
  'use strict';

  // Utils
  const escapeHTML = (str) =>
    String(str).replace(/[&<>"']/g, (s) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[s]));
  
  const debounce = (fn, wait = 300) => {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(null, args), wait);
    };
  };

  class WACAdminEditor {
    constructor() {
      this.yamlEditor = null;
      this.previewContainer = null;
      this.hiddenConfig = null;
      this.debounceTimer = null;
      
      this.init();
    }

    init() {
      // Esperar a que el DOM esté listo
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => this.initializeEditor());
      } else {
        this.initializeEditor();
      }
    }

    initializeEditor() {
      try {
        console.log('🐛 WAC Chat Funnels: Initializing editor...');
        
        this.initYAMLEditor();
        this.initDebugButtons();
        
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
                <p><strong>Message:</strong> ${escapeHTML(event.message)}</p>
                <p><strong>File:</strong> ${escapeHTML(event.filename)}</p>
                <p><strong>Line:</strong> ${event.lineno}:${event.colno}</p>
                <p><strong>Error:</strong> ${escapeHTML(event.error ? event.error.toString() : 'No error object')}</p>
                <p style="margin-top: 10px; font-size: 12px;">Check browser console (F12) for full stack trace.</p>
              </div>
            `;
          }
        });
        
        console.log('✅ WAC Chat Funnels: Editor initialized successfully');
      } catch (error) {
        console.error('❌ WAC Chat Funnels: Failed to initialize editor:', error);
      }
    }

    initYAMLEditor() {
      const editorElement = document.getElementById('wac-yaml-editor');
      if (!editorElement) return;

      // Obtener el valor del textarea oculto de forma segura
      this.hiddenConfig = document.getElementById('wac-funnel-config');
      let configValue = (this.hiddenConfig && typeof this.hiddenConfig.value === 'string') ? this.hiddenConfig.value : '';
      if (configValue.trim() === 'undefined' || configValue.trim() === 'null') configValue = '';

      // Simple textarea editor for now
      // In production, you'd integrate Monaco Editor
      editorElement.innerHTML = `
        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
          <button id="debug-btn" type="button" class="button">🐛 Debug Info</button>
          <button id="clear-editor" type="button" class="button">🗑️ Limpiar</button>
          <button id="load-example" type="button" class="button">📝 Cargar Ejemplo</button>
        </div>
        <textarea id="yaml-content" style="width: 100%; height: calc(100% - 50px); border: none; outline: none; font-family: 'Courier New', monospace; font-size: 13px; padding: 10px; resize: none;">${escapeHTML(configValue)}</textarea>
      `;

      this.yamlEditor = document.getElementById('yaml-content');
      this.previewContainer = document.getElementById('wac-chat-preview');
      
      // Sincronizar con el textarea oculto
      if (this.yamlEditor && this.hiddenConfig) {
        this.yamlEditor.addEventListener('input', () => {
          this.hiddenConfig.value = this.yamlEditor.value;
          this.debouncedPreview();
        });
      }
      
      this.initDebugButtons();
      
      // Primera pintura de preview
      this.updatePreview();
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
          this.clearEditor();
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

    debouncedPreview = debounce(() => this.updatePreview(), 350);

    async updatePreview() {
      const yaml = this.yamlEditor ? this.yamlEditor.value : '';
      if (!yaml.trim()) {
        return this.renderInfo('Pega un YAML para ver la vista previa.');
      }
      
      const result = await this.validateYAML(yaml);
      if (!result.valid) return this.renderError(result);
      this.renderSuccess(result);
    }

    async validateYAML(yaml) {
      const url = (window.wacChatAdmin && wacChatAdmin.rest_url) 
        ? wacChatAdmin.rest_url + 'validate-yaml'
        : '/wp-json/wac-chat/v1/validate-yaml';

      const headers = { 'Content-Type': 'application/json' };
      if (window.wacChatAdmin && wacChatAdmin.nonce) {
        headers['X-WP-Nonce'] = wacChatAdmin.nonce;
      }

      let raw;
      try {
        const resp = await fetch(url, { 
          method: 'POST', 
          headers, 
          body: JSON.stringify({ yaml }) 
        });
        raw = await resp.json();
      } catch (e) {
        return { 
          valid: false, 
          message: 'No se pudo contactar el validador.', 
          errors: [e.message] 
        };
      }

      // Normalización robusta de errores (para evitar .join sobre undefined)
      let errs = [];
      if (Array.isArray(raw?.errors)) errs = raw.errors;
      else if (Array.isArray(raw?.data)) errs = raw.data;
      else if (Array.isArray(raw?.data?.errors)) errs = raw.data.errors;
      else if (raw?.errors && typeof raw.errors === 'object') errs = Object.values(raw.errors).flat();
      else if (!raw.valid && typeof raw.message === 'string') errs = [raw.message];

      return {
        valid: !!raw.valid,
        message: raw.message || '',
        errors: errs,
        config: raw.config || null,
        preview: raw.preview || null,
      };
    }

    renderInfo(msg) {
      if (!this.previewContainer) return;
      this.previewContainer.innerHTML =
        `<div style="padding:12px;background:#f6f7f7;border-radius:6px;color:#555;">${escapeHTML(msg)}</div>`;
    }

    renderError(result) {
      // Manejo robusto de errores para evitar .join sobre undefined
      const errorList = Array.isArray(result.errors)
        ? result.errors
        : (result?.data && Array.isArray(result.data))
            ? result.data
            : (result?.data && Array.isArray(result.data?.errors))
                ? result.data.errors
                : (result?.errors && typeof result.errors === 'object')
                    ? Object.values(result.errors).flat()
                    : (result?.errors ? [String(result.errors)] : []);

      const errorHTML = errorList.length
        ? errorList.map(escapeHTML).join('<br>')
        : escapeHTML(result.message || 'Error desconocido');

      if (!this.previewContainer) return;
      this.previewContainer.innerHTML = `
        <div style="padding:16px;color:#d63638;background:#fcf0f1;border:1px solid #f5c2c7;border-radius:6px;">
          <strong>Error en YAML:</strong><br>${errorHTML}
        </div>`;
    }

    renderSuccess(result) {
      if (!this.previewContainer) return;
      // Si el servidor devuelve HTML de preview, úsalo
      if (result.preview && typeof result.preview === 'string') {
        this.previewContainer.innerHTML = result.preview;
        return;
      }
      // Si no, muestra el objeto validado (útil para debug)
      const pretty = escapeHTML(JSON.stringify(result.config, null, 2));
      this.previewContainer.innerHTML = `
        <div style="padding:12px;background:#f6fff6;border:1px solid #b7ebc6;border-radius:6px;">
          <strong>YAML válido.</strong>
          <pre style="white-space:pre-wrap;margin:8px 0 0;font-size:12px;">${pretty}</pre>
        </div>`;
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

      if (this.yamlEditor) {
        this.yamlEditor.value = exampleYAML;
      }
      if (this.hiddenConfig) {
        this.hiddenConfig.value = exampleYAML;
      }
      this.updatePreview();
    }

    clearEditor() {
      if (this.yamlEditor) {
        this.yamlEditor.value = '';
      }
      if (this.hiddenConfig) {
        this.hiddenConfig.value = '';
      }
      this.updatePreview();
    }

    showDebugInfo() {
      const debugInfo = {
        yamlEditor: !!this.yamlEditor,
        previewContainer: !!this.previewContainer,
        hiddenConfig: !!this.hiddenConfig,
        wacChatAdmin: !!window.wacChatAdmin,
        restUrl: window.wacChatAdmin ? window.wacChatAdmin.rest_url : 'No disponible',
        nonce: window.wacChatAdmin ? window.wacChatAdmin.nonce : 'No disponible'
      };

      const basicInfo = `🐛 WAC Chat Funnels Debug Info:
- YAML Editor: ${debugInfo.yamlEditor}
- Preview Container: ${debugInfo.previewContainer}
- Hidden Config: ${debugInfo.hiddenConfig}
- WAC Admin Object: ${debugInfo.wacChatAdmin}
- REST URL: ${debugInfo.restUrl}
- Nonce: ${debugInfo.nonce}`;
      
      console.log('🐛 WAC Chat Funnels Debug Info:', debugInfo);
      
      alert(basicInfo + '\n\nFull details logged to console (F12).');
    }
  }

  // Inicializar cuando el DOM esté listo
  document.addEventListener('DOMContentLoaded', () => {
    try {
      window.WACAdminEditor = new WACAdminEditor();
      console.log('✅ WACAdminEditor initialized');
    } catch (error) {
      console.error('❌ Failed to initialize WACAdminEditor:', error);
    }
  });

})();