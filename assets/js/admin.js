/**
 * WAC Chat Funnels Admin Interface
 * Editor YAML con preview en vivo - Versión Robusta
 */

(() => {
  'use strict';

  // === UTILIDADES ROBUSTAS PARA LEER/ESCRIBIR YAML ===
  function getYamlEl() {
    return document.querySelector('#yaml-content, #wac-funnel-yaml, [data-wac-yaml], textarea[name="wac_funnel_yaml"]');
  }
  
  function getHiddenEl() {
    return document.getElementById('wac-funnel-config') || document.querySelector('[name="wac_funnel_config"]');
  }
  
  function getCodeMirror() {
    const cmHost = document.querySelector('.CodeMirror');
    return cmHost && cmHost.CodeMirror ? cmHost.CodeMirror : null;
  }
  
  function getMonacoEditor() {
    if (window.wacMonacoEditor && typeof wacMonacoEditor.getValue === 'function') return window.wacMonacoEditor;
    if (window.monaco && monaco.editor) {
      const models = monaco.editor.getModels?.() || [];
      const editors = monaco.editor.getEditors?.() || [];
      // Prioriza instancia si existe, si no toma el primer modelo
      if (editors[0]) return editors[0];
      if (models[0]) return {
        getValue: () => models[0].getValue(),
        setValue: (v) => models[0].setValue(v),
        onDidChangeModelContent: (fn) => models[0].onDidChangeContent?.(fn)
      };
    }
    return null;
  }
  
  function readYAML() {
    // 1) CodeMirror
    const cm = getCodeMirror();
    if (cm) {
      const v = cm.getValue();
      if (v && v.trim()) return v;
    }
    // 2) Monaco
    const me = getMonacoEditor();
    if (me && typeof me.getValue === 'function') {
      const v = me.getValue();
      if (v && v.trim()) return v;
    }
    // 3) Textarea / contenteditable
    const el = getYamlEl();
    if (el) {
      let v = typeof el.value === 'string' ? el.value : '';
      if (!v || !v.trim()) {
        if (el.getAttribute('contenteditable') === 'true') v = el.textContent || '';
        else v = el.textContent && !el.value ? el.textContent : v;
      }
      if (v && v.trim()) return v;
    }
    // 4) Hidden
    const hidden = getHiddenEl();
    if (hidden && typeof hidden.value === 'string' && hidden.value.trim()) return hidden.value;

    return '';
  }
  
  function writeYAML(v) {
    // 1) CodeMirror
    const cm = getCodeMirror();
    if (cm) { cm.setValue(v); return; }
    // 2) Monaco
    const me = getMonacoEditor();
    if (me && typeof me.setValue === 'function') { me.setValue(v); return; }
    // 3) Textarea / contenteditable
    const el = getYamlEl();
    if (el) {
      if (typeof el.value === 'string') el.value = v;
      else if (el.getAttribute('contenteditable') === 'true') el.textContent = v;
      else el.textContent = v;
    }
    // 4) Hidden espejo
    const hidden = getHiddenEl();
    if (hidden && typeof hidden.value === 'string') hidden.value = v;
  }
  
  function hookExternalEditors(onChange) {
    const cm = getCodeMirror();
    if (cm) cm.on('change', onChange);
    const me = getMonacoEditor();
    if (me && typeof me.onDidChangeModelContent === 'function') me.onDidChangeModelContent(onChange);
  }

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

  // Clase principal del editor
  class WACAdminEditor {
    constructor() {
      this.previewContainer = document.getElementById('wac-chat-preview')
        || document.getElementById('wac-preview')
        || document.getElementById('wac-preview-pane')
        || document.querySelector('.wac-preview');

      this.btnLoad = document.getElementById('wac-load-example') || document.querySelector('[data-wac="load-example"]');
      this.btnDebug = document.getElementById('wac-test-debug') || document.querySelector('[data-wac="test-debug"]');

      // Valor inicial seguro (evita "undefined/null")
      const hidden = getHiddenEl();
      let initial = hidden && typeof hidden.value === 'string' ? hidden.value : '';
      if (initial.trim() === 'undefined' || initial.trim() === 'null') initial = '';
      if (initial) writeYAML(initial);

      // Listeners
      const yamlEl = getYamlEl();
      if (yamlEl) {
        const onUiChange = () => {
          const v = readYAML();
          if (hidden) hidden.value = v;
          this.debouncedPreview();
        };
        yamlEl.addEventListener('input', onUiChange);
        yamlEl.addEventListener('keyup', onUiChange);
      }
      hookExternalEditors(() => {
        const v = readYAML();
        if (hidden) hidden.value = v;
        this.debouncedPreview();
      });

      if (this.btnLoad) this.btnLoad.addEventListener('click', () => this.loadExample());
      if (this.btnDebug) this.btnDebug.addEventListener('click', () => this.testDebug());

      this.updatePreview();
    }

    debouncedPreview = debounce(() => this.updatePreview(), 350);

    async updatePreview() {
      const yaml = readYAML();
      // espejo al hidden por si guardas el post
      const hidden = getHiddenEl();
      if (hidden) hidden.value = yaml;

      if (!yaml.trim()) {
        return this.renderInfo('Pega un YAML para ver la vista previa.');
      }

      const res = await this.validateYAML(yaml);
      if (!res.valid) return this.renderError(res);
      this.renderSuccess(res);
    }

    async validateYAML(yaml) {
      const url = (window.wacChatAdmin && (wacChatAdmin.rest_url)) 
        ? wacChatAdmin.rest_url + 'validate-yaml'
        : '/wp-json/wac-chat/v1/validate-yaml';

      const headers = { 'Content-Type': 'application/json' };
      if (window.wacChatAdmin && wacChatAdmin.nonce) {
        headers['X-WP-Nonce'] = wacChatAdmin.nonce;
      }

      let raw;
      try {
        const r = await fetch(url, { method: 'POST', headers, body: JSON.stringify({ yaml }) });
        raw = await r.json();
      } catch (e) {
        return { valid: false, message: 'No se pudo contactar el validador.', errors: [e.message] };
      }

      let errs = [];
      if (Array.isArray(raw?.errors)) errs = raw.errors;
      else if (Array.isArray(raw?.data?.errors)) errs = raw.data.errors;
      else if (raw?.errors && typeof raw.errors === 'object') errs = Object.values(raw.errors).flat();
      else if (!raw.valid && typeof raw.message === 'string') errs = [raw.message];

      return { valid: !!raw.valid, message: raw.message || '', errors: errs, config: raw.config || null, preview: raw.preview || null };
    }

    renderInfo(msg) {
      if (!this.previewContainer) return;
      this.previewContainer.innerHTML = `<div style="padding:12px;background:#f6f7f7;border-radius:6px;color:#555;">${escapeHTML(String(msg))}</div>`;
    }

    renderError(result) {
      const list = Array.isArray(result.errors) ? result.errors : (result.errors ? [String(result.errors)] : []);
      const html = list.length ? list.map(escapeHTML).join('<br>') : escapeHTML(result.message || 'Error desconocido');
      if (!this.previewContainer) return;
      this.previewContainer.innerHTML = `
        <div style="padding:16px;color:#d63638;background:#fcf0f1;border:1px solid #f5c2c7;border-radius:6px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <strong>Error en YAML:</strong>
            <button id="debug-error-btn" type="button" class="button button-small" style="margin-left: 10px;">
              🔍 Debug Error
            </button>
          </div>
          <div>${html}</div>
        </div>`;
      
      // Agregar listener al botón de debug de error
      const debugErrorBtn = document.getElementById('debug-error-btn');
      if (debugErrorBtn) {
        debugErrorBtn.addEventListener('click', () => {
          this.debugYAMLError(result);
        });
      }
    }

    renderSuccess(result) {
      if (!this.previewContainer) return;
      if (result.preview && typeof result.preview === 'string') {
        this.previewContainer.innerHTML = result.preview;
        return;
      }
      const pretty = escapeHTML(JSON.stringify(result.config, null, 2));
      this.previewContainer.innerHTML = `
        <div style="padding:12px;background:#f6fff6;border:1px solid #b7ebc6;border-radius:6px;">
          <strong>YAML válido.</strong>
          <pre style="white-space:pre-wrap;margin:8px 0 0;font-size:12px;">${pretty}</pre>
        </div>`;
    }

    loadExample() {
      const example = `funnel:
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
      
      writeYAML(example);
      const hidden = getHiddenEl();
      if (hidden) hidden.value = example;
      this.updatePreview();
    }

    testDebug() {
      const yaml = readYAML();
      console.log('🚨 Force Debug YAML');
      console.log('len:', yaml.length, 'first 60:', yaml.slice(0, 60).replace(/\n/g,'⏎'));
      alert(
`🚨 Force Debug YAML
Longitud: ${yaml.length}
Primeras líneas:
${yaml.split('\n').slice(0,5).map((l,i)=>`${i+1}: ${l||'(vacío)'}`).join('\n')}`
      );
    }

    debugYAMLError(errorResult) {
      const yaml = readYAML();
      
      // Información detallada del error
      const debugInfo = {
        timestamp: new Date().toISOString(),
        yamlLength: yaml.length,
        yamlFirstChars: yaml.substring(0, 100),
        yamlLastChars: yaml.substring(Math.max(0, yaml.length - 100)),
        errorResult: errorResult,
        hasFunnelKey: yaml.includes('funnel:'),
        funnelKeyPosition: yaml.indexOf('funnel:'),
        lines: yaml.split('\n').length,
        firstLine: yaml.split('\n')[0],
        secondLine: yaml.split('\n')[1],
        thirdLine: yaml.split('\n')[2]
      };

      // Mostrar información en el preview
      if (this.previewContainer) {
        this.previewContainer.innerHTML = `
          <div style="padding:16px;background:#f0f8ff;border:1px solid #b3d9ff;border-radius:6px;">
            <h3 style="margin: 0 0 15px 0; color: #0066cc;">🔍 Debug YAML Error</h3>
            
            <div style="margin-bottom: 10px;">
              <strong>YAML Info:</strong><br>
              • Longitud: ${debugInfo.yamlLength} caracteres<br>
              • Líneas: ${debugInfo.lines}<br>
              • ¿Contiene 'funnel:'? ${debugInfo.hasFunnelKey ? '✅ Sí' : '❌ No'}<br>
              ${debugInfo.funnelKeyPosition >= 0 ? `• Posición de 'funnel:': ${debugInfo.funnelKeyPosition}` : ''}
            </div>

            <div style="margin-bottom: 10px;">
              <strong>Primeras líneas:</strong><br>
              <pre style="background: #f5f5f5; padding: 8px; border-radius: 4px; font-size: 12px; margin: 5px 0;">
1: ${escapeHTML(debugInfo.firstLine)}
2: ${escapeHTML(debugInfo.secondLine)}
3: ${escapeHTML(debugInfo.thirdLine)}
              </pre>
            </div>

            <div style="margin-bottom: 10px;">
              <strong>Error Details:</strong><br>
              <pre style="background: #f5f5f5; padding: 8px; border-radius: 4px; font-size: 12px; margin: 5px 0;">
${escapeHTML(JSON.stringify(errorResult, null, 2))}
              </pre>
            </div>

            <div style="margin-bottom: 10px;">
              <strong>YAML Completo:</strong><br>
              <textarea readonly style="width: 100%; height: 200px; font-family: monospace; font-size: 12px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">${escapeHTML(yaml)}</textarea>
            </div>

            <div style="margin-top: 15px;">
              <button id="close-debug-btn" type="button" class="button">Cerrar Debug</button>
              <button id="copy-yaml-btn" type="button" class="button">Copiar YAML</button>
            </div>
          </div>
        `;

        // Agregar listeners a los botones
        const closeBtn = document.getElementById('close-debug-btn');
        if (closeBtn) {
          closeBtn.addEventListener('click', () => {
            this.updatePreview();
          });
        }

        const copyBtn = document.getElementById('copy-yaml-btn');
        if (copyBtn) {
          copyBtn.addEventListener('click', () => {
            navigator.clipboard.writeText(yaml).then(() => {
              alert('YAML copiado al portapapeles');
            }).catch(() => {
              alert('Error al copiar. Revisa la consola para el YAML completo.');
            });
          });
        }
      }

      // Log completo en consola
      console.group('🔍 WAC Chat Funnels - YAML Error Debug');
      console.log('YAML Content:', yaml);
      console.log('Error Result:', errorResult);
      console.log('Debug Info:', debugInfo);
      console.groupEnd();
    }
  }

  // Inicializar cuando el DOM esté listo
  document.addEventListener('DOMContentLoaded', () => {
    try {
      window.WACAdminEditor = new WACAdminEditor();
      window.wacChatAdmin = window.WACAdminEditor; // Alias para compatibilidad
      console.log('✅ WACAdminEditor initialized with robust YAML handling');
    } catch (error) {
      console.error('❌ Failed to initialize WACAdminEditor:', error);
    }
  });

})();