<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueCompanyRule implements ValidationRule
{

    public $user_id;

    public function __construct(int $user_id)
    {
        $this->user_id = $user_id;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $company = \App\Models\Company::where('ruc', $value)
            ->where('user_id', $this->user_id)
            ->first();

        if ($company) {
            $fail('Ya existe una empresa con este RUC');
        }
    }
}
