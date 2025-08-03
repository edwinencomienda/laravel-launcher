<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FqdnRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^(?=.{1,253}$)(?!\-)([a-zA-Z0-9\-]{1,63}\.)+[a-zA-Z]{2,}$/', $value)) {
            $fail('The :attribute must be a valid domain name.');
        }
    }
}
