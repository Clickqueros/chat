/**
 * WAC Chat Funnels Admin Interface (patched)
 * - Usa #wac-yaml-editor como editor visible (contenteditable)
 * - Sincroniza con #wac-funnel-config (hidden)
 * - Llama /wac-chat/v1/validate-yaml con wacChatAdmin.apiUrl + apiNonce
 */

(() => {
  'use strict';

  // ==== Utils ====
  const escapeHTML = (s) =>
    String(s ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const debounce = (fn, t=300) => { let id; return (...a)=>{ clearTimeout(id); id=setTimeout(()=>fn(...a),t); }; };

  // Selectores que realmente existen en tu metabox
  const getVisibleEditor = () => document.getElementById('wac-yaml-editor');     // <div> visible
  const getHiddenField   = () => document.getElementById('wac-funnel-config');    // <textarea hidden>

  // Intenta enganchar CodeMirror/Monaco si los agregas en el futuro
  const getCodeMirror = () => {
    const host = document.querySelector('.CodeMirror');
    return host && host.CodeMirror ? host.CodeMirror : null;
  };
  const getMonaco = () => {
    if (window.wacMonacoEditor && typeof wacMonacoEditor.getValue === 'function') return window.wacMonacoEditor;
    if (window.monaco?.editor) {
      const editors = monaco.editor.getEditors?.() || [];
      const models  = monaco.editor.getModels?.() || [];
      if (editors[0]) return editors[0];
      if (models[0]) {
        return {
          getValue: () => models[0].getValue(),
          setValue: (v) => models[0].setValue(v),
          onDidChangeModelContent: (fn) => models[0].onDidChangeContent?.(fn)
        };
      }
    }
    return null;
  };

  // Lectura robusta del YAML desde editor visible / CM / Monaco / hidden
  function readYAML() {
    const cm = getCodeMirror();
    if (cm) {
      const v = cm.getValue(); if (v?.trim()) return v;
    }
    const me = getMonaco();
    if (me && typeof me.getValue === 'function') {
      const v = me.getValue(); if (v?.trim()) return v;
    }
    const el = getVisibleEditor();
    if (el) {
      // El div será contenteditable; usamos textContent
      const v = el.textContent ?? '';
      if (v.trim()) return v;
    }
    const hidden = getHiddenField();
    if (hidden && typeof hidden.value === 'string' && hidden.value.trim()) return hidden.value;
    return '';
  }

  function writeYAML(v) {
    // Limpia "undefined" o "null" que pudieran venir del pasado
    if (v === 'undefined' || v === 'null') v = '';
    const cm = getCodeMirror();
    if (cm) { cm.setValue(v); }
    const me = getMonaco();
    if (me && typeof me.setValue === 'function') { me.setValue(v); }
    const el = getVisibleEditor();
    if (el) {
      // Mostramos como texto preformateado
      el.textContent = v;
    }
    const hidden = getHiddenField();
    if (hidden) hidden.value = v;
  }

  // ==== Clase principal ====
  class WACAdminEditor {
    constructor() {
      this.preview = document.getElementById('wac-chat-preview')
                 || document.getElementById('wac-preview')
                 || document.getElementById('wac-preview-pane')
                 || document.querySelector('.wac-preview');

      // Asegurar editor visible como contenteditable (si no hay CodeMirror/Monaco)
      const vis = getVisibleEditor();
      if (vis && vis.getAttribute('contenteditable') !== 'true') {
        vis.setAttribute('contenteditable', 'true');
        vis.style.whiteSpace = 'pre';       // que respete saltos
        vis.style.fontFamily = 'Menlo,Consolas,monospace';
        vis.style.padding = '8px';
      }

      // Inicial: carga desde hidden
      const hidden = getHiddenField();
      let initial = hidden && typeof hidden.value === 'string' ? hidden.value : '';
      if (initial === 'undefined' || initial === 'null') initial = '';
      writeYAML(initial);

      // Hooks de cambio
      const onUiChange = () => {
        const v = readYAML();
        if (hidden) hidden.value = v;
        this.debouncedPreview();
      };

      // Cambios en div contenteditable
      if (vis) {
        vis.addEventListener('input', onUiChange);
        vis.addEventListener('keyup', onUiChange);
        vis.addEventListener('paste', (e) => setTimeout(onUiChange, 0));
      }

      // Cambios en CM/Monaco
      const cm = getCodeMirror();
      if (cm) cm.on('change', onUiChange);
      const me = getMonaco();
      if (me?.onDidChangeModelContent) me.onDidChangeModelContent(onUiChange);

      // Botones debug opcionales (si existen en tu plantilla)
      const btnLoad  = document.getElementById('load-example-btn');
      const btnClear = document.getElementById('clear-editor-btn');
      const btnForce = document.getElementById('force-debug-btn');

      if (btnLoad)  btnLoad.addEventListener('click', () => this.loadExample());
      if (btnClear) btnClear.addEventListener('click', () => { writeYAML(''); this.updatePreview(); });
      if (btnForce) btnForce.addEventListener('click', () => this.forceDebug());

      // Pintar por primera vez
      this.updatePreview();
    }

    debouncedPreview = debounce(() => this.updatePreview(), 350);

    async updatePreview() {
      const yaml = readYAML();
      // espejo al hidden (para que guardes el post con lo que ves)
      const hidden = getHiddenField();
      if (hidden) hidden.value = yaml;

      if (!yaml.trim()) {
        return this.renderInfo('Pega o escribe tu YAML a la izquierda para ver la vista previa.');
      }
      const res = await this.validateYAML(yaml);
      if (!res.valid) return this.renderError(res);
      this.renderSuccess(res);
    }

    async validateYAML(yaml) {
      // Usa los nombres que SÍ localizas en tu plugin: apiUrl + apiNonce
      const base = (window.wacChatAdmin && (wacChatAdmin.apiUrl || wacChatAdmin.rest_url)) || '/wp-json/wac-chat/v1/';
      const url  = base.replace(/\/+$/,'/') + 'validate-yaml';
      const headers = { 'Content-Type': 'application/json' };
      if (window.wacChatAdmin && (wacChatAdmin.apiNonce || wacChatAdmin.nonce)) {
        headers['X-WP-Nonce'] = wacChatAdmin.apiNonce || wacChatAdmin.nonce;
      }

      try {
        const r = await fetch(url, { method: 'POST', headers, body: JSON.stringify({ yaml }) });
        const raw = await r.json();

        let errs = [];
        if (Array.isArray(raw?.errors)) errs = raw.errors;
        else if (Array.isArray(raw?.data?.errors)) errs = raw.data.errors;
        else if (raw?.errors && typeof raw.errors === 'object') errs = Object.values(raw.errors).flat();
        else if (!raw.valid && typeof raw.message === 'string') errs = [raw.message];

        return { valid: !!raw.valid, message: raw.message || '', errors: errs, config: raw.config || null, preview: raw.preview || null };
      } catch (e) {
        return { valid: false, message: 'No se pudo contactar el validador.', errors: [e.message] };
      }
    }

    renderInfo(msg) {
      if (!this.preview) return;
      this.preview.innerHTML = `<div style="padding:12px;background:#f6f7f7;border-radius:6px;color:#555;">${escapeHTML(msg)}</div>`;
    }

    renderError(result) {
      if (!this.preview) return;
      const list = Array.isArray(result.errors) ? result.errors : (result.errors ? [String(result.errors)] : []);
      const html = list.length ? list.map(escapeHTML).join('<br>') : escapeHTML(result.message || 'Error desconocido');
      this.preview.innerHTML = `
        <div style="padding:16px;color:#d63638;background:#fcf0f1;border:1px solid #f5c2c7;border-radius:6px;">
          <strong>Error en YAML:</strong><br>${html}
          <div style="margin-top:8px;">
            <button id="debug-error-btn" type="button" class="button">🔎 Detalles</button>
          </div>
        </div>`;
      const btn = document.getElementById('debug-error-btn');
      if (btn) btn.addEventListener('click', () => this.forceDebug());
    }

    renderSuccess(result) {
      if (!this.preview) return;
      if (result.preview && typeof result.preview === 'string') {
        this.preview.innerHTML = result.preview; return;
      }
      const pretty = escapeHTML(JSON.stringify(result.config, null, 2));
      this.preview.innerHTML = `
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
      this.updatePreview();
    }

    forceDebug() {
      const yaml = readYAML();
      const lines = (yaml || '').split('\n');
      const info = [
        `YAML Info:`,
        `• Longitud: ${yaml.length} caracteres`,
        `• Líneas: ${lines.length}`,
        `• ¿Contiene 'funnel:'? ${yaml.includes('funnel:') ? '✅ Sí' : '❌ No'}`,
        `• Posición de 'funnel:': ${yaml.indexOf('funnel:')}`,
        `• ¿Contiene 'Funnel:'? ${yaml.includes('Funnel:') ? '✅ Sí' : '❌ No'}`,
        ``,
        `Primeras líneas:`,
        `1: ${escapeHTML(lines[0] || '(vacío)')}`,
        `2: ${escapeHTML(lines[1] || '(vacío)')}`,
        `3: ${escapeHTML(lines[2] || '(vacío)')}`,
        ``,
        `YAML Completo:`,
        escapeHTML(yaml || '(vacío)'),
      ].join('\n');

      if (this.preview) {
        this.preview.innerHTML = `<pre style="font-size:12px; white-space:pre-wrap; background:#fff; border:1px solid #ddd; padding:10px; border-radius:6px;">${info}</pre>`;
      } else {
        alert(info);
      }
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    window.WACAdminEditor = new WACAdminEditor();
    console.log('✅ WACAdminEditor inicializado');
  });
})();