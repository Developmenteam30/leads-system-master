<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class SlugRule implements Rule
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
        return empty($value) || preg_match('/^[a-zA-Z][a-zA-Z_]+[a-zA-Z]$/', $value) === 1;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute can only contain A-Z and underscores, and must start and end with a character.';
    }
}
