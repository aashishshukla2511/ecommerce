<?php

namespace App\Core;

class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function make(array $data): static
    {
        return new static($data);
    }

    public function validate(array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $this->data[$field] ?? null;
            $ruleList = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

            foreach ($ruleList as $rule) {
                $param = null;
                if (str_contains($rule, ':')) {
                    [$rule, $param] = explode(':', $rule, 2);
                }

                switch ($rule) {
                    case 'required':
                        if ($value === null || (is_string($value) && trim($value) === '') || (is_array($value) && empty($value))) {
                            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " is required.");
                        }
                        break;
                    case 'string':
                        if ($value !== null && !is_string($value)) {
                            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must be a string.");
                        }
                        break;
                    case 'numeric':
                        if ($value !== null && !is_numeric($value)) {
                            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must be a numeric value.");
                        }
                        break;
                    case 'integer':
                        if ($value !== null && filter_var($value, FILTER_VALIDATE_INT) === false) {
                            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must be an integer.");
                        }
                        break;
                    case 'min':
                        if ($value !== null && is_numeric($value) && (float)$value < (float)$param) {
                            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must be at least {$param}.");
                        }
                        break;
                    case 'max_len':
                        if ($value !== null && is_string($value) && mb_strlen(trim($value)) > (int)$param) {
                            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " cannot exceed {$param} characters.");
                        }
                        break;
                    case 'email':
                        if ($value !== null && trim($value) !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must be a valid email address.");
                        }
                        break;
                }
            }
        }

        return empty($this->errors);
    }

    public function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }
}
