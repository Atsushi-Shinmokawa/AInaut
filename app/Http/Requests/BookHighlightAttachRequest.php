<?php

namespace App\Http\Requests;

class BookHighlightAttachRequest extends AbstractFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'book_id' => ['required', 'uuid'],
        ];
    }
}
