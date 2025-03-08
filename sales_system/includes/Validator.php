<?php
/**
 * Input Validation and Sanitization class
 */
class Validator {
    private $errors = [];
    private $data = [];
    private $rules = [];
    
    /**
     * Constructor
     * @param array $data Data to validate
     * @param array $rules Validation rules
     */
    public function __construct($data = [], $rules = []) {
        $this->data = $data;
        $this->rules = $rules;
    }
    
    /**
     * Validate data against rules
     * @return bool Whether validation passed
     */
    public function validate() {
        $this->errors = [];
        
        foreach ($this->rules as $field => $rules) {
            $value = $this->data[$field] ?? null;
            $rules = explode('|', $rules);
            
            foreach ($rules as $rule) {
                $params = [];
                
                if (strpos($rule, ':') !== false) {
                    list($rule, $param) = explode(':', $rule);
                    $params = explode(',', $param);
                }
                
                $method = 'validate' . ucfirst($rule);
                
                if (method_exists($this, $method)) {
                    if (!$this->$method($field, $value, $params)) {
                        break; // Stop validating this field after first error
                    }
                }
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Get validation errors
     * @return array Validation errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Get sanitized data
     * @return array Sanitized data
     */
    public function getSanitized() {
        $sanitized = [];
        
        foreach ($this->data as $key => $value) {
            $sanitized[$key] = $this->sanitize($value);
        }
        
        return $sanitized;
    }
    
    /**
     * Validate required field
     * @param string $field Field name
     * @param mixed $value Field value
     * @return bool
     */
    protected function validateRequired($field, $value) {
        if ($value === null || $value === '') {
            $this->errors[$field] = "O campo {$field} é obrigatório";
            return false;
        }
        return true;
    }
    
    /**
     * Validate email
     * @param string $field Field name
     * @param mixed $value Field value
     * @return bool
     */
    protected function validateEmail($field, $value) {
        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "O campo {$field} deve ser um email válido";
            return false;
        }
        return true;
    }
    
    /**
     * Validate minimum length
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $params Parameters [min_length]
     * @return bool
     */
    protected function validateMin($field, $value, $params) {
        $min = $params[0] ?? 0;
        if (strlen($value) < $min) {
            $this->errors[$field] = "O campo {$field} deve ter no mínimo {$min} caracteres";
            return false;
        }
        return true;
    }
    
    /**
     * Validate maximum length
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $params Parameters [max_length]
     * @return bool
     */
    protected function validateMax($field, $value, $params) {
        $max = $params[0] ?? PHP_INT_MAX;
        if (strlen($value) > $max) {
            $this->errors[$field] = "O campo {$field} deve ter no máximo {$max} caracteres";
            return false;
        }
        return true;
    }
    
    /**
     * Validate numeric value
     * @param string $field Field name
     * @param mixed $value Field value
     * @return bool
     */
    protected function validateNumeric($field, $value) {
        if ($value && !is_numeric($value)) {
            $this->errors[$field] = "O campo {$field} deve ser um número";
            return false;
        }
        return true;
    }
    
    /**
     * Validate integer value
     * @param string $field Field name
     * @param mixed $value Field value
     * @return bool
     */
    protected function validateInteger($field, $value) {
        if ($value && !filter_var($value, FILTER_VALIDATE_INT)) {
            $this->errors[$field] = "O campo {$field} deve ser um número inteiro";
            return false;
        }
        return true;
    }
    
    /**
     * Validate date format
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $params Parameters [format]
     * @return bool
     */
    protected function validateDate($field, $value, $params) {
        $format = $params[0] ?? 'Y-m-d';
        if ($value) {
            $date = DateTime::createFromFormat($format, $value);
            if (!$date || $date->format($format) !== $value) {
                $this->errors[$field] = "O campo {$field} deve ser uma data válida";
                return false;
            }
        }
        return true;
    }
    
    /**
     * Validate value in list
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $params Parameters [allowed_values]
     * @return bool
     */
    protected function validateIn($field, $value, $params) {
        if ($value && !in_array($value, $params)) {
            $this->errors[$field] = "O valor do campo {$field} não é válido";
            return false;
        }
        return true;
    }
    
    /**
     * Validate CPF
     * @param string $field Field name
     * @param mixed $value Field value
     * @return bool
     */
    protected function validateCpf($field, $value) {
        if ($value) {
            $cpf = preg_replace('/[^0-9]/', '', $value);
            
            if (strlen($cpf) != 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
                $this->errors[$field] = "O CPF informado não é válido";
                return false;
            }
            
            for ($t = 9; $t < 11; $t++) {
                for ($d = 0, $c = 0; $c < $t; $c++) {
                    $d += $cpf[$c] * (($t + 1) - $c);
                }
                $d = ((10 * $d) % 11) % 10;
                if ($cpf[$c] != $d) {
                    $this->errors[$field] = "O CPF informado não é válido";
                    return false;
                }
            }
        }
        return true;
    }
    
    /**
     * Validate CNPJ
     * @param string $field Field name
     * @param mixed $value Field value
     * @return bool
     */
    protected function validateCnpj($field, $value) {
        if ($value) {
            $cnpj = preg_replace('/[^0-9]/', '', $value);
            
            if (strlen($cnpj) != 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
                $this->errors[$field] = "O CNPJ informado não é válido";
                return false;
            }
            
            $sum = 0;
            $weight = 5;
            
            for ($i = 0; $i < 12; $i++) {
                $sum += $cnpj[$i] * $weight;
                $weight = ($weight == 2) ? 9 : $weight - 1;
            }
            
            $digit = ((10 * $sum) % 11) % 10;
            if ($cnpj[12] != $digit) {
                $this->errors[$field] = "O CNPJ informado não é válido";
                return false;
            }
            
            $sum = 0;
            $weight = 6;
            
            for ($i = 0; $i < 13; $i++) {
                $sum += $cnpj[$i] * $weight;
                $weight = ($weight == 2) ? 9 : $weight - 1;
            }
            
            $digit = ((10 * $sum) % 11) % 10;
            if ($cnpj[13] != $digit) {
                $this->errors[$field] = "O CNPJ informado não é válido";
                return false;
            }
        }
        return true;
    }
    
    /**
     * Validate phone number
     * @param string $field Field name
     * @param mixed $value Field value
     * @return bool
     */
    protected function validatePhone($field, $value) {
        if ($value) {
            $phone = preg_replace('/[^0-9]/', '', $value);
            if (strlen($phone) < 10 || strlen($phone) > 11) {
                $this->errors[$field] = "O telefone informado não é válido";
                return false;
            }
        }
        return true;
    }
    
    /**
     * Validate password strength
     * @param string $field Field name
     * @param mixed $value Field value
     * @return bool
     */
    protected function validatePassword($field, $value) {
        if ($value) {
            if (strlen($value) < 8) {
                $this->errors[$field] = "A senha deve ter no mínimo 8 caracteres";
                return false;
            }
            
            if (!preg_match('/[A-Z]/', $value)) {
                $this->errors[$field] = "A senha deve conter pelo menos uma letra maiúscula";
                return false;
            }
            
            if (!preg_match('/[a-z]/', $value)) {
                $this->errors[$field] = "A senha deve conter pelo menos uma letra minúscula";
                return false;
            }
            
            if (!preg_match('/[0-9]/', $value)) {
                $this->errors[$field] = "A senha deve conter pelo menos um número";
                return false;
            }
        }
        return true;
    }
    
    /**
     * Sanitize value
     * @param mixed $value Value to sanitize
     * @return mixed Sanitized value
     */
    protected function sanitize($value) {
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        
        if (is_string($value)) {
            // Remove HTML tags
            $value = strip_tags($value);
            
            // Convert special characters to HTML entities
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            
            // Trim whitespace
            $value = trim($value);
        }
        
        return $value;
    }
}
?>
