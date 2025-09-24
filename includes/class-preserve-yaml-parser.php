<?php
/**
 * Preserve YAML Parser for WAC Chat Funnels
 * 
 * Parser YAML que preserva EXACTAMENTE la estructura original
 * Sin modificaciones, sin "correcciones", sin agregar campos
 *
 * @package WAC_Chat_Funnels
 * @since 1.0.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class WAC_Chat_Preserve_YAML_Parser {
    
    /**
     * Parsear YAML preservando estructura original
     *
     * @param string $yaml_content Contenido YAML
     * @return array|WP_Error Array parseado o error
     */
    public static function parse($yaml_content) {
        if (empty($yaml_content)) {
            return new WP_Error('empty_yaml', __('Contenido YAML vacío', 'wac-chat-funnels'));
        }
        
        error_log('WAC Chat Funnels - Preserve YAML Parser: parsing YAML');
        error_log('WAC Chat Funnels - YAML length: ' . strlen($yaml_content));
        
        try {
            $result = self::parse_yaml_preserve($yaml_content);
            error_log('WAC Chat Funnels - Preserve YAML Parser result: ' . print_r($result, true));
            return $result;
        } catch (Exception $e) {
            error_log('WAC Chat Funnels - Preserve YAML Parser error: ' . $e->getMessage());
            return new WP_Error('parse_error', __('Error al parsear YAML: ', 'wac-chat-funnels') . $e->getMessage());
        }
    }
    
    /**
     * Parsear YAML preservando estructura exacta
     *
     * @param string $yaml_content
     * @return array
     */
    private static function parse_yaml_preserve($yaml_content) {
        $lines = explode("\n", $yaml_content);
        $result = array();
        $context = array(); // Stack de contexto
        $in_multiline = false;
        $multiline_content = '';
        $multiline_key = '';
        
        foreach ($lines as $line_num => $line) {
            $original_line = $line;
            $trimmed = ltrim($line);
            $indent = strlen($original_line) - strlen($trimmed);
            
            // Saltar líneas vacías y comentarios
            if (empty($trimmed) || strpos($trimmed, '#') === 0) {
                continue;
            }
            
            // Manejar texto multilínea
            if ($in_multiline) {
                if ($indent <= strlen($original_line) - strlen(ltrim($original_line)) && strpos($trimmed, ':') !== false) {
                    // Fin del texto multilínea
                    $in_multiline = false;
                    self::set_value_by_context($result, $context, $multiline_key, trim($multiline_content));
                    $multiline_content = '';
                    $multiline_key = '';
                } else {
                    // Continuar texto multilínea
                    $multiline_content .= $original_line . "\n";
                    continue;
                }
            }
            
            // Determinar nivel de indentación
            $level = floor($indent / 2);
            
            // Ajustar contexto basado en indentación
            while (count($context) > $level) {
                array_pop($context);
            }
            
            if (strpos($trimmed, ':') !== false) {
                list($key, $value) = explode(':', $trimmed, 2);
                $key = trim($key);
                $value = trim($value);
                
                if (strpos($value, '|') !== false) {
                    // Inicio de texto multilínea
                    $in_multiline = true;
                    $multiline_content = '';
                    $multiline_key = $key;
                    $context[] = $key;
                    self::ensure_context_path($result, $context);
                } else if (empty($value)) {
                    // Nuevo nivel/objeto
                    $context[] = $key;
                    self::ensure_context_path($result, $context);
                } else {
                    // Valor simple
                    $context[] = $key;
                    self::set_value_by_context($result, $context, null, trim($value, '"\''));
                    array_pop($context);
                }
            } else if (strpos($trimmed, '-') === 0) {
                // Elemento de lista
                $list_content = ltrim($trimmed, '- ');
                
                // Agregar al array actual
                $target = &$result;
                foreach ($context as $path_key) {
                    if (!isset($target[$path_key])) {
                        $target[$path_key] = array();
                    }
                    $target = &$target[$path_key];
                }
                
                if (!is_array($target)) {
                    $target = array();
                }
                
                // Crear nuevo elemento
                $new_item = array();
                if (strpos($list_content, ':') !== false) {
                    list($item_key, $item_value) = explode(':', $list_content, 2);
                    $new_item[trim($item_key)] = trim($item_value, '"\'');
                } else {
                    $new_item['label'] = trim($list_content, '"\'');
                }
                
                $target[] = $new_item;
                
                // Agregar índice al contexto para propiedades anidadas
                $context[] = count($target) - 1;
            }
        }
        
        // Procesar último texto multilínea
        if ($in_multiline && !empty($multiline_content)) {
            self::set_value_by_context($result, $context, $multiline_key, trim($multiline_content));
        }
        
        return $result;
    }
    
    /**
     * Asegurar que existe el path en el contexto
     *
     * @param array $array
     * @param array $context
     */
    private static function ensure_context_path(&$array, $context) {
        $target = &$array;
        foreach ($context as $key) {
            if (!isset($target[$key])) {
                $target[$key] = array();
            }
            $target = &$target[$key];
        }
    }
    
    /**
     * Establecer valor en el contexto actual
     *
     * @param array $array
     * @param array $context
     * @param string $key
     * @param mixed $value
     */
    private static function set_value_by_context(&$array, $context, $key, $value) {
        $target = &$array;
        foreach ($context as $path_key) {
            if (!isset($target[$path_key])) {
                $target[$path_key] = array();
            }
            $target = &$target[$path_key];
        }
        
        if ($key !== null) {
            $target[$key] = $value;
        } else {
            // Para valores directos en el contexto
            $target = $value;
        }
    }
}
