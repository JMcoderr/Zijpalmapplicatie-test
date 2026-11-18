<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Symfony\Component\Validator\Constraints\Iban;
use Symfony\Component\Validator\Validation;

class ValidIban implements ValidationRule
{
    public static function normalize(mixed $value): mixed
    {
        return is_string($value) ? preg_replace('/\s+/', '', strtoupper($value)) : $value;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)
            || Validation::createValidator()->validate($value, new Iban())->count() > 0) {
            $fail('Het IBAN is niet geldig. Controleer de landcode, lengte en het controlegetal.');
        }
    }
}