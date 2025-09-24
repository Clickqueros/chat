<?php
/**
 * YAML Processor Class
 * 
 * Maneja el procesamiento de YAML usando Symfony YAML
 * Convierte YAML a JSON y viceversa para almacenamiento
 *
 * @package WAC_Chat_Funnels
 * @since 1.0.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class WAC_Chat_YAML_Processor {
    
    /**
     * Verificar si Symfony YAML está disponible
     *
     * @return bool
     */
    public static function is_symfony_yaml_available() {
        return class_exists('Symfony\Component\Yaml\Yaml');
    }
    
    /**
     * Parsear YAML a array PHP
     *
     * @param string $yaml_content Contenido YAML
     * @return array|WP_Error Array parseado o error
     */
    public static function parse_yaml($yaml_content) {
        if (empty($yaml_content)) {
            return new WP_Error('empty_yaml', __('Contenido YAML vacío', 'wac-chat-funnels'));
        }
        
        // Usar Symfony YAML si está disponible
        if (self::is_symfony_yaml_available()) {
            try {
                $parsed = \Symfony\Component\Yaml\Yaml::parse(
                    $yaml_content, 
                    \Symfony\Component\Yaml\Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE
                );
                
                if ($parsed === null) {
                    return new WP_Error('invalid_yaml', __('YAML inválido', 'wac-chat-funnels'));
                }
                
                return $parsed;
                
            } catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
                return new WP_Error('yaml_parse_error', 
                    sprintf(__('Error al parsear YAML: %s', 'wac-chat-funnels'), $e->getMessage())
                );
            } catch (Exception $e) {
                return new WP_Error('yaml_error', 
                    sprintf(__('Error inesperado: %s', 'wac-chat-funnels'), $e->getMessage())
                );
            }
        }
        
        // Fallback: usar parser simple si Symfony no está disponible
        return self::fallback_yaml_parse($yaml_content);
    }
    
    /**
     * Convertir array PHP a YAML
     *
     * @param array $data Array PHP
     * @return string|WP_Error YAML string o error
     */
    public static function array_to_yaml($data) {
        if (!is_array($data)) {
            return new WP_Error('invalid_data', __('Datos no válidos para convertir a YAML', 'wac-chat-funnels'));
        }
        
        // Usar Symfony YAML si está disponible
        if (self::is_symfony_yaml_available()) {
            try {
                return \Symfony\Component\Yaml\Yaml::dump($data, 3, 2);
            } catch (Exception $e) {
                return new WP_Error('yaml_dump_error', 
                    sprintf(__('Error al convertir a YAML: %s', 'wac-chat-funnels'), $e->getMessage())
                );
            }
        }
        
        // Fallback: convertir a JSON si Symfony no está disponible
        return wp_json_encode($data, JSON_PRETTY_PRINT);
    }
    
    /**
     * Procesar YAML completo: parsear, validar, sanitizar y convertir a JSON
     *
     * @param string $yaml_content Contenido YAML
     * @return array|WP_Error Array con datos procesados o error
     */
    public static function process_yaml($yaml_content) {
        // Parsear YAML
        $parsed = self::parse_yaml($yaml_content);
        if (is_wp_error($parsed)) {
            return $parsed;
        }
        
        // Validar estructura
        $validation = self::validate_funnel_structure($parsed);
        if (!$validation['valid']) {
            return new WP_Error('validation_failed', $validation['message'], $validation['errors']);
        }
        
        // Sanitizar datos
        $sanitized = self::sanitize_funnel_data($parsed);
        
        // Convertir a JSON para almacenamiento
        $json_data = wp_json_encode($sanitized, JSON_UNESCAPED_UNICODE);
        if ($json_data === false) {
            return new WP_Error('json_error', __('Error al convertir a JSON', 'wac-chat-funnels'));
        }
        
        return array(
            'parsed' => $sanitized,
            'json' => $json_data,
            'validation' => $validation
        );
    }
    
    /**
     * Validar estructura del funnel
     *
     * @param array $data Datos parseados
     * @return array Resultado de validación
     */
    private static function validate_funnel_structure($data) {
        $errors = array();
        
        // Debug: Log de lo que se está validando
        error_log('WAC Chat Funnels - Validating structure: ' . print_r($data, true));
        
        // Verificar estructura básica
        if (!isset($data['funnel'])) {
            $errors[] = __('Falta la clave "funnel" en la raíz', 'wac-chat-funnels');
            error_log('WAC Chat Funnels - Missing funnel key. Parsed structure: ' . print_r($data, true));
            return array('valid' => false, 'message' => __('Estructura inválida', 'wac-chat-funnels'), 'errors' => $errors);
        }
        
        $funnel = $data['funnel'];
        
        // Verificar campos requeridos
        if (!isset($funnel['id']) || empty($funnel['id'])) {
            $errors[] = __('Falta el campo "id" en el funnel', 'wac-chat-funnels');
        }
        
        if (!isset($funnel['start']) || empty($funnel['start'])) {
            $errors[] = __('Falta el campo "start" en el funnel', 'wac-chat-funnels');
        }
        
        if (!isset($funnel['nodes']) || !is_array($funnel['nodes'])) {
            $errors[] = __('Falta el campo "nodes" en el funnel', 'wac-chat-funnels');
        } else {
            // Verificar que existe el nodo de inicio
            if (isset($funnel['start']) && !isset($funnel['nodes'][$funnel['start']])) {
                $errors[] = sprintf(__('El nodo de inicio "%s" no existe en la lista de nodos', 'wac-chat-funnels'), $funnel['start']);
            }
            
            // Validar nodos individuales
            foreach ($funnel['nodes'] as $node_id => $node) {
                if (!isset($node['type'])) {
                    $errors[] = sprintf(__('El nodo "%s" no tiene tipo definido', 'wac-chat-funnels'), $node_id);
                } else if (!in_array($node['type'], array('message', 'question', 'condition', 'action'))) {
                    $errors[] = sprintf(__('Tipo inválido "%s" en el nodo "%s"', 'wac-chat-funnels'), $node['type'], $node_id);
                }
            }
        }
        
        $is_valid = empty($errors);
        error_log('WAC Chat Funnels - Validation result: ' . ($is_valid ? 'VALID' : 'INVALID') . '. Errors: ' . print_r($errors, true));
        
        return array(
            'valid' => $is_valid, 
            'message' => $is_valid ? __('Estructura válida', 'wac-chat-funnels') : __('Estructura inválida', 'wac-chat-funnels'), 
            'errors' => $errors
        );
    }
    
    /**
     * Sanitizar datos del funnel
     *
     * @param array $data Datos parseados
     * @return array Datos sanitizados
     */
    private static function sanitize_funnel_data($data) {
        $sanitized = $data;
        
        if (isset($sanitized['funnel'])) {
            // Sanitizar campos básicos
            if (isset($sanitized['funnel']['id'])) {
                $sanitized['funnel']['id'] = sanitize_text_field($sanitized['funnel']['id']);
            }
            if (isset($sanitized['funnel']['start'])) {
                $sanitized['funnel']['start'] = sanitize_text_field($sanitized['funnel']['start']);
            }
            
            // Sanitizar nodos
            if (isset($sanitized['funnel']['nodes']) && is_array($sanitized['funnel']['nodes'])) {
                foreach ($sanitized['funnel']['nodes'] as $node_id => $node) {
                    // Sanitizar tipo
                    if (isset($node['type'])) {
                        $sanitized['funnel']['nodes'][$node_id]['type'] = sanitize_text_field($node['type']);
                    }
                    
                    // Sanitizar texto (permitir Markdown básico)
                    if (isset($node['text'])) {
                        $sanitized['funnel']['nodes'][$node_id]['text'] = wp_kses_post($node['text']);
                    }
                    
                    // Sanitizar URLs
                    if (isset($node['url'])) {
                        $sanitized['funnel']['nodes'][$node_id]['url'] = esc_url_raw($node['url']);
                    }
                    
                    // Sanitizar teléfono
                    if (isset($node['phone'])) {
                        $sanitized['funnel']['nodes'][$node_id]['phone'] = sanitize_text_field($node['phone']);
                    }
                    
                    // Sanitizar opciones
                    if (isset($node['options']) && is_array($node['options'])) {
                        foreach ($node['options'] as $opt_index => $option) {
                            if (isset($option['label'])) {
                                $sanitized['funnel']['nodes'][$node_id]['options'][$opt_index]['label'] = sanitize_text_field($option['label']);
                            }
                            if (isset($option['next'])) {
                                $sanitized['funnel']['nodes'][$node_id]['options'][$opt_index]['next'] = sanitize_text_field($option['next']);
                            }
                            if (isset($option['url'])) {
                                $sanitized['funnel']['nodes'][$node_id]['options'][$opt_index]['url'] = esc_url_raw($option['url']);
                            }
                            if (isset($option['phone'])) {
                                $sanitized['funnel']['nodes'][$node_id]['options'][$opt_index]['phone'] = sanitize_text_field($option['phone']);
                            }
                            if (isset($option['prefill'])) {
                                $sanitized['funnel']['nodes'][$node_id]['options'][$opt_index]['prefill'] = sanitize_textarea_field($option['prefill']);
                            }
                        }
                    }
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Parser YAML simple como fallback
     *
     * @param string $yaml_string Contenido YAML
     * @return array|null Array parseado o null si falla
     */
    private static function fallback_yaml_parse($yaml_string) {
        // Implementación básica del parser simple que ya teníamos
        $lines = explode("\n", $yaml_string);
        $result = array();
        $current_path = array();
        $current_node = '';
        $in_multiline_text = false;
        $multiline_content = '';
        
        foreach ($lines as $line_num => $line) {
            $original_line = $line;
            $trimmed = trim($line);
            
            // Saltar líneas vacías y comentarios
            if (empty($trimmed) || strpos($trimmed, '#') === 0) {
                continue;
            }
            
            // Manejar texto multilínea
            if ($in_multiline_text) {
                if (strpos($trimmed, ':') !== false && !strpos($trimmed, '|') !== false) {
                    // Fin del texto multilínea
                    $in_multiline_text = false;
                    $result['funnel']['nodes'][$current_node]['text'] = trim($multiline_content);
                    $multiline_content = '';
                    // Continuar procesando esta línea
                } else {
                    // Agregar línea al contenido multilínea
                    $multiline_content .= $line . "\n";
                    continue;
                }
            }
            
            $indent = strlen($line) - strlen(ltrim($line));
            $path_level = floor($indent / 2);
            
            if (strpos($trimmed, ':') !== false) {
                list($key, $value) = explode(':', $trimmed, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Ajustar el path actual basado en la indentación
                $current_path = array_slice($current_path, 0, $path_level);
                
                if ($path_level === 0) {
                    // Nivel raíz
                    if ($key === 'funnel') {
                        $current_path = array('funnel');
                        $result['funnel'] = array();
                    }
                } else if ($path_level === 1) {
                    // Nivel funnel
                    if (in_array($key, array('id', 'start'))) {
                        $result['funnel'][$key] = trim($value, '"\'');
                    } else if ($key === 'nodes') {
                        $current_path[] = 'nodes';
                        $result['funnel']['nodes'] = array();
                    }
                } else if ($path_level === 2) {
                    // Nivel nodos
                    if (empty($value)) {
                        $current_node = $key;
                        $result['funnel']['nodes'][$current_node] = array();
                    } else {
                        // Verificar si es texto multilínea
                        if (strpos($value, '|') !== false) {
                            $in_multiline_text = true;
                            $multiline_content = '';
                        } else {
                            $result['funnel']['nodes'][$current_node][$key] = trim($value, '"\'');
                        }
                    }
                } else if ($path_level === 3) {
                    // Nivel propiedades de nodos
                    if ($key === 'options' && empty($value)) {
                        $result['funnel']['nodes'][$current_node]['options'] = array();
                    } else if (isset($result['funnel']['nodes'][$current_node]['options'])) {
                        // Manejar opciones
                        if ($key === 'label') {
                            $result['funnel']['nodes'][$current_node]['options'][] = array('label' => trim($value, '"\''));
                        } else {
                            $last_index = count($result['funnel']['nodes'][$current_node]['options']) - 1;
                            if ($last_index >= 0) {
                                $result['funnel']['nodes'][$current_node]['options'][$last_index][$key] = trim($value, '"\'');
                            }
                        }
                    } else {
                        // Verificar si es texto multilínea
                        if (strpos($value, '|') !== false) {
                            $in_multiline_text = true;
                            $multiline_content = '';
                        } else {
                            $result['funnel']['nodes'][$current_node][$key] = trim($value, '"\'');
                        }
                    }
                } else if ($path_level === 4) {
                    // Nivel de opciones individuales
                    if (isset($result['funnel']['nodes'][$current_node]['options'])) {
                        $last_index = count($result['funnel']['nodes'][$current_node]['options']) - 1;
                        if ($last_index >= 0) {
                            $result['funnel']['nodes'][$current_node]['options'][$last_index][$key] = trim($value, '"\'');
                        }
                    }
                }
            }
        }
        
        // Procesar último texto multilínea si existe
        if ($in_multiline_text && !empty($multiline_content)) {
            $result['funnel']['nodes'][$current_node]['text'] = trim($multiline_content);
        }
        
        // Debug: Log del resultado parseado
        error_log('WAC Chat Funnels - Fallback Parsed YAML: ' . print_r($result, true));
        
        return $result;
    }
}
