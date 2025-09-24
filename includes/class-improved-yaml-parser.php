<?php
/**
 * Improved YAML Parser for WAC Chat Funnels
 * 
 * Parser YAML mejorado que maneja correctamente las estructuras anidadas
 * Específicamente diseñado para la estructura de funnels con actions
 *
 * @package WAC_Chat_Funnels
 * @since 1.0.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class WAC_Chat_Improved_YAML_Parser {
    
    /**
     * Parsear YAML a array PHP
     *
     * @param string $yaml_content Contenido YAML
     * @return array|WP_Error Array parseado o error
     */
    public static function parse($yaml_content) {
        if (empty($yaml_content)) {
            return new WP_Error('empty_yaml', __('Contenido YAML vacío', 'wac-chat-funnels'));
        }
        
        error_log('WAC Chat Funnels - Improved YAML Parser: parsing YAML');
        error_log('WAC Chat Funnels - YAML length: ' . strlen($yaml_content));
        
        try {
            $result = self::parse_yaml_content($yaml_content);
            error_log('WAC Chat Funnels - Improved YAML Parser result: ' . print_r($result, true));
            return $result;
        } catch (Exception $e) {
            error_log('WAC Chat Funnels - Improved YAML Parser error: ' . $e->getMessage());
            return new WP_Error('parse_error', __('Error al parsear YAML: ', 'wac-chat-funnels') . $e->getMessage());
        }
    }
    
    /**
     * Parsear contenido YAML con manejo mejorado de estructuras anidadas
     *
     * @param string $yaml_content
     * @return array
     */
    private static function parse_yaml_content($yaml_content) {
        $lines = explode("\n", $yaml_content);
        $result = array();
        $stack = array(); // Stack para manejar la estructura anidada
        $in_multiline = false;
        $multiline_content = '';
        $multiline_path = array();
        $multiline_indent = 0;
        
        foreach ($lines as $line_num => $line) {
            $original_line = $line;
            $trimmed = trim($line);
            
            // Saltar líneas vacías y comentarios
            if (empty($trimmed) || strpos($trimmed, '#') === 0) {
                continue;
            }
            
            // Manejar texto multilínea
            if ($in_multiline) {
                $current_indent = strlen($original_line) - strlen(ltrim($original_line));
                if ($current_indent <= $multiline_indent && strpos($trimmed, ':') !== false) {
                    // Fin del texto multilínea
                    $in_multiline = false;
                    self::set_nested_value($result, $multiline_path, trim($multiline_content));
                    $multiline_content = '';
                } else {
                    // Agregar línea al contenido multilínea
                    $multiline_content .= $original_line . "\n";
                    continue;
                }
            }
            
            $indent = strlen($original_line) - strlen(ltrim($original_line));
            $level = floor($indent / 2);
            
            // Ajustar el stack basado en el nivel de indentación
            while (count($stack) > $level) {
                array_pop($stack);
            }
            
            if (strpos($trimmed, ':') !== false) {
                list($key, $value) = explode(':', $trimmed, 2);
                $key = trim($key);
                $value = trim($value);
                
                if (strpos($value, '|') !== false) {
                    // Inicio de texto multilínea
                    $in_multiline = true;
                    $multiline_content = '';
                    $multiline_indent = $indent;
                    $multiline_path = array_merge($stack, array($key));
                    $stack[] = $key;
                    self::ensure_nested_array($result, $stack);
                } else if (empty($value)) {
                    // Nuevo nivel
                    $stack[] = $key;
                    self::ensure_nested_array($result, $stack);
                } else {
                    // Valor simple
                    $stack[] = $key;
                    self::set_nested_value($result, $stack, trim($value, '"\''));
                    array_pop($stack);
                }
            } else if (strpos($trimmed, '-') === 0) {
                // Elemento de lista - manejo mejorado para actions
                $option_line = ltrim($trimmed, '- ');
                
                // Crear nuevo elemento de lista
                $new_item = array();
                
                if (strpos($option_line, ':') !== false) {
                    list($opt_key, $opt_value) = explode(':', $option_line, 2);
                    $new_item[trim($opt_key)] = trim($opt_value, '"\'');
                } else {
                    $new_item['label'] = trim($option_line, '"\'');
                }
                
                // Obtener el array actual y agregar el nuevo elemento
                $target = &$result;
                foreach ($stack as $path_key) {
                    $target = &$target[$path_key];
                }
                
                if (!is_array($target)) {
                    $target = array();
                }
                
                $target[] = $new_item;
                
                // Agregar el índice del nuevo elemento al stack para propiedades anidadas
                $stack[] = count($target) - 1;
                
            } else {
                // Línea sin ':' ni '-' - probablemente una propiedad de un elemento de lista
                if (count($stack) > 0 && strpos($trimmed, ':') !== false) {
                    list($key, $value) = explode(':', $trimmed, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // Establecer en el elemento actual del stack
                    $target = &$result;
                    foreach ($stack as $path_key) {
                        $target = &$target[$path_key];
                    }
                    
                    $target[trim($key)] = trim($value, '"\'');
                }
            }
        }
        
        // Procesar último texto multilínea si existe
        if ($in_multiline && !empty($multiline_content)) {
            self::set_nested_value($result, $multiline_path, trim($multiline_content));
        }
        
        return $result;
    }
    
    /**
     * Asegurar que existe un array anidado en el path dado
     *
     * @param array $array Array principal
     * @param array $path Path de claves
     */
    private static function ensure_nested_array(&$array, $path) {
        $target = &$array;
        foreach ($path as $key) {
            if (!isset($target[$key]) || !is_array($target[$key])) {
                $target[$key] = array();
            }
            $target = &$target[$key];
        }
    }
    
    /**
     * Establecer valor en un path anidado
     *
     * @param array $array Array principal
     * @param array $path Path de claves
     * @param mixed $value Valor a establecer
     */
    private static function set_nested_value(&$array, $path, $value) {
        $target = &$array;
        foreach ($path as $key) {
            if (!isset($target[$key]) || !is_array($target[$key])) {
                $target[$key] = array();
            }
            $target = &$target[$key];
        }
        $target = $value;
    }
}
