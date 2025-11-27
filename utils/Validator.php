<?php
/**
 * WorkoutMate - Validator Class
 * 
 * Clase para validaciones comunes
 */

class Validator {
    
    /**
     * Validar email
     */
    public static function email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validar longitud mínima
     */
    public static function minLength($value, $min) {
        return strlen($value) >= $min;
    }
    
    /**
     * Validar longitud máxima
     */
    public static function maxLength($value, $max) {
        return strlen($value) <= $max;
    }
    
    /**
     * Validar que un campo es requerido
     */
    public static function required($value) {
        if (is_string($value)) {
            return trim($value) !== '';
        }
        return $value !== null && $value !== '';
    }
    
    /**
     * Validar UUID
     */
    public static function uuid($uuid) {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        return preg_match($pattern, $uuid) === 1;
    }
    
    /**
     * Validar número entero positivo
     */
    public static function positiveInteger($value) {
        return is_numeric($value) && intval($value) > 0 && intval($value) == $value;
    }
    
    /**
     * Validar enum
     */
    public static function inArray($value, array $allowed) {
        return in_array($value, $allowed, true);
    }
    
    /**
     * Validar conjunto de campos requeridos
     */
    public static function requiredFields($data, array $fields) {
        $missing = [];
        
        foreach ($fields as $field) {
            if (!isset($data->$field) || !self::required($data->$field)) {
                $missing[] = $field;
            }
        }
        
        return empty($missing) ? true : $missing;
    }
    
    /**
     * Sanitizar string
     */
    public static function sanitizeString($value) {
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validar fecha formato Y-m-d
     */
    public static function date($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
?>
