<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ClassExists implements Rule
{
    // An optional prefix/namespace for the class to be checked.
    protected $prefix = '';

    public function __construct($prefix = '')
    {
        $this->prefix = $prefix;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return empty($value) || class_exists($this->prefix . $value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return ':attribute is not a valid class.';
    }
}
