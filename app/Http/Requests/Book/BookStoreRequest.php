<?php

namespace App\Http\Requests\Book;

use App\Http\Requests\AbstractFormRequest;

class BookStoreRequest extends AbstractFormRequest
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
