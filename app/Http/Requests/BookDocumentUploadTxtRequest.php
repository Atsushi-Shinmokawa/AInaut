<?php

namespace App\Http\Requests;

class BookDocumentUploadTxtRequest extends AbstractFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'txt' => ['required', 'file', 'mimes:txt', 'max:5120'], // 5MB
        ];
    }
}
