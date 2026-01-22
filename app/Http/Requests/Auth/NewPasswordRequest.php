<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\AbstractFormRequest;
use Illuminate\Validation\Rules;

class NewPasswordRequest extends AbstractFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }
}
