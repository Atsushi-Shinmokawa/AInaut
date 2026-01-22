<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\AbstractFormRequest;

class PasswordResetLinkRequest extends AbstractFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}
