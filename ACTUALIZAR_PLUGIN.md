# 🚀 Instrucciones para Actualizar el Plugin WAC Chat Funnels

## ✅ **El Plugin Ya Está Actualizado**

El repositorio GitHub ya tiene la versión más reciente con:
- ✅ Parser YAML mejorado que maneja estructuras anidadas
- ✅ Botones de debug completos
- ✅ Sincronización automática antes de guardar
- ✅ Builder de reglas funcional

## 📥 **Pasos para Actualizar en WordPress**

### **Opción 1: Actualización Manual (Recomendado)**

1. **Descargar el plugin actualizado:**
   ```bash
   # Si tienes acceso SSH:
   cd /ruta/a/tu/sitio/wp-content/plugins/
   rm -rf wac-chat-funnels
   git clone https://github.com/Clickqueros/chat.git wac-chat-funnels
   ```

2. **O descargar ZIP desde GitHub:**
   - Ve a: https://github.com/Clickqueros/chat
   - Haz clic en "Code" → "Download ZIP"
   - Extrae el ZIP en `/wp-content/plugins/wac-chat-funnels/`

### **Opción 2: Actualización vía WordPress Admin**

1. **Desactivar el plugin actual**
2. **Eliminar el plugin** (mantener datos)
3. **Subir la nueva versión** como ZIP
4. **Activar el plugin**

## 🔧 **Verificación Post-Actualización**

### **1. Verificar que el Parser Mejorado se Cargó**
- Ve al editor de funnels
- Haz clic en "🧪 Test Parser"
- Deberías ver en los logs del servidor:
  ```
  WAC Chat Funnels - Improved YAML Parser: parsing YAML
  ```

### **2. Probar la Secuencia Completa**
1. **Haz clic en "🗑️ Clear"** para limpiar
2. **Haz clic en "📝 Load Example"** para cargar ejemplo
3. **Haz clic en "🧪 Test Parser"** - debería mostrar "YAML válido"
4. **Guarda el funnel** (botón "Actualizar")
5. **Haz clic en "🔍 Verify Saved"** - debería mostrar estructura anidada

### **3. Verificar Estructura Anidada**
El YAML guardado debería verse así:
```yaml
- label: "Ver portafolio"
  action:
    type: redirect
    url: "/portafolio"
```

**NO así:**
```yaml
- label: "Ver portafolio"
  action: redirect
  url: "/portafolio"
```

## 🎯 **Resultado Esperado**

Después de la actualización:
- ✅ **Test Parser** funciona con estructuras anidadas
- ✅ **Load Example** carga YAML con estructura correcta
- ✅ **Guardar** preserva la estructura anidada
- ✅ **Verify Saved** muestra actions como objetos anidados
- ✅ **Preview** funciona sin errores

## 🆘 **Si Algo No Funciona**

1. **Revisa los logs** del servidor para ver errores
2. **Usa "🔍 Verify Saved"** para ver qué se guardó realmente
3. **Usa "💾 Debug Save"** para verificar sincronización
4. **Verifica** que el nonce se esté enviando correctamente

## 📞 **Soporte**

Si tienes problemas:
1. Comparte el resultado de "🔍 Verify Saved"
2. Comparte los logs del servidor
3. Verifica que tienes la versión correcta

---

**¡Una vez actualizado, el plugin debería funcionar perfectamente con estructuras YAML anidadas!** 🚀
