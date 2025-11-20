<?php

namespace App\Http\Requests\Book;

use Illuminate\Foundation\Http\FormRequest;

class BookStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title'  => ['required', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
        ];
    }
}
