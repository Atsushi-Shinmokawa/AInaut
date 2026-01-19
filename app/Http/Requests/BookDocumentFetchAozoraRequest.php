<?php

namespace App\Http\Requests;

class BookDocumentFetchAozoraRequest extends AbstractFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'aozora_url' => ['required', 'string', 'url', 'max:2000'],
        ];
    }
}
