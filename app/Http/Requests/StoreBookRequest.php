<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 認証済みユーザーなら許可
    }

    public function rules(): array
    {
        return [
            // ISBNは必須、かつ10桁か13桁の数字
            'isbn' => ['required', 'string', 'min:10', 'max:13'],
        ];
    }
}