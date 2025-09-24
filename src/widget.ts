/**
 * WAC Chat Funnels Widget
 * Web Component TypeScript implementation
 */

interface FunnelConfig {
  funnelId: number;
  config: string;
  position: string;
  title: string;
}

interface FunnelNode {
  type: 'message' | 'question' | 'delay' | 'condition' | 'action';
  text?: string;
  style?: 'choice' | 'input';
  options?: Array<{
    label: string;
    next?: string;
    action?: string;
    url?: string;
    phone?: string;
    prefill?: string;
  }>;
  validation?: 'email' | 'phone' | 'name' | 'regex';
  store_as?: string;
  next?: string;
  action?: string;
  event_name?: string;
}

interface FunnelData {
  id: string;
  start: string;
  nodes: Record<string, FunnelNode>;
}

interface ChatState {
  currentStep: string;
  userData: Record<string, any>;
  sessionId: string;
  isOpen: boolean;
  isMinimized: boolean;
}

class WACChatWidget extends HTMLElement {
  private config: FunnelConfig;
  private funnelData: FunnelData;
  private state: ChatState;
  private shadowRoot: ShadowRoot;
  private chatContainer: HTMLElement;
  private messageContainer: HTMLElement;
  private inputContainer: HTMLElement;
  private teaserElement: HTMLElement;
  private isInitialized = false;

  constructor() {
    super();
    this.shadowRoot = this.attachShadow({ mode: 'open' });
    this.state = {
      currentStep: '',
      userData: {},
      sessionId: this.generateSessionId(),
      isOpen: false,
      isMinimized: false
    };
  }

  connectedCallback() {
    this.loadConfig();
    this.loadSavedState();
    this.render();
    this.initializeEventListeners();
    this.showTeaser();
  }

  private loadConfig() {
    const configAttr = this.getAttribute('data-config');
    if (!configAttr) return;

    try {
      this.config = JSON.parse(configAttr);
      this.funnelData = this.parseYAML(this.config.config);
      this.state.currentStep = this.funnelData.start;
    } catch (error) {
      console.error('Error loading chat config:', error);
    }
  }

  private parseYAML(yamlString: string): FunnelData {
    // Simple YAML parser for our specific use case
    // In production, you might want to use a proper YAML library
    const lines = yamlString.split('\n');
    const result: any = { nodes: {} };
    let currentPath: string[] = [];
    let currentNode = '';

    for (const line of lines) {
      const trimmed = line.trim();
      if (!trimmed || trimmed.startsWith('#')) continue;

      const indent = line.length - line.trimStart().length;
      const path = currentPath.slice(0, indent / 2);
      
      if (trimmed.includes(':')) {
        const [key, ...valueParts] = trimmed.split(':');
        const keyTrimmed = key.trim();
        const value = valueParts.join(':').trim();

        if (indent === 0) {
          if (keyTrimmed === 'funnel') {
            currentPath = ['funnel'];
          }
        } else if (path.length === 1 && path[0] === 'funnel') {
          if (keyTrimmed === 'id' || keyTrimmed === 'start') {
            result[keyTrimmed] = value.replace(/['"]/g, '');
          } else if (keyTrimmed === 'nodes') {
            currentPath = ['funnel', 'nodes'];
          }
        } else if (path.length === 2 && path[1] === 'nodes') {
          if (!value) {
            currentNode = keyTrimmed;
            result.nodes[currentNode] = {};
          } else {
            result.nodes[currentNode][keyTrimmed] = value.replace(/['"]/g, '');
          }
        } else if (path.length === 3) {
          const nodeKey = keyTrimmed;
          let nodeValue: any = value.replace(/['"]/g, '');

          if (nodeKey === 'options' && !value) {
            result.nodes[currentNode].options = [];
          } else if (path[2] === 'options') {
            if (!result.nodes[currentNode].options) {
              result.nodes[currentNode].options = [];
            }
            
            if (keyTrimmed === 'label') {
              result.nodes[currentNode].options.push({
                label: nodeValue
              });
            } else {
              const lastOption = result.nodes[currentNode].options[result.nodes[currentNode].options.length - 1];
              lastOption[keyTrimmed] = nodeValue;
            }
          } else {
            result.nodes[currentNode][nodeKey] = nodeValue;
          }
        }
      }
    }

    return result;
  }

  private generateSessionId(): string {
    return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
  }

  private loadSavedState() {
    try {
      const saved = localStorage.getItem(`wac_chat_${this.config.funnelId}`);
      if (saved) {
        const parsed = JSON.parse(saved);
        this.state.userData = parsed.userData || {};
        this.state.currentStep = parsed.currentStep || this.funnelData.start;
      }
    } catch (error) {
      console.error('Error loading saved state:', error);
    }
  }

  private saveState() {
    try {
      const stateToSave = {
        userData: this.state.userData,
        currentStep: this.state.currentStep,
        timestamp: Date.now()
      };
      localStorage.setItem(`wac_chat_${this.config.funnelId}`, JSON.stringify(stateToSave));
    } catch (error) {
      console.error('Error saving state:', error);
    }
  }

  private render() {
    this.shadowRoot.innerHTML = `
      <style>
        ${this.getStyles()}
      </style>
      <div class="wac-chat-widget" data-position="${this.config.position}">
        <div class="wac-teaser" id="teaser">
          <div class="wac-teaser-content">
            <span class="wac-teaser-icon">💬</span>
            <span class="wac-teaser-text">${this.config.title}</span>
          </div>
        </div>
        
        <div class="wac-chat-container" id="chat-container" style="display: none;">
          <div class="wac-chat-header">
            <div class="wac-chat-title">${this.config.title}</div>
            <div class="wac-chat-controls">
              <button class="wac-btn-minimize" id="minimize-btn">−</button>
              <button class="wac-btn-close" id="close-btn">×</button>
            </div>
          </div>
          
          <div class="wac-chat-messages" id="messages-container">
          </div>
          
          <div class="wac-chat-input" id="input-container">
          </div>
        </div>
      </div>
    `;

    this.chatContainer = this.shadowRoot.getElementById('chat-container') as HTMLElement;
    this.messageContainer = this.shadowRoot.getElementById('messages-container') as HTMLElement;
    this.inputContainer = this.shadowRoot.getElementById('input-container') as HTMLElement;
    this.teaserElement = this.shadowRoot.getElementById('teaser') as HTMLElement;
  }

  private getStyles(): string {
    return `
      :host {
        --wac-primary: #511013;
        --wac-secondary: #f7f7f7;
        --wac-text: #1a1a1a;
        --wac-bg: #ffffff;
        --wac-border: #ddd;
        --wac-shadow: 0 4px 12px rgba(0,0,0,0.15);
        --wac-radius: 8px;
        --wac-spacing: 12px;
      }

      .wac-chat-widget {
        position: fixed;
        z-index: 999999;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        font-size: 14px;
        line-height: 1.4;
      }

      .wac-chat-widget[data-position*="bottom"] {
        bottom: 20px;
      }

      .wac-chat-widget[data-position*="top"] {
        top: 20px;
      }

      .wac-chat-widget[data-position*="right"] {
        right: 20px;
      }

      .wac-chat-widget[data-position*="left"] {
        left: 20px;
      }

      .wac-teaser {
        width: 60px;
        height: 60px;
        background: var(--wac-primary);
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: var(--wac-shadow);
        transition: all 0.3s ease;
        animation: wac-pulse 2s infinite;
      }

      .wac-teaser:hover {
        transform: scale(1.1);
      }

      .wac-teaser-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        color: white;
        text-align: center;
      }

      .wac-teaser-icon {
        font-size: 24px;
        margin-bottom: 2px;
      }

      .wac-teaser-text {
        font-size: 10px;
        font-weight: 500;
      }

      .wac-chat-container {
        width: 350px;
        height: 500px;
        background: var(--wac-bg);
        border-radius: var(--wac-radius);
        box-shadow: var(--wac-shadow);
        display: flex;
        flex-direction: column;
        overflow: hidden;
      }

      .wac-chat-header {
        background: var(--wac-primary);
        color: white;
        padding: var(--wac-spacing);
        display: flex;
        justify-content: space-between;
        align-items: center;
      }

      .wac-chat-title {
        font-weight: 600;
        font-size: 16px;
      }

      .wac-chat-controls {
        display: flex;
        gap: 8px;
      }

      .wac-btn-minimize,
      .wac-btn-close {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        font-size: 18px;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        transition: background-color 0.2s;
      }

      .wac-btn-minimize:hover,
      .wac-btn-close:hover {
        background: rgba(255,255,255,0.2);
      }

      .wac-chat-messages {
        flex: 1;
        padding: var(--wac-spacing);
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: var(--wac-spacing);
      }

      .wac-message {
        max-width: 80%;
        padding: 10px 12px;
        border-radius: var(--wac-radius);
        word-wrap: break-word;
      }

      .wac-message-bot {
        background: var(--wac-secondary);
        color: var(--wac-text);
        align-self: flex-start;
        border-bottom-left-radius: 4px;
      }

      .wac-message-user {
        background: var(--wac-primary);
        color: white;
        align-self: flex-end;
        border-bottom-right-radius: 4px;
      }

      .wac-options {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-top: var(--wac-spacing);
      }

      .wac-option-btn {
        background: white;
        border: 2px solid var(--wac-primary);
        color: var(--wac-primary);
        padding: 10px 12px;
        border-radius: var(--wac-radius);
        cursor: pointer;
        transition: all 0.2s;
        text-align: left;
      }

      .wac-option-btn:hover {
        background: var(--wac-primary);
        color: white;
      }

      .wac-input-group {
        display: flex;
        gap: 8px;
        padding: var(--wac-spacing);
        border-top: 1px solid var(--wac-border);
      }

      .wac-input {
        flex: 1;
        padding: 10px 12px;
        border: 1px solid var(--wac-border);
        border-radius: var(--wac-radius);
        outline: none;
        font-size: 14px;
      }

      .wac-input:focus {
        border-color: var(--wac-primary);
      }

      .wac-send-btn {
        background: var(--wac-primary);
        color: white;
        border: none;
        padding: 10px 16px;
        border-radius: var(--wac-radius);
        cursor: pointer;
        font-weight: 500;
      }

      .wac-send-btn:hover {
        background: #3d0a0c;
      }

      .wac-send-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
      }

      @keyframes wac-pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
      }

      @media (max-width: 480px) {
        .wac-chat-container {
          width: calc(100vw - 40px);
          height: calc(100vh - 40px);
          position: fixed;
          top: 20px;
          left: 20px;
          right: 20px;
          bottom: 20px;
        }
      }
    `;
  }

  private initializeEventListeners() {
    // Teaser click
    this.teaserElement.addEventListener('click', () => {
      this.openChat();
    });

    // Close button
    this.shadowRoot.getElementById('close-btn')?.addEventListener('click', () => {
      this.closeChat();
    });

    // Minimize button
    this.shadowRoot.getElementById('minimize-btn')?.addEventListener('click', () => {
      this.minimizeChat();
    });

    // Keyboard events
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && this.state.isOpen) {
        this.closeChat();
      }
    });
  }

  private showTeaser() {
    setTimeout(() => {
      this.teaserElement.style.display = 'flex';
    }, 3000); // 3 second delay
  }

  private openChat() {
    this.state.isOpen = true;
    this.teaserElement.style.display = 'none';
    this.chatContainer.style.display = 'flex';
    
    this.trackEvent('chat_open', this.state.currentStep);
    
    if (!this.isInitialized) {
      this.processCurrentStep();
      this.isInitialized = true;
    }
  }

  private closeChat() {
    this.state.isOpen = false;
    this.chatContainer.style.display = 'none';
    this.teaserElement.style.display = 'flex';
    this.trackEvent('chat_close', this.state.currentStep);
  }

  private minimizeChat() {
    this.state.isMinimized = !this.state.isMinimized;
    const messagesContainer = this.shadowRoot.getElementById('messages-container');
    const inputContainer = this.shadowRoot.getElementById('input-container');
    
    if (this.state.isMinimized) {
      messagesContainer.style.display = 'none';
      inputContainer.style.display = 'none';
    } else {
      messagesContainer.style.display = 'flex';
      inputContainer.style.display = 'flex';
    }
  }

  private processCurrentStep() {
    const currentNode = this.funnelData.nodes[this.state.currentStep];
    if (!currentNode) return;

    switch (currentNode.type) {
      case 'message':
        this.showMessage(currentNode.text || '');
        this.handleMessageNext(currentNode);
        break;
        
      case 'question':
        this.showQuestion(currentNode);
        break;
        
      case 'action':
        this.handleAction(currentNode);
        break;
    }
  }

  private showMessage(text: string) {
    const processedText = this.processVariables(text);
    const messageEl = document.createElement('div');
    messageEl.className = 'wac-message wac-message-bot';
    messageEl.innerHTML = this.parseMarkdown(processedText);
    
    this.messageContainer.appendChild(messageEl);
    this.scrollToBottom();
  }

  private showQuestion(node: FunnelNode) {
    if (node.text) {
      this.showMessage(node.text);
    }

    if (node.style === 'choice' && node.options) {
      this.showChoiceOptions(node.options);
    } else if (node.style === 'input') {
      this.showInput(node);
    }
  }

  private showChoiceOptions(options: FunnelNode['options']) {
    const optionsContainer = document.createElement('div');
    optionsContainer.className = 'wac-options';
    
    options?.forEach(option => {
      const button = document.createElement('button');
      button.className = 'wac-option-btn';
      button.textContent = option.label;
      button.addEventListener('click', () => {
        this.handleOptionClick(option);
      });
      optionsContainer.appendChild(button);
    });
    
    this.messageContainer.appendChild(optionsContainer);
    this.scrollToBottom();
  }

  private showInput(node: FunnelNode) {
    const inputGroup = document.createElement('div');
    inputGroup.className = 'wac-input-group';
    
    const input = document.createElement('input');
    input.className = 'wac-input';
    input.type = this.getInputType(node.validation);
    input.placeholder = this.getInputPlaceholder(node.validation);
    
    const sendBtn = document.createElement('button');
    sendBtn.className = 'wac-send-btn';
    sendBtn.textContent = 'Enviar';
    sendBtn.disabled = true;
    
    input.addEventListener('input', () => {
      sendBtn.disabled = !this.validateInput(input.value, node.validation);
    });
    
    input.addEventListener('keypress', (e) => {
      if (e.key === 'Enter' && !sendBtn.disabled) {
        sendBtn.click();
      }
    });
    
    sendBtn.addEventListener('click', () => {
      if (this.validateInput(input.value, node.validation)) {
        this.handleInputSubmit(input.value, node);
        inputGroup.remove();
      }
    });
    
    inputGroup.appendChild(input);
    inputGroup.appendChild(sendBtn);
    this.inputContainer.appendChild(inputGroup);
    
    input.focus();
  }

  private handleOptionClick(option: any) {
    this.showUserMessage(option.label);
    
    if (option.next) {
      this.goToStep(option.next);
    } else if (option.action) {
      this.handleAction(option);
    }
  }

  private handleInputSubmit(value: string, node: FunnelNode) {
    this.showUserMessage(value);
    
    if (node.store_as) {
      this.state.userData[node.store_as] = value;
      this.saveState();
    }
    
    if (node.next) {
      this.goToStep(node.next);
    }
  }

  private handleMessageNext(node: FunnelNode) {
    if (node.next) {
      setTimeout(() => {
        this.goToStep(node.next!);
      }, 1000);
    } else if (node.action) {
      setTimeout(() => {
        this.handleAction(node);
      }, 1000);
    }
  }

  private handleAction(node: FunnelNode) {
    switch (node.action) {
      case 'whatsapp':
        this.handleWhatsAppAction(node);
        break;
      case 'redirect':
        this.handleRedirectAction(node);
        break;
      case 'event':
        this.handleEventAction(node);
        break;
    }
  }

  private handleWhatsAppAction(node: FunnelNode) {
    const phone = node.phone || '';
    const prefill = this.processVariables(node.prefill || '');
    const whatsappUrl = `https://api.whatsapp.com/send?phone=${phone}&text=${encodeURIComponent(prefill)}`;
    
    this.trackEvent('whatsapp_click', this.state.currentStep, { phone, prefill });
    window.open(whatsappUrl, '_blank');
  }

  private handleRedirectAction(node: FunnelNode) {
    if (node.url) {
      this.trackEvent('redirect_click', this.state.currentStep, { url: node.url });
      window.location.href = node.url;
    }
  }

  private handleEventAction(node: FunnelNode) {
    if (node.event_name) {
      this.trackEvent('custom_event', this.state.currentStep, { 
        event_name: node.event_name,
        user_data: this.state.userData 
      });
      
      // Handle lead capture
      if (node.event_name === 'lead_capturado') {
        this.trackEvent('lead_captured', this.state.currentStep, { 
          lead_data: this.state.userData 
        });
      }
    }
  }

  private goToStep(step: string) {
    this.state.currentStep = step;
    this.trackEvent('step_change', step);
    this.processCurrentStep();
  }

  private showUserMessage(text: string) {
    const messageEl = document.createElement('div');
    messageEl.className = 'wac-message wac-message-user';
    messageEl.textContent = text;
    
    this.messageContainer.appendChild(messageEl);
    this.scrollToBottom();
  }

  private processVariables(text: string): string {
    return text.replace(/\{\{(\w+)\}\}/g, (match, key) => {
      return this.state.userData[key] || match;
    });
  }

  private parseMarkdown(text: string): string {
    return text
      .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
      .replace(/\*(.*?)\*/g, '<em>$1</em>')
      .replace(/\n/g, '<br>');
  }

  private getInputType(validation?: string): string {
    switch (validation) {
      case 'email': return 'email';
      case 'phone': return 'tel';
      default: return 'text';
    }
  }

  private getInputPlaceholder(validation?: string): string {
    switch (validation) {
      case 'email': return 'tu@email.com';
      case 'phone': return '+57 300 123 4567';
      case 'name': return 'Tu nombre';
      default: return 'Escribe tu respuesta...';
    }
  }

  private validateInput(value: string, validation?: string): boolean {
    switch (validation) {
      case 'email':
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
      case 'phone':
        return /^[\+]?[0-9\s\-\(\)]{10,}$/.test(value);
      case 'name':
        return value.trim().length >= 2;
      default:
        return value.trim().length > 0;
    }
  }

  private scrollToBottom() {
    this.messageContainer.scrollTop = this.messageContainer.scrollHeight;
  }

  private trackEvent(eventType: string, step: string, metadata: any = {}) {
    // Send to WordPress
    if (window.wacChatConfig) {
      fetch(window.wacChatConfig.ajaxUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          action: 'wac_chat_track_event',
          nonce: window.wacChatConfig.nonce,
          funnel_id: this.config.funnelId.toString(),
          session_id: this.state.sessionId,
          event_type: eventType,
          step: step,
          metadata: JSON.stringify(metadata)
        })
      }).catch(console.error);
    }

    // Send to Google Analytics
    if (typeof gtag !== 'undefined') {
      gtag('event', eventType, {
        event_category: 'chat_funnel',
        event_label: step,
        custom_parameter_1: this.config.funnelId
      });
    }

    // Send to Meta Pixel
    if (typeof fbq !== 'undefined') {
      fbq('track', 'CustomizeProduct', {
        content_name: eventType,
        content_category: 'chat_funnel'
      });
    }
  }
}

// Register the custom element
customElements.define('wac-chat-widget', WACChatWidget);

// Auto-initialize widgets on page load
document.addEventListener('DOMContentLoaded', () => {
  const widgets = document.querySelectorAll('.wac-chat-funnel');
  widgets.forEach(widget => {
    const config = widget.getAttribute('data-config');
    if (config) {
      const chatWidget = document.createElement('wac-chat-widget');
      chatWidget.setAttribute('data-config', config);
      widget.appendChild(chatWidget);
    }
  });
});

// Export for module usage
export { WACChatWidget, FunnelConfig, FunnelNode, FunnelData, ChatState };
