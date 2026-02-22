<?php
declare(strict_types=1);

namespace App\Core;

class Validator
{
    private array $errors = [];

    public function validate(array $data, array $rules): bool
    {
        foreach ($rules as $field => $ruleList) {

            $value = trim($data[$field] ?? '');

            foreach ($ruleList as $rule) {

                if ($rule === 'required' && empty($value)) {
                    $this->errors[$field][] = 'Champ requis.';
                }

                if (str_starts_with($rule, 'min:')) {
                    $min = (int) explode(':', $rule)[1];
                    if (strlen($value) < $min) {
                        $this->errors[$field][] = "Minimum $min caractères.";
                    }
                }

                if (str_starts_with($rule, 'max:')) {
                    $max = (int) explode(':', $rule)[1];
                    if (strlen($value) > $max) {
                        $this->errors[$field][] = "Maximum $max caractères.";
                    }
                }
            }
        }

        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}