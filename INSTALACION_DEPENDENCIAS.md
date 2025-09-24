# Instalación de Dependencias - WAC Chat Funnels

## Requisitos Previos

- PHP 8.1 o superior
- WordPress 6.0 o superior
- Composer (para gestionar dependencias)

## Instalación de Symfony YAML

### Opción 1: Con Composer (Recomendado)

1. **Instalar Composer** (si no lo tienes):
   ```bash
   # Descargar e instalar Composer
   curl -sS https://getcomposer.org/installer | php
   sudo mv composer.phar /usr/local/bin/composer
   ```

2. **Instalar dependencias**:
   ```bash
   cd /ruta/a/tu/plugin/wac-chat-funnels/
   composer install --no-dev --optimize-autoloader
   ```

3. **Verificar instalación**:
   ```bash
   composer show symfony/yaml
   ```

### Opción 2: Manual (Sin Composer)

Si no puedes usar Composer, el plugin funcionará con el parser YAML simple como fallback, pero con limitaciones.

## Verificación de Instalación

1. **Activar el plugin** en WordPress
2. **Ir a Chat Funnels** en el admin
3. **Crear un nuevo funnel**
4. **Hacer clic en "Load Example"**
5. **Verificar que no hay errores** en la preview

## Estructura de Archivos Después de la Instalación

```
wac-chat-funnels/
├── composer.json
├── composer.lock
├── vendor/
│   ├── autoload.php
│   └── symfony/
│       └── yaml/
├── includes/
│   ├── class-yaml-processor.php
│   └── ... (otras clases)
└── ... (otros archivos del plugin)
```

## Solución de Problemas

### Error: "Class Symfony\Component\Yaml\Yaml not found"

**Causa**: Symfony YAML no está instalado o no se puede cargar.

**Solución**:
1. Verificar que `vendor/autoload.php` existe
2. Ejecutar `composer install` nuevamente
3. Verificar permisos de archivos

### Error: "Composer not found"

**Causa**: Composer no está instalado en el servidor.

**Solución**:
1. Instalar Composer siguiendo las instrucciones oficiales
2. O usar el parser simple como fallback (limitado)

### Error: "Memory limit exceeded"

**Causa**: Límite de memoria PHP insuficiente.

**Solución**:
1. Aumentar `memory_limit` en `php.ini`
2. O usar `composer install --no-dev` para optimizar

## Alternativas sin Composer

Si no puedes usar Composer, el plugin incluye un parser YAML simple que funciona para casos básicos, pero:

- ❌ No soporta todas las características YAML
- ❌ Menos robusto para YAML complejo
- ❌ No valida tipos de datos avanzados
- ✅ Funciona para funnels simples
- ✅ No requiere dependencias externas

## Comandos Útiles

```bash
# Instalar dependencias
composer install

# Instalar solo dependencias de producción
composer install --no-dev --optimize-autoloader

# Actualizar dependencias
composer update

# Verificar dependencias
composer check

# Limpiar cache
composer clear-cache
```

## Soporte

Si tienes problemas con la instalación:

1. Revisar logs de WordPress (`/wp-content/debug.log`)
2. Verificar permisos de archivos
3. Consultar documentación de Composer
4. Contactar soporte técnico
