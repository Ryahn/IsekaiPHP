<?php

namespace IsekaiPHP\Http\Validation;

use IsekaiPHP\Http\Request;

/**
 * Validator
 * 
 * Validates request data against rules.
 */
class Validator
{
    protected array $data;
    protected array $rules;
    protected array $messages = [];
    protected array $errors = [];

    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
    }

    /**
     * Create a new validator instance
     */
    public static function make(array $data, array $rules, array $messages = []): self
    {
        return new self($data, $rules, $messages);
    }

    /**
     * Validate the data
     */
    public function validate(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $ruleString) {
            $rules = $this->parseRules($ruleString);
            $value = $this->getValue($field);

            foreach ($rules as $rule) {
                $this->validateField($field, $value, $rule);
            }
        }

        return empty($this->errors);
    }

    /**
     * Get validation errors
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Check if validation failed
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get first error message for a field
     */
    public function first(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Get all errors for a field
     */
    public function get(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Parse rules string into array
     */
    protected function parseRules(string $rules): array
    {
        return array_map('trim', explode('|', $rules));
    }

    /**
     * Get value from data array using dot notation
     */
    protected function getValue(string $field)
    {
        $keys = explode('.', $field);
        $value = $this->data;

        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    /**
     * Validate a field against a rule
     */
    protected function validateField(string $field, $value, string $rule): void
    {
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $parameters = isset($parts[1]) ? explode(',', $parts[1]) : [];

        $method = 'validate' . str_replace(' ', '', ucwords(str_replace('_', ' ', $ruleName)));

        if (method_exists($this, $method)) {
            if (!$this->$method($field, $value, $parameters)) {
                $this->addError($field, $ruleName, $parameters);
            }
        }
    }

    /**
     * Add error message
     */
    protected function addError(string $field, string $rule, array $parameters = []): void
    {
        $message = $this->messages[$field . '.' . $rule] ?? $this->getDefaultMessage($field, $rule, $parameters);
        $this->errors[$field][] = $message;
    }

    /**
     * Get default error message
     */
    protected function getDefaultMessage(string $field, string $rule, array $parameters): string
    {
        $fieldName = str_replace('_', ' ', $field);

        return match ($rule) {
            'required' => "The {$fieldName} field is required.",
            'email' => "The {$fieldName} must be a valid email address.",
            'min' => "The {$fieldName} must be at least {$parameters[0]} characters.",
            'max' => "The {$fieldName} may not be greater than {$parameters[0]} characters.",
            'numeric' => "The {$fieldName} must be a number.",
            'integer' => "The {$fieldName} must be an integer.",
            'string' => "The {$fieldName} must be a string.",
            'array' => "The {$fieldName} must be an array.",
            'confirmed' => "The {$fieldName} confirmation does not match.",
            'same' => "The {$fieldName} and {$parameters[0]} must match.",
            'different' => "The {$fieldName} and {$parameters[0]} must be different.",
            'unique' => "The {$fieldName} has already been taken.",
            'exists' => "The selected {$fieldName} is invalid.",
            'in' => "The selected {$fieldName} is invalid.",
            'not_in' => "The selected {$fieldName} is invalid.",
            'regex' => "The {$fieldName} format is invalid.",
            default => "The {$fieldName} field is invalid.",
        };
    }

    // Validation Rules

    protected function validateRequired(string $field, $value, array $parameters): bool
    {
        if (is_null($value)) {
            return false;
        }

        if (is_string($value) && trim($value) === '') {
            return false;
        }

        if (is_array($value) && count($value) === 0) {
            return false;
        }

        return true;
    }

    protected function validateEmail(string $field, $value, array $parameters): bool
    {
        if (is_null($value) || $value === '') {
            return true; // Use required rule for null/empty checks
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function validateMin(string $field, $value, array $parameters): bool
    {
        if (is_null($value)) {
            return true;
        }

        $min = (int)($parameters[0] ?? 0);

        if (is_string($value)) {
            return mb_strlen($value) >= $min;
        }

        if (is_numeric($value)) {
            return $value >= $min;
        }

        if (is_array($value)) {
            return count($value) >= $min;
        }

        return false;
    }

    protected function validateMax(string $field, $value, array $parameters): bool
    {
        if (is_null($value)) {
            return true;
        }

        $max = (int)($parameters[0] ?? PHP_INT_MAX);

        if (is_string($value)) {
            return mb_strlen($value) <= $max;
        }

        if (is_numeric($value)) {
            return $value <= $max;
        }

        if (is_array($value)) {
            return count($value) <= $max;
        }

        return false;
    }

    protected function validateNumeric(string $field, $value, array $parameters): bool
    {
        if (is_null($value)) {
            return true;
        }

        return is_numeric($value);
    }

    protected function validateInteger(string $field, $value, array $parameters): bool
    {
        if (is_null($value)) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    protected function validateString(string $field, $value, array $parameters): bool
    {
        if (is_null($value)) {
            return true;
        }

        return is_string($value);
    }

    protected function validateArray(string $field, $value, array $parameters): bool
    {
        if (is_null($value)) {
            return true;
        }

        return is_array($value);
    }

    protected function validateConfirmed(string $field, $value, array $parameters): bool
    {
        $confirmationField = $field . '_confirmation';
        $confirmationValue = $this->getValue($confirmationField);

        return $value === $confirmationValue;
    }

    protected function validateSame(string $field, $value, array $parameters): bool
    {
        if (empty($parameters)) {
            return false;
        }

        $otherValue = $this->getValue($parameters[0]);

        return $value === $otherValue;
    }

    protected function validateDifferent(string $field, $value, array $parameters): bool
    {
        if (empty($parameters)) {
            return false;
        }

        $otherValue = $this->getValue($parameters[0]);

        return $value !== $otherValue;
    }

    protected function validateUnique(string $field, $value, array $parameters): bool
    {
        if (is_null($value)) {
            return true;
        }

        if (empty($parameters)) {
            return false;
        }

        // Format: unique:table,column,except,idColumn
        $table = $parameters[0] ?? null;
        $column = $parameters[1] ?? $field;
        $except = $parameters[2] ?? null;
        $idColumn = $parameters[3] ?? 'id';

        if (!$table) {
            return false;
        }

        try {
            $query = \Illuminate\Database\Capsule\Manager::table($table)->where($column, $value);

            if ($except) {
                $query->where($idColumn, '!=', $except);
            }

            return $query->count() === 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function validateExists(string $field, $value, array $parameters): bool
    {
        if (is_null($value)) {
            return true;
        }

        if (empty($parameters)) {
            return false;
        }

        $table = $parameters[0] ?? null;
        $column = $parameters[1] ?? $field;

        if (!$table) {
            return false;
        }

        try {
            return \Illuminate\Database\Capsule\Manager::table($table)
                ->where($column, $value)
                ->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function validateIn(string $field, $value, array $parameters): bool
    {
        if (is_null($value)) {
            return true;
        }

        return in_array($value, $parameters);
    }

    protected function validateNotIn(string $field, $value, array $parameters): bool
    {
        if (is_null($value)) {
            return true;
        }

        return !in_array($value, $parameters);
    }

    protected function validateRegex(string $field, $value, array $parameters): bool
    {
        if (is_null($value)) {
            return true;
        }

        if (empty($parameters)) {
            return false;
        }

        return preg_match($parameters[0], $value) === 1;
    }
}

