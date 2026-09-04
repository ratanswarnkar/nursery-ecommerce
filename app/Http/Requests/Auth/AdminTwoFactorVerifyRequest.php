<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class AdminTwoFactorVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'digits:6', 'required_without:recovery_code'],
            'recovery_code' => ['nullable', 'string', 'max:30', 'required_without:code'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required_without' => 'Please enter a 6-digit authenticator code or a recovery code.',
            'recovery_code.required_without' => 'Please enter a 6-digit authenticator code or a recovery code.',
        ];
    }
}
