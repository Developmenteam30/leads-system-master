<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class NumericEmptyOrWithDecimal implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return empty($value) || preg_match('/^\d+(\.\d{1,2})?$/', $value) === 1;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute is not a valid dollar amount.';
    }
}
