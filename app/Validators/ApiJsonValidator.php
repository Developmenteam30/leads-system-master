<?php


namespace App\Validators;


use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ApiJsonValidator
{
    /**
     * @throws \Exception
     */
    public static function validate(array $data, array $rules, array $messages = [], array $customAttributes = [])
    {
        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            $errors = $validator->errors();
            throw new ValidationException($validator, response()->json(['message' => $errors->first()], 400));
        }
    }
}
