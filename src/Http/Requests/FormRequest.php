<?php

namespace IsekaiPHP\Http\Requests;

use IsekaiPHP\Http\Request;
use IsekaiPHP\Http\Response;
use IsekaiPHP\Http\Validation\Validator;

/**
 * Form Request
 * 
 * Base class for form validation requests.
 */
abstract class FormRequest extends Request
{
    protected ?Validator $validator = null;

    /**
     * Get validation rules
     */
    abstract public function rules(): array;

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validator instance
     */
    public function getValidator(): Validator
    {
        if ($this->validator === null) {
            $this->validator = Validator::make(
                $this->all(),
                $this->rules(),
                $this->messages()
            );
        }

        return $this->validator;
    }

    /**
     * Validate the request
     */
    public function validate(): bool
    {
        if (!$this->authorize()) {
            return false;
        }

        $validator = $this->getValidator();

        if ($validator->fails()) {
            $this->throwValidationException($validator);
        }

        return true;
    }

    /**
     * Throw validation exception
     */
    protected function throwValidationException(Validator $validator): void
    {
        // For JSON requests, return JSON response
        if ($this->wantsJson()) {
            $response = new Response(
                json_encode(['errors' => $validator->errors()]),
                422
            );
            $response->headers->set('Content-Type', 'application/json');
            $response->send();
            exit;
        }

        // For regular requests, redirect back with errors
        // In a real implementation, you'd want to flash errors to session
        $response = new Response('Validation failed', 422);
        $response->send();
        exit;
    }

    /**
     * Get validated data
     */
    public function validated(): array
    {
        $this->validate();

        $data = [];
        foreach ($this->rules() as $field => $rules) {
            if ($this->has($field)) {
                $data[$field] = $this->input($field);
            }
        }

        return $data;
    }
}

