<?php

namespace IsekaiPHP\Http;

use IsekaiPHP\Core\View;

/**
 * Base Controller Class
 *
 * Provides common helper methods for controllers.
 * Inspired by Laravel's base controller.
 */
abstract class Controller
{
    /**
     * Return a view response
     *
     * @param string $view
     * @param array $data
     * @return Response
     */
    protected function view(string $view, array $data = []): Response
    {
        return new Response(View::render($view, $data));
    }

    /**
     * Return a JSON response
     *
     * @param array $data
     * @param int $status
     * @return Response
     */
    protected function json(array $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    /**
     * Redirect to a URL
     *
     * @param string $url
     * @param int $status
     * @return Response
     */
    protected function redirect(string $url, int $status = 302): Response
    {
        return Response::redirect($url, $status);
    }

    /**
     * Validate the request data
     *
     * @param Request $request
     * @param array $rules
     * @return array
     * @throws \Exception
     */
    protected function validate(Request $request, array $rules): array
    {
        $data = $request->all();
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $rulesArray = explode('|', $ruleString);
            $value = $data[$field] ?? null;

            foreach ($rulesArray as $rule) {
                $ruleParts = explode(':', $rule);
                $ruleName = $ruleParts[0];
                $ruleValue = $ruleParts[1] ?? null;

                switch ($ruleName) {
                    case 'required':
                        if (empty($value) && $value !== '0') {
                            $errors[$field][] = "The {$field} field is required.";
                        }
                        break;

                    case 'email':
                        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = "The {$field} must be a valid email address.";
                        }
                        break;

                    case 'min':
                        if (!empty($value) && strlen($value) < (int)$ruleValue) {
                            $errors[$field][] = "The {$field} must be at least {$ruleValue} characters.";
                        }
                        break;

                    case 'max':
                        if (!empty($value) && strlen($value) > (int)$ruleValue) {
                            $errors[$field][] = "The {$field} may not be greater than {$ruleValue} characters.";
                        }
                        break;

                    case 'confirmed':
                        $confirmField = $field . '_confirmation';
                        if (!isset($data[$confirmField]) || $value !== $data[$confirmField]) {
                            $errors[$field][] = "The {$field} confirmation does not match.";
                        }
                        break;
                }
            }
        }

        if (!empty($errors)) {
            $errorMessage = "Validation failed:\n";
            foreach ($errors as $field => $fieldErrors) {
                $errorMessage .= "  - {$field}: " . implode(' ', $fieldErrors) . "\n";
            }
            throw new \Exception($errorMessage);
        }

        return $data;
    }
}

