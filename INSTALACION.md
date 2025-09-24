# Instalación y Configuración de WAC Chat Funnels

## Requisitos del Sistema

- **WordPress**: 6.0 o superior
- **PHP**: 8.1 o superior
- **MySQL**: 5.7 o superior
- **Navegador**: Chrome, Firefox, Safari, Edge (versiones modernas)

## Instalación

### 1. Subir el Plugin

1. Comprimir la carpeta `wac-chat-funnels` en un archivo ZIP
2. En WordPress, ir a **Plugins > Añadir nuevo > Subir plugin**
3. Seleccionar el archivo ZIP y hacer clic en **Instalar ahora**
4. Activar el plugin

### 2. Configuración Inicial

1. Ve a **Chat Funnels > Configuración**
2. Configura tus integraciones:
   - **Google Analytics 4 ID** (opcional)
   - **Meta Pixel ID** (opcional)

### 3. Crear tu Primer Funnel

1. Ve a **Chat Funnels > Añadir Nuevo**
2. Dale un título a tu funnel
3. En la pestaña **Configuración del Funnel**, pega este YAML de ejemplo:

```yaml
funnel:
  id: "mi_primer_funnel"
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
```

4. En la pestaña **Configuración General**:
   - ✅ Marcar **Activo**
   - Establecer **Prioridad** (1-100)
   - Configurar el **Teaser**

5. En la pestaña **Reglas de Targeting** (opcional):
   - Agregar reglas para controlar cuándo mostrar el funnel

6. En la pestaña **Integraciones**:
   - Configurar email para recibir leads
   - Configurar webhook (opcional)

7. **Guardar** el funnel

### 4. Probar el Funnel

1. Ve al frontend de tu sitio web
2. Espera 3 segundos para que aparezca la burbuja del teaser
3. Haz clic en la burbuja para abrir el chat
4. Prueba el flujo completo del funnel

## Configuración Avanzada

### Reglas de Targeting

Puedes configurar reglas para mostrar el funnel solo en ciertas condiciones:

- **URL contiene**: `/contacto`, `/servicios`
- **Tipo de contenido**: `page`, `post`
- **UTM Source**: `facebook`, `google`
- **Dispositivo**: `mobile`, `desktop`
- **Hora del día**: `9-17` (horario laboral)

### Integraciones

#### WhatsApp
```yaml
action: whatsapp
phone: "+573154543344"
prefill: "Hola, vengo de tu sitio web"
```

#### Email
Configura en **Integraciones**:
- Email destino
- Asunto personalizado
- Plantilla HTML

#### Webhooks
Para integrar con Zapier, Make, o tu CRM:
- URL del webhook
- Secret para firma HMAC (opcional)

### Personalización Visual

El widget usa CSS variables que puedes personalizar:

```css
:root {
  --wac-primary: #511013;
  --wac-secondary: #f7f7f7;
  --wac-text: #1a1a1a;
  --wac-bg: #ffffff;
}
```

## Uso con Shortcodes

Puedes insertar funnels específicos en páginas:

```php
[chat_funnel id="123" title="Mi Chat Personalizado" position="bottom-left"]
```

## Uso con Gutenberg

1. Añadir bloque **Chat Funnel**
2. Seleccionar el funnel
3. Configurar opciones

## Analytics y Reportes

Ve a **Chat Funnels > Analytics** para ver:
- Leads capturados
- Eventos por tipo
- Tasa de conversión
- Rendimiento por funnel

## Exportación de Leads

Ve a **Chat Funnels > Leads** para:
- Ver todos los leads capturados
- Exportar a CSV
- Filtrar por funnel

## Solución de Problemas

### El widget no aparece
1. Verificar que el funnel esté **Activo**
2. Revisar las **Reglas de Targeting**
3. Comprobar que el funnel esté publicado

### Errores en el YAML
1. Usar el **validador** en el editor
2. Verificar la **sintaxis** (espacios, indentación)
3. Revisar los **tipos de nodo** válidos

### No se reciben emails
1. Verificar configuración de **Integraciones**
2. Comprobar que el servidor pueda enviar emails
3. Revisar la carpeta de spam

### Problemas de rendimiento
1. El widget se carga solo cuando es necesario
2. Bundle optimizado < 45KB gzip
3. Compatible con caché de WordPress

## Soporte

Para soporte técnico:
- **Email**: soporte@wacosta.com
- **Documentación**: https://wacosta.com/docs
- **GitHub**: https://github.com/wacosta/wac-chat-funnels

## Changelog

### v1.0.0
- Widget de chat con Web Components
- Editor YAML con preview en vivo
- Sistema de reglas de targeting
- Integración con WhatsApp
- Analytics y reportes
- Webhooks y email
- Exportación CSV
- Soporte para shortcodes y Gutenberg
